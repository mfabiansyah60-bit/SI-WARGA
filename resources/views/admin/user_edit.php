<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'super_admin') { header('Location: /login'); exit; }
$page_title = 'Edit User';
include __DIR__ . '/../layouts/dashboard_layout.php';
?>

<!-- ============ HEADER ============ -->
<div class="mb-6">
    <div class="flex items-center gap-2 mb-2">
        <a href="/users" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-blue-600 transition-all">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            Kembali ke Daftar User
        </a>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight leading-tight mb-1 flex items-center gap-3">
                <span class="material-symbols-outlined text-blue-600 text-[32px]">edit</span>
                Edit User
            </h1>
            <p class="text-sm text-slate-600">Memperbarui data akun <strong class="text-slate-900">#<?= (int)($target['id'] ?? 0) ?></strong></p>
        </div>
        <a href="/users" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-white border-2 border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-bold transition-all">
            <span class="material-symbols-outlined text-[18px]">close</span>
            Batal
        </a>
    </div>
</div>

<!-- ============ FLASH ============ -->
<?php if (!empty($_SESSION['flash'])): ?>
    <div class="mb-5 p-4 rounded-2xl border flex items-start gap-3 <?= $_SESSION['flash']['type'] === 'success' ? 'bg-emerald-50 border-emerald-200' : 'bg-rose-50 border-rose-200' ?>">
        <span class="material-symbols-outlined <?= $_SESSION['flash']['type'] === 'success' ? 'text-emerald-600' : 'text-rose-600' ?>">
            <?= $_SESSION['flash']['type'] === 'success' ? 'check_circle' : 'error' ?>
        </span>
        <p class="text-sm font-semibold <?= $_SESSION['flash']['type'] === 'success' ? 'text-emerald-800' : 'text-rose-800' ?>">
            <?= htmlspecialchars($_SESSION['flash']['message']) ?>
        </p>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<!-- ============ FORM ============ -->
<form method="POST" action="/users?action=update" class="space-y-5">
    <input type="hidden" name="id" value="<?= (int)$target['id'] ?>">

    <!-- Card: Informasi Akun -->
    <div class="glass-card rounded-3xl overflow-hidden border border-slate-200/70">
        <div class="h-1.5 bg-gradient-to-r from-blue-600 to-indigo-700"></div>
        <div class="p-6 sm:p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white flex items-center justify-center shadow-lg">
                    <span class="material-symbols-outlined text-[22px]">badge</span>
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-slate-900">Informasi Akun</h2>
                    <p class="text-[11px] text-slate-500">Data identitas dan kontak user</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">
                        Nama Lengkap <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" required maxlength="100"
                           value="<?= htmlspecialchars($target['name'] ?? '') ?>"
                           class="block w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-all">
                </div>

                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">
                        Email <span class="text-rose-500">*</span>
                    </label>
                    <input type="email" name="email" required maxlength="100"
                           value="<?= htmlspecialchars($target['email'] ?? '') ?>"
                           class="block w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-all">
                </div>

                <div>
                    <!-- UBAH LABEL & NAME JADI no_telp -->
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">
                        No. Telepon
                    </label>
                    <input type="text" name="no_telp" maxlength="20"
                           value="<?= htmlspecialchars($target['no_telp'] ?? '') ?>"
                           placeholder="08123456789"
                           class="block w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-all">
                    <p class="text-[11px] text-slate-400 mt-1.5 font-medium">Format: 08123456789 atau +628123456789</p>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">
                        Role <span class="text-rose-500">*</span>
                    </label>
                    <select name="role" id="roleSelect" required
                            class="block w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm font-bold focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-all cursor-pointer">
                        <option value="super_admin" <?= ($target['role'] ?? '') === 'super_admin' ? 'selected' : '' ?>>👑 Super Admin</option>
                        <option value="desa"        <?= ($target['role'] ?? '') === 'desa' ? 'selected' : '' ?>>🏛️ Admin Desa</option>
                        <option value="rw"          <?= ($target['role'] ?? '') === 'rw' ? 'selected' : '' ?>>🚩 Ketua RW</option>
                        <option value="rt"          <?= ($target['role'] ?? '') === 'rt' ? 'selected' : '' ?>>🏘️ Ketua RT</option>
                        <option value="warga"       <?= ($target['role'] ?? '') === 'warga' ? 'selected' : '' ?>>👤 Warga</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">
                        Persetujuan Notifikasi WhatsApp
                    </label>
                    <select name="wa_opt_in"
                            class="block w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm font-bold focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-all cursor-pointer">
                        <option value="1" <?= (!isset($target['wa_opt_in']) || (int)$target['wa_opt_in'] === 1) ? 'selected' : '' ?>>✅ Setuju (Opt-In)</option>
                        <option value="0" <?= (isset($target['wa_opt_in']) && (int)$target['wa_opt_in'] === 0) ? 'selected' : '' ?>>❌ Tidak Setuju (Opt-Out)</option>
                    </select>
                    <?php if (!empty($target['wa_opt_in_at'])): ?>
                        <p class="text-[11px] text-slate-400 mt-1.5 font-medium">
                            Waktu persetujuan terakhir: <?= date('d M Y, H:i', strtotime($target['wa_opt_in_at'])) ?> WIB
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Card: Wilayah & Data Diri -->
    <div class="glass-card rounded-3xl overflow-hidden border border-slate-200/70">
        <div class="h-1.5 bg-gradient-to-r from-emerald-500 to-teal-600"></div>
        <div class="p-6 sm:p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center shadow-lg">
                    <span class="material-symbols-outlined text-[22px]">location_on</span>
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-slate-900">Wilayah & Data Diri</h2>
                    <p class="text-[11px] text-slate-500">Domisili dan informasi personal</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">RT</label>
                    <input type="text" name="rt" maxlength="10"
                           value="<?= htmlspecialchars($target['rt'] ?? '') ?>"
                           placeholder="01"
                           class="block w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 transition-all">
                </div>

                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">RW</label>
                    <input type="text" name="rw" maxlength="10"
                           value="<?= htmlspecialchars($target['rw'] ?? '') ?>"
                           placeholder="03"
                           class="block w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 transition-all">
                </div>

                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">Jenis Kelamin</label>
                    <select name="jenis_kelamin"
                            class="block w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 transition-all cursor-pointer">
                        <option value="">-- Pilih --</option>
                        <option value="L" <?= ($target['jenis_kelamin'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="P" <?= ($target['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Card: Keamanan -->
    <div class="glass-card rounded-3xl overflow-hidden border border-slate-200/70">
        <div class="h-1.5 bg-gradient-to-r from-amber-500 to-orange-600"></div>
        <div class="p-6 sm:p-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 text-white flex items-center justify-center shadow-lg">
                    <span class="material-symbols-outlined text-[22px]">lock</span>
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-slate-900">Ganti Password</h2>
                    <p class="text-[11px] text-slate-500">Kosongkan jika tidak ingin mengubah password</p>
                </div>
            </div>

            <div class="mt-5 p-3 rounded-xl bg-amber-50 border border-amber-200 flex items-start gap-2">
                <span class="material-symbols-outlined text-amber-600 text-[18px] flex-shrink-0">warning</span>
                <p class="text-[12px] text-amber-800 leading-relaxed font-medium">Hanya isi jika user meminta ganti password. Perubahan langsung tersimpan.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mt-5">
                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">Password Baru</label>
                    <input type="password" name="new_password" minlength="6"
                           placeholder="Minimal 6 karakter"
                           class="block w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-600 focus:border-amber-600 transition-all">
                </div>

                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">Konfirmasi Password</label>
                    <input type="password" name="confirm_password" minlength="6"
                           placeholder="Ulangi password baru"
                           class="block w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-600 focus:border-amber-600 transition-all">
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Actions -->
    <div class="glass-card rounded-3xl border border-slate-200/70 p-5 sm:p-6 flex flex-col sm:flex-row gap-3 sm:justify-end bg-slate-50/50">
        <a href="/users" class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl bg-white border-2 border-slate-200 hover:bg-slate-100 text-slate-700 text-sm font-extrabold transition-all">
            <span class="material-symbols-outlined text-[18px]">cancel</span>
            Batal
        </a>
        <button type="submit" class="inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 text-white text-sm font-extrabold shadow-lg shadow-blue-700/30 hover:shadow-xl hover:-translate-y-0.5 transition-all">
            <span class="material-symbols-outlined text-[18px]">save</span>
            Simpan Perubahan
        </button>
    </div>
</form>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>