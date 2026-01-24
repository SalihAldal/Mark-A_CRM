@extends('layouts.app')

@section('title', __('ui.nav_stats') . ' - Mark-A CRM')
@section('page_title', __('ui.nav_stats'))

@section('content')
    <div class="card" x-data="{ openWon:true, openLost:true }">
        <div class="toolbar" style="justify-content:space-between; margin-bottom:8px;">
            <div>
                <div class="pageTitle">Özet</div>
            </div>
            <div class="toolbar" style="margin:0;">
                <a class="btn {{ ($period ?? 'week')==='day' ? 'btnPrimary' : '' }}" href="/stats?period=day">Günlük</a>
                <a class="btn {{ ($period ?? 'week')==='week' ? 'btnPrimary' : '' }}" href="/stats?period=week">Haftalık</a>
                <a class="btn {{ ($period ?? 'week')==='month' ? 'btnPrimary' : '' }}" href="/stats?period=month">Aylık</a>
            </div>
        </div>

        <div class="grid2" style="margin-top:12px;">
            <div class="card" style="box-shadow:none;">
                <div class="cardTitle">Lead Akışı (Son 14 Gün)</div>
                <canvas id="chartDaily" height="120"></canvas>
            </div>
            <div class="card" style="box-shadow:none;">
                <div class="cardTitle">Durum Dağılımı (Seçili Periyot)</div>
                <canvas id="chartStatus" height="120"></canvas>
            </div>
        </div>

        <div class="card" style="box-shadow:none; margin-top:12px;">
            <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center; justify-content:space-between;">
                <div style="display:flex; gap:12px; flex-wrap:wrap;">
                    <span class="badge badgeNeutral">Bugün: <b style="margin-left:6px;">{{ (int)($kpi['today'] ?? 0) }}</b></span>
                    <span class="badge badgeNeutral">Hafta: <b style="margin-left:6px;">{{ (int)($kpi['week'] ?? 0) }}</b></span>
                    <span class="badge badgeNeutral">Ay: <b style="margin-left:6px;">{{ (int)($kpi['month'] ?? 0) }}</b></span>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <span class="badge badgeNeutral">Toplam: <b style="margin-left:6px;">{{ (int)($kpi['range_total'] ?? 0) }}</b></span>
                    <span class="badge badgeSuccess">Başarılı (won): <b style="margin-left:6px;">{{ (int)($kpi['range_won'] ?? 0) }}</b></span>
                    <span class="badge badgeDanger">Başarısız (lost): <b style="margin-left:6px;">{{ (int)($kpi['range_lost'] ?? 0) }}</b></span>
                </div>
            </div>
        </div>

        <div class="grid2" style="margin-top:12px;">
            <div class="card" style="box-shadow:none; padding:0;">
                <div style="padding:12px 16px; font-weight:1000;">Günlük Özet (Son 14 Gün)</div>
                <div class="tableWrap">
                    <table class="table">
                        <thead>
                        <tr>
                            <th style="padding-left:16px;">Gün</th>
                            <th>Toplam</th>
                            <th>Won</th>
                            <th>Lost</th>
                            <th style="padding-right:16px; text-align:right;">Başarı %</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse(collect($dailySeries ?? []) as $r)
                            @php($t = (int) data_get($r, 'total', 0))
                            @php($w = (int) data_get($r, 'won', 0))
                            @php($l = (int) data_get($r, 'lost', 0))
                            @php($d = (string) data_get($r, 'd', ''))
                            @php($pct = $t > 0 ? round(($w / $t) * 100) : 0)
                            <tr>
                                <td style="padding-left:16px;">{{ $d }}</td>
                                <td><span class="badge badgeNeutral">{{ $t }}</span></td>
                                <td><span class="badge badgeSuccess">{{ $w }}</span></td>
                                <td><span class="badge badgeDanger">{{ $l }}</span></td>
                                <td style="padding-right:16px; text-align:right;" class="muted">{{ $pct }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="muted" style="padding:16px;">Kayıt yok.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card" style="box-shadow:none; padding:0;">
                <div style="padding:12px 16px; font-weight:1000;">Stage Dağılımı</div>
                <div class="tableWrap">
                    <table class="table">
                        <thead>
                        <tr>
                            <th style="padding-left:16px;">Stage</th>
                            <th>Lead</th>
                            <th style="padding-right:16px; text-align:right;">Oran</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php($sumStage = (int) collect($leadsByStage ?? [])->sum('cnt'))
                        @forelse(collect($leadsByStage ?? []) as $s)
                            @php($cnt = (int) data_get($s, 'cnt', 0))
                            @php($name = (string) data_get($s, 'name', ''))
                            @php($p = $sumStage > 0 ? round(($cnt / $sumStage) * 100) : 0)
                            <tr>
                                <td style="padding-left:16px; font-weight:1000;">{{ $name }}</td>
                                <td><span class="badge badgeNeutral">{{ $cnt }}</span></td>
                                <td style="padding-right:16px; text-align:right;" class="muted">{{ $p }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="muted" style="padding:16px;">Kayıt yok.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="grid2" style="margin-top:12px;">
            <div class="card" style="box-shadow:none;">
                <div class="toolbar" style="justify-content:space-between; margin:0;">
                    <div style="font-weight:1000;">Başarılı Leadler</div>
                    <button class="btn" type="button" @click="openWon=!openWon" x-text="openWon ? 'Kapat' : 'Aç'"></button>
                </div>
                <div x-show="openWon" x-cloak style="margin-top:10px;">
                    <div class="tableWrap">
                        <table class="table">
                            <thead>
                            <tr>
                                <th style="padding-left:16px;">Lead</th>
                                <th>Skor</th>
                                <th style="padding-right:16px; text-align:right;">Tarih</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($wonLeads as $l)
                                <tr>
                                    <td style="padding-left:16px;">
                                        <a href="/leads/{{ $l->id }}" style="font-weight:1000;">{{ $l->name }}</a>
                                        <div class="muted">{{ $l->company }}</div>
                                    </td>
                                    <td><span class="badge badgeNeutral">{{ (int)($l->score ?? 0) }}</span></td>
                                    <td style="padding-right:16px; text-align:right;" class="muted">{{ \Illuminate\Support\Carbon::parse($l->created_at)->format('d.m.Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="muted" style="padding:16px;">Kayıt yok.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card" style="box-shadow:none;">
                <div class="toolbar" style="justify-content:space-between; margin:0;">
                    <div style="font-weight:1000;">Başarısız Leadler</div>
                    <button class="btn" type="button" @click="openLost=!openLost" x-text="openLost ? 'Kapat' : 'Aç'"></button>
                </div>
                <div x-show="openLost" x-cloak style="margin-top:10px;">
                    <div class="tableWrap">
                        <table class="table">
                            <thead>
                            <tr>
                                <th style="padding-left:16px;">Lead</th>
                                <th>Skor</th>
                                <th style="padding-right:16px; text-align:right;">Tarih</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($lostLeads as $l)
                                <tr>
                                    <td style="padding-left:16px;">
                                        <a href="/leads/{{ $l->id }}" style="font-weight:1000;">{{ $l->name }}</a>
                                        <div class="muted">{{ $l->company }}</div>
                                    </td>
                                    <td><span class="badge badgeNeutral">{{ (int)($l->score ?? 0) }}</span></td>
                                    <td style="padding-right:16px; text-align:right;" class="muted">{{ \Illuminate\Support\Carbon::parse($l->created_at)->format('d.m.Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="muted" style="padding:16px;">Kayıt yok.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid2">
        <div class="card">
            <div class="cardTitle">Lead -> Stage (Bar)</div>
            <canvas id="chartLeadsByStage" height="160"></canvas>
        </div>
        <div class="card">
            <div class="cardTitle">Mesaj Tipleri (Pie)</div>
            <canvas id="chartMessagesByType" height="160"></canvas>
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <div class="cardTitle">Çalışan Performansı (Mesaj sayısı)</div>
        <canvas id="chartStaff" height="120"></canvas>
    </div>

    <script>
        (function () {
            const Chart = window.Chart;
            if (!Chart) return;

            const daily = @json($dailySeries ?? []);
            const statusDist = @json($statusDist ?? []);
            const leadsByStage = @json($leadsByStage);
            const msgByType = @json($messagesByType);
            const staff = @json($staffPerformance);

            new Chart(document.getElementById('chartDaily'), {
                type: 'bar',
                data: {
                    labels: daily.map(x => x.d),
                    datasets: [
                        {
                            label: 'Toplam',
                            data: daily.map(x => x.total),
                            backgroundColor: 'rgba(255,122,0,.30)',
                            borderColor: 'rgba(255,122,0,.85)',
                            borderWidth: 1
                        },
                        {
                            label: 'Won',
                            data: daily.map(x => x.won),
                            backgroundColor: 'rgba(52,211,153,.35)',
                            borderColor: 'rgba(52,211,153,.85)',
                            borderWidth: 1
                        },
                        {
                            label: 'Lost',
                            data: daily.map(x => x.lost),
                            backgroundColor: 'rgba(248,113,113,.35)',
                            borderColor: 'rgba(248,113,113,.85)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom' } },
                    scales: { y: { beginAtZero: true } }
                }
            });

            new Chart(document.getElementById('chartStatus'), {
                type: 'pie',
                data: {
                    labels: statusDist.map(x => x.status),
                    datasets: [{
                        data: statusDist.map(x => x.cnt),
                        backgroundColor: [
                            'rgba(255,122,0,.55)',
                            'rgba(52,211,153,.55)',
                            'rgba(248,113,113,.55)',
                            'rgba(96,165,250,.55)',
                        ]
                    }]
                },
                options: { responsive: true }
            });

            new Chart(document.getElementById('chartLeadsByStage'), {
                type: 'bar',
                data: {
                    labels: leadsByStage.map(x => x.name),
                    datasets: [{
                        label: 'Lead',
                        data: leadsByStage.map(x => x.cnt),
                        backgroundColor: 'rgba(255,122,0,.35)',
                        borderColor: 'rgba(255,122,0,.85)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });

            new Chart(document.getElementById('chartMessagesByType'), {
                type: 'pie',
                data: {
                    labels: msgByType.map(x => x.message_type),
                    datasets: [{
                        data: msgByType.map(x => x.cnt),
                        backgroundColor: [
                            'rgba(255,122,0,.55)',
                            'rgba(96,165,250,.55)',
                            'rgba(52,211,153,.55)',
                            'rgba(248,113,113,.55)',
                        ]
                    }]
                },
                options: { responsive: true }
            });

            new Chart(document.getElementById('chartStaff'), {
                type: 'bar',
                data: {
                    labels: staff.map(x => x.name),
                    datasets: [{
                        label: 'Mesaj',
                        data: staff.map(x => x.cnt),
                        backgroundColor: 'rgba(96,165,250,.35)',
                        borderColor: 'rgba(96,165,250,.85)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        })();
    </script>
@endsection

