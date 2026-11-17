<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
$is_edit = ($mode === 'edit');
$page_title = ($is_edit ? 'Edit' : 'Tambah') . ' Pelayanan';
include __DIR__ . '/../layouts/dashboard_layout.php';
?>

<div class="mb-5">
    <a href="/pelayanan" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold transition-all">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span> Kembali
    </a>
</div>

<div class="glass-card rounded-2xl p-6 sm:p-8 max-w-2xl">
    <div class="flex items-center gap-3 mb-6 pb-5 border-b border-slate-100">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-700 to-indigo-900 text-white flex items-center justify-center shadow-lg shadow-blue-700/25">
            <span class="material-symbols-outlined text-[24px]"><?= $is_edit ? 'edit' : 'add_circle' ?></span>
        </div>
        <div>
            <h2 class="text-xl font-extrabold text-slate-900"><?= $is_edit ? 'Edit' : 'Tambah' ?> Pelayanan</h2>
            <p class="text-xs text-slate-500"><?= $is_edit ? 'Ubah data layanan' : 'Isi data layanan baru' ?></p>
        </div>
    </div>

    <form method="POST" action="<?= $is_edit ? '/pelayanan?action=update&id=' . $pelayanan['id'] : '/pelayanan?action=simpan' ?>" class="space-y-5">
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Desa <span class="text-rose-500">*</span></label>
            <select name="desa_id" required <?= $role === 'desa' ? 'disabled' : '' ?> class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
                <option value="">-- Pilih Desa --</option>
                <?php foreach (($desa_list ?? []) as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= (isset($pelayanan['desa_id']) && $pelayanan['desa_id'] == $d['id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['nama']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($role === 'desa'): ?><input type="hidden" name="desa_id" value="<?= $user['desa_id'] ?? 1 ?>"><?php endif; ?>
        </div>
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Nama Layanan <span class="text-rose-500">*</span></label>
            <input type="text" name="nama" value="<?= htmlspecialchars($pelayanan['nama'] ?? '') ?>" placeholder="Contoh: Kartu Keluarga" required class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Deskripsi</label>
            <textarea name="deskripsi" rows="3" class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all"><?= htmlspecialchars($pelayanan['deskripsi'] ?? '') ?></textarea>
        </div>
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Syarat & Ketentuan</label>
            <textarea name="syarat" rows="3" class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all"><?= htmlspecialchars($pelayanan['syarat'] ?? '') ?></textarea>
        </div>
        <div class="flex gap-3 pt-5 border-t border-slate-100">
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-bold shadow-md transition-all"><span class="material-symbols-outlined text-[18px]">save</span> Simpan</button>
            <a href="/pelayanan" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold transition-all">Batal</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>