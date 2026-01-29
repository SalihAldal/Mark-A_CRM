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
                <input class="input" name="action" value="{{ request('action') }}" placeholder="action (örn: user.login, lead.create, lead.reply, calendar.event_create)">
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
                    <th>Yapan</th>
                    <th>İşlem</th>
                    <th>Teknik</th>
                </tr>
                </thead>
                <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td style="padding-left:16px;">
                            <div style="font-weight:900">{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('d.m.Y H:i') }}</div>
                            <div class="muted">{{ \Illuminate\Support\Carbon::parse($r->created_at)->diffForHumans() }}</div>
                        </td>
                        <td style="white-space:nowrap;">{{ $r->actor_name ?? '—' }}</td>
                        <td>
                            <div style="font-weight:800;">
                                {{ $r->message ?? $r->action }}
                            </div>
                            @if(!empty($r->detail))
                                <div class="muted" style="margin-top:2px;">{{ $r->detail }}</div>
                            @endif
                        </td>
                        <td style="padding-right:16px;">
                            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                <span class="badge badgeNeutral">{{ $r->action }}</span>
                                <span class="muted">{{ $r->entity_type ?? '—' }}#{{ $r->entity_id ?? '—' }}</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted" style="padding:16px;">Kayıt yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:12px 16px;">{{ $rows->links() }}</div>
    </div>
@endsection

