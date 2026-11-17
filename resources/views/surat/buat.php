<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
$page_title = 'Isi Data Surat';
include __DIR__ . '/../layouts/dashboard_layout.php';

$isAi = isset($template['_mode']) && $template['_mode'] === 'ai';
$aiData = $isAi ? ($template['_data'] ?? []) : [];
$formAction = $isAi ? '/surat?action=update_ai' : '/surat?action=generate';
$v = function($key, $default = '') use ($aiData) {
    return htmlspecialchars($aiData[$key] ?? $default);
};
?>

<div class="mb-5">
    <a href="/surat" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold transition-all">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span> Kembali
    </a>
</div>

<div class="glass-card rounded-2xl p-6 sm:p-8 max-w-3xl">
    <div class="flex items-center gap-3 mb-6 pb-5 border-b border-slate-100">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-700 to-indigo-900 text-white flex items-center justify-center shadow-lg shadow-blue-700/25">
            <span class="material-symbols-outlined text-[24px]">edit_note</span>
        </div>
        <div>
            <h2 class="text-xl font-extrabold text-slate-900">📝 <?= htmlspecialchars($template['nama']) ?></h2>
            <p class="text-xs text-slate-500">
                <?= $isAi ? 'Data sudah terisi otomatis dari AI. Edit jika perlu.' : 'Isi data berikut untuk membuat surat' ?>
            </p>
        </div>
    </div>

    <?php if (!empty($_SESSION['flash'])): ?>
        <div class="mb-4 p-3 rounded-xl <?= $_SESSION['flash']['type'] === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' ?> text-sm">
            <?= htmlspecialchars($_SESSION['flash']['message']) ?>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <form method="POST" action="<?= $formAction ?>" class="space-y-5">
        <?php if ($isAi): ?>
            <input type="hidden" name="ai_id" value="<?= (int)$template['_ai_id'] ?>">
        <?php else: ?>
            <input type="hidden" name="template_id" value="<?= $template['id'] ?>">
        <?php endif; ?>

        <h3 class="text-xs font-bold uppercase tracking-wider text-blue-700 pb-2 border-b border-blue-100">👤 Data Pribadi</h3>
        
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Nama Lengkap *</label>
            <input type="text" name="nama" value="<?= $isAi ? $v('nama', $user['name']) : htmlspecialchars($user['name']) ?>" required class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">NIK *</label>
                <input type="text" name="nik" maxlength="16" required placeholder="16 digit" value="<?= $v('nik') ?>" class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Tempat/Tgl Lahir *</label>
                <input type="text" name="ttl" required placeholder="Cirebon, 01-01-1990" value="<?= $v('ttl') ?>" class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Jenis Kelamin</label>
                <select name="jk" class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
                    <option value="Laki-laki" <?= ($aiData['jk'] ?? '') === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                    <option value="Perempuan" <?= ($aiData['jk'] ?? '') === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Pekerjaan</label>
                <input type="text" name="pekerjaan" placeholder="Karyawan" value="<?= $v('pekerjaan') ?>" class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">RT</label>
                <input type="text" name="rt" placeholder="03" value="<?= $v('rt', $user['rt'] ?? '') ?>" class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">RW</label>
                <input type="text" name="rw" placeholder="05" value="<?= $v('rw', $user['rw'] ?? '') ?>" class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
            </div>
        </div>
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Alamat Lengkap *</label>
            <textarea name="alamat" rows="2" required class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all"><?= $v('alamat') ?></textarea>
        </div>

        <h3 class="text-xs font-bold uppercase tracking-wider text-blue-700 pb-2 border-b border-blue-100 pt-3">📋 Keperluan Surat</h3>
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Keperluan *</label>
            <input type="text" name="keperluan" required placeholder="Contoh: Syarat melamar kerja" value="<?= $v('keperluan') ?>" class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
        </div>

        <div class="flex gap-3 pt-5 border-t border-slate-100">
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-bold shadow-md transition-all">
                <span class="material-symbols-outlined text-[18px]"><?= $isAi ? 'save' : 'picture_as_pdf' ?></span>
                <?= $isAi ? 'Simpan & Lihat Surat' : 'Buat Surat & Download PDF' ?>
            </button>
            <a href="<?= $isAi ? '/surat?action=cetak&id=' . (int)$template['_ai_id'] : '/surat' ?>" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold transition-all">
                <?= $isAi ? 'Lihat Surat' : 'Batal' ?>
            </a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>