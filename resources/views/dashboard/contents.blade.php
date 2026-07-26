@extends('layouts.app')

@section('content')
<div x-data="contentsPage()" x-init="load()" class="p-8 max-w-6xl">

    <div class="flex items-start justify-between mb-8">
        <div>
            <h1 class="text-xl font-semibold tracking-tight">Contents</h1>
            <p class="text-sm text-muted mt-1">Gambar, video, halaman web, atau teks yang bisa ditayangkan di device.</p>
        </div>
        <button @click="openCreate()" class="bg-wire hover:bg-wire/90 text-white text-sm font-medium rounded-lg px-4 py-2.5 transition">
            + Tambah Content
        </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" x-cloak>
        <template x-for="c in contents" :key="c.id">
            <div class="bg-surface border border-edge rounded-2xl overflow-hidden">
                <div class="aspect-video bg-surface2 flex items-center justify-center relative">
                    <template x-if="c.tipe === 'image'">
                        <img :src="c.url" class="w-full h-full object-cover">
                    </template>
                    <template x-if="c.tipe === 'video'">
                        <video :src="c.url" class="w-full h-full object-cover" muted></video>
                    </template>
                    <template x-if="c.tipe === 'url'">
                        <div class="text-center px-4">
                            <p class="text-2xl">🔗</p>
                            <p class="text-[10px] font-mono text-muted mt-1 truncate max-w-[200px]" x-text="c.payload"></p>
                        </div>
                    </template>
                    <template x-if="c.tipe === 'text'">
                        <p class="text-xs px-4 text-center text-muted line-clamp-4" x-text="c.payload"></p>
                    </template>
                    <span class="absolute top-2 right-2 text-[10px] font-mono uppercase bg-base/80 border border-edge rounded px-1.5 py-0.5 text-muted" x-text="c.tipe"></span>
                </div>
                <div class="p-4 flex items-center justify-between gap-2">
                    <h3 class="text-sm font-medium truncate" x-text="c.judul"></h3>
                    <div class="flex gap-1.5 shrink-0">
                        <button @click="openEdit(c)" class="text-xs px-2 py-1 rounded-lg border border-edge hover:border-wire/50 text-muted hover:text-ink transition">Edit</button>
                        <button @click="remove(c)" class="text-xs px-2 py-1 rounded-lg border border-edge hover:border-alert/50 text-muted hover:text-alert transition">Hapus</button>
                    </div>
                </div>
            </div>
        </template>

        <div x-show="contents.length === 0" class="col-span-full text-center py-16 text-muted text-sm border border-dashed border-edge rounded-2xl">
            Belum ada konten. Tambahkan gambar, video, URL, atau teks.
        </div>
    </div>

    <!-- Create/Edit modal -->
    <div x-show="modalOpen" x-cloak class="fixed inset-0 bg-black/60 flex items-center justify-center z-40 p-4" @keydown.escape.window="modalOpen = false">
        <div class="bg-surface border border-edge rounded-2xl p-6 w-full max-w-sm" @click.outside="modalOpen = false">
            <h3 class="font-medium mb-4" x-text="form.id ? 'Edit Content' : 'Tambah Content'"></h3>
            <form @submit.prevent="save()" class="space-y-3">
                <div>
                    <label class="block text-xs font-mono text-muted mb-1.5">JUDUL</label>
                    <input x-model="form.judul" required class="w-full bg-base border border-edge rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-wire/50">
                </div>
                <div>
                    <label class="block text-xs font-mono text-muted mb-1.5">TIPE</label>
                    <select x-model="form.tipe" class="w-full bg-base border border-edge rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-wire/50">
                        <option value="image">Gambar</option>
                        <option value="video">Video</option>
                        <option value="url">URL / Halaman web</option>
                        <option value="text">Teks</option>
                    </select>
                </div>
                <template x-if="form.tipe === 'image' || form.tipe === 'video'">
                    <div>
                        <label class="block text-xs font-mono text-muted mb-1.5">FILE</label>
                        <input type="file" @change="form.file = $event.target.files[0]" :accept="form.tipe === 'image' ? 'image/*' : 'video/*'"
                               class="w-full text-xs text-muted file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border file:border-edge file:bg-base file:text-ink file:text-xs">
                        <p class="text-[11px] text-muted mt-1" x-show="form.id">Kosongkan jika tidak ingin mengganti file.</p>
                    </div>
                </template>
                <template x-if="form.tipe === 'url'">
                    <div>
                        <label class="block text-xs font-mono text-muted mb-1.5">URL</label>
                        <input x-model="form.payload" placeholder="https://…" class="w-full bg-base border border-edge rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-wire/50">
                    </div>
                </template>
                <template x-if="form.tipe === 'text'">
                    <div>
                        <label class="block text-xs font-mono text-muted mb-1.5">TEKS</label>
                        <textarea x-model="form.payload" rows="4" class="w-full bg-base border border-edge rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-wire/50"></textarea>
                    </div>
                </template>
                <div class="flex gap-2 pt-2">
                    <button type="button" @click="modalOpen = false" class="flex-1 text-sm border border-edge rounded-lg py-2 hover:border-muted transition">Batal</button>
                    <button type="submit" :disabled="saving" class="flex-1 text-sm bg-wire hover:bg-wire/90 disabled:opacity-60 text-white rounded-lg py-2 transition">
                        <span x-text="saving ? 'Menyimpan…' : 'Simpan'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function contentsPage() {
        return {
            contents: [], modalOpen: false, saving: false,
            form: { id: null, judul: '', tipe: 'image', payload: '', file: null },

            async load() {
                this.contents = await api('/contents');
            },

            openCreate() {
                this.form = { id: null, judul: '', tipe: 'image', payload: '', file: null };
                this.modalOpen = true;
            },

            openEdit(c) {
                this.form = { id: c.id, judul: c.judul, tipe: c.tipe, payload: c.tipe === 'url' || c.tipe === 'text' ? c.payload : '', file: null };
                this.modalOpen = true;
            },

            async save() {
                this.saving = true;
                try {
                    const fd = new FormData();
                    fd.append('judul', this.form.judul);
                    fd.append('tipe', this.form.tipe);
                    if (this.form.file) fd.append('file', this.form.file);
                    if (this.form.payload) fd.append('payload', this.form.payload);

                    if (this.form.id) {
                        fd.append('_method', 'PUT');
                        await api('/contents/' + this.form.id, { method: 'POST', body: fd });
                    } else {
                        await api('/contents', { method: 'POST', body: fd });
                    }
                    this.modalOpen = false;
                    await this.load();
                } catch (e) {
                    alert(e.message);
                } finally {
                    this.saving = false;
                }
            },

            async remove(c) {
                if (!confirm('Hapus content "' + c.judul + '"?')) return;
                await api('/contents/' + c.id, { method: 'DELETE' });
                this.contents = this.contents.filter(x => x.id !== c.id);
            },
        };
    }
</script>
@endpush
