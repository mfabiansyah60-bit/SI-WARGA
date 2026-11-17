<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
$page_title = 'Laporan & Statistik';
include __DIR__ . '/../layouts/dashboard_layout.php';
?>

<div class="mb-6">
    <h2 class="text-lg font-extrabold text-slate-900 mb-1">📊 Ringkasan Sistem</h2>
    <p class="text-xs text-slate-500">Statistik lengkap SIWARGA Desa</p>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-5">
    <div class="glass-card rounded-2xl p-4 sm:p-5">
        <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center mb-3"><span class="material-symbols-outlined text-blue-700 text-[22px]">person</span></div>
        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900"><?= $total_users ?? 0 ?></div>
        <div class="text-[11px] text-slate-500 font-semibold mt-1">Total User</div>
    </div>
    <div class="glass-card rounded-2xl p-4 sm:p-5">
        <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center mb-3"><span class="material-symbols-outlined text-emerald-700 text-[22px]">groups</span></div>
        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900"><?= $total_warga ?? 0 ?></div>
        <div class="text-[11px] text-slate-500 font-semibold mt-1">Total Warga</div>
    </div>
    <div class="glass-card rounded-2xl p-4 sm:p-5">
        <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center mb-3"><span class="material-symbols-outlined text-purple-700 text-[22px]">flag</span></div>
        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900"><?= $total_rw ?? 0 ?></div>
        <div class="text-[11px] text-slate-500 font-semibold mt-1">Total RW</div>
    </div>
    <div class="glass-card rounded-2xl p-4 sm:p-5">
        <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center mb-3"><span class="material-symbols-outlined text-amber-700 text-[22px]">home</span></div>
        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900"><?= $total_rt ?? 0 ?></div>
        <div class="text-[11px] text-slate-500 font-semibold mt-1">Total RT</div>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
    <div class="glass-card rounded-2xl p-4 sm:p-5">
        <div class="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center mb-3"><span class="material-symbols-outlined text-rose-700 text-[22px]">campaign</span></div>
        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900"><?= $total_pengaduan ?? 0 ?></div>
        <div class="text-[11px] text-slate-500 font-semibold mt-1">Pengaduan</div>
    </div>
    <div class="glass-card rounded-2xl p-4 sm:p-5">
        <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center mb-3"><span class="material-symbols-outlined text-emerald-700 text-[22px]">check_circle</span></div>
        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900"><?= $pengaduan_selesai ?? 0 ?></div>
        <div class="text-[11px] text-slate-500 font-semibold mt-1">Selesai</div>
    </div>
    <div class="glass-card rounded-2xl p-4 sm:p-5">
        <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center mb-3"><span class="material-symbols-outlined text-blue-700 text-[22px]">support_agent</span></div>
        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900"><?= $total_layanan ?? 0 ?></div>
        <div class="text-[11px] text-slate-500 font-semibold mt-1">Layanan</div>
    </div>
    <div class="glass-card rounded-2xl p-4 sm:p-5">
        <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center mb-3"><span class="material-symbols-outlined text-purple-700 text-[22px]">description</span></div>
        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900"><?= $total_surat ?? 0 ?></div>
        <div class="text-[11px] text-slate-500 font-semibold mt-1">Surat Terbit</div>
    </div>
</div>

<div class="glass-card rounded-2xl overflow-hidden mb-5">
    <div class="p-5 border-b border-slate-100">
        <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
            <span class="material-symbols-outlined text-blue-700 text-[20px]">groups</span>
            User per Role
        </h3>
    </div>
    <?php if (empty($users_by_role)): ?>
        <div class="p-8 text-center text-slate-400 text-sm">Belum ada data</div>
    <?php else: ?>
        <table class="w-full">
            <thead class="bg-slate-50/80"><tr>
                <th class="text-left px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-slate-500">Role</th>
                <th class="text-left px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-slate-500">Total</th>
            </tr></thead>
            <tbody>
                <?php 
                $bc_map = [
                    'super_admin' => ['bg' => 'bg-rose-100', 'text' => 'text-rose-700'],
                    'desa' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700'],
                    'rw' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700'],
                    'rt' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700'],
                    'warga' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700'],
                ];
                foreach ($users_by_role as $u): 
                    $bc = $bc_map[$u['role']] ?? ['bg' => 'bg-slate-100', 'text' => 'text-slate-700'];
                ?>
                    <tr class="border-t border-slate-100">
                        <td class="px-5 py-3"><span class="inline-flex items-center px-3 py-1 rounded-full <?= $bc['bg'] ?> <?= $bc['text'] ?> text-[11px] font-bold uppercase"><?= ucfirst(str_replace('_', ' ', $u['role'])) ?></span></td>
                        <td class="px-5 py-3 text-sm font-bold text-slate-900"><?= $u['total'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="glass-card rounded-2xl p-6 bg-gradient-to-br from-blue-700 to-indigo-900 text-white">
    <div class="flex items-center gap-4">
        <span class="material-symbols-outlined text-[48px] opacity-30">trending_up</span>
        <div>
            <h3 class="text-base font-extrabold mb-1">📊 Ringkasan Sistem</h3>
            <p class="text-sm opacity-90">
                SIWARGA memiliki <strong><?= $total_users ?? 0 ?> user</strong>, 
                <strong><?= $total_warga ?? 0 ?> warga</strong>, 
                <strong><?= $total_rw ?? 0 ?> RW</strong>, dan 
                <strong><?= $total_rt ?? 0 ?> RT</strong> terdaftar.
            </p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>