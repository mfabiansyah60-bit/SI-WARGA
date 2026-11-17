<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user'])) { header('Location: /login'); exit; }

$is_edit = ($mode === 'edit');
$page_title = ($is_edit ? 'Edit ' : 'Tambah ') . strtoupper($type);
include __DIR__ . '/../layouts/dashboard_layout.php';
?>

<!-- TOMBOL KEMBALI -->
<div class="mb-5">
    <a href="/rt_rw?type=<?= $type ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/80 hover:bg-slate-100 border border-slate-200/80 text-slate-700 text-sm font-bold shadow-sm transition-all group">
        <span class="material-symbols-outlined text-[18px] group-hover:-translate-x-0.5 transition-transform">arrow_back</span>
        Kembali ke Data <?= strtoupper($type) ?>
    </a>
</div>

<!-- FORM CARD -->
<div class="glass-card rounded-2xl p-6 sm:p-8 max-w-2xl bg-white border border-slate-200/80 shadow-sm">

    <!-- HEADER CARD -->
    <div class="flex items-center gap-4 mb-6 pb-6 border-b border-slate-100">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 text-white flex items-center justify-center shadow-lg shadow-blue-700/25 flex-shrink-0">
            <span class="material-symbols-outlined text-[26px]"><?= $type === 'rw' ? 'holiday_village' : 'home' ?></span>
        </div>
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">
                    <?= $is_edit ? 'Edit ' . strtoupper($type) : 'Tambah ' . strtoupper($type) . ' Baru' ?>
                </h2>
                <?php if ($is_edit && isset($item['id'])): ?>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-100">ID #<?= htmlspecialchars($item['id']) ?></span>
                <?php endif; ?>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">
                <?= $is_edit ? 'Ubah informasi dan ketua wilayah ' . strtoupper($type) : 'Daftarkan wilayah ' . strtoupper($type) . ' baru ke dalam sistem desa' ?>
            </p>
        </div>
    </div>

    <!-- FORM -->
    <form method="POST" action="/rt_rw?action=<?= $is_edit ? 'edit&id=' . ($item['id'] ?? '') : 'tambah' ?>&type=<?= $type ?>" class="space-y-5">

        <?php if ($type === 'rw'): ?>

            <!-- FORM RW -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Desa <span class="text-rose-500">*</span></label>
                <div class="relative">
                    <select name="desa_id" required class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all font-medium appearance-none pr-10">
                        <option value="">-- Pilih Desa --</option>
                        <?php foreach (($desa_list ?? []) as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= (isset($item['desa_id']) && $item['desa_id'] == $d['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="material-symbols-outlined absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[20px] pointer-events-none">expand_more</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Nomor RW <span class="text-rose-500">*</span></label>
                <input type="text" name="nomor" 
                       value="<?= htmlspecialchars($item['nomor'] ?? '') ?>"
                       placeholder="Contoh: 01" required
                       class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all font-medium">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Nama Ketua RW</label>
                <input type="text" name="ketua" 
                       value="<?= htmlspecialchars($item['ketua'] ?? '') ?>"
                       placeholder="Masukkan nama ketua RW"
                       class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all font-medium">
            </div>

            <!-- TAMBAHAN FORM EMAIL RW -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Email Ketua RW</label>
                <input type="email" name="email" 
                       value="<?= htmlspecialchars($item['email'] ?? '') ?>"
                       placeholder="email@gmail.com"
                       class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all font-medium">
            </div>

            <!-- TAMBAHAN FORM NO TELP RW -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">No. Telepon Ketua RW</label>
                <input type="text" name="no_telp" 
                       value="<?= htmlspecialchars($item['no_telp'] ?? '') ?>"
                       placeholder="08xxxxxxxxxx"
                       class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all font-medium">
            </div>

        <?php else: ?>

            <!-- FORM RT -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Induk RW <span class="text-rose-500">*</span></label>
                <div class="relative">
                    <select name="rw_id" required class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all font-medium appearance-none pr-10">
                        <option value="">-- Pilih RW --</option>
                        <?php foreach (($rw_list ?? []) as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= (isset($item['rw_id']) && $item['rw_id'] == $r['id']) ? 'selected' : '' ?>>
                                RW <?= htmlspecialchars($r['nomor']) ?> - <?= htmlspecialchars($r['desa_nama'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="material-symbols-outlined absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[20px] pointer-events-none">expand_more</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Nomor RT <span class="text-rose-500">*</span></label>
                <input type="text" name="nomor" 
                       value="<?= htmlspecialchars($item['nomor'] ?? '') ?>"
                       placeholder="Contoh: 01" required
                       class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all font-medium">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Nama Ketua RT</label>
                <input type="text" name="ketua" 
                       value="<?= htmlspecialchars($item['ketua'] ?? '') ?>"
                       placeholder="Masukkan nama ketua RT"
                       class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all font-medium">
            </div>

            <!-- TAMBAHAN FORM EMAIL RT -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Email Ketua RT</label>
                <input type="email" name="email" 
                       value="<?= htmlspecialchars($item['email'] ?? '') ?>"
                       placeholder="email@gmail.com"
                       class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all font-medium">
            </div>

            <!-- TAMBAHAN FORM NO TELP RT -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">No. Telepon Ketua RT</label>
                <input type="text" name="no_telp" 
                       value="<?= htmlspecialchars($item['no_telp'] ?? '') ?>"
                       placeholder="08xxxxxxxxxx"
                       class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all font-medium">
            </div>

        <?php endif; ?>

        <!-- TOMBOL AKSI -->
        <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-bold shadow-md shadow-blue-700/25 hover:shadow-lg transition-all">
                <span class="material-symbols-outlined text-[18px]">save</span> 
                <?= $is_edit ? 'Simpan Perubahan' : 'Tambah ' . strtoupper($type) ?>
            </button>
            <a href="/rt_rw?type=<?= $type ?>" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold transition-all">
                Batal
            </a>
        </div>

    </form>
</div>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>