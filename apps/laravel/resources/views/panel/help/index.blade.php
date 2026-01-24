@extends('layouts.app')

@section('title', __('ui.nav_help') . ' - Mark-A CRM')
@section('page_title', __('ui.nav_help'))

@section('content')
    <div class="card">
        <div class="toolbar" style="justify-content:space-between; margin-bottom:8px;">
            <div>
                <div class="pageTitle">Help Center</div>
                <div class="muted">Bilgi bankası +Res (Reklam metnisi)</div>
            </div>
        </div>

        <form method="GET" class="filterPanel" style="margin-top:12px;">
            <div class="filterRow r3actions">
                <div class="filterField">
                    <div class="label">Arama</div>
                    <input class="input" name="q" value="{{ request('q') }}" placeholder="Başlık">
                </div>
                <div class="filterField">
                    <div class="label">Tip</div>
                    <select class="input" name="type">
                        <option value="">Tümü</option>
                        <option value="knowledge" @selected(request('type')==='knowledge')>knowledge</option>
                        <option value="res_ad_copy" @selected(request('type')==='res_ad_copy')>res_ad_copy</option>
                    </select>
                </div>
                <div class="filterActions">
                    <button class="btn btnPrimary" type="submit">Filtrele</button>
                    <a class="btn" href="/help">Sıfırla</a>
                </div>
            </div>
        </form>

        <form method="POST" action="/help" class="filterPanel" style="margin-top:12px;">
            @csrf
            <div class="filterRow r3">
                <div class="filterField">
                    <div class="label">Tip</div>
                    <select class="input" name="type" required>
                        <option value="knowledge">knowledge</option>
                        <option value="res_ad_copy">res_ad_copy</option>
                    </select>
                </div>
                <div class="filterField">
                    <div class="label">Dil</div>
                    <select class="input" name="language" required>
                        <option value="tr">tr</option>
                        <option value="en">en</option>
                    </select>
                </div>
                <div class="filterActions">
                    <button class="btn btnPrimary" type="submit">Ekle</button>
                </div>
            </div>
            <div class="filterRow">
                <div class="filterField" style="grid-column: 1 / -1;">
                    <div class="label">Başlık</div>
                    <input class="input" name="title" required>
                </div>
                <div class="filterField" style="grid-column: 1 / -1;">
                    <div class="label">İçerik</div>
                    <textarea class="input" name="content" rows="5" required></textarea>
                </div>
            </div>
        </form>
    </div>

    <div class="card" style="margin-top:14px; padding:0;">
        <div class="tableWrap">
            <table class="table">
                <thead>
                <tr>
                    <th style="padding-left:16px;">Başlık</th>
                    <th>Tip</th>
                    <th>Dil</th>
                    <th style="padding-right:16px; text-align:right;">Tarih</th>
                </tr>
                </thead>
                <tbody>
                @forelse($articles as $a)
                    <tr>
                        <td style="padding-left:16px;">
                            <div style="font-weight:1000">{{ $a->title }}</div>
                            <div class="muted">{{ \Illuminate\Support\Str::limit((string)$a->content, 120) }}</div>
                        </td>
                        <td><span class="badge badgeNeutral">{{ $a->type }}</span></td>
                        <td><span class="badge badgeNeutral">{{ $a->language }}</span></td>
                        <td style="padding-right:16px; text-align:right;" class="muted">{{ \Illuminate\Support\Carbon::parse($a->created_at)->format('d.m.Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted" style="padding:16px;">Kayıt yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:12px 16px;">
            {{ $articles->links() }}
        </div>
    </div>
@endsection

