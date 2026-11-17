<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
$is_edit = ($action === 'edit');
$page_title = ($is_edit ? 'Edit ' : 'Tambah ') . ucfirst($type);
include __DIR__ . '/../layouts/dashboard_layout.php';
?>

<div class="mb-5">
    <a href="/wilayah?type=<?= $type ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold transition-all">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span> Kembali
    </a>
</div>

<div class="glass-card rounded-2xl p-6 sm:p-8 max-w-2xl">
    <div class="flex items-center gap-3 mb-6 pb-5 border-b border-slate-100">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-700 to-indigo-900 text-white flex items-center justify-center shadow-lg shadow-blue-700/25">
            <span class="material-symbols-outlined text-[24px]"><?= $is_edit ? 'edit' : 'add_circle' ?></span>
        </div>
        <div>
            <h2 class="text-xl font-extrabold text-slate-900"><?= $is_edit ? 'Edit' : 'Tambah' ?> <?= ucfirst($type) ?></h2>
            <p class="text-xs text-slate-500"><?= $is_edit ? 'Ubah data yang sudah ada' : 'Isi data baru' ?></p>
        </div>
    </div>

    <form method="POST" action="/wilayah?action=<?= $is_edit ? 'edit&id=' . $item['id'] : 'tambah' ?>&type=<?= $type ?>" class="space-y-5">
        
        <?php if ($type === 'desa'): ?>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Nama Desa <span class="text-rose-500">*</span></label>
                <input type="text" name="nama" value="<?= htmlspecialchars($item['nama'] ?? '') ?>" required class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Alamat</label>
                <input type="text" name="alamat" value="<?= htmlspecialchars($item['alamat'] ?? '') ?>" class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Telepon</label>
                <input type="text" name="telepon" value="<?= htmlspecialchars($item['telepon'] ?? '') ?>" class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
            </div>
        <?php elseif ($type === 'rw'): ?>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Desa <span class="text-rose-500">*</span></label>
                <select name="desa_id" required class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
                    <option value="">-- Pilih Desa --</option>
                    <?php foreach (($desa_list ?? []) as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= (isset($item['desa_id']) && $item['desa_id'] == $d['id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Nomor RW <span class="text-rose-500">*</span></label>
                <input type="text" name="nomor" value="<?= htmlspecialchars($item['nomor'] ?? '') ?>" placeholder="Contoh: 01" required class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Nama Ketua RW</label>
                <input type="text" name="ketua" value="<?= htmlspecialchars($item['ketua'] ?? '') ?>" class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
            </div>
        <?php else: ?>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">RW <span class="text-rose-500">*</span></label>
                <select name="rw_id" required class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
                    <option value="">-- Pilih RW --</option>
                    <?php foreach (($rw_list ?? []) as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= (isset($item['rw_id']) && $item['rw_id'] == $r['id']) ? 'selected' : '' ?>>RW <?= htmlspecialchars($r['nomor']) ?> - <?= htmlspecialchars($r['desa_nama'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Nomor RT <span class="text-rose-500">*</span></label>
                <input type="text" name="nomor" value="<?= htmlspecialchars($item['nomor'] ?? '') ?>" placeholder="Contoh: 01" required class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Nama Ketua RT</label>
                <input type="text" name="ketua" value="<?= htmlspecialchars($item['ketua'] ?? '') ?>" class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
            </div>
        <?php endif; ?>

        <div class="flex gap-3 pt-5 border-t border-slate-100">
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-bold shadow-md shadow-blue-700/25 transition-all">
                <span class="material-symbols-outlined text-[18px]">save</span> <?= $is_edit ? 'Update' : 'Simpan' ?>
            </button>
            <a href="/wilayah?type=<?= $type ?>" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold transition-all">
                Batal
            </a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>