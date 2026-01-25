@extends('layouts.app')

@section('title', __('ui.nav_chats') . ' - Mark-A CRM')
@section('page_title', __('ui.nav_chats'))

@section('content')
    @php
        function platform_svg($channel) {
            $c = strtolower((string)$channel);
            // Minimal monochrome icons
            if ($c === 'instagram') return '<svg viewBox="0 0 24 24" fill="none"><path d="M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4Z" stroke="currentColor" stroke-width="2"/><path d="M12 17a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z" stroke="currentColor" stroke-width="2"/><path d="M17.5 6.5h.01" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>';
            if ($c === 'whatsapp' || $c === 'wp') return '<svg viewBox="0 0 24 24" fill="none"><path d="M12 21a9 9 0 1 0-7.8-4.5L3 21l4.7-1.2A8.9 8.9 0 0 0 12 21Z" stroke="currentColor" stroke-width="2"/><path d="M9.2 9.2c.6 1.4 2.2 3 3.6 3.6.3.1.6.1.8-.1l1.1-.8c.2-.2.6-.2.8 0l1.2.8c.3.2.4.6.2.9-.6 1.1-2 1.7-3.2 1.3-2.7-.9-5.6-3.8-6.5-6.5-.4-1.2.2-2.6 1.3-3.2.3-.2.7-.1.9.2l.8 1.2c.1.2.1.6 0 .8l-.8 1.1c-.2.2-.2.5-.1.8Z" fill="currentColor"/></svg>';
            if ($c === 'telegram') return '<svg viewBox="0 0 24 24" fill="none"><path d="M21 4 3 11.5l7 2.5 2.5 7L21 4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M10 14 21 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
            if ($c === 'facebook') return '<svg viewBox="0 0 24 24" fill="none"><path d="M14 9h3V6h-3c-1.7 0-3 1.3-3 3v3H8v3h3v6h3v-6h3l1-3h-4V9c0-.6.4-1 1-1Z" fill="currentColor"/></svg>';
            if ($c === 'wechat') return '<svg viewBox="0 0 24 24" fill="none"><path d="M10.5 17c-3.6 0-6.5-2.5-6.5-5.5S6.9 6 10.5 6s6.5 2.5 6.5 5.5c0 1.1-.4 2.1-1.1 3l.6 2-2.2-.9c-1.1.6-2.4.9-3.8.9Z" stroke="currentColor" stroke-width="2"/><path d="M14.5 18.5c3 0 5.5-2 5.5-4.5S17.5 9.5 14.5 9.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
            return '<svg viewBox="0 0 24 24" fill="none"><path d="M6 7h12M6 12h12M6 17h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
        }
    @endphp

    <div class="card" style="padding:0; overflow:hidden;" x-data="chatUI()" x-init="init()">
        <div class="chatGrid">
            <div style="border-right:1px solid var(--line); padding:14px;">
                <div style="display:flex; gap:10px; align-items:center; justify-content:space-between; margin-bottom:10px;">
                    <div style="font-weight:900">{{ __('ui.chats_title') }}</div>
                    <button class="btn" type="button" onclick="window.location.reload()">{{ __('ui.refresh') }}</button>
                </div>

                <form method="GET" class="filterPanel" style="margin-top:12px;">
                    <div class="filterRow r3actions">
                        <div class="filterField">
                            <div class="label">Thread</div>
                            <input class="input" name="thread" value="{{ request('thread') }}" placeholder="ID">
                        </div>
                        <div class="filterField">
                            <div class="label">Kanal</div>
                            <select class="input" name="channel">
                        <option value="">Kanal</option>
                        <option value="instagram" @selected(request('channel')==='instagram')>instagram</option>
                        <option value="whatsapp" @selected(request('channel')==='whatsapp')>whatsapp</option>
                        <option value="telegram" @selected(request('channel')==='telegram')>telegram</option>
                        <option value="facebook" @selected(request('channel')==='facebook')>facebook</option>
                        <option value="wechat" @selected(request('channel')==='wechat')>wechat</option>
                        <option value="internal" @selected(request('channel')==='internal')>internal</option>
                            </select>
                        </div>
                        <div class="filterField">
                            <div class="label">Durum</div>
                            <select class="input" name="status">
                                <option value="">Tümü</option>
                                <option value="open" @selected(request('status')==='open')>open</option>
                                <option value="closed" @selected(request('status')==='closed')>closed</option>
                            </select>
                        </div>
                    </div>

                    <div class="filterRow">
                        <div class="filterActions">
                            <button class="btn btnPrimary" type="submit">Filtrele</button>
                            <a class="btn" href="/chats">Sıfırla</a>
                        </div>
                    </div>
                </form>

                <div style="display:flex; flex-direction:column; gap:10px; margin-top:12px;">
                    @foreach($threads as $t)
                        <a href="/chats?{{ http_build_query(array_merge(request()->query(), ['thread' => $t->id])) }}"
                           class="card"
                           style="padding:12px; box-shadow:none; border-color: {{ (string)request('thread')===(string)$t->id ? '#111827' : 'var(--line)' }};">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                                <div style="display:flex; align-items:center; gap:10px; min-width:0;">
                                    <span class="platformIcon">{!! platform_svg($t->channel) !!}</span>
                                    <div style="min-width:0;">
                                        <div style="font-weight:1000; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                            @php($isIntegration = in_array(strtolower((string)($t->channel ?? '')), ['instagram','whatsapp','wp','telegram'], true))
                                            {{ $isIntegration ? ($t->contact_name ?? $t->lead_name ?? ('Thread #' . $t->id)) : ($t->lead_name ?? $t->contact_name ?? ('Thread #' . $t->id)) }}
                                        </div>
                                        <div class="muted">
                                            @if(!empty($t->contact_username))
                                                <span>@{{ $t->contact_username }}</span>
                                                <span style="padding:0 6px;">•</span>
                                            @endif
                                            <span>{{ $t->status }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="muted" style="margin-top:2px;">
                                {{ $t->last_message_at ? \Illuminate\Support\Carbon::parse($t->last_message_at)->diffForHumans() : '-' }}
                            </div>
                        </a>
                    @endforeach
                </div>

                <div style="margin-top:12px;">
                    {{ $threads->links() }}
                </div>
            </div>

            <div style="padding:14px; display:flex; flex-direction:column; min-width:0;">
                @if(!$selected)
                    <div class="card" style="box-shadow:none;">
                        <div class="cardTitle">Chat</div>
                        <div class="muted">Sol listeden bir sohbet seçin.</div>
                    </div>
                @else
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:10px;">
                        <div style="min-width:0;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <span class="platformIcon">{!! platform_svg($selected->channel) !!}</span>
                                <div style="min-width:0;">
                                    <div style="font-weight:1000; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        @php($isIntegrationSel = in_array(strtolower((string)($selected->channel ?? '')), ['instagram','whatsapp','wp','telegram'], true))
                                        {{ $isIntegrationSel ? ($selected->contact_name ?? $selected->lead_name ?? ('Thread #' . $selected->id)) : ($selected->lead_name ?? $selected->contact_name ?? ('Thread #' . $selected->id)) }}
                                    </div>
                                    @if(!empty($selected->contact_username))
                                        <div class="muted">@{{ $selected->contact_username }}</div>
                                    @endif
                                </div>
                            </div>
                            <div class="muted">Durum: {{ $selected->status }}</div>
                        </div>

                        <button class="btn btnPrimary" type="button" style="min-width:74px" @click="openAi()">
                            AI
                        </button>
                    </div>

                    <div class="card" style="flex:1; min-height:0; box-shadow:none; background: #f8fafc; border-color: var(--line);">
                        <div style="display:flex; flex-direction:column; gap:10px; overflow:auto; max-height:50vh;" id="chatScroll">
                            @foreach($messages as $m)
                                @php($isMine = $m->sender_type === 'user')
                                <div style="display:flex; justify-content: {{ $isMine ? 'flex-end' : 'flex-start' }};">
                                    <div class="card" style="box-shadow:none; max-width:75%; padding:12px; border-color: {{ $isMine ? '#111827' : 'var(--line)' }}; background: {{ $isMine ? '#111827' : '#ffffff' }}; color: {{ $isMine ? '#ffffff' : 'var(--text)' }};">
                                        <div class="muted" style="margin-bottom:6px; {{ $isMine ? 'color: rgba(255,255,255,.75);' : '' }}">
                                            {{ $isMine ? 'Temsilci' : ($m->sender_type === 'contact' ? 'Müşteri' : 'Sistem') }}
                                            • {{ \Illuminate\Support\Carbon::parse($m->created_at)->format('H:i') }}
                                        </div>

                                        @if($m->message_type === 'text')
                                            <div style="white-space:pre-wrap;">{{ $m->body_text }}</div>
                                        @elseif($m->message_type === 'voice')
                                            <audio controls src="{{ $m->file_path }}"></audio>
                                        @elseif($m->message_type === 'image')
                                            <a href="{{ $m->file_path }}" target="_blank" rel="noreferrer">
                                                <img src="{{ $m->file_path }}" style="max-width:100%; border-radius:12px; border:1px solid var(--line)">
                                            </a>
                                        @else
                                            <a class="pill" href="{{ $m->file_path }}" target="_blank" rel="noreferrer">Dosya indir</a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            <div id="chatBottom" style="height:1px;"></div>
                        </div>
                    </div>

                    <div class="composer" style="margin-top:12px;">
                        <div class="voicePreview" x-show="voice.state==='ready'" x-cloak>
                            <audio controls :src="voice.url" style="width:100%"></audio>
                            <button class="btn" type="button" @click="voiceCancel()">Sil</button>
                        </div>

                        <div class="composerRow">
                            <input type="file" x-ref="fileInput" style="display:none" @change="fileSelected({{ (int)$selected->id }}, $event)">

                            <button class="iconBtn" type="button" title="Dosya ekle" @click="filePick()">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M21 12.5V17a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4h9.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M21 3v7h-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M21 3 10 14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            </button>

                            <button class="iconBtn" type="button" title="Ses kaydı" @click="voiceToggle()">
                                <template x-if="voice.state !== 'recording'">
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 14a3 3 0 0 0 3-3V6a3 3 0 0 0-6 0v5a3 3 0 0 0 3 3Z" stroke="currentColor" stroke-width="2"/><path d="M19 11a7 7 0 0 1-14 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M12 18v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M8 21h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                </template>
                                <template x-if="voice.state === 'recording'">
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M6 6h12v12H6z" stroke="currentColor" stroke-width="2"/></svg>
                                </template>
                            </button>

                            <input class="input composerInput" x-model="message" placeholder="Mesaj yaz...">
                            <button class="btn btnPrimary" type="button" @click="send({{ (int)$selected->id }})" :disabled="sending">Gönder</button>
                        </div>
                        <div class="muted" style="margin-top:8px;" x-show="sending" x-cloak>Gönderiliyor...</div>
                    </div>

                    <!-- AI Modal (ORTADA, üstten inme yok) -->
                    <div class="modalOverlay" x-show="ai.open" x-cloak @click.self="ai.open=false">
                        <div class="card modalCard">
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
                                <div style="font-weight:900;">AI</div>
                                <button class="btn" type="button" @click="ai.open=false">Kapat</button>
                            </div>

                            <div class="toolbar" style="flex-wrap:wrap; margin-top:12px;">
                                <label style="flex:1; min-width:220px;">
                                    <div class="label">Kullanıcı adı</div>
                                    <input class="input" x-model="ai.username" placeholder="Örn: Salih">
                                </label>
                                <label style="display:flex; gap:10px; align-items:center; margin-top:22px;">
                                    <input type="checkbox" x-model="ai.scanAll">
                                    <span>Tüm sohbeti tara</span>
                                </label>
                            </div>

                            <div style="margin-top:12px;">
                                <div class="toolbar" style="justify-content:space-between;">
                                    <div style="font-weight:900;">Aksiyonlar</div>
                                    <button class="btn" type="button" @click="ai.actionsOpen = !ai.actionsOpen">Seç</button>
                                </div>

                                <div class="dropdown" x-show="ai.actionsOpen" x-cloak>
                                    <label class="checkRow"><input type="checkbox" value="last_message_to_sale" x-model="ai.selected"> <span>Son Mesajı Satışa Bağla</span></label>
                                    <label class="checkRow"><input type="checkbox" value="objection_handle" x-model="ai.selected"> <span>İtiraz Kırma</span></label>
                                    <label class="checkRow"><input type="checkbox" value="offer_generate" x-model="ai.selected"> <span>Teklif Üret</span></label>
                                    <label class="checkRow"><input type="checkbox" value="continue_chat" x-model="ai.selected"> <span>Sohbet Devam</span></label>
                                    <label class="checkRow"><input type="checkbox" value="warm_sales" x-model="ai.selected"> <span>Samimi Satış</span></label>
                                    <label class="checkRow"><input type="checkbox" value="professional_sales" x-model="ai.selected"> <span>Profesyonel Satış</span></label>
                                </div>

                                <div class="filtersActions" style="margin-top:10px;">
                                    <button class="btn btnPrimary" type="button" @click="aiRunSelected({{ (int)$selected->id }})" :disabled="ai.loading || ai.selected.length===0">Seçilenleri Üret</button>
                                    <button class="btn" type="button" @click="ai.selected=[]; ai.outputs=[]; ai.output=''">Temizle</button>
                                </div>
                            </div>

                            <div style="margin-top:12px;">
                                <div class="muted" x-show="ai.loading" x-cloak>Üretiliyor...</div>
                                <template x-for="o in ai.outputs" :key="o.key">
                                    <div class="card" style="margin-top:10px; box-shadow:none; background:#f8fafc;">
                                        <div class="cardTitle" x-text="o.title"></div>
                                        <pre style="white-space:pre-wrap; margin:0;" x-text="o.text"></pre>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function chatUI() {
            return {
                sending: false,
                message: '',
                ai: { open:false, username:'', scanAll:false, loading:false, actionsOpen:false, selected:[], outputs:[] },
                voice: { state:'idle', blob:null, url:null, recorder:null, chunks:[], startedAt:0, stream:null },
                init() {
                    // On page load (and after reload), keep chat at the bottom (newest messages visible)
                    const go = () => {
                        const sc = document.getElementById('chatScroll');
                        if (!sc) return;
                        sc.scrollTop = sc.scrollHeight;
                    };
                    this.$nextTick(() => {
                        go();
                        // second tick for images/audio layout shifts
                        setTimeout(go, 50);
                        setTimeout(go, 250);
                    });
                },

                openAi() {
                    this.ai.open = true;
                    this.ai.outputs = [];
                    this.ai.actionsOpen = false;
                },

                async aiRun(templateKey, threadId) {
                    this.ai.loading = true;
                    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const r = await fetch(`/chats/${threadId}/ai/suggest`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({
                            template_key: templateKey,
                            username: this.ai.username || null,
                            scan_all: !!this.ai.scanAll
                        })
                    });
                    const j = await r.json().catch(() => ({}));
                    this.ai.loading = false;
                    if (!r.ok || !j.ok) {
                        throw new Error((j && (j.error || JSON.stringify(j))) || 'AI hata verdi.');
                    }
                    return j.output_text || '';
                },

                async aiRunSelected(threadId) {
                    this.ai.loading = true;
                    this.ai.outputs = [];
                    const titles = {
                        last_message_to_sale: 'Son Mesajı Satışa Bağla',
                        objection_handle: 'İtiraz Kırma',
                        offer_generate: 'Teklif Üret',
                        continue_chat: 'Sohbet Devam',
                        warm_sales: 'Samimi Satış',
                        professional_sales: 'Profesyonel Satış',
                    };
                    try {
                        for (const key of this.ai.selected) {
                            const text = await this.aiRun(key, threadId);
                            this.ai.outputs.push({ key, title: titles[key] || key, text });
                        }
                    } catch (e) {
                        this.ai.outputs.push({ key: 'error', title: 'Hata', text: String(e && e.message ? e.message : e) });
                    } finally {
                        this.ai.loading = false;
                    }
                },

                voiceCancel() {
                    if (this.voice.url) { try { URL.revokeObjectURL(this.voice.url); } catch(e){} }
                    if (this.voice.stream) { try { this.voice.stream.getTracks().forEach(t => t.stop()); } catch(e){} }
                    this.voice = { state:'idle', blob:null, url:null, recorder:null, chunks:[], startedAt:0, stream:null };
                },

                async voiceToggle() {
                    if (this.voice.state === 'recording') {
                        if (this.voice.recorder) this.voice.recorder.stop();
                        return;
                    }
                    // idle/ready -> start new recording
                    this.voiceCancel();
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    this.voice.stream = stream;
                    this.voice.chunks = [];
                    this.voice.startedAt = Date.now();
                    const rec = new MediaRecorder(stream, { mimeType: 'audio/webm' });
                    this.voice.recorder = rec;
                    rec.ondataavailable = (e) => { if (e.data && e.data.size) this.voice.chunks.push(e.data); };
                    rec.onstop = () => {
                        const blob = new Blob(this.voice.chunks, { type: 'audio/webm' });
                        this.voice.blob = blob;
                        this.voice.url = URL.createObjectURL(blob);
                        this.voice.state = 'ready';
                        try { stream.getTracks().forEach(t => t.stop()); } catch(e) {}
                        this.voice.stream = null;
                    };
                    rec.start();
                    this.voice.state = 'recording';
                },

                filePick() {
                    this.$refs.fileInput.click();
                },

                async fileSelected(threadId, ev) {
                    const file = ev.target.files && ev.target.files[0];
                    ev.target.value = '';
                    if (!file) return;
                    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const fd = new FormData();
                    fd.append('file', file);
                    this.sending = true;
                    const r = await fetch(`/chats/${threadId}/messages/file`, { method:'POST', headers:{ 'X-CSRF-TOKEN': csrf }, body: fd });
                    this.sending = false;
                    if (!r.ok) { alert('Dosya gönderilemedi.'); return; }
                    window.location.reload();
                },

                async send(threadId) {
                    if (this.sending) return;
                    if (this.voice.state === 'ready' && this.voice.blob) {
                        await this.voiceSend(threadId);
                        return;
                    }
                    const text = (this.message || '').trim();
                    if (!text) return;
                    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    this.sending = true;
                    const r = await fetch(`/chats/${threadId}/messages/text`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ text })
                    });
                    this.sending = false;
                    if (!r.ok) { alert('Mesaj gönderilemedi.'); return; }
                    this.message = '';
                    window.location.reload();
                },

                async voiceSend(threadId) {
                    if (!this.voice.blob || this.voice.state !== 'ready') return;
                    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    const fd = new FormData();
                    fd.append('voice', this.voice.blob, 'voice.webm');
                    fd.append('duration_ms', String(Math.max(0, Date.now() - this.voice.startedAt)));

                    this.sending = true;
                    const r = await fetch(`/chats/${threadId}/messages/voice`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf },
                        body: fd
                    });
                    this.sending = false;
                    if (!r.ok) {
                        alert('Ses gönderilemedi.');
                        return;
                    }
                    this.voiceCancel();
                    window.location.reload();
                }
            }
        }
    </script>
@endsection

