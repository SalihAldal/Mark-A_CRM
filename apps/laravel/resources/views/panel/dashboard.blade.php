@extends('layouts.app')

@section('title', __('ui.nav_dashboard') . ' - Mark-A CRM')
@section('page_title', __('ui.nav_dashboard'))

@section('content')
    <div class="card">
        <div class="toolbar" style="justify-content:space-between; margin-bottom:8px;">
            <div>
                <div class="pageTitle">Dashboard</div>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <span class="badge badgeNeutral">Toplam: <b style="margin-left:6px;">{{ (int)($leadCount ?? 0) }}</b></span>
                <span class="badge badgeSuccess">Won: <b style="margin-left:6px;">{{ (int)($wonCount ?? 0) }}</b></span>
                <span class="badge badgeDanger">Lost: <b style="margin-left:6px;">{{ (int)($lostCount ?? 0) }}</b></span>
            </div>
        </div>
    </div>

    <div class="grid2" style="margin-top:14px;">
        <div class="card" style="padding:0;">
            <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px;">
                <div style="font-weight:1000;">Canlı Leads</div>
                <a class="btn" href="/leads">Tümü</a>
            </div>
            <div class="tableWrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th style="padding-left:16px;">Lead</th>
                        <th>Stage</th>
                        <th style="padding-right:16px; text-align:right;">Skor</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($liveLeads ?? [] as $l)
                        <tr>
                            <td style="padding-left:16px;">
                                <a href="/leads/{{ (int)data_get($l,'id',0) }}" style="font-weight:1000;">{{ data_get($l,'name','—') }}</a>
                                <div class="muted">{{ data_get($l,'email','') }} • {{ data_get($l,'phone','') }}</div>
                            </td>
                            <td>
                                <span class="badge badgeNeutral" @if(!empty(data_get($l,'stage_color'))) style="background: {{ data_get($l,'stage_color') }}20; border-color: {{ data_get($l,'stage_color') }}40;" @endif>
                                    {{ data_get($l,'stage_name','—') }}
                                </span>
                            </td>
                            <td style="padding-right:16px; text-align:right;">
                                <span class="badge badgeNeutral">{{ (int) data_get($l,'score',0) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="muted" style="padding:16px;">Kayıt yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" style="padding:0;">
            <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px;">
                <div style="font-weight:1000;">Canlı Görüşmeler (Inbox)</div>
                <a class="btn" href="/chats">Inbox</a>
            </div>
            <div class="tableWrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th style="padding-left:16px;">Kişi</th>
                        <th>Platform</th>
                        <th style="padding-right:16px; text-align:right;">Tarih</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($liveThreads ?? [] as $t)
                        @php($when = data_get($t,'last_message_at') ? \Illuminate\Support\Carbon::parse(data_get($t,'last_message_at')) : null)
                        <tr>
                            <td style="padding-left:16px;">
                                <a href="/chats?thread={{ (int)data_get($t,'id',0) }}" style="font-weight:1000;">
                                    {{ data_get($t,'contact_name') ? data_get($t,'contact_name') : ('Thread #' . (int)data_get($t,'id',0)) }}
                                </a>
                                <div class="muted">{{ data_get($t,'status','') }}</div>
                            </td>
                            <td class="muted">{{ data_get($t,'provider') ?? data_get($t,'channel') ?? '—' }}</td>
                            <td style="padding-right:16px; text-align:right;" class="muted">{{ $when ? $when->format('Y-m-d H:i') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="muted" style="padding:16px;">Kayıt yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

