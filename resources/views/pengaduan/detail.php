<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
$page_title = 'Detail Pengaduan';
include __DIR__ . '/../layouts/dashboard_layout.php';

$prioritas_meta = [
    'rendah'  => ['label' => 'Rendah',  'icon' => '🟢', 'bg' => 'from-emerald-500 to-teal-600',  'text' => 'text-emerald-700', 'soft' => 'bg-emerald-50 border-emerald-200'],
    'sedang'  => ['label' => 'Sedang',  'icon' => '🟡', 'bg' => 'from-amber-500 to-orange-600',  'text' => 'text-amber-700',   'soft' => 'bg-amber-50 border-amber-200'],
    'tinggi'  => ['label' => 'Tinggi',  'icon' => '🟠', 'bg' => 'from-orange-500 to-red-600',    'text' => 'text-orange-700',  'soft' => 'bg-orange-50 border-orange-200'],
    'darurat' => ['label' => 'Darurat', 'icon' => '🔴', 'bg' => 'from-rose-600 to-red-700',      'text' => 'text-rose-700',    'soft' => 'bg-rose-50 border-rose-200'],
];
$pm = $prioritas_meta[$detail['prioritas'] ?? 'sedang'] ?? $prioritas_meta['sedang'];

$status_meta = [
    'pending' => ['label' => 'Menunggu',  'icon' => 'schedule',    'class' => 'bg-amber-100 text-amber-700 border-amber-200'],
    'proses'  => ['label' => 'Diproses',  'icon' => 'autorenew',   'class' => 'bg-blue-100 text-blue-700 border-blue-200'],
    'selesai' => ['label' => 'Selesai',   'icon' => 'check_circle','class' => 'bg-emerald-100 text-emerald-700 border-emerald-200'],
    'ditolak' => ['label' => 'Ditolak',   'icon' => 'cancel',      'class' => 'bg-rose-100 text-rose-700 border-rose-200'],
];
$sm = $status_meta[$detail['status'] ?? 'pending'] ?? $status_meta['pending'];

$isAnon  = !empty($detail['is_anonymous']);
$pelapor = $isAnon ? '🕵️ Anonymous' : ($detail['user_name'] ?? '-');
$adaBuktiPelapor = !empty($detail['bukti_gambar']) || !empty($detail['bukti_gambar_2']);
$adaPenanganan   = !empty($detail['tanggapan']) || !empty($detail['bukti_tanggapan_1']) || !empty($detail['bukti_tanggapan_2']);
$isPetugas = in_array($role, ['super_admin', 'desa', 'rt', 'rw']);
?>

<!-- HEADER BACK -->
<div class="mb-5 flex items-center justify-between flex-wrap gap-3">
    <a href="/pengaduan" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 text-sm font-bold border border-slate-200 shadow-sm transition-all">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span> Kembali ke Daftar
    </a>
    <span class="text-xs text-slate-400">ID Pengaduan: #<?= htmlspecialchars($detail['id'] ?? '-') ?></span>
</div>

<!-- ============ HERO CARD ============ -->
<div class="glass-card rounded-3xl overflow-hidden mb-5 border border-slate-200/70">
    <!-- Top gradient strip -->
    <div class="h-1.5 bg-gradient-to-r <?= $pm['bg'] ?>"></div>
    
    <div class="p-6 sm:p-8">
        <div class="flex items-start justify-between gap-4 flex-wrap mb-5">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full <?= $pm['soft'] ?> <?= $pm['text'] ?> border text-[11px] font-extrabold uppercase tracking-wide">
                        <span><?= $pm['icon'] ?></span> Prioritas <?= $pm['label'] ?>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full <?= $sm['class'] ?> border text-[11px] font-extrabold uppercase tracking-wide">
                        <span class="material-symbols-outlined text-[13px]"><?= $sm['icon'] ?></span>
                        <?= $sm['label'] ?>
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 leading-tight"><?= htmlspecialchars($detail['judul'] ?? '-') ?></h1>
            </div>
        </div>

        <!-- Meta info grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5">
            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100">
                <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase text-slate-400 mb-1">
                    <span class="material-symbols-outlined text-[14px]">person</span> Pelapor
                </div>
                <div class="text-sm font-bold text-slate-800 truncate"><?= htmlspecialchars($pelapor) ?></div>
            </div>
            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100">
                <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase text-slate-400 mb-1">
                    <span class="material-symbols-outlined text-[14px]">tag</span> Kategori
                </div>
                <div class="text-sm font-bold text-slate-800 capitalize truncate"><?= htmlspecialchars($detail['kategori'] ?? 'umum') ?></div>
            </div>
            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100">
                <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase text-slate-400 mb-1">
                    <span class="material-symbols-outlined text-[14px]">location_on</span> Lokasi
                </div>
                <div class="text-sm font-bold text-slate-800 truncate"><?= !empty($detail['lokasi']) ? htmlspecialchars($detail['lokasi']) : '-' ?></div>
            </div>
            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100">
                <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase text-slate-400 mb-1">
                    <span class="material-symbols-outlined text-[14px]">schedule</span> Tanggal
                </div>
                <div class="text-sm font-bold text-slate-800 truncate"><?= !empty($detail['created_at']) ? date('d M Y H:i', strtotime($detail['created_at'])) : '-' ?></div>
            </div>
        </div>
    </div>
</div>

<!-- ============ ISI PENGADUAN ============ -->
<div class="glass-card rounded-3xl p-6 sm:p-8 mb-5 border border-slate-200/70">
    <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-slate-700 to-slate-900 text-white flex items-center justify-center shadow-lg shadow-slate-900/20">
            <span class="material-symbols-outlined text-[20px]">description</span>
        </div>
        <div>
            <h3 class="text-base font-extrabold text-slate-900">Isi Pengaduan</h3>
            <p class="text-[11px] text-slate-400">Detail keluhan dari pelapor</p>
        </div>
    </div>
    <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100">
        <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars($detail['isi'] ?? '-') ?></p>
    </div>
</div>

<!-- ============ BUKTI DARI PELAPOR ============ -->
<?php if ($adaBuktiPelapor): ?>
<div class="glass-card rounded-3xl p-6 sm:p-8 mb-5 border border-slate-200/70">
    <div class="flex items-center gap-3 mb-5">
        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-rose-500 to-red-700 text-white flex items-center justify-center shadow-lg shadow-rose-700/25">
            <span class="material-symbols-outlined text-[20px]">photo_camera</span>
        </div>
        <div>
            <h3 class="text-base font-extrabold text-slate-900">Bukti dari Pelapor</h3>
            <p class="text-[11px] text-slate-400">Kondisi awal yang dilaporkan warga</p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php if (!empty($detail['bukti_gambar'])): ?>
            <a href="/<?= htmlspecialchars($detail['bukti_gambar']) ?>" target="_blank" class="group relative block rounded-2xl overflow-hidden border-2 border-rose-200 hover:border-rose-400 transition-all shadow-sm hover:shadow-xl">
                <img src="/<?= htmlspecialchars($detail['bukti_gambar']) ?>" alt="Bukti 1" class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-500">
                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-3">
                    <div class="flex items-center justify-between text-white">
                        <span class="text-xs font-bold">Foto 1</span>
                        <span class="material-symbols-outlined text-[18px]">zoom_in</span>
                    </div>
                </div>
            </a>
        <?php endif; ?>
        <?php if (!empty($detail['bukti_gambar_2'])): ?>
            <a href="/<?= htmlspecialchars($detail['bukti_gambar_2']) ?>" target="_blank" class="group relative block rounded-2xl overflow-hidden border-2 border-rose-200 hover:border-rose-400 transition-all shadow-sm hover:shadow-xl">
                <img src="/<?= htmlspecialchars($detail['bukti_gambar_2']) ?>" alt="Bukti 2" class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-500">
                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-3">
                    <div class="flex items-center justify-between text-white">
                        <span class="text-xs font-bold">Foto 2</span>
                        <span class="material-symbols-outlined text-[18px]">zoom_in</span>
                    </div>
                </div>
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============ KONTAK PELAPOR (Admin) ============ -->
<?php if (!empty($detail['kontak']) && $isPetugas): ?>
<div class="rounded-3xl p-5 mb-5 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 flex items-center gap-4">
    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 text-white flex items-center justify-center shadow-lg shadow-amber-600/25 flex-shrink-0">
        <span class="material-symbols-outlined text-[24px]">call</span>
    </div>
    <div class="flex-1 min-w-0">
        <div class="text-[10px] font-extrabold uppercase tracking-wider text-amber-700 mb-0.5">Kontak Pelapor</div>
        <div class="text-base font-extrabold text-amber-900"><?= htmlspecialchars($detail['kontak']) ?></div>
    </div>
    <a href="tel:<?= htmlspecialchars($detail['kontak']) ?>" class="hidden sm:inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-md transition-all">
        <span class="material-symbols-outlined text-[16px]">phone</span> Hubungi
    </a>
</div>
<?php endif; ?>

<!-- ============ TANGGAPAN & PENANGANAN ============ -->
<?php if ($adaPenanganan): ?>
<div class="glass-card rounded-3xl overflow-hidden mb-5 border border-slate-200/70">
    <div class="h-1.5 bg-gradient-to-r from-emerald-500 to-teal-600"></div>
    <div class="p-6 sm:p-8">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center shadow-lg shadow-emerald-600/25">
                <span class="material-symbols-outlined text-[20px]">verified</span>
            </div>
            <div>
                <h3 class="text-base font-extrabold text-emerald-700">Tanggapan & Penanganan</h3>
                <p class="text-[11px] text-slate-400">Respon dari petugas desa</p>
            </div>
        </div>

        <?php if (!empty($detail['tanggapan'])): ?>
            <div class="p-5 rounded-2xl bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-100 mb-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-emerald-600 text-[18px]">chat</span>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-700">Pesan Tanggapan</span>
                </div>
                <p class="text-sm text-slate-800 leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars($detail['tanggapan']) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($detail['bukti_tanggapan_1']) || !empty($detail['bukti_tanggapan_2'])): ?>
            <div class="flex items-center gap-2 mb-3">
                <span class="material-symbols-outlined text-emerald-600 text-[18px]">photo_library</span>
                <h4 class="text-xs font-extrabold uppercase tracking-wider text-emerald-700">Bukti Penanganan</h4>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php if (!empty($detail['bukti_tanggapan_1'])): ?>
                    <a href="/<?= htmlspecialchars($detail['bukti_tanggapan_1']) ?>" target="_blank" class="group relative block rounded-2xl overflow-hidden border-2 border-emerald-200 hover:border-emerald-500 transition-all shadow-sm hover:shadow-xl">
                        <img src="/<?= htmlspecialchars($detail['bukti_tanggapan_1']) ?>" alt="Hasil 1" class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-emerald-900/80 to-transparent p-3">
                            <div class="flex items-center justify-between text-white">
                                <span class="text-xs font-bold">✅ Hasil 1</span>
                                <span class="material-symbols-outlined text-[18px]">zoom_in</span>
                            </div>
                        </div>
                    </a>
                <?php endif; ?>
                <?php if (!empty($detail['bukti_tanggapan_2'])): ?>
                    <a href="/<?= htmlspecialchars($detail['bukti_tanggapan_2']) ?>" target="_blank" class="group relative block rounded-2xl overflow-hidden border-2 border-emerald-200 hover:border-emerald-500 transition-all shadow-sm hover:shadow-xl">
                        <img src="/<?= htmlspecialchars($detail['bukti_tanggapan_2']) ?>" alt="Hasil 2" class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-emerald-900/80 to-transparent p-3">
                            <div class="flex items-center justify-between text-white">
                                <span class="text-xs font-bold">✅ Hasil 2</span>
                                <span class="material-symbols-outlined text-[18px]">zoom_in</span>
                            </div>
                        </div>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============ FORM TANGGAPI (Petugas) ============ -->
<?php if ($isPetugas): ?>
<div class="glass-card rounded-3xl overflow-hidden border border-slate-200/70">
    <div class="h-1.5 bg-gradient-to-r from-blue-600 to-indigo-700"></div>
    <div class="p-6 sm:p-8">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white flex items-center justify-center shadow-lg shadow-blue-700/25">
                <span class="material-symbols-outlined text-[20px]">reply</span>
            </div>
            <div>
                <h3 class="text-base font-extrabold text-slate-900">Beri Tanggapan</h3>
                <p class="text-[11px] text-slate-400">Update status + upload bukti penanganan</p>
            </div>
        </div>

        <form method="POST" action="/pengaduan?action=tanggapi&id=<?= $detail['id'] ?>" enctype="multipart/form-data" class="space-y-5">
            <div>
                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">
                    Status <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <?php foreach ($status_meta as $key => $meta): ?>
                        <label class="cursor-pointer">
                            <input type="radio" name="status" value="<?= $key ?>" class="peer sr-only" <?= ($detail['status'] ?? '') === $key ? 'checked' : '' ?> required>
                            <div class="flex flex-col items-center gap-1.5 p-3 rounded-2xl border-2 border-slate-200 hover:border-blue-400 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition-all">
                                <span class="material-symbols-outlined text-[22px] <?= $key === 'pending' ? 'text-amber-500' : ($key === 'proses' ? 'text-blue-500' : ($key === 'selesai' ? 'text-emerald-500' : 'text-rose-500')) ?>"><?= $meta['icon'] ?></span>
                                <span class="text-[11px] font-bold text-slate-700"><?= $meta['label'] ?></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">
                    <span class="material-symbols-outlined text-[14px] align-middle">chat</span>
                    Tanggapan
                </label>
                <textarea name="tanggapan" rows="4" placeholder="Jelaskan tindakan yang sudah dilakukan..." class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-2xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all resize-none"><?= htmlspecialchars($detail['tanggapan'] ?? '') ?></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-emerald-700 mb-2">
                        <span class="material-symbols-outlined text-[14px] align-middle">photo_camera</span>
                        Bukti Penanganan 1
                    </label>
                    <input type="file" name="bukti_tanggapan_1" accept="image/jpeg,image/png,image/webp" capture="environment" onchange="previewImg(this, 'previewT1')" class="block w-full text-xs text-slate-600 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:bg-emerald-600 file:text-white file:font-bold file:text-xs hover:file:bg-emerald-700 cursor-pointer">
                    <img id="previewT1" class="mt-3 rounded-2xl max-h-48 hidden border-2 border-emerald-200 shadow-sm">
                    <p class="text-[10px] text-slate-400 mt-1.5">Foto hasil perbaikan / tindakan</p>
                </div>
                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-emerald-700 mb-2">
                        <span class="material-symbols-outlined text-[14px] align-middle">photo_camera</span>
                        Bukti Penanganan 2 <span class="text-slate-400 font-normal">(opsional)</span>
                    </label>
                    <input type="file" name="bukti_tanggapan_2" accept="image/jpeg,image/png,image/webp" capture="environment" onchange="previewImg(this, 'previewT2')" class="block w-full text-xs text-slate-600 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:bg-slate-600 file:text-white file:font-bold file:text-xs hover:file:bg-slate-700 cursor-pointer">
                    <img id="previewT2" class="mt-3 rounded-2xl max-h-48 hidden border-2 border-emerald-200 shadow-sm">
                    <p class="text-[10px] text-slate-400 mt-1.5">Opsional — angle lain</p>
                </div>
            </div>

            <div class="flex gap-3 pt-5 border-t border-slate-100">
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 text-white text-sm font-extrabold shadow-lg shadow-blue-700/25 transition-all">
                    <span class="material-symbols-outlined text-[18px]">send</span> Kirim Tanggapan
                </button>
                <a href="/pengaduan" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold transition-all">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function previewImg(input, targetId) {
    const img = document.getElementById(targetId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { 
            img.src = e.target.result; 
            img.classList.remove('hidden'); 
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        img.src = ''; 
        img.classList.add('hidden');
    }
}
</script>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>