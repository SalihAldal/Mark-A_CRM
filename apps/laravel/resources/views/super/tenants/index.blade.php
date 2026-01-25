@extends('layouts.app')

@section('title', 'Tenant Yönetimi - Mark-A CRM')
@section('page_title', 'Tenant Yönetimi')

@section('content')
    <div class="card">
        <div class="toolbar" style="justify-content:space-between;">
            <div>
                <div class="pageTitle">Tenant Yönetimi</div>
                <div class="muted">Tenant + domain + kullanıcı yönetimi.</div>
            </div>
            <form class="toolbar" method="GET" action="/super/tenants" style="margin:0;">
                <input class="input" name="q" value="{{ request('q') }}" placeholder="Tenant ara (name/slug)">
                <select class="input" name="status">
                    <option value="">Tümü</option>
                    <option value="active" {{ request('status')==='active' ? 'selected' : '' }}>active</option>
                    <option value="disabled" {{ request('status')==='disabled' ? 'selected' : '' }}>disabled</option>
                </select>
                <button class="btn btnPrimary" type="submit">Filtrele</button>
                <a class="btn" href="/super/tenants">Sıfırla</a>
            </form>
        </div>
    </div>

    <div class="card" style="margin-top:14px;">
        <div class="cardTitle">Yeni Tenant</div>
        <form method="POST" action="/super/tenants" style="margin-top:10px;">
            @csrf
            <div class="filterRow r3wide">
                <div>
                    <div class="label">Tenant Adı</div>
                    <input class="input" name="name" placeholder="Örn: Mark-A Tenant" required>
                </div>
                <div>
                    <div class="label">Slug</div>
                    <input class="input" name="slug" placeholder="örn: tenant2" required>
                </div>
                <div>
                    <div class="label">Status</div>
                    <select class="input" name="status" required>
                        <option value="active">active</option>
                        <option value="disabled">disabled</option>
                    </select>
                </div>
            </div>
            <div class="filterRow r3wide" style="margin-top:10px;">
                <div style="grid-column:1 / span 2;">
                    <div class="label">Primary Domain (opsiyonel)</div>
                    <input class="input" name="primary_host" placeholder="örn: tenant2.localhost (boşsa otomatik: {slug}.localhost)">
                </div>
                <div style="display:flex; gap:10px; align-items:flex-end;">
                    <label style="display:flex; gap:8px; align-items:center; font-size:13px;">
                        <input type="checkbox" name="create_admin" value="1" checked style="transform: translateY(1px);">
                        Admin kullanıcı oluştur
                    </label>
                </div>
            </div>
            <div class="filterRow r3wide" style="margin-top:10px;">
                <div>
                    <div class="label">Admin Adı</div>
                    <input class="input" name="admin_name" placeholder="Tenant Admin">
                </div>
                <div>
                    <div class="label">Admin Email</div>
                    <input class="input" name="admin_email" placeholder="admin@tenant2.local">
                </div>
                <div>
                    <div class="label">Admin Şifre</div>
                    <input class="input" name="admin_password" placeholder="password">
                </div>
            </div>
            <div class="filterActions" style="margin-top:10px;">
                <button class="btn btnPrimary" type="submit">Tenant Oluştur</button>
            </div>
        </form>
    </div>

    <div class="card" style="margin-top:14px; padding:0;">
        <div style="padding:12px 16px; font-weight:1000;">Tenantler</div>
        <div class="tableWrap">
            <table class="table">
                <thead>
                <tr>
                    <th style="padding-left:16px;">Tenant</th>
                    <th>Domain</th>
                    <th>Leads</th>
                    <th>Won</th>
                    <th>Lost</th>
                    <th>Status</th>
                    <th style="padding-right:16px; text-align:right;">İşlem</th>
                </tr>
                </thead>
                <tbody>
                @forelse($tenants as $t)
                    <tr>
                        <td style="padding-left:16px;">
                            <div style="font-weight:1000">{{ $t->name }}</div>
                            <div class="muted">#{{ (int)$t->id }} • {{ $t->slug }}</div>
                        </td>
                        <td class="muted">{{ $t->primary_host ?: '—' }}</td>
                        <td><span class="badge badgeNeutral">{{ (int)($t->lead_total ?? 0) }}</span></td>
                        <td><span class="badge badgeSuccess">{{ (int)($t->lead_won ?? 0) }}</span></td>
                        <td><span class="badge badgeDanger">{{ (int)($t->lead_lost ?? 0) }}</span></td>
                        <td><span class="badge {{ $t->status==='active' ? 'badgeSuccess' : 'badgeDanger' }}">{{ $t->status }}</span></td>
                        <td style="padding-right:16px; text-align:right;">
                            <a class="btn" href="/super/tenants/{{ (int)$t->id }}">Düzenle</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted" style="padding:16px;">Kayıt yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:12px 16px;">{{ $tenants->links() }}</div>
    </div>
@endsection

