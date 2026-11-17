<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'super_admin') { header('Location: /login'); exit; }
$page_title = 'Manajemen User';
include __DIR__ . '/../layouts/dashboard_layout.php';
?>

<div class="flex justify-between items-center mb-4 flex-wrap gap-3">
    <h2 class="text-lg font-extrabold text-slate-900">👥 Daftar User (<?= count($users ?? []) ?>)</h2>
    <a href="/users?action=tambah" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-bold shadow-md transition-all">
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
                    <?php foreach ($users as $u): ?>
                        <tr class="border-t border-slate-100 hover:bg-slate-50/70">
                            <td class="px-5 py-4 text-sm text-slate-500">#<?= $u['id'] ?></td>
                            <td class="px-5 py-4 text-sm font-bold text-slate-900"><?= htmlspecialchars($u['name']) ?></td>
                            <td class="px-5 py-4 text-sm text-slate-600"><?= htmlspecialchars($u['email']) ?></td>
                            <td class="px-5 py-4"><span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-[11px] font-bold uppercase"><?= ucfirst($u['role']) ?></span></td>
                            <td class="px-5 py-4 text-right">
                                <a href="/users?action=edit&id=<?= $u['id'] ?>" class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-amber-100 text-amber-700 hover:bg-amber-500 hover:text-white transition-all">✏️</a>
                                <?php if ($u['id'] != $_SESSION['user']['id']): ?>
                                    <a href="/users?action=hapus&id=<?= $u['id'] ?>" onclick="return confirm('Hapus?')" class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-rose-100 text-rose-700 hover:bg-rose-500 hover:text-white transition-all ml-1">🗑️</a>
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
