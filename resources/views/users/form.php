<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'super_admin') { 
    header('Location: /login'); 
    exit; 
}

$is_edit = ($action === 'edit');
$page_title = ($is_edit ? 'Edit' : 'Tambah') . ' User';
include __DIR__ . '/../layouts/dashboard_layout.php';

// Ambil data user yang sedang diedit dengan aman
$curr_user = $user_data ?? ($item ?? []);
$user_id   = $curr_user['id'] ?? ($id ?? '');
?>

<!-- TOMBOL KEMBALI -->
<div class="mb-5">
    <a href="/users" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/80 hover:bg-slate-100 border border-slate-200/80 text-slate-700 text-sm font-bold shadow-sm transition-all group">
        <span class="material-symbols-outlined text-[18px] group-hover:-translate-x-0.5 transition-transform">arrow_back</span>
        Kembali ke Daftar User
    </a>
</div>

<!-- FORM CARD -->
<div class="glass-card rounded-2xl p-6 sm:p-8 max-w-2xl bg-white border border-slate-200/80 shadow-sm">
    
    <!-- CARD HEADER -->
    <div class="flex items-center gap-4 mb-6 pb-6 border-b border-slate-100">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 text-white flex items-center justify-center shadow-lg shadow-blue-700/25 flex-shrink-0">
            <span class="material-symbols-outlined text-[26px]"><?= $is_edit ? 'manage_accounts' : 'person_add' ?></span>
        </div>
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-xl font-extrabold text-slate-900 tracking-tight"><?= $is_edit ? 'Edit Data User' : 'Tambah User Baru' ?></h2>
                <?php if ($is_edit && !empty($user_id)): ?>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-100">ID #<?= htmlspecialchars((string)$user_id) ?></span>
                <?php endif; ?>
            </div>
            <p class="text-xs text-slate-500 mt-0.5"><?= $is_edit ? 'Perbarui informasi akun, email, serta penetapan role & wilayah user' : 'Isi formulir berikut untuk mendaftarkan akun pengguna baru ke sistem' ?></p>
        </div>
    </div>

    <!-- FORM BODY -->
    <form method="POST" action="/users?action=<?= $is_edit ? 'update&id=' . urlencode((string)$user_id) : 'tambah' ?>" class="space-y-5">
        
        <!-- NAMA LENGKAP -->
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                Nama Lengkap <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[20px] pointer-events-none">person</span>
                <input type="text" name="name" 
                       value="<?= htmlspecialchars($curr_user['name'] ?? '') ?>" 
                       placeholder="Masukkan nama lengkap" 
                       required 
                       class="block w-full pl-11 pr-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all font-medium">
            </div>
        </div>

        <!-- EMAIL -->
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                Alamat Email <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[20px] pointer-events-none">mail</span>
                <input type="email" name="email" 
                       value="<?= htmlspecialchars($curr_user['email'] ?? '') ?>" 
                       placeholder="nama@email.com" 
                       required 
                       class="block w-full pl-11 pr-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all font-medium">
            </div>
        </div>

        <!-- PASSWORD -->
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                <?= $is_edit ? 'Password Baru (Opsional)' : 'Password Akun <span class="text-rose-500">*</span>' ?>
            </label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[20px] pointer-events-none">lock</span>
                <input type="password" name="password" 
                       <?= $is_edit ? '' : 'required minlength="6"' ?> 
                       placeholder="<?= $is_edit ? 'Biarkan kosong jika tidak ingin mengubah password' : 'Minimal 6 karakter' ?>" 
                       class="block w-full pl-11 pr-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all font-medium">
            </div>
            <?php if ($is_edit): ?>
                <p class="text-[11px] text-slate-400 mt-1.5 flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">info</span>
                    Biarkan kosong jika password user ini tidak ingin diubah.
                </p>
            <?php endif; ?>
        </div>

        <!-- ROLE -->
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                Hak Akses / Role <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[20px] pointer-events-none">shield_person</span>
                <select name="role" required class="block w-full pl-11 pr-10 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all font-medium appearance-none">
                    <option value="super_admin" <?= (isset($curr_user['role']) && $curr_user['role'] == 'super_admin') ? 'selected' : '' ?>>👑 Super Admin (Akses Penuh)</option>
                    <option value="desa" <?= (isset($curr_user['role']) && $curr_user['role'] == 'desa') ? 'selected' : '' ?>>🏛️ Admin Desa (Pengelola Desa)</option>
                    <option value="rw" <?= (isset($curr_user['role']) && $curr_user['role'] == 'rw') ? 'selected' : '' ?>>🏠 Ketua RW (Pengelola RW)</option>
                    <option value="rt" <?= (isset($curr_user['role']) && $curr_user['role'] == 'rt') ? 'selected' : '' ?>>🏘️ Ketua RT (Pengelola RT)</option>
                    <option value="warga" <?= (!isset($curr_user['role']) || $curr_user['role'] == 'warga') ? 'selected' : '' ?>>👤 Warga (Layanan Mandiri)</option>
                </select>
                <span class="material-symbols-outlined absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[20px] pointer-events-none">expand_more</span>
            </div>
        </div>

        <!-- WILAYAH CARD -->
        <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-200/80 space-y-3">
            <div class="flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[18px] text-slate-600">location_on</span>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Penugasan Wilayah (Opsional)</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <!-- DESA -->
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-600 mb-1.5">Desa</label>
                    <div class="relative">
                        <select name="desa_id" class="block w-full px-3 py-2.5 bg-white border border-slate-300 rounded-lg text-slate-900 text-xs font-medium focus:ring-2 focus:ring-blue-600 transition-all appearance-none pr-8">
                            <option value="">-- Pilih Desa --</option>
                            <?php foreach (($desa_list ?? []) as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= (isset($curr_user['desa_id']) && $curr_user['desa_id'] == $d['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['nama']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[16px] pointer-events-none">expand_more</span>
                    </div>
                </div>

                <!-- RW -->
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-600 mb-1.5">RW</label>
                    <div class="relative">
                        <select name="rw_id" class="block w-full px-3 py-2.5 bg-white border border-slate-300 rounded-lg text-slate-900 text-xs font-medium focus:ring-2 focus:ring-blue-600 transition-all appearance-none pr-8">
                            <option value="">-- Pilih RW --</option>
                            <?php foreach (($rw_list ?? []) as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= (isset($curr_user['rw_id']) && $curr_user['rw_id'] == $r['id']) ? 'selected' : '' ?>>
                                    RW <?= htmlspecialchars($r['nomor']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[16px] pointer-events-none">expand_more</span>
                    </div>
                </div>

                <!-- RT -->
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-600 mb-1.5">RT</label>
                    <div class="relative">
                        <select name="rt_id" class="block w-full px-3 py-2.5 bg-white border border-slate-300 rounded-lg text-slate-900 text-xs font-medium focus:ring-2 focus:ring-blue-600 transition-all appearance-none pr-8">
                            <option value="">-- Pilih RT --</option>
                            <?php foreach (($rt_list ?? []) as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= (isset($curr_user['rt_id']) && $curr_user['rt_id'] == $r['id']) ? 'selected' : '' ?>>
                                    RT <?= htmlspecialchars($r['nomor']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[16px] pointer-events-none">expand_more</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- TOMBOL AKSI -->
        <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-bold shadow-md shadow-blue-700/25 hover:shadow-lg transition-all">
                <span class="material-symbols-outlined text-[18px]">save</span>
                <?= $is_edit ? 'Simpan Perubahan' : 'Tambah User' ?>
            </button>
            <a href="/users" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold transition-all">
                Batal
            </a>
        </div>

    </form>
</div>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>