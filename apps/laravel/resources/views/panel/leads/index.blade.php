@extends('layouts.app')

@section('title', __('ui.nav_leads') . ' - Mark-A CRM')
@section('page_title', __('ui.nav_leads'))

@section('content')
    @php($tenant = app(\App\Support\TenantContext::class)->tenant())

    <div class="card">
        <div class="toolbar" style="justify-content:space-between; margin-bottom:8px;">
            <div>
                <div class="pageTitle">Müşteriler (Lead kayıtları)</div>
                <div class="muted">Lead müşterileri • durumları • devralan çalışan</div>
            </div>
            <div class="toolbar" style="margin:0;">
                @if((string)(auth()->user()->role?->key ?? '') !== 'customer')
                    <a class="btn btnPrimary" href="/leads/create">Yeni Lead</a>
                @endif
                <a class="btn" href="/leads/kanban">{{ __('ui.leads_kanban') }}</a>
                <button class="btn" type="button" onclick="window.location.reload()">{{ __('ui.refresh') }}</button>
            </div>
        </div>

        <form method="GET" class="filterPanel" style="margin-top:12px;">
            <div class="filterRow">
                <div class="filterField" style="grid-column: 1 / -1;">
                    <div class="label">Arama</div>
                    <input class="input" name="q" value="{{ request('q') }}" placeholder="isim / email / telefon">
                </div>
            </div>

            <div class="filterRow r3wide">
                <div class="filterField" style="grid-column: 1 / 2;">
                    <div class="label">Tenant</div>
                    <select class="input" disabled>
                        <option>{{ $tenant?->name ?? 'Tenant' }}</option>
                    </select>
                </div>
                <div class="filterField">
                    <div class="label">Devralan</div>
                    <select class="input" name="assigned_user_id">
                        <option value="">Tümü</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" @selected((string)$u->id === (string)request('assigned_user_id'))>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filterField">
                    <div class="label">Stage</div>
                    <select class="input" name="stage_id">
                        <option value="">Tümü</option>
                        @foreach($stages as $s)
                            <option value="{{ $s->id }}" @selected((string)$s->id === (string)request('stage_id'))>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="filterRow r3actions">
                <div class="filterField">
                    <div class="label">Durum</div>
                    <select class="input" name="status">
                        <option value="">Tümü</option>
                        <option value="open" @selected(request('status')==='open')>open</option>
                        <option value="won" @selected(request('status')==='won')>won</option>
                        <option value="lost" @selected(request('status')==='lost')>lost</option>
                    </select>
                </div>
                <div class="filterField">
                    <div class="label">Kaynak</div>
                    <select class="input" name="source">
                        <option value="">Tümü</option>
                        <option value="manual" @selected(request('source')==='manual')>manual</option>
                        <option value="wp" @selected(request('source')==='wp')>wp</option>
                        <option value="instagram" @selected(request('source')==='instagram')>instagram</option>
                        <option value="whatsapp" @selected(request('source')==='whatsapp')>whatsapp</option>
                        <option value="telegram" @selected(request('source')==='telegram')>telegram</option>
                        <option value="facebook" @selected(request('source')==='facebook')>facebook</option>
                        <option value="wechat" @selected(request('source')==='wechat')>wechat</option>
                    </select>
                </div>
                <div class="filterActions">
                    <button class="btn btnPrimary" type="submit">Filtrele</button>
                    <a class="btn" href="/leads">Sıfırla</a>
                </div>
            </div>
        </form>
    </div>

    <div class="card" style="margin-top:14px; padding:0;">
        <div class="tableWrap">
            <table class="table">
                <thead>
                <tr>
                    <th style="padding-left:16px;">Müşteri (Lead)</th>
                    <th>Devralan</th>
                    <th>Kaynak</th>
                    <th>Stage</th>
                    <th>Durum</th>
                    <th>Skor</th>
                    <th style="text-align:right; padding-right:16px;">İşlem</th>
                </tr>
                </thead>
                <tbody>
                @forelse($leads as $lead)
                    <tr>
                        <td style="padding-left:16px;">
                            <div style="font-weight:1000">{{ $lead->name }}</div>
                            <div class="muted">
                                {{ $lead->email ?? '-' }} • {{ $lead->phone ?? '-' }}
                                • #{{ $lead->id }} • {{ \Illuminate\Support\Carbon::parse($lead->created_at)->format('d.m.Y H:i') }}
                            </div>
                        </td>
                        <td>{{ $lead->assigned_name ?? '—' }}</td>
                        <td>{{ $lead->source }}</td>
                        <td>
                            <span class="badge badgeNeutral">{{ $lead->stage_name ?? '-' }}</span>
                        </td>
                        <td>
                            @php($st = (string) $lead->status)
                            @if($st === 'won')
                                <span class="badge badgeSuccess">won</span>
                            @elseif($st === 'lost')
                                <span class="badge badgeDanger">lost</span>
                            @else
                                <span class="badge badgeSuccess">active</span>
                            @endif
                        </td>
                        <td><span class="badge badgeNeutral">{{ (int) $lead->score }}</span></td>
                        <td style="text-align:right; padding-right:16px;">
                            <a class="btn" href="/leads/{{ (int)$lead->id }}">Aç</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted" style="padding:16px;">Kayıt yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:12px 16px;">
            {{ $leads->links() }}
        </div>
    </div>
@endsection

