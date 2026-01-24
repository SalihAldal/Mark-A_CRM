@extends('layouts.app')

@section('title', 'Yeni Lead - Mark-A CRM')
@section('page_title', 'Yeni Lead')

@section('content')
    <div class="card">
        <div class="toolbar" style="justify-content:space-between; margin-bottom:8px;">
            <div>
                <div class="pageTitle">Yeni Lead</div>
                <div class="muted">Manuel lead ekle</div>
            </div>
            <div class="toolbar" style="margin:0;">
                <a class="btn" href="/leads">← Leads</a>
            </div>
        </div>

        <form method="POST" action="/leads" class="filterPanel" style="margin-top:12px;">
            @csrf
            <div class="filterRow r3wide">
                <div class="filterField">
                    <div class="label">İsim</div>
                    <input class="input" name="name" required placeholder="Örn: Ahmet Yılmaz" value="{{ old('name') }}">
                </div>
                <div class="filterField">
                    <div class="label">Telefon</div>
                    <input class="input" name="phone" placeholder="+90..." value="{{ old('phone') }}">
                </div>
                <div class="filterField">
                    <div class="label">E-posta</div>
                    <input class="input" name="email" placeholder="mail@..." value="{{ old('email') }}">
                </div>
            </div>

            <div class="filterRow r3wide">
                <div class="filterField">
                    <div class="label">Firma</div>
                    <input class="input" name="company" placeholder="Şirket adı" value="{{ old('company') }}">
                </div>
                <div class="filterField">
                    <div class="label">Kaynak</div>
                    <select class="input" name="source" required>
                        @php($src = old('source', 'manual'))
                        <option value="manual" @selected($src==='manual')>manual</option>
                        <option value="wp" @selected($src==='wp')>wp</option>
                        <option value="instagram" @selected($src==='instagram')>instagram</option>
                        <option value="whatsapp" @selected($src==='whatsapp')>whatsapp</option>
                        <option value="telegram" @selected($src==='telegram')>telegram</option>
                        <option value="facebook" @selected($src==='facebook')>facebook</option>
                        <option value="wechat" @selected($src==='wechat')>wechat</option>
                    </select>
                </div>
                <div class="filterField">
                    <div class="label">Durum</div>
                    @php($st = old('status', 'open'))
                    <select class="input" name="status" required>
                        <option value="open" @selected($st==='open')>open</option>
                        <option value="won" @selected($st==='won')>won</option>
                        <option value="lost" @selected($st==='lost')>lost</option>
                    </select>
                </div>
            </div>

            <div class="filterRow r3wide">
                <div class="filterField">
                    <div class="label">Stage</div>
                    <select class="input" name="stage_id">
                        <option value="">—</option>
                        @foreach($stages as $s)
                            <option value="{{ $s->id }}" @selected((string)old('stage_id')===(string)$s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filterField">
                    <div class="label">Devralan</div>
                    <select class="input" name="assigned_user_id">
                        <option value="">—</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" @selected((string)old('assigned_user_id')===(string)$u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filterActions">
                    <button class="btn btnPrimary" type="submit">Kaydet</button>
                </div>
            </div>

            <div class="filterRow">
                <div class="filterField" style="grid-column: 1 / -1;">
                    <div class="label">Not</div>
                    <textarea class="input" name="notes" rows="4" placeholder="İlk not..." >{{ old('notes') }}</textarea>
                </div>
            </div>
        </form>
    </div>
@endsection

