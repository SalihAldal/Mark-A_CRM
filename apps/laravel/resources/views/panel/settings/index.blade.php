@extends('layouts.app')

@section('title', __('ui.nav_settings') . ' - Mark-A CRM')
@section('page_title', __('ui.nav_settings'))

@section('content')
    <div class="settingsLayout">
        <div>
    <div class="card" id="settings-ai">
        <div class="toolbar" style="justify-content:space-between; margin-bottom:8px;">
            <div>
                <div class="pageTitle">Settings</div>
            </div>
        </div>

        <form method="POST" action="/settings/ai-rules" class="filterPanel" style="margin-top:12px;">
            @csrf
            <div class="filterRow r3">
                <div class="filterField">
                    <div class="label">Firma sektörü</div>
                    <input class="input" name="sector" placeholder="örn: mermer, inşaat, yazılım" required>
                </div>
                <div class="filterField">
                    <div class="label">Ton</div>
                    <input class="input" name="tone" placeholder="resmi / samimi / satış odaklı" required>
                </div>
                <div class="filterField">
                    <div class="label">Dil</div>
                    <select class="input" name="language" required>
                        <option value="tr">Türkçe</option>
                        <option value="en">English</option>
                    </select>
                </div>
            </div>
            <div class="filterRow">
                <div class="filterField" style="grid-column: 1 / -1;">
                    <div class="label">Yasaklar</div>
                    <textarea class="input" name="forbidden_phrases" rows="3" placeholder="örn: Rakiplerle kıyaslama: YAPMA"></textarea>
                </div>
                <div class="filterField" style="grid-column: 1 / -1; display:flex; gap:10px; align-items:center;">
                    <input type="hidden" name="sales_focus" value="0">
                    <input type="checkbox" name="sales_focus" value="1" checked>
                    <div style="font-weight:900;">Sales focus</div>
                </div>
                <div class="filterActions">
                    <button class="btn btnPrimary" type="submit">Kaydet</button>
                </div>
            </div>
        </form>
    </div>

    <div class="card" style="margin-top:14px;" id="settings-mail">
        <div class="toolbar" style="justify-content:space-between; margin-bottom:8px;">
            <div>
                <div class="pageTitle">SMTP</div>
            </div>
        </div>

        <form method="POST" action="/settings/mail" class="filterPanel" style="margin-top:12px;">
            @csrf
            <div class="filterRow r3wide">
                <div class="filterField">
                    <div class="label">SMTP Host</div>
                    <input class="input" name="smtp_host" value="{{ $mail['smtp_host'] ?? '' }}" placeholder="smtp.gmail.com" required>
                </div>
                <div class="filterField">
                    <div class="label">SMTP Mail (Username)</div>
                    <input class="input" name="smtp_username" value="{{ $mail['smtp_username'] ?? '' }}" placeholder="mail@domain.com" required>
                </div>
                <div class="filterField">
                    <div class="label">SMTP Password</div>
                    <input class="input" name="smtp_password" type="password" placeholder="•••••• (değiştirmek için yaz)">
                </div>
                <div class="filterActions">
                    <button class="btn btnPrimary" type="submit">Kaydet</button>
                </div>
            </div>
        </form>
    </div>

    <div class="card" style="margin-top:14px;" id="settings-staff">
        <div class="toolbar" style="justify-content:space-between; margin-bottom:8px;">
            <div>
                <div class="pageTitle">Çalışanlar</div>
            </div>
        </div>

        <form method="POST" action="/settings/staff" class="filterPanel" style="margin-top:12px;">
            @csrf
            <div class="filterRow r3wide">
                <div class="filterField">
                    <div class="label">Ad Soyad</div>
                    <input class="input" name="name" placeholder="Örn: Çalışan 2" required>
                </div>
                <div class="filterField">
                    <div class="label">E-posta</div>
                    <input class="input" name="email" placeholder="staff2@domain.com" required>
                </div>
                <div class="filterField">
                    <div class="label">Şifre</div>
                    <input class="input" name="password" type="password" placeholder="min 6 karakter" required>
                </div>
            </div>
            <div class="filterRow">
                <div class="filterActions">
                    <button class="btn btnPrimary" type="submit">Çalışan Ekle</button>
                </div>
            </div>
        </form>
    </div>

    <div class="card" style="margin-top:14px; padding:0;">
        <div class="tableWrap">
            <table class="table">
                <thead>
                <tr>
                    <th style="padding-left:16px;">Çalışan</th>
                    <th>Rol</th>
                    <th>Durum</th>
                    <th style="padding-right:16px; text-align:right;">Tarih</th>
                </tr>
                </thead>
                <tbody>
                @forelse(($staff ?? collect()) as $u)
                    <tr>
                        <td style="padding-left:16px;">
                            <div style="font-weight:1000">{{ $u->name }}</div>
                            <div class="muted">{{ $u->email }}</div>
                        </td>
                        <td><span class="badge badgeNeutral">{{ $u->role_name_tr ?? $u->role_key ?? '-' }}</span></td>
                        <td>
                            @if((string)($u->status ?? '') === 'active')
                                <span class="badge badgeSuccess">active</span>
                            @else
                                <span class="badge badgeNeutral">{{ $u->status ?? '-' }}</span>
                            @endif
                        </td>
                        <td style="padding-right:16px; text-align:right;" class="muted">{{ $u->created_at ? \Illuminate\Support\Carbon::parse($u->created_at)->format('d.m.Y H:i') : '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted" style="padding:16px;">Kayıt yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:14px;" id="settings-stages">
        <div class="toolbar" style="justify-content:space-between; margin-bottom:8px;">
            <div>
                <div class="pageTitle">Lead Stage Yönetimi</div>
            </div>
        </div>

        <form method="POST" action="/settings/stages" class="filterPanel" style="margin-top:12px;">
            @csrf
            <div class="filterRow r3wide">
                <div class="filterField">
                    <div class="label">Stage adı</div>
                    <input class="input" name="name" placeholder="örn: Yeni, İletişimde, Teklif, Kazanıldı" required>
                </div>
                <div class="filterField">
                    <div class="label">Renk</div>
                    <input class="input" type="color" name="color" value="#111827">
                </div>
                <div class="filterField">
                    <div class="label">Sıra</div>
                    <input class="input" type="number" name="sort_order" value="0" min="0" step="1">
                </div>
            </div>

            <div class="filterRow r3actions">
                <div class="filterField" style="display:flex; gap:18px; align-items:center;">
                    <label style="display:flex; gap:10px; align-items:center; font-weight:900;">
                        <input type="hidden" name="is_won" value="0">
                        <input type="checkbox" name="is_won" value="1">
                        WON
                    </label>
                    <label style="display:flex; gap:10px; align-items:center; font-weight:900;">
                        <input type="hidden" name="is_lost" value="0">
                        <input type="checkbox" name="is_lost" value="1">
                        LOST
                    </label>
                </div>
                <div></div>
                <div class="filterActions">
                    <button class="btn btnPrimary" type="submit">Stage Ekle</button>
                </div>
            </div>
        </form>
    </div>

    <div class="card" style="margin-top:14px; padding:0;">
        <div class="tableWrap">
            <table class="table">
                <thead>
                <tr>
                    <th style="padding-left:16px;">Stage</th>
                    <th>Renk</th>
                    <th>Sıra</th>
                    <th>WON</th>
                    <th>LOST</th>
                    <th style="padding-right:16px; text-align:right;">İşlem</th>
                </tr>
                </thead>
                <tbody>
                @forelse(($stages ?? collect()) as $s)
                    @php($fid = 'stage_' . $s->id)
                    <tr>
                        <td style="padding-left:16px;">
                            <input class="input" name="name" value="{{ $s->name }}" style="max-width:320px;" form="{{ $fid }}">
                        </td>
                        <td>
                            <input class="input" type="color" name="color" value="{{ $s->color ? $s->color : '#111827' }}" style="max-width:90px;" form="{{ $fid }}">
                        </td>
                        <td>
                            <input class="input" type="number" name="sort_order" value="{{ (int)$s->sort_order }}" min="0" step="1" style="max-width:110px;" form="{{ $fid }}">
                        </td>
                        <td>
                            <input type="hidden" name="is_won" value="0" form="{{ $fid }}">
                            <input type="checkbox" name="is_won" value="1" @checked((bool)$s->is_won) form="{{ $fid }}">
                        </td>
                        <td>
                            <input type="hidden" name="is_lost" value="0" form="{{ $fid }}">
                            <input type="checkbox" name="is_lost" value="1" @checked((bool)$s->is_lost) form="{{ $fid }}">
                        </td>
                        <td style="padding-right:16px; text-align:right;">
                            <form id="{{ $fid }}" method="POST" action="/settings/stages/{{ $s->id }}" style="display:inline;">
                                @csrf
                                <button class="btn btnPrimary" type="submit">Kaydet</button>
                            </form>
                            <form method="POST" action="/settings/stages/{{ $s->id }}/delete" style="display:inline;">
                                @csrf
                                <button class="btn" type="submit" onclick="return confirm('Bu stage silinsin mi?')">Sil</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted" style="padding:16px;">Stage yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:14px;" id="settings-integrations">
        <div class="toolbar" style="justify-content:space-between; margin-bottom:8px;">
            <div>
                <div class="pageTitle">Entegrasyonlar (Instagram / WhatsApp / Telegram)</div>
            </div>
            <form method="POST" action="/settings/integrations/demo">
                @csrf
                <button class="btn" type="submit" onclick="return confirm('Demo entegrasyon + demo chat oluşturulsun mu? (disabled)')">Demo Oluştur</button>
            </form>
        </div>

        @php($byProvider = ($integrationsByProvider ?? collect()))
        @php($waAcc = $byProvider['whatsapp'] ?? $byProvider['wp'] ?? null)
        @php($igAcc = $byProvider['instagram'] ?? null)
        @php($tgAcc = $byProvider['telegram'] ?? null)
        @php($waCfg = $waAcc ? (is_array($waAcc->config_json) ? $waAcc->config_json : (json_decode((string)($waAcc->config_json ?? ''), true) ?: [])) : [])
        @php($igCfg = $igAcc ? (is_array($igAcc->config_json) ? $igAcc->config_json : (json_decode((string)($igAcc->config_json ?? ''), true) ?: [])) : [])
        @php($tgCfg = $tgAcc ? (is_array($tgAcc->config_json) ? $tgAcc->config_json : (json_decode((string)($tgAcc->config_json ?? ''), true) ?: [])) : [])
        @php($igTokenOk = !empty($igCfg['page_access_token']))
        @php($waTokenOk = !empty($waCfg['access_token']))
        @php($tgTokenOk = !empty($tgCfg['bot_token']))

        <div class="grid2" style="margin-top:12px;">
            <div class="card" style="box-shadow:none; background:#f8fafc;">
                <div class="cardTitle">WhatsApp Cloud API</div>
                <form method="POST" action="/settings/integrations" class="filterPanel" style="box-shadow:none;">
                    @csrf
                    <input type="hidden" name="provider" value="whatsapp">
                    <div class="filterRow r3wide">
                        <div class="filterField">
                            <div class="label">Ad</div>
                            <input class="input" name="name" value="{{ $waAcc ? $waAcc->name : 'WhatsApp' }}" required>
                        </div>
                        <div class="filterField">
                            <div class="label">Durum</div>
                            <select class="input" name="status">
                                <option value="active" @selected(($waAcc?->status ?? 'disabled') === 'active')>active</option>
                                <option value="disabled" @selected(($waAcc?->status ?? 'disabled') !== 'active')>disabled</option>
                            </select>
                        </div>
                        <div class="filterActions">
                            <button class="btn btnPrimary" type="submit">Kaydet</button>
                        </div>
                    </div>
                    <div class="filterRow r3">
                        <div class="filterField">
                            <div class="label">Phone Number ID</div>
                            <input class="input" name="config[phone_number_id]" value="{{ $waCfg['phone_number_id'] ?? '' }}" placeholder="Meta phone_number_id">
                        </div>
                        <div class="filterField">
                            <div class="label">Access Token</div>
                            <input class="input" type="password" name="config[access_token]" placeholder="•••••• (değiştirmek için yaz)">
                            <div class="muted" style="font-size:12px; margin-top:6px;">
                                {{ $waTokenOk ? 'Token: kayıtlı' : 'Token: eksik' }}
                            </div>
                        </div>
                        <div class="filterField">
                            <div class="label">Verify Token (Webhook)</div>
                            <div class="muted" style="font-size:12px; padding-top:10px;">Tek kaynak: <code>.env</code> → <code>META_VERIFY_TOKEN</code></div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card" style="box-shadow:none; background:#f8fafc;">
                <div class="cardTitle">Instagram Messaging API</div>
                <form method="POST" action="/settings/integrations" class="filterPanel" style="box-shadow:none;">
                    @csrf
                    <input type="hidden" name="provider" value="instagram">
                    <div class="filterRow r3wide">
                        <div class="filterField">
                            <div class="label">Ad</div>
                            <input class="input" name="name" value="{{ $igAcc ? $igAcc->name : 'Instagram' }}" required>
                        </div>
                        <div class="filterField">
                            <div class="label">Durum</div>
                            <select class="input" name="status">
                                <option value="active" @selected(($igAcc?->status ?? 'disabled') === 'active')>active</option>
                                <option value="disabled" @selected(($igAcc?->status ?? 'disabled') !== 'active')>disabled</option>
                            </select>
                        </div>
                        <div class="filterActions">
                            <button class="btn btnPrimary" type="submit">Kaydet</button>
                        </div>
                    </div>
                    <div class="filterRow r3">
                        <div class="filterField">
                            <div class="label">Facebook Page ID</div>
                            <input class="input" name="config[page_id]" value="{{ (string)($igCfg['page_id'] ?? '') }}" placeholder="örn: 908670365670378">
                        </div>
                        <div class="filterField">
                            <div class="label">IG Business Account ID (webhook entry.id)</div>
                            @php($igBiz = (string)($igCfg['ig_business_id'] ?? ''))
                            @php($legacyMaybeIg = (string)($igCfg['page_id'] ?? ''))
                            <input class="input" name="config[ig_business_id]" value="{{ $igBiz !== '' ? $igBiz : (str_starts_with($legacyMaybeIg,'1784') ? $legacyMaybeIg : '') }}" placeholder="örn: 17841478140523860">
                        </div>
                        <div class="filterField">
                            <div class="label">Page Access Token</div>
                            <input class="input" type="password" name="config[page_access_token]" placeholder="•••••• (değiştirmek için yaz)">
                            <div class="muted" style="font-size:12px; margin-top:6px;">
                                {{ $igTokenOk ? 'Token: kayıtlı' : 'Token: eksik (cevap atamazsın)' }}
                            </div>
                        </div>
                    </div>
                    <div class="filterRow">
                        <div class="muted" style="font-size:12px;">
                            Verify Token (Webhook) tek kaynak: <code>.env</code> → <code>META_VERIFY_TOKEN</code>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card" style="box-shadow:none; background:#f8fafc;">
                <div class="cardTitle">Telegram Bot</div>
                <form method="POST" action="/settings/integrations" class="filterPanel" style="box-shadow:none;">
                    @csrf
                    <input type="hidden" name="provider" value="telegram">
                    <div class="filterRow r3wide">
                        <div class="filterField">
                            <div class="label">Ad</div>
                            <input class="input" name="name" value="{{ $tgAcc ? $tgAcc->name : 'Telegram' }}" required>
                        </div>
                        <div class="filterField">
                            <div class="label">Durum</div>
                            <select class="input" name="status">
                                <option value="active" @selected(($tgAcc?->status ?? 'disabled') === 'active')>active</option>
                                <option value="disabled" @selected(($tgAcc?->status ?? 'disabled') !== 'active')>disabled</option>
                            </select>
                        </div>
                        <div class="filterActions">
                            <button class="btn btnPrimary" type="submit">Kaydet</button>
                        </div>
                    </div>
                    <div class="filterRow r3">
                        <div class="filterField" style="grid-column: 1 / -1;">
                            <div class="label">Bot Token</div>
                            <input class="input" name="config[bot_token]" placeholder="123456:ABC-DEF...">
                            <div class="muted" style="font-size:12px; margin-top:6px;">
                                {{ $tgTokenOk ? 'Token: kayıtlı' : 'Token: eksik' }}
                            </div>
                        </div>
                        <div class="filterField" style="grid-column: 1 / -1;">
                            <div class="label">Webhook Secret Token (opsiyonel)</div>
                            <input class="input" name="webhook_secret" value="{{ $tgAcc ? ($tgAcc->webhook_secret ?? '') : '' }}" placeholder="Telegram webhook secret header">
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:14px; padding:0;">
        <div class="tableWrap">
            <table class="table">
                <thead>
                <tr>
                    <th style="padding-left:16px;">Provider</th>
                    <th>Ad</th>
                    <th>Durum</th>
                    <th>Özet</th>
                    <th style="padding-right:16px; text-align:right;">İşlem</th>
                </tr>
                </thead>
                <tbody>
                @forelse(($integrations ?? collect()) as $i)
                    @php($fid = 'int_' . $i->id)
                    @php($cfg = is_array($i->config_json) ? $i->config_json : (json_decode((string)$i->config_json, true) ?: []))
                    <tr>
                        <td style="padding-left:16px;"><span class="badge badgeNeutral">{{ $i->provider }}</span></td>
                        <td><input class="input" name="name" value="{{ $i->name }}" form="{{ $fid }}" style="max-width:220px;"></td>
                        <td>
                            <select class="input" name="status" form="{{ $fid }}" style="max-width:140px;">
                                <option value="active" @selected($i->status==='active')>active</option>
                                <option value="disabled" @selected($i->status!=='active')>disabled</option>
                            </select>
                        </td>
                        <td>
                            @if($i->provider === 'whatsapp')
                                <div class="filters2" style="grid-template-columns: 1fr 1fr 1fr; gap:10px; margin:0;">
                                    <input class="input" name="config[phone_number_id]" value="{{ $cfg['phone_number_id'] ?? '' }}" placeholder="phone_number_id" form="{{ $fid }}">
                                    <input class="input" type="password" name="config[access_token]" value="" placeholder="•••••• (kayıtlı, değiştirmek için yaz)" form="{{ $fid }}">
                                    <input class="input" value="META_VERIFY_TOKEN (.env)" placeholder="verify_token" disabled>
                                </div>
                            @elseif($i->provider === 'instagram')
                                <div class="filters2" style="grid-template-columns: 1fr 1fr 1fr; gap:10px; margin:0;">
                                    <input class="input" name="config[page_id]" value="{{ $cfg['page_id'] ?? '' }}" placeholder="page_id" form="{{ $fid }}">
                                    <input class="input" type="password" name="config[page_access_token]" value="" placeholder="•••••• (kayıtlı, değiştirmek için yaz)" form="{{ $fid }}">
                                    <input class="input" value="META_VERIFY_TOKEN (.env)" placeholder="verify_token" disabled>
                                </div>
                            @elseif($i->provider === 'telegram')
                                <div class="filters2" style="grid-template-columns: 1fr 1fr; gap:10px; margin:0;">
                                    <input class="input" type="password" name="config[bot_token]" value="" placeholder="•••••• (kayıtlı, değiştirmek için yaz)" form="{{ $fid }}">
                                    <input class="input" name="webhook_secret" value="{{ $i->webhook_secret ?? '' }}" placeholder="secret token (ops.)" form="{{ $fid }}">
                                </div>
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>
                        <td style="padding-right:16px; text-align:right;">
                            <form id="{{ $fid }}" method="POST" action="/settings/integrations/{{ $i->id }}" style="display:inline;">
                                @csrf
                                <button class="btn btnPrimary" type="submit">Kaydet</button>
                            </form>
                            <form method="POST" action="/settings/integrations/{{ $i->id }}/delete" style="display:inline;">
                                @csrf
                                <button class="btn" type="submit" onclick="return confirm('Entegrasyon silinsin mi?')">Sil</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted" style="padding:16px;">Entegrasyon yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:14px; padding:0;">
        <div class="tableWrap">
            <table class="table">
                <thead>
                <tr>
                    <th style="padding-left:16px;">Sektör</th>
                    <th>Ton</th>
                    <th>Dil</th>
                    <th>Sales</th>
                    <th>Yasaklar</th>
                    <th style="padding-right:16px; text-align:right;">İşlem</th>
                </tr>
                </thead>
                <tbody>
                @forelse($rules as $r)
                    @php($fid = 'rule_' . $r->id)
                    <tr>
                        <td style="padding-left:16px;">
                            <input class="input" name="sector" value="{{ $r->sector }}" form="{{ $fid }}" style="max-width:220px;">
                        </td>
                        <td>
                            <input class="input" name="tone" value="{{ $r->tone }}" form="{{ $fid }}" style="max-width:180px;">
                        </td>
                        <td>
                            <select class="input" name="language" form="{{ $fid }}" style="max-width:120px;">
                                <option value="tr" @selected($r->language==='tr')>tr</option>
                                <option value="en" @selected($r->language==='en')>en</option>
                            </select>
                        </td>
                        <td>
                            <input type="hidden" name="sales_focus" value="0" form="{{ $fid }}">
                            <input type="checkbox" name="sales_focus" value="1" @checked((bool)$r->sales_focus) form="{{ $fid }}">
                        </td>
                        <td>
                            <textarea class="input" name="forbidden_phrases" rows="2" form="{{ $fid }}" style="min-width:260px;">{{ (string)($r->forbidden_phrases ?? '') }}</textarea>
                        </td>
                        <td style="padding-right:16px; text-align:right;">
                            <form id="{{ $fid }}" method="POST" action="/settings/ai-rules/{{ $r->id }}" style="display:inline;">
                                @csrf
                                <button class="btn btnPrimary" type="submit">Kaydet</button>
                            </form>
                            <form method="POST" action="/settings/ai-rules/{{ $r->id }}/delete" style="display:inline;">
                                @csrf
                                <button class="btn" type="submit" onclick="return confirm('Bu AI rule silinsin mi?')">Sil</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted" style="padding:16px;">Kayıt yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:12px 16px;">
            {{ $rules->links() }}
        </div>
    </div>
        </div>

        <aside class="settingsNav">
            <div class="card" style="box-shadow:none;">
                <div class="cardTitle">Ayarlar</div>
                <a href="#settings-ai">AI Kuralları <small>→</small></a>
                <a href="#settings-mail">Mail (SMTP/IMAP) <small>→</small></a>
                <a href="#settings-staff">Çalışanlar <small>→</small></a>
                <a href="#settings-stages">Lead Stage <small>→</small></a>
                <a href="#settings-integrations">Entegrasyonlar <small>→</small></a>
            </div>
        </aside>
    </div>
@endsection

