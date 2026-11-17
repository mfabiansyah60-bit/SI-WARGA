<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
$page_title = 'Pelayanan Publik';
include __DIR__ . '/../layouts/dashboard_layout.php';

$isAdmin = in_array($role, ['super_admin', 'desa']);

function layanan_meta($nama) {
    $n = strtolower($nama);
    if (strpos($n, 'kelahiran') !== false || strpos($n, 'lahir') !== false)
        return ['icon' => 'child_care',      'grad' => 'from-pink-500 to-rose-600',      'text' => 'text-pink-600'];
    if (strpos($n, 'kematian') !== false || strpos($n, 'mati') !== false)
        return ['icon' => 'sentiment_sad',   'grad' => 'from-slate-500 to-slate-700',    'text' => 'text-slate-600'];
    if (strpos($n, 'kk') !== false || strpos($n, 'keluarga') !== false)
        return ['icon' => 'family_restroom', 'grad' => 'from-emerald-500 to-teal-600',   'text' => 'text-emerald-600'];
    if (strpos($n, 'domisili') !== false)
        return ['icon' => 'home',            'grad' => 'from-blue-500 to-indigo-600',    'text' => 'text-blue-600'];
    if (strpos($n, 'mampu') !== false || strpos($n, 'sktm') !== false)
        return ['icon' => 'volunteer_activism', 'grad' => 'from-amber-500 to-orange-600', 'text' => 'text-amber-600'];
    if (strpos($n, 'usaha') !== false || strpos($n, 'umkm') !== false)
        return ['icon' => 'storefront',      'grad' => 'from-violet-500 to-purple-600',  'text' => 'text-violet-600'];
    if (strpos($n, 'ktp') !== false)
        return ['icon' => 'badge',           'grad' => 'from-cyan-500 to-blue-600',      'text' => 'text-cyan-600'];
    if (strpos($n, 'skck') !== false || strpos($n, 'polisi') !== false)
        return ['icon' => 'local_police',    'grad' => 'from-indigo-500 to-blue-700',    'text' => 'text-indigo-600'];
    if (strpos($n, 'nikah') !== false || strpos($n, 'kawin') !== false)
        return ['icon' => 'favorite',        'grad' => 'from-rose-500 to-pink-600',      'text' => 'text-rose-600'];
    if (strpos($n, 'tanah') !== false || strpos($n, 'sertifikat') !== false)
        return ['icon' => 'landscape',       'grad' => 'from-lime-500 to-green-600',     'text' => 'text-lime-700'];
    if (strpos($n, 'izin') !== false)
        return ['icon' => 'verified',        'grad' => 'from-teal-500 to-emerald-600',   'text' => 'text-teal-600'];
    if (strpos($n, 'pindah') !== false)
        return ['icon' => 'move_up',         'grad' => 'from-orange-500 to-red-600',     'text' => 'text-orange-600'];
    return ['icon' => 'description',         'grad' => 'from-slate-500 to-slate-700',    'text' => 'text-slate-600'];
}
?>

<!-- ============ HEADER ============ -->
<div class="mb-7 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
    <div>
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 border border-blue-200 mb-3">
            <span class="material-symbols-outlined text-blue-600 text-[16px]">support_agent</span>
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-blue-700">Layanan Desa</span>
        </div>
        <h1 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight leading-tight mb-2">
            Katalog Layanan
        </h1>
        <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
            <strong class="text-slate-900"><?= count($pelayanan_list ?? []) ?> layanan</strong> tersedia — pilih dan ajukan sesuai kebutuhan Anda.
        </p>
    </div>
    <?php if ($isAdmin): ?>
        <a href="/pelayanan?action=tambah" 
           class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 text-white text-sm font-extrabold shadow-lg shadow-blue-700/30 hover:shadow-xl hover:shadow-blue-700/40 hover:-translate-y-0.5 transition-all">
            <span class="material-symbols-outlined text-[20px]">add_circle</span> 
            Tambah Pelayanan
        </a>
    <?php endif; ?>
</div>

<!-- ============ SEARCH BAR ============ -->
<?php if (!empty($pelayanan_list)): ?>
<div class="mb-6">
    <div class="relative max-w-xl">
        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[22px] pointer-events-none">search</span>
        <input type="text" 
               id="searchPelayanan" 
               placeholder="Cari layanan... (akta, KTP, KK, surat)" 
               class="w-full pl-12 pr-4 py-4 bg-white border-2 border-slate-200 rounded-2xl text-[15px] font-medium text-slate-800 placeholder-slate-400 focus:bg-white focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/10 transition-all shadow-sm">
    </div>
</div>
<?php endif; ?>

<!-- ============ LIST ============ -->
<?php if (empty($pelayanan_list)): ?>
    <div class="glass-card rounded-3xl p-20 text-center border border-slate-200/70">
        <div class="w-24 h-24 mx-auto rounded-3xl bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center mb-5">
            <span class="material-symbols-outlined text-[48px] text-slate-400">inbox</span>
        </div>
        <h3 class="text-xl font-extrabold text-slate-800 mb-2">Belum Ada Data Pelayanan</h3>
        <p class="text-sm text-slate-500 max-w-xs mx-auto leading-relaxed"><?= $isAdmin ? 'Klik tombol "Tambah Pelayanan" di atas untuk mulai menambahkan.' : 'Layanan akan muncul di sini setelah ditambahkan oleh admin desa.' ?></p>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5" id="pelayananGrid">
        <?php foreach ($pelayanan_list as $i => $p): 
            $meta = layanan_meta($p['nama']);
            $syaratList = [];
            if (!empty($p['syarat'])) {
                $syaratList = array_filter(array_map('trim', preg_split('/[,;\n\r]+/', $p['syarat'])));
            }
        ?>
            <div class="pelayanan-card group relative flex flex-col bg-white rounded-3xl border border-slate-200 overflow-hidden hover:border-slate-300 hover:shadow-2xl hover:shadow-slate-900/10 hover:-translate-y-1 transition-all duration-300" 
                 data-search="<?= htmlspecialchars(strtolower($p['nama'] . ' ' . ($p['deskripsi'] ?? '') . ' ' . ($p['syarat'] ?? ''))) ?>">
                
                <!-- Top strip -->
                <div class="h-1.5 bg-gradient-to-r <?= $meta['grad'] ?>"></div>
                
                <div class="flex-1 p-6">
                    <!-- Header: Icon + Title -->
                    <div class="flex items-start gap-4 mb-5">
                        <div class="flex-shrink-0 w-14 h-14 rounded-2xl bg-gradient-to-br <?= $meta['grad'] ?> text-white flex items-center justify-center shadow-lg shadow-current/20">
                            <span class="material-symbols-outlined text-[28px]"><?= $meta['icon'] ?></span>
                        </div>
                        <div class="flex-1 min-w-0 pt-0.5">
                            <h3 class="text-[17px] font-extrabold text-slate-900 leading-snug mb-1 tracking-tight">
                                <?= htmlspecialchars($p['nama']) ?>
                            </h3>
                            <?php if (!empty($p['desa_nama'])): ?>
                                <div class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-500 uppercase tracking-wide">
                                    <span class="material-symbols-outlined text-[13px]">location_on</span>
                                    <?= htmlspecialchars($p['desa_nama']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Deskripsi -->
                    <?php if (!empty($p['deskripsi'])): ?>
                        <p class="text-[13.5px] leading-relaxed text-slate-600 mb-5 line-clamp-2">
                            <?= htmlspecialchars($p['deskripsi']) ?>
                        </p>
                    <?php endif; ?>

                    <!-- Syarat -->
                    <?php if (!empty($syaratList)): ?>
                        <div class="pt-5 border-t border-dashed border-slate-200">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[14px] <?= $meta['text'] ?>">checklist</span>
                                    Persyaratan
                                </span>
                                <span class="text-[11px] font-bold text-slate-400">
                                    <?= count($syaratList) ?> item
                                </span>
                            </div>
                            <ul class="space-y-2">
                                <?php foreach (array_slice($syaratList, 0, 3) as $s): ?>
                                    <li class="flex items-start gap-2.5">
                                        <span class="material-symbols-outlined text-[16px] <?= $meta['text'] ?> flex-shrink-0 mt-0.5">check_circle</span>
                                        <span class="text-[13px] leading-snug text-slate-700 font-medium"><?= htmlspecialchars($s) ?></span>
                                    </li>
                                <?php endforeach; ?>
                                <?php if (count($syaratList) > 3): ?>
                                    <li class="text-[12px] text-slate-400 italic pl-7 font-medium">
                                        +<?= count($syaratList) - 3 ?> syarat lainnya — lihat detail
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="pt-5 border-t border-dashed border-slate-200">
                            <div class="p-3 rounded-xl bg-slate-50 border border-dashed border-slate-200 text-[12px] text-slate-400 text-center font-medium">
                                Belum ada info persyaratan
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Footer Aksi -->
                <div class="p-4 bg-slate-50/70 border-t border-slate-100 flex gap-2">
                    <?php if ($isAdmin): ?>
                        <a href="/pelayanan?action=edit&id=<?= $p['id'] ?>" 
                           class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl bg-white hover:bg-amber-50 text-amber-700 border border-amber-200 text-[13px] font-bold transition-all">
                            <span class="material-symbols-outlined text-[17px]">edit</span> Edit
                        </a>
                        <button type="button"
                                onclick="confirmHapus('/pelayanan?action=hapus&id=<?= $p['id'] ?>', '<?= htmlspecialchars(addslashes($p['nama'])) ?>')"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl bg-white hover:bg-rose-50 text-rose-700 border border-rose-200 text-[13px] font-bold transition-all">
                            <span class="material-symbols-outlined text-[17px]">delete</span> Hapus
                        </button>
                    <?php else: ?>
                        <button type="button"
                                onclick='showDetail(<?= json_encode([
                                    "nama"      => $p["nama"],
                                    "deskripsi" => $p["deskripsi"] ?? "",
                                    "desa_nama" => $p["desa_nama"] ?? "",
                                    "syarat"    => $p["syarat"] ?? "",
                                    "icon"      => $meta["icon"],
                                    "grad"      => $meta["grad"],
                                ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                class="w-full inline-flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 text-white text-[13px] font-extrabold shadow-sm hover:shadow-md transition-all">
                            <span class="material-symbols-outlined text-[17px]">visibility</span> 
                            Lihat Detail
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Empty search state -->
    <div id="emptySearch" class="hidden glass-card rounded-3xl p-16 text-center border border-slate-200/70">
        <div class="w-20 h-20 mx-auto rounded-3xl bg-slate-100 flex items-center justify-center mb-4">
            <span class="material-symbols-outlined text-[42px] text-slate-400">search_off</span>
        </div>
        <h3 class="text-lg font-extrabold text-slate-800 mb-1">Layanan tidak ditemukan</h3>
        <p class="text-sm text-slate-500">Coba kata kunci lain atau periksa ejaan</p>
    </div>
<?php endif; ?>

<!-- ============ MODAL DETAIL ============ -->
<div id="modalDetail" class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-hidden flex flex-col" style="animation: modalIn .25s cubic-bezier(.2,1.25,.4,1);">
        
        <div id="modalDetailHeader" class="p-6 bg-gradient-to-br from-blue-600 to-indigo-700 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-40 h-40 rounded-full bg-white/10 -mr-12 -mt-12"></div>
            <div class="absolute bottom-0 left-0 w-24 h-24 rounded-full bg-white/5 -ml-8 -mb-8"></div>
            <div class="relative flex items-start gap-4">
                <div class="flex-shrink-0 w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm border border-white/30 flex items-center justify-center shadow-inner">
                    <span class="material-symbols-outlined text-[32px]" id="modalDetailIcon">description</span>
                </div>
                <div class="flex-1 min-w-0 pt-1">
                    <h3 id="modalDetailTitle" class="text-2xl font-black leading-tight mb-2 tracking-tight">-</h3>
                    <div id="modalDetailDesa" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white/20 text-[11px] font-bold tracking-wide">
                        <span class="material-symbols-outlined text-[12px]">location_on</span>
                        <span>-</span>
                    </div>
                </div>
                <button type="button" onclick="closeDetail()" class="flex-shrink-0 w-10 h-10 rounded-xl bg-white/15 hover:bg-white/30 flex items-center justify-center transition-all">
                    <span class="material-symbols-outlined text-[22px]">close</span>
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-[20px] text-slate-400">info</span>
                    <span class="text-[12px] font-extrabold uppercase tracking-wider text-slate-500">Deskripsi Layanan</span>
                </div>
                <p id="modalDetailDesc" class="text-[14.5px] text-slate-700 leading-relaxed">-</p>
            </div>

            <div>
                <div class="flex items-center gap-2 mb-4">
                    <span class="material-symbols-outlined text-[20px] text-slate-400">checklist</span>
                    <span class="text-[12px] font-extrabold uppercase tracking-wider text-slate-500">Persyaratan Lengkap</span>
                </div>
                <ul id="modalDetailSyarat" class="space-y-2.5">
                    <!-- JS fill -->
                </ul>
            </div>

            <div class="p-4 rounded-2xl bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-200 flex items-start gap-3">
                <div class="flex-shrink-0 w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center shadow-sm">
                    <span class="material-symbols-outlined text-[20px]">lightbulb</span>
                </div>
                <div class="text-[13px] text-amber-900 leading-relaxed font-medium">
                    <strong class="font-extrabold">Tips:</strong> Bawa berkas asli + fotokopi saat mengajukan ke kantor desa. Pastikan semua dokumen masih berlaku.
                </div>
            </div>
        </div>

        <div class="p-4 border-t border-slate-100 bg-slate-50 flex gap-3">
            <button type="button" onclick="closeDetail()" class="flex-1 px-5 py-3.5 rounded-2xl bg-white border-2 border-slate-200 hover:bg-slate-100 hover:border-slate-300 text-slate-700 text-sm font-extrabold transition-all">
                Tutup
            </button>
            <a href="/surat?action=buat" class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 text-white text-sm font-extrabold shadow-lg shadow-blue-700/30 hover:shadow-xl transition-all">
                <span class="material-symbols-outlined text-[18px]">send</span>
                Ajukan Sekarang
            </a>
        </div>
    </div>
</div>

<style>
@keyframes modalIn {
    from { opacity: 0; transform: scale(.94) translateY(12px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
.pelayanan-card h3 { letter-spacing: -0.02em; }
</style>

<script>
// SEARCH LIVE FILTER
(function(){
    var input = document.getElementById('searchPelayanan');
    if (!input) return;
    var cards = document.querySelectorAll('.pelayanan-card');
    var empty = document.getElementById('emptySearch');
    
    input.addEventListener('input', function(){
        var q = this.value.toLowerCase().trim();
        var shown = 0;
        cards.forEach(function(card){
            var match = !q || card.dataset.search.indexOf(q) !== -1;
            card.style.display = match ? '' : 'none';
            if (match) shown++;
        });
        if (empty) empty.classList.toggle('hidden', shown > 0 || cards.length === 0);
    });
})();

// MODAL DETAIL
function showDetail(data) {
    document.getElementById('modalDetailTitle').textContent = data.nama || '-';
    document.getElementById('modalDetailDesc').textContent  = data.deskripsi || 'Belum ada deskripsi.';
    document.getElementById('modalDetailIcon').textContent  = data.icon || 'description';

    var hdr = document.getElementById('modalDetailHeader');
    hdr.className = 'p-6 bg-gradient-to-br ' + (data.grad || 'from-blue-600 to-indigo-700') + ' text-white relative overflow-hidden';

    var desaEl = document.getElementById('modalDetailDesa');
    if (data.desa_nama) {
        desaEl.innerHTML = '<span class="material-symbols-outlined text-[12px]">location_on</span><span>' + data.desa_nama + '</span>';
        desaEl.style.display = 'inline-flex';
    } else {
        desaEl.style.display = 'none';
    }

    var listEl = document.getElementById('modalDetailSyarat');
    var syaratArr = [];
    if (data.syarat) {
        syaratArr = data.syarat.split(/[,;\n\r]+/).map(function(s){ return s.trim(); }).filter(Boolean);
    }
    if (syaratArr.length === 0) {
        listEl.innerHTML = '<li class="text-[13px] text-slate-400 italic p-4 rounded-xl bg-slate-50 border border-dashed border-slate-200 text-center font-medium">Belum ada info persyaratan</li>';
    } else {
        listEl.innerHTML = syaratArr.map(function(s){
            return '<li class="flex items-start gap-3 p-3.5 rounded-xl bg-slate-50 border border-slate-100">' +
                '<span class="material-symbols-outlined text-emerald-600 text-[20px] flex-shrink-0 mt-0.5">check_circle</span>' +
                '<span class="text-[14px] text-slate-700 leading-snug font-medium">' + s + '</span>' +
                '</li>';
        }).join('');
    }

    var modal = document.getElementById('modalDetail');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeDetail() {
    var modal = document.getElementById('modalDetail');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

var modalEl = document.getElementById('modalDetail');
if (modalEl) {
    modalEl.addEventListener('click', function(e){
        if (e.target.id === 'modalDetail') closeDetail();
    });
}

document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') closeDetail();
});
</script>

<?php include __DIR__ . '/../layouts/modal_hapus.php'; ?>
<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>