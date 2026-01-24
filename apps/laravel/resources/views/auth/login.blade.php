<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('ui.login_title') }} - Mark-A CRM</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="auth">
<div class="authShell">
    <div class="authCard">
        <div class="authBrand">
            <div class="logo">M</div>
            <div>
                <div class="authTitle">{{ __('ui.login_title') }}</div>
                <div class="authSub">{{ request()->getHost() }}</div>
            </div>
        </div>

        <form method="POST" action="/login" class="form">
            @csrf

            <label class="field">
                <div class="label">{{ __('ui.email') }}</div>
                <input class="input" type="email" name="email" value="{{ old('email') }}" required autofocus>
                @error('email')<div class="error">{{ $message }}</div>@enderror
            </label>

            <label class="field">
                <div class="label">{{ __('ui.password') }}</div>
                <input class="input" type="password" name="password" required>
                @error('password')<div class="error">{{ $message }}</div>@enderror
            </label>

            <button class="btn btnPrimary" type="submit">{{ __('ui.login') }}</button>

            <div class="authHint">
                <div class="muted">{{ __('ui.login_hint') }}</div>
            </div>
        </form>
    </div>
</div>
</body>
</html>

