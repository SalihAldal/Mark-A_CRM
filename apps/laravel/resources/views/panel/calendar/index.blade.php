@extends('layouts.app')

@section('title', __('ui.nav_calendar') . ' - Mark-A CRM')
@section('page_title', __('ui.nav_calendar'))

@section('content')
    @php($roleKey = (string)(auth()->user()->role?->key ?? ''))
    <div class="card" x-data="calendarUI()" x-init="init({{ $roleKey === 'tenant_admin' || $roleKey === 'staff' ? 'true' : 'false' }})">
        <div class="toolbar" style="justify-content:space-between; margin-bottom:10px;">
            <div>
                <div class="pageTitle">Takvim</div>
                <div class="muted">Aylık görünüm</div>
            </div>
            @if($roleKey === 'tenant_admin' || $roleKey === 'staff')
                <button class="btn btnPrimary" type="button" @click="openCreate()">Yeni Etkinlik</button>
            @endif
        </div>

        <div id="calendarRoot"></div>

        <!-- Event detail modal -->
        <div class="modalOverlay" x-show="detail.open" x-cloak @click.self="detail.open=false">
            <div class="card modalCard">
                <div class="toolbar" style="justify-content:space-between; margin-bottom:8px;">
                    <div style="font-weight:1000;" x-text="detail.title"></div>
                    <div class="toolbar" style="margin:0;">
                        <button class="btn" type="button" @click="deleteEvent()" :disabled="detail.deleting">Sil</button>
                        <button class="btn" type="button" @click="detail.open=false">Kapat</button>
                    </div>
                </div>
                <div class="muted" x-text="detail.time"></div>
                <div style="margin-top:12px; white-space:pre-wrap;" x-text="detail.description"></div>
            </div>
        </div>

        <!-- Create modal -->
        <div class="modalOverlay" x-show="create.open" x-cloak @click.self="create.open=false">
            <div class="card modalCard">
                <div class="toolbar" style="justify-content:space-between; margin-bottom:6px;">
                    <div>
                        <div style="font-weight:1000;">Yeni Etkinlik</div>
                        <div class="muted">Tarih aralığı + aciliyet rengi</div>
                    </div>
                    <button class="btn" type="button" @click="create.open=false">Kapat</button>
                </div>

                <div class="filters" style="margin-top:12px;">
                    <div style="grid-column: 1 / -1;">
                        <div class="label">Başlık</div>
                        <input class="input" x-model="create.title" placeholder="Örn: Müşteri görüşmesi" required>
                    </div>

                    <div>
                        <div class="label">Başlangıç</div>
                        <input class="input" type="datetime-local" x-model="create.starts_at" required>
                    </div>
                    <div>
                        <div class="label">Bitiş</div>
                        <input class="input" type="datetime-local" x-model="create.ends_at" required>
                    </div>

                    <div style="grid-column: 1 / -1;">
                        <div class="label">Aciliyet</div>
                        <div class="segGroup">
                            <button type="button" class="segBtn" :class="create.urgency==='low' ? 'segBtnActive urgLow' : 'urgLow'" @click="create.urgency='low'">Mavi</button>
                            <button type="button" class="segBtn" :class="create.urgency==='medium' ? 'segBtnActive urgMed' : 'urgMed'" @click="create.urgency='medium'">Sarı</button>
                            <button type="button" class="segBtn" :class="create.urgency==='high' ? 'segBtnActive urgHigh' : 'urgHigh'" @click="create.urgency='high'">Kırmızı</button>
                        </div>
                    </div>

                    <div style="grid-column: 1 / -1;">
                        <div class="label">Açıklama</div>
                        <textarea class="input" rows="4" x-model="create.description" placeholder="Detay..."></textarea>
                    </div>
                </div>

                <div class="filtersActions" style="margin-top:12px;">
                    <button class="btn btnPrimary" type="button" @click="save()" :disabled="create.saving || !create.title">Kaydet</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function calendarUI() {
            return {
                calendar: null,
                detail: { open:false, id:null, title:'', time:'', description:'', deleting:false },
                create: { open:false, title:'', starts_at:'', ends_at:'', description:'', urgency:'low', saving:false },
                canManage: false,

                openCreate() {
                    const now = new Date();
                    const pad = n => String(n).padStart(2, '0');
                    const toLocal = d => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
                    const end = new Date(now.getTime() + 60 * 60 * 1000);
                    this.create = { open:true, title:'', starts_at: toLocal(now), ends_at: toLocal(end), description:'', urgency:'low', saving:false };
                },

                async save() {
                    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    this.create.saving = true;
                    const r = await fetch('/calendar', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({
                            title: this.create.title,
                            starts_at: this.create.starts_at,
                            ends_at: this.create.ends_at,
                            description: this.create.description,
                            urgency: this.create.urgency,
                        })
                    });
                    this.create.saving = false;
                    if (!r.ok) { alert('Kaydedilemedi.'); return; }
                    this.create.open = false;
                    if (this.calendar) this.calendar.refetchEvents();
                },

                async deleteEvent() {
                    if (!this.detail.id) return;
                    if (!confirm('Etkinlik silinsin mi?')) return;
                    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    this.detail.deleting = true;
                    const r = await fetch(`/calendar/${this.detail.id}/delete`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: JSON.stringify({})
                    });
                    this.detail.deleting = false;
                    if (!r.ok) { alert('Silinemedi.'); return; }
                    this.detail.open = false;
                    if (this.calendar) this.calendar.refetchEvents();
                },

                init(canManage) {
                    this.canManage = !!canManage;
                    const FC = window.FullCalendar;
                    if (!FC) return;
                    const root = document.getElementById('calendarRoot');
                    this.calendar = new FC.Calendar(root, {
                        plugins: [FC.dayGridPlugin, FC.interactionPlugin],
                        initialView: 'dayGridMonth',
                        height: 'auto',
                        dayMaxEvents: true,
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: ''
                        },
                        selectable: true,
                        eventSources: [{
                            url: '/calendar/events',
                            method: 'GET',
                        }],
                        dateClick: (info) => {
                            if (!this.canManage) return;
                            // tıklayınca hızlı oluştur (gün bazlı)
                            const d = new Date(info.date);
                            const end = new Date(d.getTime() + 60 * 60 * 1000);
                            const pad = n => String(n).padStart(2, '0');
                            const toLocal = dt => `${dt.getFullYear()}-${pad(dt.getMonth()+1)}-${pad(dt.getDate())}T${pad(dt.getHours())}:${pad(dt.getMinutes())}`;
                            this.create.open = true;
                            this.create.title = '';
                            this.create.starts_at = toLocal(d);
                            this.create.ends_at = toLocal(end);
                            this.create.description = '';
                            this.create.urgency = 'low';
                        },
                        eventClick: (info) => {
                            const ev = info.event;
                            this.detail.open = true;
                            this.detail.id = ev.id;
                            this.detail.title = ev.title;
                            const start = ev.start ? ev.start.toLocaleString() : '';
                            const end = ev.end ? ev.end.toLocaleString() : '';
                            this.detail.time = end ? `${start} → ${end}` : start;
                            this.detail.description = (ev.extendedProps && ev.extendedProps.description) ? ev.extendedProps.description : '';
                        },
                    });
                    this.calendar.render();
                }
            }
        }
    </script>
@endsection

