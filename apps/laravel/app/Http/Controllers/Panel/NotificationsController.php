<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Notification;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationsController extends Controller
{
    public function index(Request $request)
    {
        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->requireTenantId();

        $q = Notification::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id');

        if ($request->filled('unread')) {
            $q->where('is_read', 0);
        }

        $items = $q->paginate(25)->appends($request->query());

        $leadIds = collect($items->items())->pluck('entity_id')->filter()->unique()->values()->all();
        $leads = Lead::query()
            ->whereIn('id', $leadIds)
            ->get()
            ->keyBy('id');

        $unreadCount = Notification::query()
            ->where('user_id', $request->user()->id)
            ->where('is_read', 0)
            ->count();

        return view('panel.notifications.index', [
            'items' => $items,
            'leads' => $leads,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function markRead(Request $request, Notification $notification)
    {
        if ((int) $notification->user_id !== (int) $request->user()->id) {
            abort(403);
        }
        $notification->is_read = 1;
        $notification->read_at = now();
        $notification->save();
        return redirect()->back()->with('status', 'Okundu.');
    }

    public function claim(Request $request, Notification $notification)
    {
        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->requireTenantId();

        if ((int) $notification->user_id !== (int) $request->user()->id) {
            abort(403);
        }
        if ((string) $notification->entity_type !== 'lead' || !$notification->entity_id) {
            return redirect()->back()->with('status', 'Bildirim lead değil.');
        }

        $lead = Lead::query()->find((int) $notification->entity_id);
        if (!$lead) {
            return redirect()->back()->with('status', 'Lead bulunamadı.');
        }

        $role = (string) ($request->user()->role?->key ?? '');
        if ($role === 'staff') {
            if ($lead->assigned_user_id && (int) $lead->assigned_user_id !== (int) $request->user()->id) {
                return redirect()->back()->with('status', 'Bu lead başka bir çalışana atanmış.');
            }
        }

        DB::transaction(function () use ($lead, $request, $notification, $tenantId) {
            if (!$lead->assigned_user_id) {
                $lead->assigned_user_id = $request->user()->id;
            }
            $lead->save();

            $notification->is_read = 1;
            $notification->read_at = now();
            $notification->save();

            DB::table('audit_logs')->insert([
                'tenant_id' => $tenantId,
                'actor_user_id' => $request->user()->id,
                'action' => 'lead.claim',
                'entity_type' => 'lead',
                'entity_id' => $lead->id,
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'metadata_json' => json_encode(['assigned_user_id' => (int) $lead->assigned_user_id], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
            ]);
        });

        return redirect()->back()->with('status', 'Lead devralındı.');
    }

    public function release(Request $request, Notification $notification)
    {
        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->requireTenantId();

        if ((int) $notification->user_id !== (int) $request->user()->id) {
            abort(403);
        }
        if ((string) $notification->entity_type !== 'lead' || !$notification->entity_id) {
            return redirect()->back()->with('status', 'Bildirim lead değil.');
        }

        $lead = Lead::query()->find((int) $notification->entity_id);
        if (!$lead) {
            return redirect()->back()->with('status', 'Lead bulunamadı.');
        }

        if ((int) ($lead->assigned_user_id ?? 0) !== (int) $request->user()->id) {
            return redirect()->back()->with('status', 'Bu lead sende değil.');
        }

        DB::transaction(function () use ($lead, $request, $notification, $tenantId) {
            $lead->assigned_user_id = null;
            $lead->save();

            $notification->is_read = 1;
            $notification->read_at = now();
            $notification->save();

            DB::table('audit_logs')->insert([
                'tenant_id' => $tenantId,
                'actor_user_id' => $request->user()->id,
                'action' => 'lead.release',
                'entity_type' => 'lead',
                'entity_id' => $lead->id,
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'metadata_json' => json_encode(['assigned_user_id' => null], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
            ]);
        });

        return redirect()->back()->with('status', 'Devralma kaldırıldı.');
    }
}

