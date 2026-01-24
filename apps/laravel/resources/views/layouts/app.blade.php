<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="realtime-gateway" content="{{ config('services.realtime.base_url') }}">
    <title>@yield('title', 'Mark-A CRM')</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="app" x-data="{ mobileNav:false }">
<div class="shell">
    <aside class="sidebar" :class="{open: mobileNav}">
        @php($ctx = app(\App\Support\TenantContext::class))
        @php($roleKey = (string)(auth()->user()->role?->key ?? ''))
        <div class="brand">
            <div class="logo">M</div>
            <div class="brandText">
                <div class="brandName">Mark-A CRM</div>
                <div class="brandSub">{{ request()->getHost() }}</div>
            </div>
        </div>

        <nav class="nav">
            @if($ctx->isSuperPanel())
                <a class="navItem {{ request()->is('super') ? 'active' : '' }}" href="/super">Super Dashboard</a>
                <a class="navItem {{ request()->is('super/tenants') ? 'active' : '' }}" href="/super/tenants">Tenant Yönetimi</a>
                <a class="navItem {{ request()->is('super/settings') ? 'active' : '' }}" href="/super/settings">Sistem Ayarları</a>
            @else
                <a class="navItem {{ request()->is('panel') ? 'active' : '' }}" href="/panel">{{ __('ui.nav_dashboard') }}</a>
                <a class="navItem {{ request()->is('leads*') ? 'active' : '' }}" href="/leads">{{ __('ui.nav_leads') }}</a>
                <a class="navItem {{ request()->is('chats*') ? 'active' : '' }}" href="/chats">{{ __('ui.nav_chats') }}</a>
                <a class="navItem {{ request()->is('calendar*') ? 'active' : '' }}" href="/calendar">{{ __('ui.nav_calendar') }}</a>
                <a class="navItem {{ request()->is('stats*') ? 'active' : '' }}" href="/stats">{{ __('ui.nav_stats') }}</a>
                @if($roleKey !== 'customer')
                    <a class="navItem {{ request()->is('lists*') ? 'active' : '' }}" href="/lists">{{ __('ui.nav_lists') }}</a>
                    <a class="navItem {{ request()->is('mail*') ? 'active' : '' }}" href="/mail">{{ __('ui.nav_mail') }}</a>
                    <a class="navItem {{ request()->is('settings*') ? 'active' : '' }}" href="/settings">{{ __('ui.nav_settings') }}</a>
                    <a class="navItem {{ request()->is('help*') ? 'active' : '' }}" href="/help">{{ __('ui.nav_help') }}</a>
                @endif
            @endif
        </nav>

        <div class="sidebarFooter">
            <div class="userBox">
                <div class="avatar">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</div>
                <div class="userMeta">
                    <div class="userName">{{ auth()->user()->name ?? '-' }}</div>
                    <div class="userRole">{{ auth()->user()->role?->name_tr ?? auth()->user()->role?->key ?? '-' }}</div>
                </div>
            </div>
            <form method="POST" action="/logout">
                @csrf
                <button class="btn btnGhost" type="submit">{{ __('ui.logout') }}</button>
            </form>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <button class="btnIcon" type="button" @click="mobileNav = !mobileNav" aria-label="Menu">☰</button>
            <div class="topbarTitle">@yield('page_title', '')</div>
            <div class="topbarRight">
                <a class="pill" href="?lang=tr">TR</a>
                <a class="pill" href="?lang=en">EN</a>
            </div>
        </header>

        <section class="content">
            @if(session('status'))
                <div class="alert">{{ session('status') }}</div>
            @endif
            @yield('content')
        </section>
    </main>
</div>

<!-- Floating Support Chatbot -->
<div x-data="supportBot()" x-cloak>
    <button type="button" class="chatbotFab" @click="open = !open" aria-label="Support">
        <span style="font-weight:1000;">?</span>
    </button>

    <div class="chatbotPanel" x-show="open" @click.outside="open=false">
        <div class="chatbotHeader">
            <div style="font-weight:1000;">Yardım</div>
            <button class="btn" type="button" @click="open=false">Kapat</button>
        </div>
        <div class="chatbotMsgs" x-ref="msgs">
            <template x-for="m in messages" :key="m.id">
                <div :style="`display:flex; justify-content:${m.role==='user'?'flex-end':'flex-start'};`">
                    <div class="chatbotBubble" :class="m.role==='user' ? 'me' : 'ai'" x-text="m.text"></div>
                </div>
            </template>
        </div>
        <div class="chatbotInputRow">
            <input class="input" x-model="text" @keydown.enter.prevent="send()" placeholder="Sorunu yaz...">
            <button class="btn btnPrimary" type="button" @click="send()" :disabled="sending || !text.trim()">Gönder</button>
        </div>
    </div>
</div>

<script>
    function supportBot() {
        return {
            open: false,
            sending: false,
            text: '',
            messages: [
                { id: 'hello', role: 'ai', text: 'Merhaba! Mark-A CRM ile ilgili sorunu yaz, çözüm üreteyim.' }
            ],
            pageContext() {
                const titleEl = document.querySelector('.topbarTitle');
                const pageTitle = titleEl ? (titleEl.textContent || '').trim() : '';
                return {
                    path: window.location.pathname || '',
                    query: window.location.search || '',
                    page_title: pageTitle,
                    role: @json($roleKey),
                };
            },
            scrollDown() {
                this.$nextTick(() => {
                    try { this.$refs.msgs.scrollTop = this.$refs.msgs.scrollHeight; } catch(e) {}
                });
            },
            async send() {
                const q = (this.text || '').trim();
                if (!q || this.sending) return;
                this.messages.push({ id: 'u_' + Date.now(), role: 'user', text: q });
                this.text = '';
                this.scrollDown();

                const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                this.sending = true;
                try {
                    const r = await fetch('/support/bot', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: JSON.stringify({ question: q, context: this.pageContext() })
                    });
                    const j = await r.json().catch(() => ({}));
                    if (!r.ok || !j.ok) {
                        this.messages.push({ id: 'e_' + Date.now(), role: 'ai', text: 'Şu an cevap üretemedim. (OpenAI/ayar) Tekrar dener misin?' });
                    } else {
                        this.messages.push({ id: 'a_' + Date.now(), role: 'ai', text: (j.answer || '').trim() || '—' });
                    }
                } catch (e) {
                    this.messages.push({ id: 'e_' + Date.now(), role: 'ai', text: 'Bağlantı hatası oldu. Tekrar dener misin?' });
                } finally {
                    this.sending = false;
                    this.scrollDown();
                }
            }
        }
    }
</script>
</body>
</html>

