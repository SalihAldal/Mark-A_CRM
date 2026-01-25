@extends('layouts.app')

@section('title', 'Bildirimler - Mark-A CRM')
@section('page_title', 'Bildirimler')

@section('content')
    <div class="card">
        <div class="toolbar" style="justify-content:space-between; margin-bottom:8px;">
            <div>
                <div class="pageTitle">Bildirimler</div>
            </div>
            <div class="toolbar" style="margin:0;">
                <a class="btn {{ request('unread') ? 'btnPrimary' : '' }}" href="/notifications?unread=1">Okunmamış ({{ (int)($unreadCount ?? 0) }})</a>
                <a class="btn" href="/notifications">Tümü</a>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:14px; padding:0;">
        <div class="tableWrap">
            <table class="table">
                <thead>
                <tr>
                    <th style="padding-left:16px;">Bildirim</th>
                    <th>Lead</th>
                    <th style="padding-right:16px; text-align:right;">İşlem</th>
                </tr>
                </thead>
                <tbody>
                @forelse($items as $n)
                    @php($lead = (($n->entity_type ?? '') === 'lead' && $n->entity_id) ? ($leads[(int)$n->entity_id] ?? null) : null)
                    <tr>
                        <td style="padding-left:16px;">
                            <div style="display:flex; gap:10px; align-items:flex-start;">
                                <span class="badge {{ $n->is_read ? 'badgeNeutral' : 'badgeSuccess' }}">{{ $n->is_read ? 'okundu' : 'yeni' }}</span>
                                <div>
                                    <div style="font-weight:1000">{{ $n->title }}</div>
                                    <div class="muted">{{ $n->body }}</div>
                                    <div class="muted" style="margin-top:4px;">{{ \Illuminate\Support\Carbon::parse($n->created_at)->diffForHumans() }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($lead)
                                <a href="/leads/{{ (int)$lead->id }}" style="font-weight:1000">{{ $lead->name }}</a>
                                <div class="muted">{{ $lead->status }} • {{ $lead->source }}</div>
                                <div class="muted">
                                    Devralan:
                                    <b>{{ $lead->assigned_user_id ? '#' . (int)$lead->assigned_user_id : '—' }}</b>
                                </div>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td style="padding-right:16px; text-align:right;">
                            <form method="POST" action="/notifications/{{ $n->id }}/read" style="display:inline;">
                                @csrf
                                <button class="btn" type="submit">Okundu</button>
                            </form>
                            @if($lead)
                                @if(!$lead->assigned_user_id)
                                    <form method="POST" action="/notifications/{{ $n->id }}/claim" style="display:inline;">
                                        @csrf
                                        <button class="btn btnPrimary" type="submit">Devral</button>
                                    </form>
                                @elseif((int)$lead->assigned_user_id === (int)auth()->id())
                                    <form method="POST" action="/notifications/{{ $n->id }}/release" style="display:inline;">
                                        @csrf
                                        <button class="btn" type="submit">Devralma</button>
                                    </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted" style="padding:16px;">Bildirim yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:12px 16px;">
            {{ $items->links() }}
        </div>
    </div>
@endsection

