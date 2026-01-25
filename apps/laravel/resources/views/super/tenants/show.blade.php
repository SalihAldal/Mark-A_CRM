@extends('layouts.app')

@section('title', 'Tenant - ' . $tenant->name . ' - Mark-A CRM')
@section('page_title', 'Tenant Detay')

@section('content')
    <div class="card">
        <div class="toolbar" style="justify-content:space-between;">
            <div>
                <div class="pageTitle">{{ $tenant->name }}</div>
                <div class="muted">#{{ (int)$tenant->id }} • slug: {{ $tenant->slug }}</div>
            </div>
            <div class="toolbar" style="margin:0;">
                <a class="btn" href="/super/tenants">← Geri</a>
                <span class="badge badgeNeutral">Leads: <b style="margin-left:6px;">{{ (int)($leadTotal ?? 0) }}</b></span>
                <span class="badge badgeSuccess">Won: <b style="margin-left:6px;">{{ (int)($leadWon ?? 0) }}</b></span>
                <span class="badge badgeDanger">Lost: <b style="margin-left:6px;">{{ (int)($leadLost ?? 0) }}</b></span>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:14px;">
        <div class="cardTitle">Tenant Bilgileri</div>
        <form method="POST" action="/super/tenants/{{ (int)$tenant->id }}" style="margin-top:10px;">
            @csrf
            <div class="filterRow r3wide">
                <div>
                    <div class="label">Ad</div>
                    <input class="input" name="name" value="{{ $tenant->name }}" required>
                </div>
                <div>
                    <div class="label">Slug</div>
                    <input class="input" name="slug" value="{{ $tenant->slug }}" required>
                </div>
                <div>
                    <div class="label">Status</div>
                    <select class="input" name="status" required>
                        <option value="active" {{ $tenant->status==='active' ? 'selected' : '' }}>active</option>
                        <option value="disabled" {{ $tenant->status==='disabled' ? 'selected' : '' }}>disabled</option>
                    </select>
                </div>
            </div>
            <div class="filterActions" style="margin-top:10px;">
                <button class="btn btnPrimary" type="submit">Kaydet</button>
            </div>
        </form>
    </div>

    <div class="grid2" style="margin-top:14px;">
        <div class="card" style="padding:0;">
            <div style="padding:12px 16px; font-weight:1000;">Domainler</div>
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
                    @forelse($domains as $d)
                        <tr>
                            <td style="padding-left:16px; font-weight:1000;">{{ $d->host }}</td>
                            <td class="muted">{{ (int)$d->is_primary ? 'yes' : 'no' }}</td>
                            <td><span class="badge {{ $d->status==='active' ? 'badgeSuccess' : 'badgeDanger' }}">{{ $d->status }}</span></td>
                            <td style="padding-right:16px; text-align:right;">
                                <form method="POST" action="/super/domains/{{ (int)$d->id }}/primary" style="display:inline;">
                                    @csrf
                                    <button class="btn" type="submit" {{ (int)$d->is_primary ? 'disabled' : '' }}>Primary</button>
                                </form>
                                <form method="POST" action="/super/domains/{{ (int)$d->id }}/toggle" style="display:inline;">
                                    @csrf
                                    <button class="btn" type="submit">{{ $d->status==='active' ? 'Pasif' : 'Aktif' }}</button>
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
                <form method="POST" action="/super/tenants/{{ (int)$tenant->id }}/domains">
                    @csrf
                    <div class="filterRow r3wide">
                        <div style="grid-column:1 / span 2;">
                            <div class="label">Yeni Domain</div>
                            <input class="input" name="host" placeholder="örn: tenant2.localhost" required>
                        </div>
                        <div>
                            <div class="label">Status</div>
                            <select class="input" name="status" required>
                                <option value="active">active</option>
                                <option value="disabled">disabled</option>
                            </select>
                        </div>
                    </div>
                    <div class="filterActions" style="margin-top:10px;">
                        <label style="display:flex; gap:8px; align-items:center; font-size:13px;">
                            <input type="checkbox" name="is_primary" value="1" style="transform: translateY(1px);">
                            Primary yap
                        </label>
                        <button class="btn btnPrimary" type="submit">Ekle</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card" style="padding:0;">
            <div style="padding:12px 16px; font-weight:1000;">Kullanıcılar</div>
            <div class="tableWrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th style="padding-left:16px;">Kullanıcı</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th style="padding-right:16px; text-align:right;">Tarih</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($users as $u)
                        @php($roleName = collect($roles)->firstWhere('id', $u->role_id)?->key ?? '—')
                        <tr>
                            <td style="padding-left:16px;">
                                <div style="font-weight:1000">{{ $u->name }}</div>
                                <div class="muted">{{ $u->email }}</div>
                            </td>
                            <td class="muted">{{ $roleName }}</td>
                            <td><span class="badge {{ $u->status==='active' ? 'badgeSuccess' : 'badgeDanger' }}">{{ $u->status }}</span></td>
                            <td style="padding-right:16px; text-align:right;" class="muted">{{ \Illuminate\Support\Carbon::parse($u->created_at)->format('d.m.Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted" style="padding:16px;">Kayıt yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div style="padding:12px 16px; border-top:1px solid var(--line);">
                <form method="POST" action="/super/tenants/{{ (int)$tenant->id }}/users">
                    @csrf
                    <div class="filterRow r3wide">
                        <div>
                            <div class="label">Ad</div>
                            <input class="input" name="name" placeholder="Örn: Çalışan 2" required>
                        </div>
                        <div>
                            <div class="label">Email</div>
                            <input class="input" name="email" placeholder="user@tenant2.local" required>
                        </div>
                        <div>
                            <div class="label">Şifre</div>
                            <input class="input" name="password" placeholder="password" required>
                        </div>
                    </div>
                    <div class="filterRow r3wide" style="margin-top:10px;">
                        <div>
                            <div class="label">Role</div>
                            <select class="input" name="role_key" required>
                                <option value="tenant_admin">tenant_admin</option>
                                <option value="staff" selected>staff</option>
                                <option value="customer">customer</option>
                            </select>
                        </div>
                        <div>
                            <div class="label">Status</div>
                            <select class="input" name="status" required>
                                <option value="active" selected>active</option>
                                <option value="disabled">disabled</option>
                            </select>
                        </div>
                        <div style="display:flex; align-items:flex-end;">
                            <button class="btn btnPrimary" type="submit">Kullanıcı Ekle</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

