@extends('layouts.app')

@section('title', __('ui.nav_mail') . ' - Mark-A CRM')
@section('page_title', __('ui.nav_mail'))

@section('content')
    <div class="card" style="padding:0; overflow:hidden;">
        <div class="chatGrid" style="grid-template-columns: 380px 1fr;">
            <div style="border-right:1px solid var(--line); padding:14px;">
                <div style="display:flex; gap:10px; align-items:center; justify-content:space-between; margin-bottom:10px;">
                    <div style="font-weight:900">Mail</div>
                    <div style="display:flex; gap:8px;">
                        <a class="btn {{ ($tab ?? 'inbox')==='inbox' ? 'btnPrimary' : '' }}" href="/mail?tab=inbox">Inbox</a>
                        <a class="btn {{ ($tab ?? 'inbox')==='outbox' ? 'btnPrimary' : '' }}" href="/mail?tab=outbox">Giden</a>
                    </div>
                </div>

                <form method="GET" class="filterPanel" style="margin-top:12px;">
                    <input type="hidden" name="tab" value="{{ $tab ?? 'inbox' }}">
                    <div class="filterRow r3actions">
                        <div class="filterField">
                            <div class="label">Arama</div>
                            <input class="input" name="q" value="{{ request('q') }}" placeholder="Konu / içerik">
                        </div>
                        <div class="filterField">
                            <div class="label">Durum</div>
                            <select class="input" name="status" @disabled(($tab ?? 'inbox')!=='outbox')>
                                <option value="">Tümü</option>
                                <option value="sent" @selected(request('status')==='sent')>sent</option>
                                <option value="failed" @selected(request('status')==='failed')>failed</option>
                                <option value="queued" @selected(request('status')==='queued')>queued</option>
                                <option value="received" @selected(request('status')==='received')>received</option>
                            </select>
                        </div>
                        <div class="filterActions">
                            <button class="btn btnPrimary" type="submit">Filtrele</button>
                            <a class="btn" href="/mail?tab={{ $tab ?? 'inbox' }}">Sıfırla</a>
                        </div>
                    </div>
                    @if(($tab ?? 'inbox')==='inbox')
                        <div class="filterRow">
                            <div class="filterActions" style="justify-content:flex-start;">
                                <a class="btn" href="/mail?tab=inbox&sync=1">Yenile (IMAP)</a>
                                @if(!($imapStatus['supported'] ?? true))
                                    <span class="muted">IMAP eklentisi yok (php imap). Inbox çekilemez.</span>
                                @elseif(!($imapStatus['configured'] ?? false))
                                    <span class="muted">IMAP ayarı yok. Settings > Mail ayarlarından gir.</span>
                                @elseif(($imapStatus['error'] ?? null))
                                    <span class="muted">Hata: {{ $imapStatus['error'] }}</span>
                                @elseif(($imapStatus['synced'] ?? false))
                                    <span class="muted">Senkronlandı.</span>
                                @endif
                            </div>
                        </div>
                    @endif
                </form>

                <div style="display:flex; flex-direction:column; gap:10px; margin-top:12px;">
                    @foreach($mails as $m)
                        @php($active = (string)request('msg') === (string)$m->id)
                        @php($meta = $m->meta_json ? (json_decode((string)$m->meta_json, true) ?: []) : [])
                        <a href="/mail?{{ http_build_query(array_merge(request()->query(), ['msg' => $m->id])) }}"
                           class="card"
                           style="padding:12px; box-shadow:none; border-color: {{ $active ? '#111827' : 'var(--line)' }};">
                            <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start;">
                                <div style="min-width:0;">
                                    <div style="font-weight:1000; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        {{ $m->subject ?: '(konu yok)' }}
                                    </div>
                                    <div class="muted" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        @if(($tab ?? 'inbox')==='inbox')
                                            {{ $meta['from'] ?? '—' }}
                                        @else
                                            {{ $meta['to'] ?? '—' }}
                                        @endif
                                    </div>
                                </div>
                                <span class="badge {{ (string)$m->status==='sent' ? 'badgeSuccess' : (((string)$m->status==='failed') ? 'badgeDanger' : 'badgeNeutral') }}">
                                    {{ $m->status }}
                                </span>
                            </div>
                            <div class="muted" style="margin-top:6px;">
                                {{ \Illuminate\Support\Carbon::parse($m->created_at)->format('d.m.Y H:i') }}
                            </div>
                        </a>
                    @endforeach
                </div>

                <div style="margin-top:12px;">
                    {{ $mails->links() }}
                </div>
            </div>

            <div style="padding:14px; display:flex; flex-direction:column; min-width:0;">
                <div class="card" style="box-shadow:none;">
                    <div class="cardTitle">Mail Gönder</div>
                    <form method="POST" action="/mail" class="filterPanel" style="margin-top:12px;">
                        @csrf
                        <div class="filterRow r3wide">
                            <div class="filterField">
                                <div class="label">Alıcı</div>
                                <input class="input" name="to" placeholder="alici@mail.com" required>
                            </div>
                            <div class="filterField">
                                <div class="label">Gönderen</div>
                                <input class="input" value="{{ $smtpCfg['from_email'] ?? '' }}" disabled>
                            </div>
                            <div class="filterActions">
                                <button class="btn btnPrimary" type="submit">Gönder</button>
                            </div>
                        </div>
                        <div class="filterRow">
                            <div class="filterField" style="grid-column: 1 / -1;">
                                <div class="label">Konu</div>
                                <input class="input" name="subject" required>
                            </div>
                            <div class="filterField" style="grid-column: 1 / -1;">
                                <div class="label">İçerik</div>
                                <textarea class="input" name="body" rows="8" required></textarea>
                            </div>
                        </div>
                        <div class="muted" style="margin-top:8px;">
                            SMTP ayarı: {{ ($smtpCfg['host'] ?? '') !== '' ? 'hazır' : 'yok (Settings > Mail)' }}
                        </div>
                    </form>
                </div>

                <div class="card" style="box-shadow:none; margin-top:14px; flex:1; min-height:0;">
                    <div class="cardTitle">İçerik</div>
                    @if(!$selected)
                        <div class="muted">Soldan bir mail seç.</div>
                    @else
                        @php($meta = $selected->meta_json ? (json_decode((string)$selected->meta_json, true) ?: []) : [])
                        <div class="muted" style="margin-bottom:10px;">
                            <div><b>Konu:</b> {{ $selected->subject ?: '(konu yok)' }}</div>
                            <div><b>Tarih:</b> {{ \Illuminate\Support\Carbon::parse($selected->created_at)->format('d.m.Y H:i') }}</div>
                            @if(($tab ?? 'inbox')==='inbox')
                                <div><b>From:</b> {{ $meta['from'] ?? '—' }}</div>
                            @else
                                <div><b>To:</b> {{ $meta['to'] ?? '—' }}</div>
                                @if(!empty($meta['error']))
                                    <div><b>Hata:</b> {{ $meta['error'] }}</div>
                                @endif
                            @endif
                        </div>
                        <div class="card" style="box-shadow:none; background:#f8fafc; white-space:pre-wrap;">{{ $selected->body }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

