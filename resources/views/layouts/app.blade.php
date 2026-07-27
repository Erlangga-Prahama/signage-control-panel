<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Signage Control Panel' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs/dist/cdn.min.js" defer></script>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo/dist/echo.iife.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                        mono: ['"JetBrains Mono"', 'ui-monospace', 'SFMono-Regular'],
                    },
                    colors: {
                        base: '#0c4a6e',
                        surface: '#0a3d5c',
                        surface2: '#0e4569',
                        edge: '#155e83',
                        ink: '#E8EDF2',
                        muted: '#9ec3db',
                        signal: '#3DDC84',
                        amber: '#F5A623',
                        alert: '#E5484D',
                        wire: '#14b8a6',
                    },
                    boxShadow: {
                        glow: '0 0 0 1px rgba(61,220,132,0.25), 0 0 24px -6px rgba(61,220,132,0.45)',
                    },
                    keyframes: {
                        ping2: { '75%,100%': { transform: 'scale(2)', opacity: 0 } },
                    },
                },
            },
        };
    </script>
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: #0c4a6e; }
        ::-webkit-scrollbar-thumb { background: #155e83; border-radius: 8px; }
    </style>
</head>
<body class="h-full bg-base text-ink font-sans antialiased" x-data="signageApp()" x-init="init()">

    <div class="min-h-full flex">
        <!-- Sidebar -->
        <aside class="w-60 shrink-0 border-r border-edge bg-surface flex flex-col">
            <div class="px-5 py-5 border-b border-edge">
                <div class="flex items-center gap-2.5">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="absolute inline-flex h-full w-full rounded-full bg-signal opacity-75 animate-[ping2_1.8s_cubic-bezier(0,0,0.2,1)_infinite]"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-signal"></span>
                    </span>
                    <span class="font-semibold tracking-tight text-[15px]">Signage<span class="text-muted">Control</span></span>
                </div>
                <p class="text-[11px] text-muted mt-1 font-mono tracking-wide">BROADCAST OPS</p>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
                <a href="{{ route('dashboard.devices') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('dashboard.devices') ? 'bg-surface2 text-ink' : 'text-muted hover:text-ink hover:bg-surface2' }} transition">
                    <span class="font-mono text-xs w-4 text-center">▣</span> Devices
                </a>
                <a href="{{ route('dashboard.contents') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('dashboard.contents') ? 'bg-surface2 text-ink' : 'text-muted hover:text-ink hover:bg-surface2' }} transition">
                    <span class="font-mono text-xs w-4 text-center">▤</span> Contents
                </a>
                <a href="{{ route('dashboard.playlists') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('dashboard.playlists') ? 'bg-surface2 text-ink' : 'text-muted hover:text-ink hover:bg-surface2' }} transition">
                    <span class="font-mono text-xs w-4 text-center">▥</span> Playlists
                </a>
            </nav>

            <div class="px-3 py-4 border-t border-edge space-y-2">
                <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-surface2 text-[11px] font-mono">
                    <span class="h-1.5 w-1.5 rounded-full" :class="wsConnected ? 'bg-signal' : 'bg-alert'"></span>
                    <span x-text="wsConnected ? 'REALTIME: LIVE' : 'REALTIME: OFF'" class="text-muted"></span>
                </div>
                <div class="px-3 flex items-center justify-between">
                    <span class="text-xs text-muted truncate" x-text="userEmail"></span>
                    <button @click="logout()" class="text-xs text-alert hover:underline">Keluar</button>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-1 min-w-0">
            @yield('content')
        </div>
    </div>

    <!-- Toasts -->
    <div class="fixed bottom-5 right-5 space-y-2 z-50" x-cloak>
        <template x-for="t in toasts" :key="t.id">
            <div class="px-4 py-2.5 rounded-lg border text-sm shadow-lg font-mono"
                 :class="t.type === 'error' ? 'bg-alert/10 border-alert/40 text-alert' : 'bg-signal/10 border-signal/40 text-signal'"
                 x-text="t.msg" x-transition></div>
        </template>
    </div>

    <script>
        function signageApp() {
            return {
                toasts: [],
                wsConnected: false,
                userEmail: '',
                echo: null,

                init() {
                    const token = localStorage.getItem('signage_token');
                    if (!token && !window.location.pathname.startsWith('/login') && !window.location.pathname.startsWith('/player')) {
                        window.location.href = '/login';
                        return;
                    }
                    this.userEmail = localStorage.getItem('signage_email') || '';
                    this.connectRealtime();
                },

                connectRealtime() {
                    try {
                        this.echo = new Echo({
                            broadcaster: 'reverb',
                            key: '{{ env('REVERB_APP_KEY', 'signage-key') }}',
                            wsHost: '{{ env('REVERB_HOST', 'localhost') }}',
                            wsPort: {{ env('REVERB_PORT', 8080) }},
                            wssPort: {{ env('REVERB_PORT', 8080) }},
                            forceTLS: '{{ env('REVERB_SCHEME', 'http') }}' === 'https',
                            enabledTransports: ['ws', 'wss'],
                        });

                        this.echo.connector.pusher.connection.bind('connected', () => this.wsConnected = true);
                        this.echo.connector.pusher.connection.bind('disconnected', () => this.wsConnected = false);
                        this.echo.connector.pusher.connection.bind('unavailable', () => this.wsConnected = false);

                        window.SignageEcho = this.echo;
                        window.dispatchEvent(new CustomEvent('signage-echo-ready'));
                    } catch (e) {
                        console.error('Reverb connection failed', e);
                    }
                },

                notify(msg, type = 'ok') {
                    const id = Date.now() + Math.random();
                    this.toasts.push({ id, msg, type });
                    setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 3500);
                },

                logout() {
                    localStorage.removeItem('signage_token');
                    localStorage.removeItem('signage_email');
                    window.location.href = '/login';
                },
            };
        }

        // Shared fetch helper: attaches JWT bearer token, JSON headers.
        async function api(path, options = {}) {
            const token = localStorage.getItem('signage_token');
            const headers = Object.assign(
                { 'Accept': 'application/json' },
                options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' },
                token ? { 'Authorization': 'Bearer ' + token } : {},
                options.headers || {}
            );
            const res = await fetch('/api' + path, { ...options, headers });
            if (res.status === 401) {
                localStorage.removeItem('signage_token');
                window.location.href = '/login';
                throw new Error('Unauthorized');
            }
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw Object.assign(new Error(data.message || 'Request failed'), { data });
            return data;
        }
    </script>

    @stack('scripts')
</body>
</html>
