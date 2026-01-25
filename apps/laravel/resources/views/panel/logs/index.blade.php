@extends('layouts.app')

@section('title', 'Logs - Mark-A CRM')
@section('page_title', 'Logs')

@section('content')
    <div class="card">
        <div class="toolbar" style="justify-content:space-between;">
            <div>
                <div class="pageTitle">İşlem Logları</div>
                <div class="muted">Çalışan aksiyonları ve sistem olayları.</div>
            </div>
            <form class="toolbar" style="margin:0;" method="GET" action="/logs">
                <input class="input" name="action" value="{{ request('action') }}" placeholder="action (örn: lead.claim)">
                <button class="btn btnPrimary" type="submit">Filtrele</button>
                <a class="btn" href="/logs">Temizle</a>
            </form>
        </div>
    </div>

    <div class="card" style="margin-top:14px; padding:0;">
        <div class="tableWrap">
            <table class="table">
                <thead>
                <tr>
                    <th style="padding-left:16px;">Zaman</th>
                    <th>Actor</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th style="padding-right:16px;">Meta</th>
                </tr>
                </thead>
                <tbody>
                @forelse($rows as $r)
                    @php($actor = $r->actor_user_id ? ($actors[(int)$r->actor_user_id] ?? null) : null)
                    <tr>
                        <td style="padding-left:16px;">
                            <div style="font-weight:900">{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('d.m.Y H:i') }}</div>
                            <div class="muted">{{ \Illuminate\Support\Carbon::parse($r->created_at)->diffForHumans() }}</div>
                        </td>
                        <td>{{ $actor?->name ?? '—' }}</td>
                        <td><span class="badge badgeNeutral">{{ $r->action }}</span></td>
                        <td class="muted">{{ $r->entity_type ?? '—' }}#{{ $r->entity_id ?? '—' }}</td>
                        <td style="padding-right:16px;">
                            <div class="muted" style="max-width:520px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                {{ $r->metadata_json }}
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted" style="padding:16px;">Kayıt yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:12px 16px;">{{ $rows->links() }}</div>
    </div>
@endsection

