@extends('layouts.app')

@section('content')
<div x-data="playlistsPage()" x-init="load()" class="p-8 max-w-6xl">

    <div class="flex items-start justify-between mb-8">
        <div>
            <h1 class="text-xl font-semibold tracking-tight">Playlists</h1>
            <p class="text-sm text-muted mt-1">Urutan konten yang diputar berulang di sebuah device.</p>
        </div>
        <button @click="openCreate()" class="bg-wire hover:bg-wire/90 text-white text-sm font-medium rounded-lg px-4 py-2.5 transition">
            + Tambah Playlist
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4" x-cloak>
        <template x-for="p in playlists" :key="p.id">
            <div class="bg-surface border border-edge rounded-2xl p-5">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="font-medium" x-text="p.nama"></h3>
                        <p class="text-xs text-muted mt-0.5" x-text="p.deskripsi || ''"></p>
                    </div>
                    <div class="flex gap-1.5 shrink-0">
                        <button @click="openEditItems(p)" class="text-xs px-2.5 py-1.5 rounded-lg border border-edge hover:border-signal/50 text-muted hover:text-ink transition">Kelola Item</button>
                        <button @click="removePlaylist(p)" class="text-xs px-2.5 py-1.5 rounded-lg border border-edge hover:border-alert/50 text-muted hover:text-alert transition">Hapus</button>
                    </div>
                </div>
                <ol class="space-y-1.5 font-mono text-xs">
                    <template x-for="(item, i) in p.items" :key="item.id">
                        <li class="flex items-center justify-between px-3 py-2 rounded-lg bg-surface2">
                            <span class="text-muted"><span x-text="i + 1"></span>. <span class="text-ink" x-text="item.content.judul"></span></span>
                            <span class="text-muted" x-text="item.durasi_detik + 's'"></span>
                        </li>
                    </template>
                    <li x-show="p.items.length === 0" class="text-muted px-3 py-2">Belum ada item.</li>
                </ol>
            </div>
        </template>

        <div x-show="playlists.length === 0" class="col-span-full text-center py-16 text-muted text-sm border border-dashed border-edge rounded-2xl">
            Belum ada playlist.
        </div>
    </div>

    <!-- Create playlist modal -->
    <div x-show="modalOpen" x-cloak class="fixed inset-0 bg-black/60 flex items-center justify-center z-40 p-4" @keydown.escape.window="modalOpen = false">
        <div class="bg-surface border border-edge rounded-2xl p-6 w-full max-w-sm" @click.outside="modalOpen = false">
            <h3 class="font-medium mb-4">Tambah Playlist</h3>
            <form @submit.prevent="save()" class="space-y-3">
                <div>
                    <label class="block text-xs font-mono text-muted mb-1.5">NAMA</label>
                    <input x-model="form.nama" required class="w-full bg-base border border-edge rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-wire/50">
                </div>
                <div>
                    <label class="block text-xs font-mono text-muted mb-1.5">DESKRIPSI</label>
                    <textarea x-model="form.deskripsi" rows="2" class="w-full bg-base border border-edge rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-wire/50"></textarea>
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="button" @click="modalOpen = false" class="flex-1 text-sm border border-edge rounded-lg py-2 hover:border-muted transition">Batal</button>
                    <button type="submit" class="flex-1 text-sm bg-wire hover:bg-wire/90 text-white rounded-lg py-2 transition">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Manage items modal -->
    <div x-show="itemsOpen" x-cloak class="fixed inset-0 bg-black/60 flex items-center justify-center z-40 p-4" @keydown.escape.window="itemsOpen = false">
        <div class="bg-surface border border-edge rounded-2xl p-6 w-full max-w-lg" @click.outside="itemsOpen = false">
            <h3 class="font-medium mb-1">Item playlist — <span x-text="activePlaylist?.nama"></span></h3>
            <p class="text-xs text-muted mb-4">Atur urutan tayang & durasi tiap konten (detik).</p>

            <div class="space-y-2 mb-4 max-h-64 overflow-y-auto">
                <template x-for="(item, i) in workingItems" :key="item.content_id + '-' + i">
                    <div class="flex items-center gap-2 bg-surface2 rounded-lg px-3 py-2">
                        <span class="font-mono text-xs text-muted w-5" x-text="i + 1"></span>
                        <span class="text-sm flex-1 truncate" x-text="contentTitle(item.content_id)"></span>
                        <input type="number" min="1" x-model.number="item.durasi_detik" class="w-16 bg-base border border-edge rounded px-2 py-1 text-xs font-mono">
                        <button @click="moveUp(i)" class="text-muted hover:text-ink px-1">↑</button>
                        <button @click="moveDown(i)" class="text-muted hover:text-ink px-1">↓</button>
                        <button @click="workingItems.splice(i, 1)" class="text-muted hover:text-alert px-1">✕</button>
                    </div>
                </template>
                <p x-show="workingItems.length === 0" class="text-xs text-muted text-center py-4">Belum ada item.</p>
            </div>

            <div class="flex gap-2 mb-4">
                <select x-model="addContentId" class="flex-1 bg-base border border-edge rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-wire/50">
                    <option value="">— Pilih konten —</option>
                    <template x-for="c in contents" :key="c.id">
                        <option :value="c.id" x-text="c.judul"></option>
                    </template>
                </select>
                <button @click="addItem()" class="text-sm px-4 border border-edge rounded-lg hover:border-signal/50 transition">Tambah</button>
            </div>

            <div class="flex gap-2">
                <button @click="itemsOpen = false" class="flex-1 text-sm border border-edge rounded-lg py-2 hover:border-muted transition">Batal</button>
                <button @click="saveItems()" class="flex-1 text-sm bg-wire hover:bg-wire/90 text-white rounded-lg py-2 transition">Simpan Urutan</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function playlistsPage() {
        return {
            playlists: [], contents: [],
            modalOpen: false, form: { nama: '', deskripsi: '' },
            itemsOpen: false, activePlaylist: null, workingItems: [], addContentId: '',

            async load() {
                [this.playlists, this.contents] = await Promise.all([api('/playlists'), api('/contents')]);
            },

            openCreate() {
                this.form = { nama: '', deskripsi: '' };
                this.modalOpen = true;
            },

            async save() {
                try {
                    await api('/playlists', { method: 'POST', body: JSON.stringify(this.form) });
                    this.modalOpen = false;
                    await this.load();
                } catch (e) {
                    alert(e.message);
                }
            },

            async removePlaylist(p) {
                if (!confirm('Hapus playlist "' + p.nama + '"?')) return;
                await api('/playlists/' + p.id, { method: 'DELETE' });
                this.playlists = this.playlists.filter(x => x.id !== p.id);
            },

            contentTitle(id) {
                return this.contents.find(c => c.id === id)?.judul || '—';
            },

            openEditItems(p) {
                this.activePlaylist = p;
                this.workingItems = p.items.map(i => ({ content_id: i.content.id, durasi_detik: i.durasi_detik }));
                this.itemsOpen = true;
            },

            addItem() {
                if (!this.addContentId) return;
                this.workingItems.push({ content_id: Number(this.addContentId), durasi_detik: 10 });
                this.addContentId = '';
            },

            moveUp(i) {
                if (i === 0) return;
                [this.workingItems[i - 1], this.workingItems[i]] = [this.workingItems[i], this.workingItems[i - 1]];
            },

            moveDown(i) {
                if (i === this.workingItems.length - 1) return;
                [this.workingItems[i + 1], this.workingItems[i]] = [this.workingItems[i], this.workingItems[i + 1]];
            },

            async saveItems() {
                try {
                    await api('/playlists/' + this.activePlaylist.id + '/items', {
                        method: 'PUT',
                        body: JSON.stringify({ items: this.workingItems }),
                    });
                    this.itemsOpen = false;
                    await this.load();
                } catch (e) {
                    alert(e.message);
                }
            },
        };
    }
</script>
@endpush
