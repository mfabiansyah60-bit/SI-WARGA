<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'super_admin') { header('Location: /login'); exit; }
$page_title = 'Tambah User';
include __DIR__ . '/../layouts/dashboard_layout.php';

$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);
?>

<!-- HEADER -->
<div class="mb-6 flex items-center justify-between flex-wrap gap-3">
    <div>
               <div class="flex items-center gap-3 mb-2">
            <a href="/users" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-slate-800">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                Kembali ke Daftar User
            </a>
            <span class="text-slate-300">|</span>
            <a href="/dashboard" class="inline-flex items-center gap-2 text-xs font-bold text-blue-600 hover:text-blue-800">
                <span class="material-symbols-outlined text-[16px]">dashboard</span>
                Dashboard
            </a>
        </div>
        <h1 class="text-2xl font-extrabold text-slate-900 flex items-center gap-2">
            <span class="material-symbols-outlined text-blue-600">person_add</span>
            Tambah User Baru
        </h1>
        <p class="text-xs text-slate-500 mt-1">Isi data lengkap untuk membuat akun pengguna baru</p>
    </div>
</div>

<!-- FLASH MESSAGE -->
<?php if (!empty($_SESSION['flash'])): ?>
    <div class="mb-5 p-4 rounded-2xl border flex items-start gap-3
        <?= $_SESSION['flash']['type'] === 'success' ? 'bg-emerald-50 border-emerald-200' : 'bg-rose-50 border-rose-200' ?>">
        <span class="material-symbols-outlined <?= $_SESSION['flash']['type'] === 'success' ? 'text-emerald-600' : 'text-rose-600' ?>">
            <?= $_SESSION['flash']['type'] === 'success' ? 'check_circle' : 'error' ?>
        </span>
        <p class="text-sm font-semibold <?= $_SESSION['flash']['type'] === 'success' ? 'text-emerald-800' : 'text-rose-800' ?>">
            <?= htmlspecialchars($_SESSION['flash']['message']) ?>
        </p>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<!-- FORM -->
<form method="POST" action="/users?action=simpan" class="glass-card rounded-3xl overflow-hidden border border-slate-200/70">
    <!-- Top strip -->
    <div class="h-1.5 bg-gradient-to-r from-blue-600 to-indigo-700"></div>

    <div class="p-6 sm:p-8 space-y-6">
        <!-- Section: Info Dasar -->
        <div>
            <h3 class="text-sm font-extrabold text-slate-900 mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] text-blue-600">badge</span>
                Informasi Dasar
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">
                        Nama Lengkap <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" required maxlength="100"
                           value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                           placeholder="Contoh: Admin Desa Sukamaju"
                           class="block w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-all">
                </div>

                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">
                        Email <span class="text-rose-500">*</span>
                    </label>
                    <input type="email" name="email" required maxlength="100"
                           value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                           placeholder="email@contoh.com"
                           class="block w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-all">
                </div>

                <div>
                    <!-- UBAH LABEL & NAME JADI no_telp -->
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">
                        No. Telepon
                    </label>
                    <input type="text" name="no_telp" maxlength="20"
                           value="<?= htmlspecialchars($old['no_telp'] ?? '') ?>"
                           placeholder="08xxxxxxxxxx"
                           class="block w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-all">
                </div>

                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">
                        Jenis Kelamin
                    </label>
                    <select name="jenis_kelamin" class="block w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-all">
                        <option value="">-- Pilih --</option>
                        <option value="L" <?= ($old['jenis_kelamin'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="P" <?= ($old['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Divider -->
        <div class="border-t border-slate-100"></div>

        <!-- Section: Role & Wilayah -->
        <div>
            <h3 class="text-sm font-extrabold text-slate-900 mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] text-blue-600">shield_person</span>
                Role & Wilayah
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="sm:col-span-3">
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">
                        Role <span class="text-rose-500">*</span>
                    </label>
                    <select name="role" id="roleSelect" required
                            class="block w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-all">
                        <option value="">-- Pilih Role --</option>
                        <option value="super_admin" <?= ($old['role'] ?? '') === 'super_admin' ? 'selected' : '' ?>>👑 Super Admin</option>
                        <option value="desa"        <?= ($old['role'] ?? '') === 'desa' ? 'selected' : '' ?>>🏛️ Admin Desa</option>
                        <option value="rw"          <?= ($old['role'] ?? '') === 'rw' ? 'selected' : '' ?>>🚩 Ketua RW</option>
                        <option value="rt"          <?= ($old['role'] ?? '') === 'rt' ? 'selected' : '' ?>>🏘️ Ketua RT</option>
                        <option value="warga"       <?= ($old['role'] ?? '') === 'warga' ? 'selected' : '' ?>>👤 Warga</option>
                    </select>
                </div>

                <div id="fieldRW" class="hidden">
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">
                        Nomor RW
                    </label>
                    <input type="text" name="rw" maxlength="10"
                           value="<?= htmlspecialchars($old['rw'] ?? '') ?>"
                           placeholder="Contoh: 01"
                           class="block w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
                </div>

                <div id="fieldRT" class="hidden">
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">
                        Nomor RT
                    </label>
                    <input type="text" name="rt" maxlength="10"
                           value="<?= htmlspecialchars($old['rt'] ?? '') ?>"
                           placeholder="Contoh: 03"
                           class="block w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
                </div>
            </div>

            <div id="roleHint" class="mt-3 p-3 rounded-xl bg-blue-50 border border-blue-100 text-xs text-blue-800 font-medium hidden">
                <span class="material-symbols-outlined text-[14px] align-middle">info</span>
                <span id="roleHintText"></span>
            </div>
        </div>

        <!-- Divider -->
        <div class="border-t border-slate-100"></div>

        <!-- Section: Password -->
        <div>
            <h3 class="text-sm font-extrabold text-slate-900 mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] text-blue-600">lock</span>
                Keamanan Akun
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">
                        Password <span class="text-rose-500">*</span>
                    </label>
                    <input type="password" name="password" required minlength="6"
                           placeholder="Minimal 6 karakter"
                           class="block w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
                </div>

                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2">
                        Konfirmasi Password <span class="text-rose-500">*</span>
                    </label>
                    <input type="password" name="confirm_password" required minlength="6"
                           placeholder="Ulangi password"
                           class="block w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Actions -->
    <div class="p-5 sm:p-6 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row gap-3 sm:justify-end">
        <a href="/users" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl bg-white border-2 border-slate-200 hover:bg-slate-100 text-slate-700 text-sm font-extrabold transition-all">
            <span class="material-symbols-outlined text-[18px]">close</span>
            Batal
        </a>
        <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 text-white text-sm font-extrabold shadow-lg shadow-blue-700/30 transition-all">
            <span class="material-symbols-outlined text-[18px]">save</span>
            Simpan User
        </button>
    </div>
</form>

<!-- SCRIPT: Show/Hide RT & RW berdasarkan role -->
<script>
(function(){
    var roleSelect = document.getElementById('roleSelect');
    var fieldRT    = document.getElementById('fieldRT');
    var fieldRW    = document.getElementById('fieldRW');
    var hint       = document.getElementById('roleHint');
    var hintText   = document.getElementById('roleHintText');

    function update() {
        var role = roleSelect.value;
        fieldRT.classList.add('hidden');
        fieldRW.classList.add('hidden');
        hint.classList.add('hidden');

        if (role === 'rw') {
            fieldRW.classList.remove('hidden');
            hintText.textContent = 'Ketua RW membutuhkan nomor RW (contoh: 01).';
            hint.classList.remove('hidden');
        } else if (role === 'rt') {
            fieldRT.classList.remove('hidden');
            fieldRW.classList.remove('hidden');
            hintText.textContent = 'Ketua RT membutuhkan nomor RT dan RW.';
            hint.classList.remove('hidden');
        } else if (role === 'warga') {
            fieldRT.classList.remove('hidden');
            fieldRW.classList.remove('hidden');
            hintText.textContent = 'Warga membutuhkan nomor RT dan RW untuk domisili.';
            hint.classList.remove('hidden');
        } else if (role === 'desa') {
            hintText.textContent = 'Admin Desa tidak membutuhkan RT/RW — mengelola seluruh desa.';
            hint.classList.remove('hidden');
        } else if (role === 'super_admin') {
            hintText.textContent = 'Super Admin memiliki akses penuh ke semua modul.';
            hint.classList.remove('hidden');
        }
    }

    roleSelect.addEventListener('change', update);
    update(); // panggil saat pertama load
})();
</script>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>