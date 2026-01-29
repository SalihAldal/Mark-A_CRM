<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Support\TenantContext;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LogsController extends Controller
{
    public function index(Request $request)
    {
        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->requireTenantId();

        $q = DB::table('audit_logs')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id');

        $role = (string) ($request->user()->role?->key ?? '');
        if ($role === 'staff') {
            $q->where('actor_user_id', (int) $request->user()->id);
        }

        if ($request->filled('action')) {
            $q->where('action', $request->string('action')->toString());
        }

        /** @var LengthAwarePaginator $rows */
        $rows = $q->paginate(30)->appends($request->query());

        $actorIds = collect($rows->items())->pluck('actor_user_id')->filter()->unique()->values()->all();
        $actors = DB::table('users')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $actorIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        // Preload related display data for human-friendly log messages
        $metaById = [];
        $leadIds = [];
        $calendarEventIds = [];
        $userIdsFromMeta = [];
        $stageIdsFromMeta = [];

        foreach ($rows->items() as $r) {
            $meta = [];
            if (!empty($r->metadata_json)) {
                $decoded = json_decode((string) $r->metadata_json, true);
                if (is_array($decoded)) {
                    $meta = $decoded;
                }
            }
            $metaById[(int) $r->id] = $meta;

            if (($r->entity_type ?? null) === 'lead' && !empty($r->entity_id)) {
                $leadIds[] = (int) $r->entity_id;
            }
            if (!empty($meta['lead_id'])) {
                $leadIds[] = (int) $meta['lead_id'];
            }
            if (($r->entity_type ?? null) === 'calendar_event' && !empty($r->entity_id)) {
                $calendarEventIds[] = (int) $r->entity_id;
            }
            foreach (['assigned_user_id', 'from', 'to', 'owner_user_id'] as $k) {
                if (isset($meta[$k]) && $meta[$k] !== null && $meta[$k] !== '') {
                    $userIdsFromMeta[] = (int) $meta[$k];
                }
            }
            foreach (['from_stage_id', 'to_stage_id'] as $k) {
                if (isset($meta[$k]) && $meta[$k] !== null && $meta[$k] !== '') {
                    $stageIdsFromMeta[] = (int) $meta[$k];
                }
            }
        }

        $leadIds = array_values(array_unique(array_filter($leadIds)));
        $calendarEventIds = array_values(array_unique(array_filter($calendarEventIds)));
        $userIdsFromMeta = array_values(array_unique(array_filter($userIdsFromMeta)));
        $stageIdsFromMeta = array_values(array_unique(array_filter($stageIdsFromMeta)));

        $leads = empty($leadIds)
            ? collect()
            : DB::table('leads')
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $leadIds)
                ->get(['id', 'name'])
                ->keyBy('id');

        $usersFromMeta = empty($userIdsFromMeta)
            ? collect()
            : DB::table('users')
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $userIdsFromMeta)
                ->get(['id', 'name'])
                ->keyBy('id');

        $stages = empty($stageIdsFromMeta)
            ? collect()
            : DB::table('lead_stages')
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $stageIdsFromMeta)
                ->get(['id', 'name'])
                ->keyBy('id');

        $calendarEvents = empty($calendarEventIds)
            ? collect()
            : DB::table('calendar_events')
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $calendarEventIds)
                ->get(['id', 'title'])
                ->keyBy('id');

        $rows->setCollection(
            $rows->getCollection()->map(function ($r) use ($actors, $metaById, $leads, $usersFromMeta, $stages, $calendarEvents) {
                $meta = $metaById[(int) $r->id] ?? [];
                $actor = $r->actor_user_id ? ($actors[(int) $r->actor_user_id] ?? null) : null;
                $actorName = $actor?->name ?? 'Sistem';

                $leadName = function (?int $leadId) use ($leads): string {
                    if (!$leadId) return 'Lead';
                    $l = $leads[$leadId] ?? null;
                    return $l?->name ? (string) $l->name : ('Lead #' . $leadId);
                };

                $userName = function ($userId) use ($usersFromMeta): string {
                    $id = (int) ($userId ?? 0);
                    if ($id <= 0) return '—';
                    $u = $usersFromMeta[$id] ?? null;
                    return $u?->name ? (string) $u->name : ('Kullanıcı #' . $id);
                };

                $stageName = function ($stageId) use ($stages): string {
                    $id = (int) ($stageId ?? 0);
                    if ($id <= 0) return '—';
                    $s = $stages[$id] ?? null;
                    return $s?->name ? (string) $s->name : ('Aşama #' . $id);
                };

                $calendarTitle = function (?int $eventId) use ($calendarEvents): string {
                    if (!$eventId) return 'Etkinlik';
                    $e = $calendarEvents[$eventId] ?? null;
                    return $e?->title ? (string) $e->title : ('Etkinlik #' . $eventId);
                };

                $action = (string) ($r->action ?? '');
                $entityType = (string) ($r->entity_type ?? '');
                $entityId = (int) ($r->entity_id ?? 0);

                $message = null;
                if ($action === 'lead.reply') {
                    $lid = (int) ($meta['lead_id'] ?? $entityId);
                    $t = (string) ($meta['message_type'] ?? 'text');
                    if ($t === 'text') {
                        $preview = isset($meta['text_preview']) && $meta['text_preview'] !== ''
                            ? (' — “' . (string) $meta['text_preview'] . '”')
                            : '.';
                        $message = 'Lead’e mesaj gönderdi: ' . $leadName($lid) . $preview;
                    } elseif ($t === 'voice') {
                        $message = 'Lead’e ses mesajı gönderdi: ' . $leadName($lid) . '.';
                    } else {
                        $message = 'Lead’e dosya gönderdi: ' . $leadName($lid) . '.';
                    }
                }

                if ($message === null) {
                    $message = match ($action) {
                    'user.login' => 'Giriş yaptı.',
                    'lead.create' => 'Yeni lead oluşturdu: ' . $leadName((int) $entityId) . '.',
                    'lead.create_webhook' => 'Entegrasyon üzerinden yeni lead oluştu: ' . $leadName((int) $entityId) . '.',
                    'lead.stage_move' => 'Lead aşaması değişti: ' . $leadName((int) $entityId) . ' (' . $stageName($meta['from_stage_id'] ?? null) . ' → ' . $stageName($meta['to_stage_id'] ?? null) . ').',
                    'lead.assign' => 'Lead ataması güncellendi: ' . $leadName((int) $entityId) . ' (' . $userName($meta['from'] ?? null) . ' → ' . $userName($meta['to'] ?? null) . ').',
                    'lead.claim' => 'Lead’i üzerine aldı: ' . $leadName((int) $entityId) . '.',
                    'lead.release' => 'Lead’i bıraktı: ' . $leadName((int) $entityId) . '.',
                    'calendar.event_create' => 'Takvime etkinlik ekledi: ' . $calendarTitle($entityType === 'calendar_event' ? $entityId : null) . (isset($meta['starts_at'], $meta['ends_at']) ? (' (' . (string) $meta['starts_at'] . ' → ' . (string) $meta['ends_at'] . ')') : '.') ,
                    'calendar.event_delete' => 'Takvim etkinliğini sildi: ' . ($meta['title'] ?? $calendarTitle($entityType === 'calendar_event' ? $entityId : null)) . '.',
                    default => ($entityType && $entityId)
                        ? (Str::of($action)->replace('_', ' ')->replace('.', ' ')->trim()->toString() . ' • ' . $entityType . '#' . $entityId)
                        : (Str::of($action)->replace('_', ' ')->replace('.', ' ')->trim()->toString() ?: 'İşlem'),
                    };
                }

                // Optional extra detail line (small)
                $detail = null;
                if ($action === 'lead.create') {
                    $bits = [];
                    if (!empty($meta['source'])) $bits[] = 'Kaynak: ' . (string) $meta['source'];
                    if (!empty($meta['status'])) $bits[] = 'Durum: ' . (string) $meta['status'];
                    if (!empty($meta['assigned_user_id'])) $bits[] = 'Atanan: ' . $userName($meta['assigned_user_id']);
                    $detail = empty($bits) ? null : implode(' • ', $bits);
                } elseif ($action === 'lead.create_webhook') {
                    $detail = !empty($meta['source']) ? ('Kaynak: ' . (string) $meta['source']) : null;
                } elseif ($action === 'lead.stage_move' && !empty($meta['reason'])) {
                    $detail = 'Not: ' . (string) $meta['reason'];
                } elseif ($action === 'calendar.event_create' && !empty($meta['urgency'])) {
                    $detail = 'Öncelik: ' . (string) $meta['urgency'];
                }

                $r->actor_name = $actorName;
                $r->message = $message;
                $r->detail = $detail;
                return $r;
            })
        );

        return view('panel.logs.index', [
            'rows' => $rows,
            'actors' => $actors,
        ]);
    }
}

