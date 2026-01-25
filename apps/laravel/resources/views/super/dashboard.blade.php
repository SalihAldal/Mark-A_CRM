@extends('layouts.app')

@section('title', 'Super Panel - Mark-A CRM')
@section('page_title', 'Super Panel')

@section('content')
    <div class="card" style="padding:0;">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 16px;">
            <div>
                <div style="font-weight:1000;">Süperadmin Denetim</div>
                <div class="muted">Tüm customer lead performansı</div>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                <span class="badge badgeNeutral">Toplam: <b style="margin-left:6px;">{{ (int) data_get($totals, 'total', 0) }}</b></span>
                <span class="badge badgeSuccess">Won: <b style="margin-left:6px;">{{ (int) data_get($totals, 'won', 0) }}</b></span>
                <span class="badge badgeDanger">Lost: <b style="margin-left:6px;">{{ (int) data_get($totals, 'lost', 0) }}</b></span>
            </div>
        </div>

        <div class="tableWrap">
            <table class="table">
                <thead>
                <tr>
                    <th style="padding-left:16px;">Tenant</th>
                    <th>Total</th>
                    <th>Won</th>
                    <th>Lost</th>
                    <th style="padding-right:16px; text-align:right;">Detay</th>
                </tr>
                </thead>
                <tbody>
                @forelse($tenantRows ?? [] as $r)
                    <tr>
                        <td style="padding-left:16px; font-weight:1000;">{{ $r->name }}</td>
                        <td><span class="badge badgeNeutral">{{ (int)($r->total ?? 0) }}</span></td>
                        <td><span class="badge badgeSuccess">{{ (int)($r->won ?? 0) }}</span></td>
                        <td><span class="badge badgeDanger">{{ (int)($r->lost ?? 0) }}</span></td>
                        <td style="padding-right:16px; text-align:right;">
                            <a class="btn" href="/super/tenants">Leads</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted" style="padding:16px;">Kayıt yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid2" style="margin-top:14px;">
        <div class="card" style="padding:0;">
            <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px;">
                <div style="font-weight:1000;">Canlı Leads</div>
                <a class="btn" href="/super/tenants">Tümü</a>
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
                                <div style="font-weight:1000;">{{ $l->name }}</div>
                                <div class="muted">{{ $l->tenant_name }} • {{ $l->email }} • {{ $l->phone }}</div>
                            </td>
                            <td>
                                <span class="badge badgeNeutral" @if(!empty($l->stage_color)) style="background: {{ $l->stage_color }}20; border-color: {{ $l->stage_color }}40;" @endif>
                                    {{ $l->stage_name ?? '—' }}
                                </span>
                            </td>
                            <td style="padding-right:16px; text-align:right;">
                                <span class="badge badgeNeutral">{{ (int)($l->score ?? 0) }}</span>
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
                <a class="btn" href="/super/tenants">Inbox</a>
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
                        @php($when = $t->last_message_at ? \Illuminate\Support\Carbon::parse($t->last_message_at) : null)
                        <tr>
                            <td style="padding-left:16px;">
                                <div style="font-weight:1000;">{{ $t->contact_name ?? ('Thread #' . (int)$t->id) }}</div>
                                <div class="muted">{{ $t->tenant_name }} • {{ $t->status }}</div>
                            </td>
                            <td class="muted">{{ $t->provider ?? $t->channel ?? '—' }}</td>
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

