<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'super_admin') { header('Location: /login'); exit; }
$page_title = 'Manajemen User';
include __DIR__ . '/../layouts/dashboard_layout.php';
?>

<div class="flex justify-between items-center mb-4 flex-wrap gap-3">
    <h2 class="text-lg font-extrabold text-slate-900">👥 Daftar User (<?= count($users ?? []) ?>)</h2>
    <a href="/users?action=tambah" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-bold shadow-md shadow-blue-700/25 transition-all">
        ➕ Tambah User
    </a>
</div>

<div class="glass-card rounded-2xl overflow-hidden">
    <?php if (empty($users)): ?>
        <div class="p-12 text-center">
            <div class="text-[60px] mb-3">👥</div>
            <h3 class="text-slate-700 font-bold">Belum Ada User</h3>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="text-left px-5 py-4 text-[11px] font-bold uppercase tracking-wider text-slate-500">ID</th>
                        <th class="text-left px-5 py-4 text-[11px] font-bold uppercase tracking-wider text-slate-500">Nama</th>
                        <th class="text-left px-5 py-4 text-[11px] font-bold uppercase tracking-wider text-slate-500">Email</th>
                        <th class="text-left px-5 py-4 text-[11px] font-bold uppercase tracking-wider text-slate-500">Role</th>
                        <th class="text-right px-5 py-4 text-[11px] font-bold uppercase tracking-wider text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $badge_colors = [
                        'super_admin' => ['bg' => 'bg-rose-100', 'text' => 'text-rose-700', 'icon' => '👑'],
                        'desa' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'icon' => '🏛️'],
                        'rw' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'icon' => '🚩'],
                        'rt' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'icon' => '🏘️'],
                        'warga' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'icon' => '👤'],
                    ];
                    foreach ($users as $u): 
                        $bc = $badge_colors[$u['role']] ?? ['bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'icon' => '👤'];
                    ?>
                        <tr class="border-t border-slate-100 hover:bg-slate-50/70">
                            <td class="px-5 py-4 text-sm text-slate-500">#<?= $u['id'] ?></td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-800 text-white flex items-center justify-center font-bold text-sm shadow-md">
                                        <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                    </div>
                                    <div class="text-sm font-bold text-slate-900"><?= htmlspecialchars($u['name']) ?></div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-600"><?= htmlspecialchars($u['email']) ?></td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full <?= $bc['bg'] ?> <?= $bc['text'] ?> text-[11px] font-bold uppercase">
                                    <?= $bc['icon'] ?> <?= ucfirst(str_replace('_', ' ', $u['role'])) ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="/users?action=edit&id=<?= $u['id'] ?>" class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-amber-100 text-amber-700 hover:bg-amber-500 hover:text-white transition-all" title="Edit">
                                    ✏️
                                </a>
                                <?php if ($u['id'] != $_SESSION['user']['id']): ?>
                                    <a href="/users?action=hapus&id=<?= $u['id'] ?>" onclick="return confirm('Hapus user ini?')" class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-rose-100 text-rose-700 hover:bg-rose-500 hover:text-white transition-all ml-1" title="Hapus">
                                        🗑️
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>