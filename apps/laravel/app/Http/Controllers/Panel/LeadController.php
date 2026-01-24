<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\LeadStage;
use App\Models\LeadStageEvent;
use App\Models\User;
use App\Services\RealtimeGateway;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->requireTenantId();

        $q = Lead::query()
            ->select([
                'leads.*',
                'lead_stages.name as stage_name',
                'lead_stages.color as stage_color',
                DB::raw('(SELECT t.id FROM threads t WHERE t.tenant_id = leads.tenant_id AND t.lead_id = leads.id ORDER BY t.last_message_at DESC, t.id DESC LIMIT 1) AS thread_id'),
                'assigned_user.name as assigned_name',
            ])
            ->leftJoin('lead_stages', 'lead_stages.id', '=', 'leads.stage_id')
            ->leftJoin('users as assigned_user', function ($join) use ($tenantId) {
                $join->on('assigned_user.id', '=', 'leads.assigned_user_id')
                    ->where('assigned_user.tenant_id', '=', $tenantId);
            })
            ->orderByDesc('leads.updated_at');

        $user = $request->user();
        $roleKey = (string) ($user?->role?->key ?? '');
        if ($roleKey === 'staff') {
            $uid = (int) $user->id;
            $q->where(function ($qq) use ($uid) {
                $qq->where('leads.assigned_user_id', $uid)->orWhere('leads.owner_user_id', $uid);
            });
        }

        if ($request->filled('status')) {
            $q->where('leads.status', $request->string('status')->toString());
        }
        if ($request->filled('source')) {
            $q->where('leads.source', $request->string('source')->toString());
        }
        if ($request->filled('stage_id')) {
            $q->where('leads.stage_id', (int) $request->input('stage_id'));
        }
        if ($request->filled('assigned_user_id')) {
            $q->where('leads.assigned_user_id', (int) $request->input('assigned_user_id'));
        }
        if ($request->filled('q')) {
            $term = '%' . Str::of($request->string('q')->toString())->trim()->limit(120, '')->toString() . '%';
            $q->where(function ($qq) use ($term) {
                $qq->where('leads.name', 'like', $term)
                    ->orWhere('leads.phone', 'like', $term)
                    ->orWhere('leads.email', 'like', $term)
                    ->orWhere('leads.company', 'like', $term);
            });
        }

        $leads = $q->paginate(20)->appends($request->query());
        $stages = LeadStage::query()->orderBy('sort_order')->get();
        $users = User::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('panel.leads.index', [
            'leads' => $leads,
            'stages' => $stages,
            'users' => $users,
        ]);
    }

    public function create(Request $request)
    {
        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->requireTenantId();

        $stages = LeadStage::query()->orderBy('sort_order')->get();
        $users = User::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('panel.leads.create', [
            'stages' => $stages,
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->requireTenantId();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:160'],
            'source' => ['required', 'string', 'max:64'],
            'status' => ['required', 'in:open,won,lost'],
            'stage_id' => ['nullable', 'integer'],
            'assigned_user_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $stageId = $data['stage_id'] ? (int) $data['stage_id'] : null;
        if ($stageId !== null) {
            $exists = LeadStage::query()->where('id', $stageId)->exists();
            if (!$exists) {
                return redirect()->back()->with('status', 'Stage bulunamadı.');
            }
        }

        $assignedId = $data['assigned_user_id'] ? (int) $data['assigned_user_id'] : null;
        if ($assignedId !== null) {
            $uExists = User::query()->where('tenant_id', $tenantId)->where('id', $assignedId)->exists();
            if (!$uExists) {
                $assignedId = null;
            }
        }

        $lead = Lead::query()->create([
            'owner_user_id' => $request->user()->id,
            'assigned_user_id' => $assignedId,
            'stage_id' => $stageId,
            'source' => (string) $data['source'],
            'status' => (string) $data['status'],
            'score' => 0,
            'name' => trim((string) $data['name']),
            'phone' => $data['phone'] ? trim((string) $data['phone']) : null,
            'email' => $data['email'] ? trim((string) $data['email']) : null,
            'company' => $data['company'] ? trim((string) $data['company']) : null,
            'notes' => $data['notes'] ? trim((string) $data['notes']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->to('/leads/' . $lead->id)->with('status', 'Lead eklendi.');
    }

    public function kanban(Request $request)
    {
        $stages = LeadStage::query()->orderBy('sort_order')->get();
        $q = Lead::query()->orderByDesc('updated_at');
        $user = $request->user();
        $roleKey = (string) ($user?->role?->key ?? '');
        if ($roleKey === 'staff') {
            $uid = (int) $user->id;
            $q->where(function ($qq) use ($uid) {
                $qq->where('assigned_user_id', $uid)->orWhere('owner_user_id', $uid);
            });
        }
        $leads = $q->get();

        $grouped = $leads->groupBy(fn ($l) => (string) ($l->stage_id ?? 0));

        return view('panel.leads.kanban', [
            'stages' => $stages,
            'grouped' => $grouped,
        ]);
    }

    public function moveStage(Request $request, Lead $lead, RealtimeGateway $gateway)
    {
        $this->authorizeLeadAccess($request, $lead);

        $data = $request->validate([
            'to_stage_id' => ['nullable', 'integer'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->requireTenantId();

        $toStageId = $data['to_stage_id'] ?? null;
        if ($toStageId !== null) {
            $exists = LeadStage::query()->where('id', (int) $toStageId)->exists();
            if (!$exists) {
                return response()->json(['ok' => false, 'error' => 'Stage not found'], 422);
            }
        }

        $from = $lead->stage_id;
        DB::transaction(function () use ($lead, $toStageId, $from, $data) {
            $lead->stage_id = $toStageId;
            $lead->save();

            LeadStageEvent::query()->create([
                'lead_id' => $lead->id,
                'from_stage_id' => $from,
                'to_stage_id' => $toStageId,
                'moved_by_user_id' => auth()->id(),
                'reason' => $data['reason'] ?? null,
                'created_at' => now(),
            ]);
        });

        $gateway->broadcast($tenantId, [
            ['type' => 'tenant', 'id' => $tenantId],
        ], 'lead.stage_changed', [
            'lead_id' => (int) $lead->id,
            'from_stage_id' => $from ? (int) $from : null,
            'to_stage_id' => $toStageId ? (int) $toStageId : null,
        ]);

        return response()->json(['ok' => true]);
    }

    public function show(Request $request, Lead $lead)
    {
        $this->authorizeLeadAccess($request, $lead);

        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->requireTenantId();

        $stage = LeadStage::query()->find($lead->stage_id);
        $assigned = null;
        if ($lead->assigned_user_id) {
            $assigned = User::query()->where('tenant_id', $tenantId)->find($lead->assigned_user_id);
        }

        $threadId = DB::table('threads')
            ->where('tenant_id', $tenantId)
            ->where('lead_id', $lead->id)
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->value('id');

        $notes = LeadNote::query()
            ->where('lead_id', $lead->id)
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        $noteAuthors = User::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $notes->pluck('user_id')->filter()->unique()->values()->all())
            ->get(['id', 'name'])
            ->keyBy('id');

        return view('panel.leads.show', [
            'lead' => $lead,
            'stage' => $stage,
            'assigned' => $assigned,
            'threadId' => $threadId,
            'notes' => $notes,
            'noteAuthors' => $noteAuthors,
        ]);
    }

    public function addNote(Request $request, Lead $lead)
    {
        $this->authorizeLeadAccess($request, $lead);

        $data = $request->validate([
            'note_text' => ['required', 'string', 'max:3000'],
        ]);

        LeadNote::query()->create([
            'lead_id' => $lead->id,
            'user_id' => $request->user()->id,
            'note_text' => trim($data['note_text']),
            'created_at' => now(),
        ]);

        return redirect()->to('/leads/' . $lead->id)->with('status', 'Not eklendi.');
    }

    private function authorizeLeadAccess(Request $request, Lead $lead): void
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $role = (string) ($user->role?->key ?? '');
        if ($role === 'tenant_admin') {
            return;
        }

        if ($role === 'staff') {
            $isOwner = (int) ($lead->owner_user_id ?? 0) === (int) $user->id;
            $isAssigned = (int) ($lead->assigned_user_id ?? 0) === (int) $user->id;
            if ($isOwner || $isAssigned) {
                return;
            }
        }

        if ($role === 'customer') {
            $uEmail = strtolower(trim((string) ($user->email ?? '')));
            $lEmail = strtolower(trim((string) ($lead->email ?? '')));
            if ($uEmail !== '' && $lEmail !== '' && $uEmail === $lEmail) {
                return;
            }
        }

        abort(403);
    }
}

