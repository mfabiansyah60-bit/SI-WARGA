<?php 
// resources/views/surat/index.php
$page_title = 'Manajemen Surat';
include __DIR__ . '/../layouts/dashboard_layout.php';
?>

<div class="p-6 md:p-8 max-w-7xl mx-auto space-y-8">
    
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                <span class="material-symbols-outlined text-3xl text-blue-600">mail</span>
                Manajemen Surat
            </h1>
            <p class="text-sm text-slate-500 mt-1">Kelola pengajuan, persetujuan, dan pengiriman surat warga.</p>
        </div>
        <?php if (in_array($user['role'], ['super_admin', 'desa'])): ?>
            <a href="/surat?action=tambah" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-blue-600/30 transition-all hover:-translate-y-0.5">
                <span class="material-symbols-outlined text-[20px]">add_circle</span>
                Buat Template Baru
            </a>
        <?php endif; ?>
    </div>

    <!-- Flash Message -->
    <?php if (isset($_SESSION['flash'])): ?>
        <div class="p-4 rounded-xl flex items-start gap-3 <?= $_SESSION['flash']['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' ?>">
            <span class="material-symbols-outlined mt-0.5"><?= $_SESSION['flash']['type'] === 'success' ? 'check_circle' : 'error' ?></span>
            <div class="text-sm font-semibold"><?= $_SESSION['flash']['message'] ?></div>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- Tabel Riwayat & Persetujuan -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50">
            <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-[18px]">history</span>
            </div>
            <h2 class="font-bold text-slate-800">Riwayat & Persetujuan Surat</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-white border-b border-slate-200 text-slate-500 uppercase text-[11px] font-bold tracking-wider">
                    <tr>
                        <th class="px-6 py-4">No. Surat</th>
                        <th class="px-6 py-4">Pemohon</th>
                        <th class="px-6 py-4">Jenis Surat</th>
                        <th class="px-6 py-4">Status Surat</th>
                        <th class="px-6 py-4">Status Email</th>
                        <th class="px-6 py-4">Status WhatsApp</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($surat_list)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-12 text-slate-400">
                                <span class="material-symbols-outlined text-5xl mb-2 opacity-50">folder_off</span>
                                <p class="font-medium">Belum ada riwayat surat.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($surat_list as $s): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-800 bg-slate-100 px-2.5 py-1 rounded-md text-xs border border-slate-200">
                                        <?= htmlspecialchars($s['nomor_surat']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-800"><?= htmlspecialchars($s['pemohon']) ?></div>
                                    <div class="text-xs text-slate-500 mt-0.5">RT <?= $s['rt'] ?> / RW <?= $s['rw'] ?></div>
                                </td>
                                <td class="px-6 py-4 font-medium">
                                    <?= htmlspecialchars($s['judul']) ?>
                                    <div class="text-[10px] text-slate-400 font-bold uppercase mt-0.5"><?= $s['tipe'] === 'ai' ? 'Draft AI' : 'Manual' ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($s['status'] === 'pending'): ?>
                                        <span class="inline-flex items-center gap-1 bg-amber-100 text-amber-700 px-2.5 py-1 rounded-full text-[11px] font-bold border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> PENDING
                                        </span>
                                    <?php elseif ($s['status'] === 'disetujui'): ?>
                                        <span class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-700 px-2.5 py-1 rounded-full text-[11px] font-bold border border-emerald-200">
                                            <span class="material-symbols-outlined text-[14px]">check_circle</span> DISETUJUI
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 bg-rose-100 text-rose-700 px-2.5 py-1 rounded-full text-[11px] font-bold border border-rose-200">
                                            <span class="material-symbols-outlined text-[14px]">cancel</span> DITOLAK
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (!empty($s['email_sent_at'])): ?>
                                        <span class="inline-flex items-center gap-1 text-blue-600 font-bold text-xs">
                                            <span class="material-symbols-outlined text-[16px]">send</span> Terkirim
                                        </span>
                                        <div class="text-[10px] text-slate-400 mt-0.5"><?= date('d/m/y H:i', strtotime($s['email_sent_at'])) ?></div>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-xs italic">Belum dikirim</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (!empty($s['whatsapp_sent_at'])): ?>
                                        <span class="inline-flex items-center gap-1 text-emerald-600 font-bold text-xs">
                                            <span class="material-symbols-outlined text-[16px]">mark_chat_read</span> Terkirim
                                        </span>
                                        <div class="text-[10px] text-slate-400 mt-0.5"><?= date('d/m/y H:i', strtotime($s['whatsapp_sent_at'])) ?></div>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-xs italic">Belum dikirim</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <?php if (in_array($user['role'], ['super_admin', 'desa', 'rt', 'rw']) && $s['status'] === 'pending'): ?>
                                            <a href="/surat?action=setujui&id=<?= $s['id'] ?>&type=<?= $s['tipe'] ?>" class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white flex items-center justify-center transition-colors tooltip" title="Setujui" onclick="return confirm('Setujui surat ini?')">
                                                <span class="material-symbols-outlined text-[18px]">check</span>
                                            </a>
                                            <a href="/surat?action=tolak&id=<?= $s['id'] ?>&type=<?= $s['tipe'] ?>" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white flex items-center justify-center transition-colors tooltip" title="Tolak" onclick="return confirm('Tolak surat ini?')">
                                                <span class="material-symbols-outlined text-[18px]">close</span>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($s['status'] === 'disetujui' && in_array($user['role'], ['super_admin', 'desa', 'rt', 'rw'])): ?>
                                            <a href="/surat?action=kirim_wa&id=<?= $s['id'] ?>&type=<?= $s['tipe'] ?>" class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white flex items-center justify-center transition-colors tooltip" title="Kirim Notifikasi via WhatsApp" onclick="<?= empty($s['email_sent_at']) ? "alert('⚠️ Notifikasi WhatsApp hanya dapat dikirim setelah surat berhasil dikirim ke email pemohon! Silakan gunakan tombol Kirim via Email (ikon amplop) terlebih dahulu.'); return false;" : "return confirm('Kirim notifikasi ke WhatsApp pemohon bahwa surat telah dikirim ke email?');" ?>">
                                                <span class="material-symbols-outlined text-[18px]">chat</span>
                                            </a>
                                            <a href="/surat?action=kirim_email&id=<?= $s['id'] ?>&type=<?= $s['tipe'] ?>" class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white flex items-center justify-center transition-colors tooltip" title="Kirim Berkas PDF via Email" onclick="return confirm('Kirim berkas PDF surat resmi ini ke email pemohon?')">
                                                <span class="material-symbols-outlined text-[18px]">mail</span>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($s['status'] === 'disetujui' || $user['role'] === 'warga'): ?>
                                            <a href="/surat?action=<?= $s['tipe'] === 'ai' ? 'cetak' : 'cetak_manual' ?>&id=<?= $s['id'] ?>" class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white flex items-center justify-center transition-colors tooltip" title="Lihat & Cetak Surat">
                                                <span class="material-symbols-outlined text-[18px]">visibility</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section Buat Surat (Template) -->
    <div class="pt-4">
        <h2 class="text-lg font-extrabold text-slate-800 mb-5 flex items-center gap-2">
            <span class="material-symbols-outlined text-amber-500">dashboard_customize</span>
            Buat Surat Baru (Pilih Template)
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <?php foreach ($template_list as $tpl): ?>
                <div class="group bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-xl hover:border-blue-300 transition-all duration-300 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-blue-50 to-transparent rounded-bl-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-[24px]">description</span>
                    </div>
                    <h3 class="font-bold text-slate-800 text-sm mb-1 leading-tight"><?= htmlspecialchars($tpl['nama']) ?></h3>
                    <p class="text-[11px] text-slate-500 mb-5 line-clamp-2"><?= htmlspecialchars($tpl['judul']) ?></p>
                    
                    <a href="/surat?action=buat&id=<?= $tpl['id'] ?>" class="block text-center w-full bg-slate-100 group-hover:bg-blue-600 text-slate-700 group-hover:text-white py-2.5 rounded-xl text-xs font-bold transition-colors">
                        Isi Form Surat
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>
