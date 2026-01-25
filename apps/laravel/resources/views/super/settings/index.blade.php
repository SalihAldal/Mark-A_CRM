@extends('layouts.app')

@section('title', 'Sistem Ayarları - Mark-A CRM')
@section('page_title', 'Sistem Ayarları')

@section('content')
    <div class="card" style="padding:0;">
        <div style="padding:14px 16px;">
            <div style="font-weight:1000;">Sistem Ayarları</div>
            <div class="muted">Global şablonlar, super domain, audit log, health.</div>
        </div>
    </div>

    <div class="grid2" style="margin-top:14px;">
        <div class="card" style="padding:0;">
            <div style="padding:12px 16px; font-weight:1000;">Super Domainler</div>
            <div class="tableWrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th style="padding-left:16px;">Host</th>
                        <th>Primary</th>
                        <th>Status</th>
                        <th style="padding-right:16px; text-align:right;">İşlem</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($superDomains ?? [] as $d)
                        <tr>
                            <td style="padding-left:16px; font-weight:1000;">{{ data_get($d,'host','—') }}</td>
                            <td class="muted">{{ (int) data_get($d,'is_primary',0) ? 'yes' : 'no' }}</td>
                            <td><span class="badge {{ data_get($d,'status')==='active' ? 'badgeSuccess' : 'badgeDanger' }}">{{ data_get($d,'status','—') }}</span></td>
                            <td style="padding-right:16px; text-align:right;">
                                <form method="POST" action="/super/settings/super-domains/{{ (int) data_get($d,'id',0) }}/toggle" style="display:inline;">
                                    @csrf
                                    <button class="btn" type="submit">{{ data_get($d,'status')==='active' ? 'Pasif' : 'Aktif' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted" style="padding:16px;">Kayıt yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div style="padding:12px 16px; border-top:1px solid var(--line);">
                <form method="POST" action="/super/settings/super-domains">
                    @csrf
                    <div class="filterRow r3wide">
                        <div style="grid-column:1 / span 2;">
                            <div class="label">Yeni Super Domain</div>
                            <input class="input" name="host" placeholder="örn: superadmin.localhost" required>
                        </div>
                        <div style="display:flex; align-items:flex-end;">
                            <label style="display:flex; gap:8px; align-items:center; font-size:13px;">
                                <input type="checkbox" name="is_primary" value="1" style="transform: translateY(1px);">
                                Primary yap
                            </label>
                        </div>
                    </div>
                    <div class="filterActions" style="margin-top:10px;">
                        <button class="btn btnPrimary" type="submit">Ekle</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="cardTitle">System Health</div>
            <div class="tableWrap" style="margin-top:10px;">
                <table class="table">
                    <tbody>
                    <tr><td style="padding-left:16px;">PHP</td><td class="muted">{{ data_get($health,'php','-') }}</td></tr>
                    <tr><td style="padding-left:16px;">ENV</td><td class="muted">{{ data_get($health,'laravel_env','-') }}</td></tr>
                    <tr><td style="padding-left:16px;">Debug</td><td class="muted">{{ data_get($health,'debug') ? 'true' : 'false' }}</td></tr>
                    <tr><td style="padding-left:16px;">Timezone</td><td class="muted">{{ data_get($health,'timezone','-') }}</td></tr>
                    <tr><td style="padding-left:16px;">storage symlink</td><td class="muted">{{ data_get($health,'storage_link') ? 'ok' : 'missing' }}</td></tr>
                    <tr>
                        <td style="padding-left:16px;">Extensions</td>
                        <td class="muted">
                            imap={{ data_get($health,'extensions.imap') ? '1' : '0' }},
                            openssl={{ data_get($health,'extensions.openssl') ? '1' : '0' }},
                            mbstring={{ data_get($health,'extensions.mbstring') ? '1' : '0' }},
                            pdo_mysql={{ data_get($health,'extensions.pdo_mysql') ? '1' : '0' }}
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:14px; padding:0;">
        <div style="padding:12px 16px; font-weight:1000;">Global AI Prompt Şablonları</div>
        <div class="tableWrap">
            <table class="table">
                <thead>
                <tr>
                    <th style="padding-left:16px;">Key</th>
                    <th>Başlık</th>
                    <th>Aktif</th>
                    <th style="padding-right:16px; text-align:right;">Düzenle</th>
                </tr>
                </thead>
                <tbody>
                @forelse($templates ?? [] as $t)
                    <tr>
                        <td style="padding-left:16px; font-weight:1000;">{{ data_get($t,'template_key','') }}</td>
                        <td class="muted">{{ data_get($t,'title','') }}</td>
                        <td>
                            <span class="badge {{ (int) data_get($t,'is_active',0) ? 'badgeSuccess' : 'badgeDanger' }}">{{ (int) data_get($t,'is_active',0) ? 'yes' : 'no' }}</span>
                        </td>
                        <td style="padding-right:16px; text-align:right;">
                            <details>
                                <summary class="btn" style="display:inline-flex;">Düzenle</summary>
                                <div style="margin-top:10px;">
                                    <form method="POST" action="/super/settings/templates/{{ (int) data_get($t,'id',0) }}">
                                        @csrf
                                        <div class="filterRow r3wide">
                                            <div style="grid-column:1 / span 3;">
                                                <div class="label">Title</div>
                                                <input class="input" name="title" value="{{ data_get($t,'title','') }}" required>
                                            </div>
                                        </div>
                                        <div class="filterRow r3wide" style="margin-top:10px;">
                                            <div style="grid-column:1 / span 3;">
                                                <div class="label">System Prompt</div>
                                                <textarea class="input" name="system_prompt" rows="4" required>{{ data_get($t,'system_prompt','') }}</textarea>
                                            </div>
                                        </div>
                                        <div class="filterRow r3wide" style="margin-top:10px;">
                                            <div style="grid-column:1 / span 3;">
                                                <div class="label">User Prompt</div>
                                                <textarea class="input" name="user_prompt" rows="4" required>{{ data_get($t,'user_prompt','') }}</textarea>
                                            </div>
                                        </div>
                                        <div class="filterActions" style="margin-top:10px;">
                                            <label style="display:flex; gap:8px; align-items:center; font-size:13px;">
                                                <input type="checkbox" name="is_active" value="1" {{ (int) data_get($t,'is_active',0) ? 'checked' : '' }} style="transform: translateY(1px);">
                                                Aktif
                                            </label>
                                            <button class="btn btnPrimary" type="submit">Kaydet</button>
                                        </div>
                                    </form>
                                </div>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted" style="padding:16px;">Kayıt yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div style="padding:12px 16px; border-top:1px solid var(--line);">
            <div style="font-weight:1000; margin-bottom:8px;">Yeni Şablon</div>
            <form method="POST" action="/super/settings/templates">
                @csrf
                <div class="filterRow r3wide">
                    <div>
                        <div class="label">template_key</div>
                        <input class="input" name="template_key" placeholder="örn: last_message_to_sale" required>
                    </div>
                    <div style="grid-column:2 / span 2;">
                        <div class="label">title</div>
                        <input class="input" name="title" placeholder="Başlık" required>
                    </div>
                </div>
                <div class="filterRow r3wide" style="margin-top:10px;">
                    <div style="grid-column:1 / span 3;">
                        <div class="label">system_prompt</div>
                        <textarea class="input" name="system_prompt" rows="3" required>Sen @{{sector}} sektöründe çalışan deneyimli bir satış uzmanısın.</textarea>
                    </div>
                </div>
                <div class="filterRow r3wide" style="margin-top:10px;">
                    <div style="grid-column:1 / span 3;">
                        <div class="label">user_prompt</div>
                        <textarea class="input" name="user_prompt" rows="4" required>Kurallar:\n@{{rules}}\n\nSohbet:\n@{{chat_history}}\n\n...</textarea>
                    </div>
                </div>
                <div class="filterActions" style="margin-top:10px;">
                    <label style="display:flex; gap:8px; align-items:center; font-size:13px;">
                        <input type="checkbox" name="is_active" value="1" checked style="transform: translateY(1px);">
                        Aktif
                    </label>
                    <button class="btn btnPrimary" type="submit">Ekle</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card" style="margin-top:14px; padding:0;">
        <div style="padding:12px 16px; font-weight:1000;">Audit Log (Son 120)</div>
        <div class="tableWrap">
            <table class="table">
                <thead>
                <tr>
                    <th style="padding-left:16px;">Zaman</th>
                    <th>Tenant</th>
                    <th>Actor</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th style="padding-right:16px;">Meta</th>
                </tr>
                </thead>
                <tbody>
                @forelse($audit ?? [] as $a)
                    <tr>
                        <td style="padding-left:16px;" class="muted">{{ \Illuminate\Support\Carbon::parse(data_get($a,'created_at'))->format('d.m.Y H:i') }}</td>
                        <td class="muted">{{ data_get($a,'tenant_name') ?? ('#' . (int) data_get($a,'tenant_id',0)) }}</td>
                        <td class="muted">{{ data_get($a,'actor_email') ?? data_get($a,'actor_name') ?? '—' }}</td>
                        <td><span class="badge badgeNeutral">{{ data_get($a,'action','') }}</span></td>
                        <td class="muted">{{ data_get($a,'entity_type','—') }}#{{ data_get($a,'entity_id','—') }}</td>
                        <td style="padding-right:16px;">
                            <div class="muted" style="max-width:520px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                {{ data_get($a,'metadata_json') }}
                            </div>
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

