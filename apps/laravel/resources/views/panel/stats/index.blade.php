@extends('layouts.app')

@section('title', __('ui.nav_stats') . ' - Mark-A CRM')
@section('page_title', __('ui.nav_stats'))

@section('content')
    <div class="card">
        <div class="toolbar" style="justify-content:space-between;">
            <div>
                <div class="pageTitle">Stats</div>
            </div>
            <div class="toolbar" style="margin:0;">
                <form method="GET" action="/stats">
                    <select class="input" name="days" onchange="this.form.submit()" style="min-width:120px;">
                        <option value="7" {{ ((int)($days ?? 30)===7) ? 'selected' : '' }}>7 gün</option>
                        <option value="14" {{ ((int)($days ?? 30)===14) ? 'selected' : '' }}>14 gün</option>
                        <option value="30" {{ ((int)($days ?? 30)===30) ? 'selected' : '' }}>30 gün</option>
                        <option value="60" {{ ((int)($days ?? 30)===60) ? 'selected' : '' }}>60 gün</option>
                        <option value="90" {{ ((int)($days ?? 30)===90) ? 'selected' : '' }}>90 gün</option>
                    </select>
                </form>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:14px;">
        <div class="cardTitle">Chat Trafiği</div>
        <div class="muted" style="margin-top:-4px;">Yeni başlayan sohbetler ve mesajlar (gelen/giden)</div>
        <canvas id="chartTraffic" height="90"></canvas>
    </div>

    <div class="grid2" style="margin-top:14px;">
        <div class="card">
            <div class="cardTitle">Leads (Pasta)</div>
            <div class="muted" style="margin-top:-4px;">Stage dağılımı</div>
            <canvas id="chartLeadsPie" height="200"></canvas>
        </div>

        <div class="card">
            <div class="grid2" style="gap:12px;">
                <div class="card" style="box-shadow:none; border:1px solid var(--line);">
                    <div class="muted">Toplam Sohbet</div>
                    <div class="metric" style="font-size:28px;">{{ (int) data_get($kpi, 'threads', 0) }}</div>
                </div>
                <div class="card" style="box-shadow:none; border:1px solid var(--line);">
                    <div class="muted">Toplam Mesaj</div>
                    <div class="metric" style="font-size:28px;">{{ (int) data_get($kpi, 'messages_total', 0) }}</div>
                </div>
                <div class="card" style="box-shadow:none; border:1px solid var(--line);">
                    <div class="muted">Gelen Mesaj</div>
                    <div class="metric" style="font-size:28px;">{{ (int) data_get($kpi, 'messages_in', 0) }}</div>
                    <div class="muted">Inbound</div>
                </div>
                <div class="card" style="box-shadow:none; border:1px solid var(--line);">
                    <div class="muted">Giden Mesaj</div>
                    <div class="metric" style="font-size:28px;">{{ (int) data_get($kpi, 'messages_out', 0) }}</div>
                    <div class="muted">Outbound</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const Chart = window.Chart;
            if (!Chart) return;

            const traffic = @json($traffic ?? []);
            const leadsByStage = @json($leadsByStage ?? []);

            new Chart(document.getElementById('chartTraffic'), {
                type: 'line',
                data: {
                    labels: traffic.map(x => (x.d || '').slice(5)),
                    datasets: [
                        {
                            label: 'Yeni Sohbet',
                            data: traffic.map(x => x.threads),
                            borderColor: 'rgba(59,130,246,.95)',
                            backgroundColor: 'rgba(59,130,246,.12)',
                            tension: .35,
                            fill: true
                        },
                        {
                            label: 'Gelen Mesaj',
                            data: traffic.map(x => x.in),
                            borderColor: 'rgba(16,185,129,.95)',
                            backgroundColor: 'rgba(16,185,129,.08)',
                            tension: .35,
                            fill: false
                        },
                        {
                            label: 'Giden Mesaj',
                            data: traffic.map(x => x.out),
                            borderColor: 'rgba(139,92,246,.95)',
                            backgroundColor: 'rgba(139,92,246,.08)',
                            tension: .35,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom' } },
                    scales: { y: { beginAtZero: true } }
                }
            });

            new Chart(document.getElementById('chartLeadsPie'), {
                type: 'pie',
                data: {
                    labels: leadsByStage.map(x => x.name),
                    datasets: [{
                        data: leadsByStage.map(x => x.cnt),
                        backgroundColor: leadsByStage.map((x, i) => {
                            const c = (x.color || '').trim();
                            if (c) return c;
                            const palette = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#111827'];
                            return palette[i % palette.length];
                        })
                    }]
                },
                options: { responsive: true }
            });
        })();
    </script>
@endsection

