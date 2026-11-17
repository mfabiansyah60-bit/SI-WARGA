<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
$page_title = 'Pengaturan Akun';
include __DIR__ . '/../layouts/dashboard_layout.php';

$user = $_SESSION['user'] ?? [];
?>

<div class="mb-6">
    <h1 class="text-2xl font-extrabold text-slate-900 flex items-center gap-2">
        <span class="material-symbols-outlined text-slate-700 text-[28px]">settings</span>
        Pengaturan Akun
    </h1>
    <p class="text-sm text-slate-500 mt-1">Kelola data profil dan keamanan akun Anda.</p>
</div>

<!-- Flash Message -->
<?php if (!empty($_SESSION['flash'])): ?>
    <div class="mb-6 p-4 rounded-2xl border flex items-start gap-3 max-w-2xl <?= $_SESSION['flash']['type'] === 'success' ? 'bg-emerald-50 border-emerald-200' : 'bg-rose-50 border-rose-200' ?>">
        <span class="material-symbols-outlined <?= $_SESSION['flash']['type'] === 'success' ? 'text-emerald-600' : 'text-rose-600' ?>">
            <?= $_SESSION['flash']['type'] === 'success' ? 'check_circle' : 'error' ?>
        </span>
        <p class="text-sm font-semibold <?= $_SESSION['flash']['type'] === 'success' ? 'text-emerald-800' : 'text-rose-800' ?>">
            <?= htmlspecialchars($_SESSION['flash']['message']) ?>
        </p>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 max-w-4xl">
    
    <!-- KARTU 1: EDIT PROFIL -->
    <div class="glass-card rounded-3xl border border-slate-200/70 overflow-hidden">
        <div class="h-1.5 bg-gradient-to-r from-blue-500 to-indigo-600"></div>
        <div class="p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">badge</span>
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-slate-900">Profil Saya</h2>
                    <p class="text-[11px] text-slate-500">Informasi data diri Anda</p>
                </div>
            </div>

            <form method="POST" action="/pengaturan" class="space-y-4">
                <input type="hidden" name="action" value="update_profil">
                
                <div>
                    <label class="block text-[11px] font-extrabold uppercase tracking-wider text-slate-500 mb-2">Nama Lengkap</label>
                    <input type="text" name="name" required value="<?= htmlspecialchars($user['name'] ?? '') ?>" 
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-sm font-bold focus:bg-white focus:ring-2 focus:ring-blue-600 transition-all">
                </div>

                <div>
                    <label class="block text-[11px] font-extrabold uppercase tracking-wider text-slate-500 mb-2">Email (Tidak bisa diubah)</label>
                    <input type="email" disabled value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
                           class="w-full px-4 py-3 bg-slate-100 border border-slate-200 rounded-xl text-sm font-medium text-slate-500 cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-[11px] font-extrabold uppercase tracking-wider text-slate-500 mb-2">No. WhatsApp</label>
                    <input type="text" name="no_hp" value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>" placeholder="08xxxxxxxxxx"
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-sm font-bold focus:bg-white focus:ring-2 focus:ring-blue-600 transition-all">
                </div>

                <!-- Persetujuan Notifikasi WhatsApp -->
                <div class="pt-3 border-t border-slate-200">
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                        <div class="flex items-start gap-3">
                            <input id="wa_opt_in" name="wa_opt_in" type="checkbox" value="1" 
                                   <?= (!isset($user['wa_opt_in']) || (int)$user['wa_opt_in'] === 1) ? 'checked' : '' ?>
                                   class="w-4 h-4 mt-0.5 text-blue-600 bg-white border-slate-300 rounded focus:ring-blue-500 cursor-pointer">
                            <label for="wa_opt_in" class="flex-1 cursor-pointer">
                                <span class="block text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[16px] text-emerald-600">notifications_active</span>
                                    Persetujuan Notifikasi WhatsApp
                                </span>
                                <span class="block text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                    Setuju menerima pemberitahuan informasi resmi, pengumuman desa, dan update status pengajuan surat/pengaduan melalui pesan WhatsApp.
                                </span>
                            </label>
                        </div>

                        <div class="flex items-center gap-2 pt-1 border-t border-slate-200/60">
                            <?php if (!isset($user['wa_opt_in']) || (int)$user['wa_opt_in'] === 1): ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-extrabold tracking-wide">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                    Status: Menyetujui (Opt-In)
                                </span>
                                <?php if (!empty($user['wa_opt_in_at'])): ?>
                                    <span class="text-[10px] text-slate-400 font-medium">
                                        <?= date('d M Y, H:i', strtotime($user['wa_opt_in_at'])) ?> WIB
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-rose-100 text-rose-800 text-[10px] font-extrabold tracking-wide">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                                    Status: Menolak Notifikasi (Opt-Out)
                                </span>
                                <?php if (!empty($user['wa_opt_in_at'])): ?>
                                    <span class="text-[10px] text-slate-400 font-medium">
                                        sejak <?= date('d M Y, H:i', strtotime($user['wa_opt_in_at'])) ?> WIB
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-3 bg-blue-700 hover:bg-blue-800 text-white text-sm font-bold rounded-xl shadow-md transition-all">
                        Simpan Profil
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- KARTU 2: GANTI PASSWORD -->
    <div class="glass-card rounded-3xl border border-slate-200/70 overflow-hidden">
        <div class="h-1.5 bg-gradient-to-r from-amber-500 to-orange-600"></div>
        <div class="p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">lock</span>
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-slate-900">Keamanan</h2>
                    <p class="text-[11px] text-slate-500">Ubah kata sandi akun Anda</p>
                </div>
            </div>

            <form method="POST" action="/pengaturan" class="space-y-4">
                <input type="hidden" name="action" value="update_password">
                
                <div>
                    <label class="block text-[11px] font-extrabold uppercase tracking-wider text-slate-500 mb-2">Password Saat Ini <span class="text-rose-500">*</span></label>
                    <input type="password" name="current_password" required placeholder="Masukkan password lama"
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-amber-600 transition-all">
                </div>

                <div>
                    <label class="block text-[11px] font-extrabold uppercase tracking-wider text-slate-500 mb-2">Password Baru <span class="text-rose-500">*</span></label>
                    <input type="password" name="new_password" required minlength="6" placeholder="Minimal 6 karakter"
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-amber-600 transition-all">
                </div>

                <div>
                    <label class="block text-[11px] font-extrabold uppercase tracking-wider text-slate-500 mb-2">Konfirmasi Password Baru <span class="text-rose-500">*</span></label>
                    <input type="password" name="confirm_password" required minlength="6" placeholder="Ulangi password baru"
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-amber-600 transition-all">
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-3 bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold rounded-xl shadow-md transition-all">
                        Ubah Password
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>