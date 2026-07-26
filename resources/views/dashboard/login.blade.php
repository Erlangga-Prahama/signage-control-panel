<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Signage Control Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/[email protected]/dist/cdn.min.js" defer></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: {
            sans: ['Inter', 'ui-sans-serif', 'system-ui'],
            mono: ['"JetBrains Mono"', 'ui-monospace'],
        }, colors: {
            base: '#0B0E11', surface: '#12161C', edge: '#232A33',
            ink: '#E8EDF2', muted: '#8592A0', signal: '#3DDC84', alert: '#E5484D', wire: '#4C8EFF',
        } } } };
    </script>
</head>
<body class="h-full bg-base text-ink font-sans antialiased flex items-center justify-center px-4"
      x-data="loginForm()">
    <div class="w-full max-w-sm">
        <div class="flex items-center gap-2.5 justify-center mb-8">
            <span class="relative flex h-2.5 w-2.5">
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-signal"></span>
            </span>
            <span class="font-semibold tracking-tight">Signage<span class="text-muted">Control</span></span>
        </div>

        <div class="bg-surface border border-edge rounded-2xl p-6">
            <h1 class="text-lg font-semibold mb-1">Masuk ke panel admin</h1>
            <p class="text-sm text-muted mb-6">Kelola device dan konten signage kamu.</p>

            <form @submit.prevent="submit()" class="space-y-4">
                <div>
                    <label class="block text-xs font-mono text-muted mb-1.5">EMAIL</label>
                    <input type="email" x-model="email" required
                           class="w-full bg-base border border-edge rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-wire/50">
                </div>
                <div>
                    <label class="block text-xs font-mono text-muted mb-1.5">PASSWORD</label>
                    <input type="password" x-model="password" required
                           class="w-full bg-base border border-edge rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-wire/50">
                </div>
                <p x-show="error" x-text="error" class="text-alert text-xs font-mono" x-cloak></p>
                <button type="submit" :disabled="loading"
                        class="w-full bg-wire hover:bg-wire/90 disabled:opacity-60 text-white text-sm font-medium rounded-lg py-2.5 transition">
                    <span x-text="loading ? 'Memproses…' : 'Masuk'"></span>
                </button>
            </form>

            <p class="text-xs text-muted mt-5 text-center">
                Belum punya akun? <button @click="showRegister = !showRegister" class="text-wire hover:underline">Daftar admin</button>
            </p>

            <template x-if="showRegister">
                <form @submit.prevent="register()" class="space-y-3 mt-4 pt-4 border-t border-edge">
                    <input type="text" x-model="regName" placeholder="Nama" required
                           class="w-full bg-base border border-edge rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-wire/50">
                    <input type="email" x-model="regEmail" placeholder="Email" required
                           class="w-full bg-base border border-edge rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-wire/50">
                    <input type="password" x-model="regPassword" placeholder="Password (min 8 karakter)" required
                           class="w-full bg-base border border-edge rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-wire/50">
                    <button type="submit" class="w-full border border-edge hover:border-signal/50 text-sm rounded-lg py-2 transition">Buat akun</button>
                </form>
            </template>
        </div>
    </div>

    <script>
        function loginForm() {
            return {
                email: '', password: '', error: '', loading: false,
                showRegister: false, regName: '', regEmail: '', regPassword: '',

                async submit() {
                    this.loading = true; this.error = '';
                    try {
                        const res = await fetch('/api/auth/login', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({ email: this.email, password: this.password }),
                        });
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || 'Login gagal');
                        localStorage.setItem('signage_token', data.token);
                        localStorage.setItem('signage_email', data.user.email);
                        window.location.href = '/dashboard';
                    } catch (e) {
                        this.error = e.message;
                    } finally {
                        this.loading = false;
                    }
                },

                async register() {
                    this.loading = true; this.error = '';
                    try {
                        const res = await fetch('/api/auth/register', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({ name: this.regName, email: this.regEmail, password: this.regPassword }),
                        });
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || JSON.stringify(data.errors) || 'Registrasi gagal');
                        localStorage.setItem('signage_token', data.token);
                        localStorage.setItem('signage_email', data.user.email);
                        window.location.href = '/dashboard';
                    } catch (e) {
                        this.error = e.message;
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
</body>
</html>