<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        return view('panel.calendar.index', [
            //
        ]);
    }

    public function events(Request $request)
    {
        $start = $request->query('start');
        $end = $request->query('end');

        $q = CalendarEvent::query()->orderBy('starts_at');
        // Overlap filtresi: event start < rangeEnd && event end > rangeStart
        if ($start && $end) {
            $q->where('starts_at', '<', $end)->where('ends_at', '>', $start);
        } elseif ($start) {
            $q->where('ends_at', '>', $start);
        } elseif ($end) {
            $q->where('starts_at', '<', $end);
        }

        $events = $q->limit(2000)->get();

        $mapColor = function (string $urgency): array {
            // mavi / sarı / kırmızı
            if ($urgency === 'high') return ['#ef4444', '#fee2e2'];
            if ($urgency === 'medium') return ['#f59e0b', '#fef3c7'];
            return ['#2563eb', '#dbeafe'];
        };

        return response()->json($events->map(function ($e) use ($mapColor) {
            [$border, $bg] = $mapColor((string) ($e->urgency ?? 'low'));
            return [
                'id' => (string) $e->id,
                'title' => (string) $e->title,
                'start' => $e->starts_at?->toIso8601String(),
                'end' => $e->ends_at?->toIso8601String(),
                'backgroundColor' => $bg,
                'borderColor' => $border,
                'textColor' => '#0f172a',
                'extendedProps' => [
                    'description' => (string) ($e->description ?? ''),
                    'location' => (string) ($e->location ?? ''),
                    'urgency' => (string) ($e->urgency ?? 'low'),
                ],
            ];
        }));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'location' => ['nullable', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'urgency' => ['required', 'in:low,medium,high'],
        ]);

        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->requireTenantId();

        $start = Carbon::parse((string) $data['starts_at']);
        $end = Carbon::parse((string) $data['ends_at']);

        // If event spans multiple days, split into daily parts so month view doesn't "stretch"
        $parts = [];
        $cursor = $start->copy()->startOfDay();
        $lastDay = $end->copy()->startOfDay();
        while ($cursor->lte($lastDay)) {
            $dayStart = $cursor->copy()->startOfDay();
            $dayEnd = $cursor->copy()->endOfDay(); // 23:59:59
            $partStart = $start->copy()->max($dayStart);
            $partEnd = $end->copy()->min($dayEnd);
            // ensure end >= start
            if ($partEnd->lt($partStart)) {
                $partEnd = $partStart->copy();
            }
            $parts[] = [$partStart, $partEnd];
            $cursor->addDay();
        }

        $firstEventId = null;
        $partsCount = 0;

        DB::transaction(function () use ($request, $data, $parts, &$firstEventId, &$partsCount) {
            foreach ($parts as [$ps, $pe]) {
                $ev = CalendarEvent::query()->create([
                    'owner_user_id' => $request->user()->id,
                    'title' => $data['title'],
                    'starts_at' => $ps,
                    'ends_at' => $pe,
                    'location' => $data['location'] ?? null,
                    'description' => $data['description'] ?? null,
                    'urgency' => $data['urgency'],
                ]);
                $partsCount++;
                if ($firstEventId === null) {
                    $firstEventId = (int) $ev->id;
                }
            }
        });

        // Audit
        DB::table('audit_logs')->insert([
            'tenant_id' => $tenantId,
            'actor_user_id' => $request->user()->id,
            'action' => 'calendar.event_create',
            'entity_type' => 'calendar_event',
            'entity_id' => (int) ($firstEventId ?? 0),
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'metadata_json' => json_encode([
                'title' => (string) $data['title'],
                'starts_at' => $start->toDateTimeString(),
                'ends_at' => $end->toDateTimeString(),
                'urgency' => (string) $data['urgency'],
                'parts_count' => (int) $partsCount,
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, CalendarEvent $event)
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $roleKey = (string) ($user->role?->key ?? '');
        if ($roleKey !== 'tenant_admin' && (int) $event->owner_user_id !== (int) $user->id) {
            abort(403);
        }

        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->requireTenantId();

        $meta = [
            'title' => (string) ($event->title ?? ''),
            'starts_at' => $event->starts_at?->toDateTimeString(),
            'ends_at' => $event->ends_at?->toDateTimeString(),
            'urgency' => (string) ($event->urgency ?? ''),
        ];
        $eventId = (int) $event->id;

        $event->delete();

        DB::table('audit_logs')->insert([
            'tenant_id' => $tenantId,
            'actor_user_id' => $user->id,
            'action' => 'calendar.event_delete',
            'entity_type' => 'calendar_event',
            'entity_id' => $eventId,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'metadata_json' => json_encode($meta, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }
}

