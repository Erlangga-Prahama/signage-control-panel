@extends('layouts.app')

@section('content')
<div x-data="devicesPage()" x-init="load()" class="p-8 max-w-6xl">

    <div class="flex items-start justify-between mb-8">
        <div>
            <h1 class="text-xl font-semibold tracking-tight">Devices</h1>
            <p class="text-sm text-muted mt-1">Status diperbarui realtime lewat WebSocket — tidak perlu refresh.</p>
        </div>
        <button @click="openCreate()" class="bg-wire hover:bg-wire/90 text-white text-sm font-medium rounded-lg px-4 py-2.5 transition">
            + Tambah Device
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" x-cloak>
        <template x-for="d in devices" :key="d.id">
            <div class="bg-surface border border-edge rounded-2xl p-5 relative overflow-hidden"
                 :class="d.status === 'online' ? 'shadow-glow' : ''">
                <div class="flex items-start justify-between mb-4">
                    <div class="min-w-0">
                        <h3 class="font-medium truncate" x-text="d.nama"></h3>
                        <p class="text-xs text-muted mt-0.5 truncate" x-text="d.lokasi || 'Tanpa lokasi'"></p>
                    </div>
                    <span class="flex items-center gap-1.5 shrink-0 pl-3">
                        <span class="relative flex h-2 w-2">
                            <span x-show="d.status === 'online'" class="absolute inline-flex h-full w-full rounded-full opacity-75 animate-[ping2_1.8s_cubic-bezier(0,0,0.2,1)_infinite]" :class="d.status === 'online' ? 'bg-signal' : ''"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2" :class="d.status === 'online' ? 'bg-signal' : 'bg-alert'"></span>
                        </span>
                        <span class="text-[11px] font-mono uppercase" :class="d.status === 'online' ? 'text-signal' : 'text-alert'" x-text="d.status"></span>
                    </span>
                </div>

                <div class="text-xs font-mono text-muted space-y-1 mb-4">
                    <p>ID <span class="text-ink/70" x-text="'#' + d.id"></span></p>
                    <p>Playlist <span class="text-ink/70" x-text="d.playlist ? d.playlist.nama : '—'"></span></p>
                    <p>Terakhir aktif <span class="text-ink/70" x-text="formatTime(d.last_seen)"></span></p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a :href="'/player/' + d.device_key" target="_blank"
                       class="text-xs px-2.5 py-1.5 rounded-lg border border-edge hover:border-wire/50 text-muted hover:text-ink transition">Buka Player</a>
                    <button @click="openPush(d)" class="text-xs px-2.5 py-1.5 rounded-lg border border-edge hover:border-signal/50 text-muted hover:text-ink transition">Push Konten</button>
                    <button @click="openEdit(d)" class="text-xs px-2.5 py-1.5 rounded-lg border border-edge hover:border-wire/50 text-muted hover:text-ink transition">Edit</button>
                    <button @click="remove(d)" class="text-xs px-2.5 py-1.5 rounded-lg border border-edge hover:border-alert/50 text-muted hover:text-alert transition">Hapus</button>
                </div>
            </div>
        </template>

        <div x-show="devices.length === 0" class="col-span-full text-center py-16 text-muted text-sm border border-dashed border-edge rounded-2xl">
            Belum ada device. Tambahkan device pertama kamu.
        </div>
    </div>

    <!-- Create/Edit modal -->
    <div x-show="modalOpen" x-cloak class="fixed inset-0 bg-black/60 flex items-center justify-center z-40 p-4" @keydown.escape.window="modalOpen = false">
        <div class="bg-surface border border-edge rounded-2xl p-6 w-full max-w-sm" @click.outside="modalOpen = false">
            <h3 class="font-medium mb-4" x-text="form.id ? 'Edit Device' : 'Tambah Device'"></h3>
            <form @submit.prevent="save()" class="space-y-3">
                <div>
                    <label class="block text-xs font-mono text-muted mb-1.5">NAMA</label>
                    <input x-model="form.nama" required class="w-full bg-base border border-edge rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-wire/50">
                </div>
                <div>
                    <label class="block text-xs font-mono text-muted mb-1.5">LOKASI</label>
                    <input x-model="form.lokasi" class="w-full bg-base border border-edge rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-wire/50">
                </div>
                <div>
                    <label class="block text-xs font-mono text-muted mb-1.5">PLAYLIST</label>
                    <select x-model="form.playlist_id" class="w-full bg-base border border-edge rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-wire/50">
                        <option value="">— Tanpa playlist —</option>
                        <template x-for="p in playlists" :key="p.id">
                            <option :value="p.id" x-text="p.nama"></option>
                        </template>
                    </select>
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="button" @click="modalOpen = false" class="flex-1 text-sm border border-edge rounded-lg py-2 hover:border-muted transition">Batal</button>
                    <button type="submit" class="flex-1 text-sm bg-wire hover:bg-wire/90 text-white rounded-lg py-2 transition">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Push content modal -->
    <div x-show="pushOpen" x-cloak class="fixed inset-0 bg-black/60 flex items-center justify-center z-40 p-4" @keydown.escape.window="pushOpen = false">
        <div class="bg-surface border border-edge rounded-2xl p-6 w-full max-w-sm" @click.outside="pushOpen = false">
            <h3 class="font-medium mb-1">Push konten ke <span x-text="pushTarget?.nama"></span></h3>
            <p class="text-xs text-muted mb-4">Konten ini langsung tampil di layar, override playlist sementara.</p>
            <div class="space-y-2 max-h-64 overflow-y-auto mb-4">
                <template x-for="c in contents" :key="c.id">
                    <button @click="pushContent(c)" class="w-full text-left px-3 py-2.5 rounded-lg border border-edge hover:border-signal/50 text-sm flex items-center justify-between transition">
                        <span x-text="c.judul"></span>
                        <span class="text-[10px] font-mono text-muted uppercase" x-text="c.tipe"></span>
                    </button>
                </template>
                <p x-show="contents.length === 0" class="text-xs text-muted text-center py-4">Belum ada konten. Tambahkan di halaman Contents.</p>
            </div>
            <button @click="pushCommand('clear_override')" class="w-full text-xs text-muted hover:text-ink border border-edge rounded-lg py-2 mb-2 transition">Kembali ke playlist</button>
            <button @click="pushOpen = false" class="w-full text-sm border border-edge rounded-lg py-2 hover:border-muted transition">Tutup</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function devicesPage() {
        return {
            devices: [], playlists: [], contents: [],
            modalOpen: false, pushOpen: false, pushTarget: null,
            form: { id: null, nama: '', lokasi: '', playlist_id: '' },

            async load() {
                try {
                    [this.devices, this.playlists, this.contents] = await Promise.all([
                        api('/devices'), api('/playlists'), api('/contents'),
                    ]);
                } catch (e) { console.error(e); }

                window.addEventListener('signage-echo-ready', () => this.subscribe());
                if (window.SignageEcho) this.subscribe();
            },

            subscribe() {
                window.SignageEcho.channel('signage-dashboard').listen('.device.status.updated', (payload) => {
                    const idx = this.devices.findIndex(d => d.id === payload.id);
                    if (idx !== -1) {
                        this.devices[idx] = { ...this.devices[idx], ...payload };
                    }
                });
            },

            formatTime(ts) {
                if (!ts) return 'belum pernah';
                return new Date(ts).toLocaleString('id-ID');
            },

            openCreate() {
                this.form = { id: null, nama: '', lokasi: '', playlist_id: '' };
                this.modalOpen = true;
            },

            openEdit(d) {
                this.form = { id: d.id, nama: d.nama, lokasi: d.lokasi, playlist_id: d.playlist_id || '' };
                this.modalOpen = true;
            },

            async save() {
                try {
                    const payload = { nama: this.form.nama, lokasi: this.form.lokasi, playlist_id: this.form.playlist_id || null };
                    if (this.form.id) {
                        await api('/devices/' + this.form.id, { method: 'PUT', body: JSON.stringify(payload) });
                    } else {
                        await api('/devices', { method: 'POST', body: JSON.stringify(payload) });
                    }
                    this.modalOpen = false;
                    this.devices = await api('/devices');
                } catch (e) {
                    alert(e.message);
                }
            },

            async remove(d) {
                if (!confirm('Hapus device "' + d.nama + '"?')) return;
                await api('/devices/' + d.id, { method: 'DELETE' });
                this.devices = this.devices.filter(x => x.id !== d.id);
            },

            openPush(d) {
                this.pushTarget = d;
                this.pushOpen = true;
            },

            async pushContent(c) {
                await this.pushCommand('push_content', c.id);
            },

            async pushCommand(command, contentId = null) {
                try {
                    await api('/devices/' + this.pushTarget.id + '/commands', {
                        method: 'POST',
                        body: JSON.stringify({ command, content_id: contentId }),
                    });
                    this.pushOpen = false;
                } catch (e) {
                    alert(e.message);
                }
            },
        };
    }
</script>
@endpush
