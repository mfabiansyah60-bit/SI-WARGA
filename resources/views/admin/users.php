<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'super_admin') { header('Location: /login'); exit; }
$page_title = 'Manajemen User';
include __DIR__ . '/../layouts/dashboard_layout.php';

$roleCounts = $roleCounts ?? ['super_admin'=>0,'desa'=>0,'rw'=>0,'rt'=>0,'warga'=>0];
$totalUsers = array_sum($roleCounts);

$badge_colors = [
    'super_admin' => ['bg' => 'bg-rose-100',    'text' => 'text-rose-700',    'border' => 'border-rose-200',    'icon' => 'shield_person'],
    'desa'        => ['bg' => 'bg-blue-100',    'text' => 'text-blue-700',    'border' => 'border-blue-200',    'icon' => 'account_balance'],
    'rw'          => ['bg' => 'bg-violet-100',  'text' => 'text-violet-700',  'border' => 'border-violet-200',  'icon' => 'flag'],
    'rt'          => ['bg' => 'bg-amber-100',   'text' => 'text-amber-700',   'border' => 'border-amber-200',   'icon' => 'home_work'],
    'warga'       => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'icon' => 'person'],
];
?>

<!-- ============ HEADER ============ -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 border border-blue-200 mb-3">
            <span class="material-symbols-outlined text-blue-600 text-[16px]">group</span>
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-blue-700">Manajemen Akun</span>
        </div>
        <h1 class="text-3xl font-black text-slate-900 tracking-tight leading-tight mb-1">
            Manajemen User
        </h1>
        <p class="text-sm text-slate-600">
            Kelola <strong class="text-slate-900"><?= $totalUsers ?> akun</strong> — role, akses, dan data pengguna sistem.
        </p>
    </div>
    <a href="/users?action=tambah" class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 text-white text-sm font-extrabold shadow-lg shadow-blue-700/30 hover:shadow-xl hover:-translate-y-0.5 transition-all">
        <span class="material-symbols-outlined text-[20px]">person_add</span>
        Tambah User
    </a>
</div>

<!-- ============ STAT CARDS ============ -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
    <div class="stat-mini stat-mini-total">
        <div class="stat-mini-icon"><span class="material-symbols-outlined">groups</span></div>
        <div class="stat-mini-value"><?= $totalUsers ?></div>
        <div class="stat-mini-label">Total</div>
    </div>
    <div class="stat-mini stat-mini-sa">
        <div class="stat-mini-icon"><span class="material-symbols-outlined">shield_person</span></div>
        <div class="stat-mini-value"><?= $roleCounts['super_admin'] ?></div>
        <div class="stat-mini-label">Super Admin</div>
    </div>
    <div class="stat-mini stat-mini-desa">
        <div class="stat-mini-icon"><span class="material-symbols-outlined">account_balance</span></div>
        <div class="stat-mini-value"><?= $roleCounts['desa'] ?></div>
        <div class="stat-mini-label">Admin Desa</div>
    </div>
    <div class="stat-mini stat-mini-rw">
        <div class="stat-mini-icon"><span class="material-symbols-outlined">flag</span></div>
        <div class="stat-mini-value"><?= $roleCounts['rw'] ?></div>
        <div class="stat-mini-label">Ketua RW</div>
    </div>
    <div class="stat-mini stat-mini-rt">
        <div class="stat-mini-icon"><span class="material-symbols-outlined">home_work</span></div>
        <div class="stat-mini-value"><?= $roleCounts['rt'] ?></div>
        <div class="stat-mini-label">Ketua RT</div>
    </div>
    <div class="stat-mini stat-mini-warga">
        <div class="stat-mini-icon"><span class="material-symbols-outlined">person</span></div>
        <div class="stat-mini-value"><?= $roleCounts['warga'] ?></div>
        <div class="stat-mini-label">Warga</div>
    </div>
</div>

<!-- ============ SEARCH & FILTER ============ -->
<form method="GET" action="/users" class="glass-card rounded-2xl p-4 mb-5 border border-slate-200/70">
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[20px] pointer-events-none">search</span>
            <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                   placeholder="Cari nama, email, atau no telepon..."
                   class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
        </div>
        <select name="role" class="px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all cursor-pointer min-w-[160px]">
            <option value="">Semua Role</option>
            <option value="super_admin" <?= ($_GET['role'] ?? '') === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
            <option value="desa"        <?= ($_GET['role'] ?? '') === 'desa' ? 'selected' : '' ?>>Admin Desa</option>
            <option value="rw"          <?= ($_GET['role'] ?? '') === 'rw' ? 'selected' : '' ?>>Ketua RW</option>
            <option value="rt"          <?= ($_GET['role'] ?? '') === 'rt' ? 'selected' : '' ?>>Ketua RT</option>
            <option value="warga"       <?= ($_GET['role'] ?? '') === 'warga' ? 'selected' : '' ?>>Warga</option>
        </select>
        <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-extrabold shadow-md transition-all">
            <span class="material-symbols-outlined text-[18px]">filter_list</span> Filter
        </button>
        <?php if (!empty($_GET['q']) || !empty($_GET['role'])): ?>
            <a href="/users" class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-sm font-bold transition-all">
                <span class="material-symbols-outlined text-[18px]">close</span> Reset
            </a>
        <?php endif; ?>
    </div>
</form>

<!-- ============ TABLE ============ -->
<div class="glass-card rounded-3xl overflow-hidden border border-slate-200/70">
    <?php if (empty($users)): ?>
        <div class="p-16 text-center">
            <div class="w-20 h-20 mx-auto rounded-3xl bg-slate-100 flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-[40px] text-slate-400">group_off</span>
            </div>
            <h3 class="text-lg font-extrabold text-slate-800 mb-1">Belum Ada User</h3>
            <p class="text-xs text-slate-500">Klik "Tambah User" untuk mulai menambahkan akun</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1050px]">
                <thead class="bg-slate-50/80 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-5 py-4 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 w-16">No</th>
                        <th class="text-left px-5 py-4 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">Pengguna</th>
                        <th class="text-left px-5 py-4 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">Email</th>
                        <th class="text-left px-5 py-4 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">No. Telepon</th>
                        <th class="text-left px-5 py-4 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">Role</th>
                        <th class="text-left px-5 py-4 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="text-left px-5 py-4 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">RT / RW</th>
                        <th class="text-right px-5 py-4 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 w-32">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $index => $u): 
                        $bc = $badge_colors[$u['role']] ?? ['bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'border' => 'border-slate-200', 'icon' => 'person'];
                        $inisial = strtoupper(substr($u['name'] ?? 'U', 0, 1));
                        $colors = ['#2563eb', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#ef4444'];
                        $bgColor = $colors[ord($inisial) % count($colors)];
                        
                        $status = $u['status_verifikasi'] ?? 'active';
                    ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50/70 transition-colors">
                            <td class="px-5 py-4 text-sm font-bold text-slate-400"><?= $index + 1 ?></td>
                            
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl text-white flex items-center justify-center font-extrabold text-sm shadow-md flex-shrink-0" style="background:<?= $bgColor ?>;">
                                        <?= htmlspecialchars($inisial) ?>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-sm font-bold text-slate-900 truncate"><?= htmlspecialchars($u['name']) ?></div>
                                        <div class="text-[11px] text-slate-500 font-semibold"><?= !empty($u['jenis_kelamin']) ? ($u['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan') : '-' ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-700 font-medium"><?= htmlspecialchars($u['email']) ?></td>
                            
                            <!-- UBAH BAGIAN INI: Nampilin no_telp, fallback ke no_hp -->
                            <td class="px-5 py-4 text-sm">
                                <?php 
                                    $nomor_tampil = $u['no_telp'] ?? ($u['no_hp'] ?? '');
                                    if (!empty($nomor_tampil)): 
                                ?>
                                    <div class="text-slate-700 font-medium"><?= htmlspecialchars($nomor_tampil) ?></div>
                                    <div class="mt-1">
                                        <?php if (!isset($u['wa_opt_in']) || (int)$u['wa_opt_in'] === 1): ?>
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200" title="<?= !empty($u['wa_opt_in_at']) ? 'Disetujui: ' . date('d/m/Y H:i', strtotime($u['wa_opt_in_at'])) : 'Menyetujui notifikasi WA' ?>">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Opt-In WA
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-md border border-rose-200" title="<?= !empty($u['wa_opt_in_at']) ? 'Menolak sejak: ' . date('d/m/Y H:i', strtotime($u['wa_opt_in_at'])) : 'Menolak notifikasi WA' ?>">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Opt-Out WA
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-slate-400 italic text-xs">Belum diisi</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full <?= $bc['bg'] ?> <?= $bc['text'] ?> border <?= $bc['border'] ?> text-[11px] font-extrabold uppercase tracking-wide">
                                    <span class="material-symbols-outlined text-[13px]" style="font-variation-settings:'FILL' 1;"><?= $bc['icon'] ?></span>
                                    <?= ucfirst(str_replace('_', ' ', $u['role'])) ?>
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <form method="POST" action="/users?action=update_status" class="inline-block">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <select name="status_verifikasi" onchange="this.form.submit()" 
                                            class="text-[11px] font-extrabold uppercase px-3 py-1.5 rounded-lg border-0 cursor-pointer focus:ring-2 focus:ring-blue-600 
                                            <?= $status === 'active' ? 'bg-emerald-100 text-emerald-700' : '' ?>
                                            <?= $status === 'pending' ? 'bg-amber-100 text-amber-700' : '' ?>
                                            <?= $status === 'rejected' ? 'bg-rose-100 text-rose-700' : '' ?>
                                            <?= $status === 'rt_approved' ? 'bg-blue-100 text-blue-700' : '' ?>
                                            ">
                                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Aktif</option>
                                        <option value="rt_approved" <?= $status === 'rt_approved' ? 'selected' : '' ?>>Disetujui RT</option>
                                        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Ditolak</option>
                                    </select>
                                </form>
                            </td>
                            <td class="px-5 py-4 text-xs font-bold">
                                <?php if (!empty($u['rt']) || !empty($u['rw'])): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700">
                                        <?php if (!empty($u['rt'])): ?>RT <?= htmlspecialchars($u['rt']) ?><?php endif; ?>
                                        <?php if (!empty($u['rt']) && !empty($u['rw'])): ?> · <?php endif; ?>
                                        <?php if (!empty($u['rw'])): ?>RW <?= htmlspecialchars($u['rw']) ?><?php endif; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-300">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="/users?action=view&id=<?= $u['id'] ?>" title="Lihat Detail" class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-700 hover:text-white transition-all">
                                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                                    </a>
                                    <a href="/users?action=edit&id=<?= $u['id'] ?>" title="Edit User" class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-amber-100 text-amber-700 hover:bg-amber-500 hover:text-white transition-all">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </a>
                                    
                                    <?php if ($u['id'] != $_SESSION['user']['id']): ?>
                                        <form action="/users?action=delete" method="POST" class="inline-block m-0 p-0">
                                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                            <button type="button" onclick="confirmDelete(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['name'])) ?>')" title="Hapus User" class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-rose-100 text-rose-700 hover:bg-rose-500 hover:text-white transition-all">
                                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ============ CUSTOM STYLES ============ -->
<style>
    .stat-mini {
        position: relative;
        padding: 16px;
        border-radius: 18px;
        border: 1px solid var(--border);
        background: var(--bg-card);
        overflow: hidden;
        transition: all .2s cubic-bezier(.2,1.25,.4,1);
    }
    .stat-mini:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px -10px rgba(15,23,42,.15);
    }
    .stat-mini-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        color: white;
    }
    .stat-mini-icon .material-symbols-outlined {
        font-size: 20px;
        font-variation-settings: 'FILL' 1;
    }
    .stat-mini-value {
        font-size: 22px;
        font-weight: 900;
        color: var(--text-primary);
        line-height: 1;
        letter-spacing: -.5px;
    }
    .stat-mini-label {
        font-size: 10.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .4px;
        color: var(--text-muted);
        margin-top: 5px;
    }
    .stat-mini-total   .stat-mini-icon { background: linear-gradient(135deg, #475569, #1e293b); }
    .stat-mini-sa      .stat-mini-icon { background: linear-gradient(135deg, #ef4444, #b91c1c); }
    .stat-mini-desa    .stat-mini-icon { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
    .stat-mini-rw      .stat-mini-icon { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
    .stat-mini-rt      .stat-mini-icon { background: linear-gradient(135deg, #f59e0b, #b45309); }
    .stat-mini-warga   .stat-mini-icon { background: linear-gradient(135deg, #10b981, #047857); }
</style>

<!-- ============ MODAL KONFIRMASI HAPUS ============ -->
<div id="deleteModal" class="hidden fixed inset-0 z-[100] items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
    <div id="deleteModalCard" class="bg-white rounded-3xl shadow-2xl max-w-[400px] w-full p-6 sm:p-8 transition-all duration-200 scale-95 opacity-0 transform">
        
        <div class="w-16 h-16 rounded-3xl bg-rose-50 text-rose-500 flex items-center justify-center mb-5 mx-auto">
            <span class="material-symbols-outlined text-[32px]">person_remove</span>
        </div>
        
        <h3 class="text-xl font-extrabold text-slate-900 text-center mb-2">Hapus User?</h3>
        <p class="text-[14px] text-slate-500 text-center leading-relaxed mb-6">
            Anda yakin ingin menghapus akun <strong id="deleteUserName" class="text-slate-800"></strong>? Tindakan ini tidak dapat dibatalkan.
        </p>

        <div class="flex gap-3">
            <button type="button" onclick="closeDeleteModal()" class="flex-1 py-3.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm transition-all">
                Batal
            </button>
            <button type="button" id="deleteConfirmBtn" class="flex-1 py-3.5 rounded-xl bg-gradient-to-r from-rose-600 to-red-700 hover:from-rose-700 hover:to-red-800 text-white font-extrabold text-sm shadow-lg shadow-rose-600/30 transition-all">
                Ya, Hapus
            </button>
        </div>
    </div>
</div>

<script>
    let targetDeleteForm = null;

    function confirmDelete(id, name) {
        document.getElementById('deleteUserName').textContent = name;
        targetDeleteForm = document.querySelector(`form[action="/users?action=delete"] input[value="${id}"]`).closest('form');
        
        const modal = document.getElementById('deleteModal');
        const card = document.getElementById('deleteModalCard');
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        setTimeout(() => {
            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        const card = document.getElementById('deleteModalCard');
        
        card.classList.remove('scale-100', 'opacity-100');
        card.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 200);
    }

    document.getElementById('deleteConfirmBtn').addEventListener('click', function() {
        if (targetDeleteForm) {
            targetDeleteForm.submit();
        }
    });
</script>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>