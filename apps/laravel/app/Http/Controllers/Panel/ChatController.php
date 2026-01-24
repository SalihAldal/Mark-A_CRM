<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\AiPromptTemplate;
use App\Models\Message;
use App\Models\Thread;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $threadsQ = Thread::query()
            ->select([
                'threads.*',
                'leads.name as lead_name',
                'contacts.name as contact_name',
            ])
            ->leftJoin('leads', 'leads.id', '=', 'threads.lead_id')
            ->leftJoin('contacts', 'contacts.id', '=', 'threads.contact_id')
            ->orderByDesc('threads.last_message_at')
            ->orderByDesc('threads.id');

        if ($request->filled('channel')) {
            $threadsQ->where('threads.channel', $request->string('channel')->toString());
        }
        if ($request->filled('status')) {
            $threadsQ->where('threads.status', $request->string('status')->toString());
        }
        if ($request->filled('source')) {
            $threadsQ->where('leads.source', $request->string('source')->toString());
        }

        $threads = $threadsQ->paginate(20)->appends($request->query());

        $selectedId = $request->integer('thread');
        $selected = null;
        $messages = collect();

        if ($selectedId) {
            $selected = Thread::query()
                ->select([
                    'threads.*',
                    'leads.name as lead_name',
                    'contacts.name as contact_name',
                ])
                ->leftJoin('leads', 'leads.id', '=', 'threads.lead_id')
                ->leftJoin('contacts', 'contacts.id', '=', 'threads.contact_id')
                ->where('threads.id', $selectedId)
                ->first();
            if ($selected) {
                $messages = Message::query()
                    ->where('thread_id', $selected->id)
                    // created_at timezone/seed farkları yüzünden sıralama kayabiliyor.
                    // id her zaman artan olduğu için sohbet akışı stabil (üstten alta) olur.
                    ->orderBy('id')
                    ->limit(300)
                    ->get();
            }
        }

        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->requireTenantId();

        $templates = AiPromptTemplate::query()
            ->where('is_active', 1)
            ->whereIn('template_key', [
                'last_message_to_sale',
                'objection_handle',
                'offer_generate',
                'continue_chat',
                'warm_sales',
                'professional_sales',
            ])
            ->where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            // Tenant-specific kayıtlar önce gelsin (tenant_id IS NULL en sona).
            ->orderByRaw('tenant_id IS NULL')
            ->orderBy('id')
            ->get()
            ->unique('template_key')
            ->keyBy('template_key');

        return view('panel.chats.index', [
            'threads' => $threads,
            'selected' => $selected,
            'messages' => $messages,
            'templates' => $templates,
        ]);
    }
}

