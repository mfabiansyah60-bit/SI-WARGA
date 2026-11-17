<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
$page_title = 'Pengaduan';
include __DIR__ . '/../layouts/dashboard_layout.php';

$status_meta = [
    'pending' => ['label' => 'Menunggu', 'icon' => 'schedule',    'class' => 'bg-amber-100 text-amber-700 border-amber-200'],
    'proses'  => ['label' => 'Diproses', 'icon' => 'autorenew',   'class' => 'bg-blue-100 text-blue-700 border-blue-200'],
    'selesai' => ['label' => 'Selesai',  'icon' => 'check_circle','class' => 'bg-emerald-100 text-emerald-700 border-emerald-200'],
    'ditolak' => ['label' => 'Ditolak',  'icon' => 'cancel',      'class' => 'bg-rose-100 text-rose-700 border-rose-200'],
];

$prioritas_meta = [
    'rendah'  => ['label' => 'Rendah',  'icon' => '🟢', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
    'sedang'  => ['label' => 'Sedang',  'icon' => '🟡', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
    'tinggi'  => ['label' => 'Tinggi',  'icon' => '🟠', 'class' => 'bg-orange-50 text-orange-700 border-orange-200'],
    'darurat' => ['label' => 'Darurat', 'icon' => '🔴', 'class' => 'bg-rose-50 text-rose-700 border-rose-200'],
];

$isPetugas = in_array($role, ['super_admin', 'desa', 'rt', 'rw']);

// Hitung stats
$stats = ['total' => count($pengaduan_list ?? []), 'pending' => 0, 'proses' => 0, 'selesai' => 0, 'ditolak' => 0];
foreach (($pengaduan_list ?? []) as $p) {
    $s = $p['status'] ?? 'pending';
    if (isset($stats[$s])) $stats[$s]++;
}
?>

<!-- ============ HEADER ============ -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 flex items-center gap-2">
            <span class="material-symbols-outlined text-blue-600">campaign</span>
            Daftar Pengaduan
        </h1>
        <p class="text-xs text-slate-500 mt-1">Kelola semua pengaduan warga di sini</p>
    </div>
    <?php if ($role === 'warga'): ?>
        <a href="/pengaduan?action=buat" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 text-white text-sm font-extrabold shadow-lg shadow-blue-700/25 transition-all">
            <span class="material-symbols-outlined text-[18px]">add_circle</span> Buat Pengaduan
        </a>
    <?php endif; ?>
</div>

<!-- ============ STATS CARDS ============ -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <div class="glass-card rounded-2xl p-4 border border-slate-200/70">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-600 to-slate-800 text-white flex items-center justify-center shadow-md">
                <span class="material-symbols-outlined text-[20px]">inventory_2</span>
            </div>
            <div>
                <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Total</div>
                <div class="text-xl font-extrabold text-slate-900"><?= $stats['total'] ?></div>
            </div>
        </div>
    </div>
    <div class="glass-card rounded-2xl p-4 border border-slate-200/70">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 text-white flex items-center justify-center shadow-md">
                <span class="material-symbols-outlined text-[20px]">schedule</span>
            </div>
            <div>
                <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Menunggu</div>
                <div class="text-xl font-extrabold text-slate-900"><?= $stats['pending'] ?></div>
            </div>
        </div>
    </div>
    <div class="glass-card rounded-2xl p-4 border border-slate-200/70">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center shadow-md">
                <span class="material-symbols-outlined text-[20px]">autorenew</span>
            </div>
            <div>
                <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Diproses</div>
                <div class="text-xl font-extrabold text-slate-900"><?= $stats['proses'] ?></div>
            </div>
        </div>
    </div>
    <div class="glass-card rounded-2xl p-4 border border-slate-200/70">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center shadow-md">
                <span class="material-symbols-outlined text-[20px]">check_circle</span>
            </div>
            <div>
                <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Selesai</div>
                <div class="text-xl font-extrabold text-slate-900"><?= $stats['selesai'] ?></div>
            </div>
        </div>
    </div>
</div>

<!-- ============ FILTER TABS ============ -->
<div class="flex flex-wrap gap-2 mb-4" id="filterTabs">
    <button data-filter="all" class="filter-btn active inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-bold shadow-md transition-all">
        <span class="material-symbols-outlined text-[16px]">list</span> Semua <span class="opacity-70">(<?= $stats['total'] ?>)</span>
    </button>
    <button data-filter="pending" class="filter-btn inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition-all">
        ⏳ Menunggu <span class="opacity-70">(<?= $stats['pending'] ?>)</span>
    </button>
    <button data-filter="proses" class="filter-btn inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition-all">
        🔄 Diproses <span class="opacity-70">(<?= $stats['proses'] ?>)</span>
    </button>
    <button data-filter="selesai" class="filter-btn inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition-all">
        ✅ Selesai <span class="opacity-70">(<?= $stats['selesai'] ?>)</span>
    </button>
    <button data-filter="ditolak" class="filter-btn inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition-all">
        ❌ Ditolak <span class="opacity-70">(<?= $stats['ditolak'] ?>)</span>
    </button>
</div>

<!-- ============ LIST ============ -->
<div class="glass-card rounded-3xl overflow-hidden border border-slate-200/70">
    <?php if (empty($pengaduan_list)): ?>
        <div class="p-16 text-center">
            <div class="w-20 h-20 mx-auto rounded-3xl bg-slate-100 flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-[40px] text-slate-400">campaign</span>
            </div>
            <h3 class="text-lg font-extrabold text-slate-800 mb-1">Belum Ada Pengaduan</h3>
            <p class="text-xs text-slate-500"><?= $role === 'warga' ? 'Klik "Buat Pengaduan" untuk mulai melapor' : 'Pengaduan warga akan muncul di sini' ?></p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px]">
                <thead class="bg-slate-50/80 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-5 py-4 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 w-12">#</th>
                        <th class="text-left px-5 py-4 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">Pengaduan</th>
                        <th class="text-left px-5 py-4 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">Kategori</th>
                        <?php if ($role !== 'warga'): ?>
                            <th class="text-left px-5 py-4 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">Pelapor</th>
                        <?php endif; ?>
                        <th class="text-left px-5 py-4 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">Prioritas</th>
                        <th class="text-left px-5 py-4 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="text-left px-5 py-4 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">Tanggal</th>
                        <th class="text-right px-5 py-4 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 w-32">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pengaduan_list as $i => $p): 
                        $sc   = $status_meta[$p['status'] ?? 'pending'] ?? $status_meta['pending'];
                        $pr   = $prioritas_meta[$p['prioritas'] ?? 'sedang'] ?? $prioritas_meta['sedang'];
                        $adaBuktiPelapor = !empty($p['bukti_gambar']) || !empty($p['bukti_gambar_2']);
                        $adaPenanganan   = !empty($p['bukti_tanggapan_1']) || !empty($p['bukti_tanggapan_2']);
                        $isAnon = !empty($p['is_anonymous']);
                    ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50/70 transition-colors pengaduan-row" data-status="<?= htmlspecialchars($p['status'] ?? 'pending') ?>">
                            <td class="px-5 py-4 text-sm font-bold text-slate-400"><?= $i + 1 ?></td>
                            <td class="px-5 py-4">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0 w-9 h-9 rounded-xl bg-gradient-to-br <?= $pr['icon'] === '🔴' ? 'from-rose-500 to-red-700' : 'from-slate-600 to-slate-800' ?> text-white flex items-center justify-center shadow-md">
                                        <span class="material-symbols-outlined text-[18px]">campaign</span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-sm font-bold text-slate-900 truncate max-w-xs"><?= htmlspecialchars($p['judul']) ?></div>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <?php if ($adaBuktiPelapor): ?>
                                                <span class="inline-flex items-center gap-0.5 text-[10px] font-bold text-rose-600" title="Ada bukti dari pelapor">
                                                    <span class="material-symbols-outlined text-[12px]">photo_camera</span> Bukti
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($adaPenanganan): ?>
                                                <span class="inline-flex items-center gap-0.5 text-[10px] font-bold text-emerald-600" title="Sudah ditangani">
                                                    <span class="material-symbols-outlined text-[12px]">verified</span> Ditangani
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($isAnon): ?>
                                                <span class="text-[10px] font-bold text-slate-400">🕵️ Anon</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 text-[11px] font-bold capitalize"><?= htmlspecialchars($p['kategori'] ?? 'umum') ?></span>
                            </td>
                            <?php if ($role !== 'warga'): ?>
                                <td class="px-5 py-4 text-sm text-slate-700 font-medium">
                                    <?= $isAnon ? '<span class="text-slate-400 italic">🕵️ Anonymous</span>' : htmlspecialchars($p['user_name'] ?? '-') ?>
                                </td>
                            <?php endif; ?>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg <?= $pr['class'] ?> border text-[11px] font-bold">
                                    <?= $pr['icon'] ?> <?= $pr['label'] ?>
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg <?= $sc['class'] ?> border text-[11px] font-bold uppercase">
                                    <span class="material-symbols-outlined text-[13px]"><?= $sc['icon'] ?></span>
                                    <?= $sc['label'] ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-500 font-medium">
                                <?= date('d M Y', strtotime($p['created_at'])) ?>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="/pengaduan?action=detail&id=<?= $p['id'] ?>" title="Lihat Detail" class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-blue-100 text-blue-700 hover:bg-blue-600 hover:text-white transition-all">
                                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                                    </a>
                                    <?php if ($isPetugas): ?>
                                        <button type="button" 
                                                onclick="confirmDelete(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['judul'])) ?>')"
                                                title="Hapus Pengaduan" 
                                                class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-rose-100 text-rose-700 hover:bg-rose-600 hover:text-white transition-all">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Empty state for filter -->
        <div id="emptyFilter" class="hidden p-12 text-center">
            <span class="material-symbols-outlined text-[48px] text-slate-300">search_off</span>
            <h3 class="text-slate-600 font-bold mt-2">Tidak ada data di kategori ini</h3>
        </div>
    <?php endif; ?>
</div>

<!-- ============ MODAL KONFIRMASI HAPUS ============ -->
<div id="deleteModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 sm:p-8 animate-in">
        <div class="flex items-start gap-4 mb-5">
            <div class="flex-shrink-0 w-14 h-14 rounded-2xl bg-gradient-to-br from-rose-500 to-red-700 text-white flex items-center justify-center shadow-lg shadow-rose-700/30">
                <span class="material-symbols-outlined text-[28px]">warning</span>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-extrabold text-slate-900 mb-1">Hapus Pengaduan?</h3>
                <p class="text-xs text-slate-500 leading-relaxed">Tindakan ini tidak dapat dibatalkan. Semua data & bukti gambar akan ikut terhapus.</p>
            </div>
        </div>

        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-100 mb-6">
            <div class="text-[10px] font-extrabold uppercase tracking-wider text-rose-600 mb-1">Judul Pengaduan</div>
            <div id="deleteJudul" class="text-sm font-bold text-rose-900 break-words">-</div>
        </div>

        <div class="flex gap-3">
            <button onclick="closeDeleteModal()" class="flex-1 px-5 py-3 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold transition-all">
                Batal
            </button>
            <a id="deleteConfirmBtn" href="#" class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-gradient-to-r from-rose-600 to-red-700 hover:from-rose-700 hover:to-red-800 text-white text-sm font-extrabold shadow-lg shadow-rose-700/25 transition-all">
                <span class="material-symbols-outlined text-[18px]">delete_forever</span>
                Ya, Hapus
            </a>
        </div>
    </div>
</div>

<style>
    @keyframes modalIn {
        from { opacity: 0; transform: scale(0.95) translateY(10px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }
    .animate-in { animation: modalIn 0.2s ease-out; }
    .filter-btn.active { background: #2563eb !important; color: white !important; border-color: #2563eb !important; }
</style>

<script>
// Filter tabs
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(b => {
            b.classList.remove('active', 'bg-blue-600', 'text-white', 'shadow-md');
            b.classList.add('bg-white', 'border', 'border-slate-200', 'text-slate-700');
        });
        btn.classList.add('active', 'bg-blue-600', 'text-white', 'shadow-md');
        btn.classList.remove('bg-white', 'border', 'border-slate-200', 'text-slate-700');

        const filter = btn.dataset.filter;
        let visible = 0;
        document.querySelectorAll('.pengaduan-row').forEach(row => {
            const match = filter === 'all' || row.dataset.status === filter;
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        const emptyEl = document.getElementById('emptyFilter');
        if (emptyEl) emptyEl.classList.toggle('hidden', visible > 0);
    });
});

// Delete modal
function confirmDelete(id, judul) {
    document.getElementById('deleteJudul').textContent = judul;
    document.getElementById('deleteConfirmBtn').href = '/pengaduan?action=hapus&id=' + id;
    const modal = document.getElementById('deleteModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
document.getElementById('deleteModal')?.addEventListener('click', e => {
    if (e.target.id === 'deleteModal') closeDeleteModal();
});
</script>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>