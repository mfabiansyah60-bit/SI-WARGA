<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($warga_list)) $warga_list = [];
if (!isset($scope_label)) $scope_label = 'Data Warga';
$total_warga = count($warga_list);
$page_title = $page_title ?? 'Data Warga';
include __DIR__ . '/../layouts/dashboard_layout.php';

$user = $_SESSION['user'] ?? [];
$role = $user['role'] ?? 'warga';

// LOGIKA BOUNDARY: Cek apakah role ini boleh lihat No. Telepon (Tambah 'rt')
$boleh_lihat_telp = in_array($role, ['super_admin', 'desa', 'rw', 'rt']);

// Warna accent per role
$accentMap = [
    'super_admin' => ['bg' => '#dc2626', 'label' => 'SUPER ADMIN'],
    'desa'        => ['bg' => '#1e40af', 'label' => 'ADMIN DESA'],
    'rw'          => ['bg' => '#7c3aed', 'label' => 'KETUA RW'],
    'rt'          => ['bg' => '#d97706', 'label' => 'KETUA RT'],
    'warga'       => ['bg' => '#059669', 'label' => 'WARGA'],
];
$accent = $accentMap[$role] ?? $accentMap['warga'];
?>

<!-- HEADER -->
<div class="mb-6">
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border text-[11px] font-bold mb-2"
                 style="background: <?= $accent['bg'] ?>15; border-color: <?= $accent['bg'] ?>40; color: <?= $accent['bg'] ?>;">
                <span class="material-symbols-outlined text-[14px]">groups</span>
                <?= $accent['label'] ?> · <?= htmlspecialchars($scope_label) ?>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Data Warga
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Menampilkan <strong><?= $total_warga ?></strong> warga · <strong><?= htmlspecialchars($scope_label) ?></strong>
            </p>
        </div>

        <?php if (in_array($role, ['super_admin', 'desa'])): ?>
            <a href="/users?action=tambah"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-bold shadow-md shadow-blue-700/20 transition-all">
                <span class="material-symbols-outlined text-[18px]">person_add</span>
                Tambah Warga
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- TABEL WARGA -->
<div class="glass-card rounded-2xl bg-white border border-slate-200 overflow-hidden">

    <?php if (empty($warga_list)): ?>

        <div class="text-center py-16">
            <span class="material-symbols-outlined text-[56px] text-slate-300">person_off</span>
            <h3 class="text-base font-bold text-slate-900 mt-3 mb-1">Belum ada warga</h3>
            <p class="text-sm text-slate-500">
                <?php if ($role === 'rw' || $role === 'rt'): ?>
                    Tidak ada warga di wilayah <?= htmlspecialchars($scope_label) ?>
                <?php else: ?>
                    Belum ada warga yang terdaftar di sistem
                <?php endif; ?>
            </p>
        </div>

    <?php else: ?>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500 w-12">#</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Nama</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Email</th>
                        
                        <!-- BOUNDARY: Kolom No. Telepon -->
                        <?php if ($boleh_lihat_telp): ?>
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">No. Telepon</th>
                        <?php endif; ?>

                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Role</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Desa</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-slate-500">RT</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-slate-500">RW</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-slate-500">Persetujuan WA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">

                    <?php foreach ($warga_list as $i => $w): ?>
                        <?php
                            $inisial = strtoupper(substr($w['name'], 0, 1));
                            $colors = ['#2563eb', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#ef4444'];
                            $bgColor = $colors[ord($inisial) % count($colors)];
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 text-slate-400 font-mono text-xs"><?= $i + 1 ?></td>

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg text-white flex items-center justify-center text-sm font-bold flex-shrink-0 shadow-sm"
                                         style="background: <?= $bgColor ?>;">
                                        <?= htmlspecialchars($inisial) ?>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 truncate"><?= htmlspecialchars($w['name']) ?></div>
                                        <div class="text-[10px] text-slate-400">
                                            <?= !empty($w['created_at']) ? waktu_lalu($w['created_at']) : '-' ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3 text-slate-600 text-xs">
                                <div class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[14px] text-slate-400">mail</span>
                                    <?= htmlspecialchars($w['email']) ?>
                                </div>
                            </td>

                            <!-- BOUNDARY: Tampilkan data No. Telepon -->
                            <?php if ($boleh_lihat_telp): ?>
                                <td class="px-4 py-3 text-slate-600 text-xs font-medium">
                                    <?php 
                                        $nomor_tampil = $w['no_telp'] ?? ($w['no_hp'] ?? '');
                                        if (!empty($nomor_tampil)): 
                                    ?>
                                        <div class="flex items-center gap-1.5">
                                            <span class="material-symbols-outlined text-[14px] text-slate-400">phone</span>
                                            <?= htmlspecialchars($nomor_tampil) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-slate-400 italic text-[11px]">Belum diisi</span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>

                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold uppercase tracking-wider">
                                    <?= htmlspecialchars($w['role']) ?>
                                </span>
                            </td>

                            <td class="px-4 py-3 text-slate-600 text-xs">
                                <?= htmlspecialchars($w['desa_nama'] ?? 'Desa Sukamaju') ?>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <?php if (!empty($w['rt'])): ?>
                                    <span class="inline-flex px-2 py-0.5 rounded-md bg-amber-50 border border-amber-200 text-amber-700 text-[11px] font-bold">
                                        <?= htmlspecialchars($w['rt']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-300">-</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <?php if (!empty($w['rw'])): ?>
                                    <span class="inline-flex px-2 py-0.5 rounded-md bg-purple-50 border border-purple-200 text-purple-700 text-[11px] font-bold">
                                        <?= htmlspecialchars($w['rw']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-300">-</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <?php if (!isset($w['wa_opt_in']) || (int)$w['wa_opt_in'] === 1): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-[10px] font-extrabold tracking-wide" title="<?= !empty($w['wa_opt_in_at']) ? 'Disetujui: ' . date('d/m/Y H:i', strtotime($w['wa_opt_in_at'])) : 'Setuju Menerima Notifikasi' ?>">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Setuju
                                    </span>
                                    <?php if (!empty($w['wa_opt_in_at'])): ?>
                                        <div class="text-[9px] text-slate-400 mt-0.5"><?= date('d/m/Y', strtotime($w['wa_opt_in_at'])) ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-rose-50 border border-rose-200 text-rose-700 text-[10px] font-extrabold tracking-wide" title="<?= !empty($w['wa_opt_in_at']) ? 'Menolak sejak: ' . date('d/m/Y H:i', strtotime($w['wa_opt_in_at'])) : 'Menolak Notifikasi' ?>">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Menolak
                                    </span>
                                    <?php if (!empty($w['wa_opt_in_at'])): ?>
                                        <div class="text-[9px] text-slate-400 mt-0.5"><?= date('d/m/Y', strtotime($w['wa_opt_in_at'])) ?></div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500">
            <div>Menampilkan <strong><?= $total_warga ?></strong> warga</div>
            <div class="font-semibold"><?= htmlspecialchars($scope_label) ?></div>
        </div>

    <?php endif; ?>

</div>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>