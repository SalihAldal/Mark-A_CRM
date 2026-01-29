@extends('layouts.app')

@section('title', __('ui.nav_lists') . ' - Mark-A CRM')
@section('page_title', __('ui.nav_lists'))

@section('content')
    <div class="card">
        <div class="toolbar" style="justify-content:space-between; margin-bottom:8px;">
            <div>
                <div class="pageTitle">Listeler</div>
                <div class="muted">Lead / Contact segment listeleri</div>
            </div>
        </div>

        <form method="GET" class="filterPanel" style="margin-top:12px;">
            <div class="filterRow r3actions">
                <div class="filterField">
                    <div class="label">Arama</div>
                    <input class="input" name="q" value="{{ request('q') }}" placeholder="Liste adı">
                </div>
                <div class="filterField">
                    <div class="label">Tip</div>
                    <select class="input" name="type">
                        <option value="">Tümü</option>
                        <option value="lead" @selected(request('type')==='lead')>lead</option>
                        <option value="contact" @selected(request('type')==='contact')>contact</option>
                    </select>
                </div>
                <div class="filterActions">
                    <button class="btn btnPrimary" type="submit">Filtrele</button>
                    <a class="btn" href="/lists">Sıfırla</a>
                </div>
            </div>
        </form>

        <form method="POST" action="/lists" class="filterPanel" style="margin-top:12px;">
            @csrf
            <div class="filterRow r3actions">
                <div class="filterField">
                    <div class="label">Yeni Liste</div>
                    <input class="input" name="name" placeholder="Örn: Sıcak Leadler" required>
                </div>
                <div class="filterField">
                    <div class="label">Tip</div>
                    <select class="input" name="type" required>
                        <option value="lead">lead</option>
                        <option value="contact">contact</option>
                    </select>
                </div>
                <div class="filterActions">
                    <button class="btn btnPrimary" type="submit">Oluştur</button>
                </div>
            </div>
        </form>
    </div>

    <div class="card" style="margin-top:14px; padding:0;">
        <div class="tableWrap">
            <table class="table">
                <thead>
                <tr>
                    <th style="padding-left:16px;">Liste</th>
                    <th>Tip</th>
                    <th style="padding-right:16px; text-align:right;">Oluşturma</th>
                </tr>
                </thead>
                <tbody>
                @forelse($lists as $l)
                    <tr>
                        <td style="padding-left:16px;">
                            <div style="font-weight:1000">{{ $l->name }}</div>
                            <div class="muted">#{{ $l->id }}</div>
                        </td>
                        <td><span class="badge badgeNeutral">{{ $l->type }}</span></td>
                        <td style="padding-right:16px; text-align:right;" class="muted">{{ \Illuminate\Support\Carbon::parse($l->created_at)->format('d.m.Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted" style="padding:16px;">Kayıt yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:12px 16px;">
            {{ $lists->links() }}
        </div>
    </div>

    <div class="card" style="margin-top:14px; padding:0;">
        <div class="toolbar" style="justify-content:space-between; padding:14px 16px;">
            <div>
                <div class="pageTitle" style="font-size:16px;">Müşteriler</div>
                <div class="muted">Lead kayıtlarındaki müşteri listesi</div>
            </div>
        </div>
        <div class="tableWrap">
            <table class="table">
                <thead>
                <tr>
                    <th style="padding-left:16px;">Müşteri</th>
                    <th class="muted">İletişim</th>
                    <th>Devralan</th>
                    <th>Kaynak</th>
                    <th>Durum</th>
                    <th style="padding-right:16px; text-align:right;">Aksiyon</th>
                </tr>
                </thead>
                <tbody>
                @forelse(($leadCustomers ?? []) as $c)
                    <tr>
                        <td style="padding-left:16px;">
                            <div style="font-weight:1000">{{ data_get($c,'name','') }}</div>
                            <div class="muted">#{{ (int) data_get($c,'id',0) }}</div>
                        </td>
                        <td class="muted">
                            {{ data_get($c,'email') ?: '—' }} • {{ data_get($c,'phone') ?: '—' }}
                        </td>
                        <td>{{ data_get($c,'assigned_name') ?: '—' }}</td>
                        <td>{{ data_get($c,'source') ?: '—' }}</td>
                        <td>
                            @php($st = (string) data_get($c,'status',''))
                            @if($st === 'won')
                                <span class="badge badgeSuccess">won</span>
                            @elseif($st === 'lost')
                                <span class="badge badgeDanger">lost</span>
                            @else
                                <span class="badge badgeSuccess">active</span>
                            @endif
                        </td>
                        <td style="padding-right:16px; text-align:right;">
                            <a class="btn" href="/leads/{{ (int) data_get($c,'id',0) }}">Aç</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted" style="padding:16px;">Kayıt yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

