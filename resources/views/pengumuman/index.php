<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
$page_title = 'Pengumuman Desa';
include __DIR__ . '/../layouts/dashboard_layout.php';

$isAdmin = in_array($role, ['super_admin', 'desa', 'rw', 'rt']);
?>

<!-- HEADER -->
<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
    <div>
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 border border-blue-200 mb-3">
            <span class="material-symbols-outlined text-blue-600 text-[16px]">campaign</span>
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-blue-700">Info Terkini</span>
        </div>
        <h1 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight leading-tight mb-2">
            Pengumuman Desa
        </h1>
        <p class="text-sm sm:text-base text-slate-500 font-medium max-w-lg leading-relaxed">
            Dapatkan informasi dan pengumuman resmi terbaru dari pemerintah desa.
        </p>
    </div>
    
    <?php if ($isAdmin): ?>
    <a href="/pengumuman?action=buat" class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 text-white text-sm font-extrabold shadow-lg shadow-blue-700/30 hover:shadow-xl hover:-translate-y-0.5 transition-all flex-shrink-0">
        <span class="material-symbols-outlined text-[20px]">add_circle</span>
        Buat Pengumuman
    </a>
    <?php endif; ?>
</div>

<!-- LIST PENGUMUMAN -->
<div class="space-y-5 max-w-4xl">
    <?php if (empty($pengumuman_list)): ?>
        <!-- EMPTY STATE (DESAIN BARU) -->
        <div class="relative overflow-hidden rounded-[32px] bg-white border border-slate-200/80 p-12 sm:p-20 text-center shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
            <!-- Efek Background Halus -->
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full max-w-lg bg-gradient-to-b from-blue-50/50 to-transparent pointer-events-none"></div>
            
            <div class="relative z-10">
                <div class="w-24 h-24 mx-auto rounded-[28px] bg-slate-50 border border-slate-100 flex items-center justify-center mb-6 shadow-inner">
                    <span class="material-symbols-outlined text-[48px] text-slate-300" style="font-variation-settings:'FILL' 1;">notifications_off</span>
                </div>
                <h3 class="text-2xl font-black text-slate-800 mb-3 tracking-tight">Belum Ada Pengumuman</h3>
                <p class="text-[15px] text-slate-500 max-w-md mx-auto leading-relaxed mb-6">
                    Saat ini belum ada informasi terbaru yang dibagikan. Silakan cek kembali nanti.
                </p>
                
                <?php if ($isAdmin): ?>
                <a href="/pengumuman?action=buat" class="inline-flex items-center gap-2 text-sm font-bold text-blue-600 hover:text-blue-700 hover:underline">
                    <span class="material-symbols-outlined text-[18px]">add</span> Buat Pengumuman Pertama
                </a>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
        <!-- CARD PENGUMUMAN (DESAIN BARU) -->
        <?php foreach ($pengumuman_list as $p): ?>
            <div class="group relative bg-white rounded-[24px] border border-slate-200 p-6 sm:p-8 hover:shadow-xl hover:shadow-blue-900/5 hover:border-blue-200 transition-all duration-300 overflow-hidden">
                
                <!-- Garis Aksen Kiri -->
                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-gradient-to-b from-blue-500 to-indigo-600 rounded-l-[24px]"></div>

                <!-- Header Card -->
                <div class="flex items-start justify-between gap-4 mb-5">
                    <div class="flex-1 min-w-0">
                        <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 leading-snug mb-3 pr-4">
                            <?= htmlspecialchars($p['judul']) ?>
                        </h2>
                        
                        <!-- Metadata (Penulis & Tanggal) -->
                        <div class="flex flex-wrap items-center gap-3 sm:gap-4 text-[11px] sm:text-xs font-bold text-slate-500 mb-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-50 border border-slate-100">
                                <span class="material-symbols-outlined text-[16px] text-blue-600">person</span>
                                <?= htmlspecialchars($p['author_name']) ?>
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-50 border border-slate-100">
                                <span class="material-symbols-outlined text-[16px] text-amber-500">schedule</span>
                                <?= date('d F Y - H:i', strtotime($p['created_at'])) ?> WIB
                            </span>
                        </div>

                        <!-- Status Pengiriman Terpisah: WhatsApp & Email (Khusus Pengurus / Admin) -->
                        <?php if ($isAdmin): ?>
                        <div class="flex flex-wrap items-center gap-2 pt-1">
                            <!-- Status WhatsApp -->
                            <?php 
                                $waTxt = $p['whatsapp_status'] ?? '';
                                $isWaSent = !empty($p['whatsapp_sent_at']) || str_contains(strtolower($waTxt), 'terkirim');
                                $isWaFailed = str_contains(strtolower($waTxt), 'gagal') || str_contains(strtolower($waTxt), 'error') || str_contains(strtolower($waTxt), 'nonaktif');
                                $isWaSkipped = ($p['saluran'] ?? '') === 'email' || str_contains(strtolower($waTxt), 'tidak dipilih');
                            ?>
                            <?php if ($isWaSent): ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-bold" title="<?= htmlspecialchars($waTxt) ?>">
                                    <span class="material-symbols-outlined text-[15px] text-emerald-600">chat</span>
                                    <span>WA: <?= htmlspecialchars($waTxt ?: 'Terkirim') ?></span>
                                </span>
                            <?php elseif ($isWaFailed): ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 text-[11px] font-bold" title="<?= htmlspecialchars($waTxt) ?>">
                                    <span class="material-symbols-outlined text-[15px] text-rose-600">chat_error</span>
                                    <span>WA: <?= htmlspecialchars($waTxt) ?></span>
                                </span>
                            <?php elseif ($isWaSkipped): ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-500 border border-slate-200 text-[11px] font-medium">
                                    <span class="material-symbols-outlined text-[15px]">chat_bubble_outline</span>
                                    <span>WA: Tidak Dipilih</span>
                                </span>
                            <?php endif; ?>

                            <!-- Status Email -->
                            <?php 
                                $mailTxt = $p['email_status'] ?? '';
                                $isMailSent = !empty($p['email_sent_at']) || str_contains(strtolower($mailTxt), 'terkirim');
                                $isMailFailed = str_contains(strtolower($mailTxt), 'gagal') || str_contains(strtolower($mailTxt), 'tidak ada');
                                $isMailSkipped = ($p['saluran'] ?? '') === 'whatsapp' || str_contains(strtolower($mailTxt), 'tidak dipilih');
                            ?>
                            <?php if ($isMailSent): ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 text-[11px] font-bold" title="<?= htmlspecialchars($mailTxt) ?>">
                                    <span class="material-symbols-outlined text-[15px] text-blue-600">mail</span>
                                    <span>Email: <?= htmlspecialchars($mailTxt ?: 'Terkirim') ?></span>
                                </span>
                            <?php elseif ($isMailFailed): ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 text-[11px] font-bold" title="<?= htmlspecialchars($mailTxt) ?>">
                                    <span class="material-symbols-outlined text-[15px] text-amber-600">mark_email_unread</span>
                                    <span>Email: <?= htmlspecialchars($mailTxt) ?></span>
                                </span>
                            <?php elseif ($isMailSkipped): ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-500 border border-slate-200 text-[11px] font-medium">
                                    <span class="material-symbols-outlined text-[15px]">mail_outline</span>
                                    <span>Email: Tidak Dipilih</span>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tombol Hapus (Hanya Admin) -->
                    <?php if ($isAdmin): ?>
                    <a href="/pengumuman?action=hapus&id=<?= $p['id'] ?>" 
                       onclick="return confirm('Yakin ingin menghapus pengumuman ini?')"
                       class="flex-shrink-0 w-10 h-10 rounded-xl bg-slate-50 text-slate-400 hover:bg-rose-500 hover:text-white flex items-center justify-center transition-all opacity-0 group-hover:opacity-100 focus:opacity-100"
                       title="Hapus Pengumuman">
                        <span class="material-symbols-outlined text-[20px]">delete</span>
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Garis Pembatas -->
                <div class="w-full h-px bg-slate-100 mb-5"></div>

                <!-- Isi Pengumuman -->
                <div class="prose prose-sm sm:prose-base prose-slate max-w-none text-slate-600 leading-relaxed whitespace-pre-wrap">
                    <?= htmlspecialchars($p['isi']) ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>