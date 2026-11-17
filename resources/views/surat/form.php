<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user'])) { header('Location: /login'); exit; }

$is_edit = ($mode === 'edit');
$page_title = ($is_edit ? 'Edit' : 'Tambah') . ' Template Surat';
include __DIR__ . '/../layouts/dashboard_layout.php';
?>

<!-- TOMBOL KEMBALI -->
<div class="mb-5">
    <a href="/surat" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold transition-all">
        ← Kembali
    </a>
</div>

<!-- FORM CARD -->
<div class="glass-card rounded-2xl p-6 sm:p-8 max-w-4xl">

    <!-- HEADER FORM -->
    <div class="flex items-center gap-3 mb-6 pb-5 border-b border-slate-100">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-700 to-indigo-900 text-white flex items-center justify-center shadow-lg shadow-blue-700/25 text-2xl">
            <?= $is_edit ? '✏️' : '📄' ?>
        </div>
        <div>
            <h2 class="text-xl font-extrabold text-slate-900"><?= $is_edit ? 'Edit Template Surat' : 'Tambah Template Surat Baru' ?></h2>
            <p class="text-xs text-slate-500"><?= $is_edit ? 'Ubah data template yang sudah ada' : 'Buat template surat baru untuk warga desa' ?></p>
        </div>
    </div>

    <form method="POST" action="/surat?action=<?= $is_edit ? 'update&id=' . $template['id'] : 'simpan' ?>" class="space-y-5">

        <!-- SECTION 1: INFO DASAR -->
        <div class="bg-slate-50/70 p-5 rounded-2xl border border-slate-100">
            <h3 class="text-xs font-bold text-slate-700 mb-4 uppercase tracking-wider flex items-center gap-2">
                ℹ️ Informasi Dasar
            </h3>

            <!-- NAMA TEMPLATE -->
            <div class="mb-4">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Nama Template <span class="text-rose-500">*</span></label>
                <input type="text" name="nama" 
                       value="<?= htmlspecialchars($template['nama'] ?? '') ?>"
                       placeholder="Contoh: Surat Keterangan Domisili"
                       required maxlength="100"
                       class="block w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
                <div class="text-[11px] text-slate-400 mt-1">💡 Nama yang muncul di daftar template</div>
            </div>

            <!-- JUDUL SURAT -->
            <div class="mb-4">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Judul Surat <span class="text-rose-500">*</span></label>
                <input type="text" name="judul" 
                       value="<?= htmlspecialchars($template['judul'] ?? '') ?>"
                       placeholder="Contoh: SURAT KETERANGAN DOMISILI"
                       required maxlength="255"
                       class="block w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
                <div class="text-[11px] text-slate-400 mt-1">💡 Judul yang tampil di kepala surat (huruf kapital)</div>
            </div>

            <!-- ICON PILIHAN -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Pilih Icon</label>
                <div class="grid grid-cols-4 gap-2 mt-2">
                    <?php 
                    $icons = [
                        'fa-file-alt'          => ['📄', 'File Default'],
                        'fa-home'              => ['🏠', 'Domisili'],
                        'fa-store'             => ['🏪', 'Usaha'],
                        'fa-hand-holding-heart'=> ['🤝', 'SKTM'],
                        'fa-id-card'           => ['🪪', 'KTP'],
                        'fa-user-shield'       => ['🛡️', 'SKCK'],
                        'fa-file-signature'    => ['✍️', 'Tanda Tangan'],
                        'fa-scroll'            => ['📜', 'Gulungan'],
                    ];
                    $current_icon = $template['icon'] ?? 'fa-file-alt';
                    foreach ($icons as $val => $info): 
                    ?>
                        <label class="cursor-pointer">
                            <input type="radio" name="icon" value="<?= $val ?>" 
                                   <?= $current_icon === $val ? 'checked' : '' ?>
                                   class="hidden peer">
                            <div class="p-3 border-2 <?= $current_icon === $val ? 'border-blue-600 bg-blue-50' : 'border-slate-200 bg-white' ?> rounded-xl text-center transition-all hover:border-blue-400 peer-checked:border-blue-600 peer-checked:bg-blue-50">
                                <div class="text-2xl mb-1"><?= $info[0] ?></div>
                                <div class="text-[10px] font-bold <?= $current_icon === $val ? 'text-blue-700' : 'text-slate-500' ?>"><?= $info[1] ?></div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- SECTION 2: ISI TEMPLATE -->
        <div class="bg-slate-50/70 p-5 rounded-2xl border border-slate-100">
            <h3 class="text-xs font-bold text-slate-700 mb-4 uppercase tracking-wider flex items-center gap-2">
                📝 Isi Template Surat
            </h3>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Isi Surat <span class="text-rose-500">*</span></label>
                <textarea name="isi_template" required 
                          rows="12"
                          style="font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.7;"
                          placeholder="Tulis isi surat dengan kata kunci nama, nik, alamat, dll..."
                          class="block w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all"><?= htmlspecialchars($template['isi_template'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- SECTION 3: PANDUAN KATA KUNCI -->
        <div class="bg-gradient-to-br from-blue-50 to-blue-100/50 rounded-2xl p-5 border-l-4 border-l-blue-600">
            <h4 class="text-sm font-bold text-blue-800 mb-4 flex items-center gap-2">
                💡 Kata Kunci Otomatis
                <span class="text-[10px] font-bold bg-blue-600 text-white px-2 py-0.5 rounded-full ml-auto">DIGANTI DATA WARGA</span>
            </h4>

            <?php 
            $placeholders_wajib = [
                ['nama',       'Nama lengkap warga',    '👤'],
                ['nik',        'NIK (16 digit)',        '🪪'],
                ['ttl',        'Tempat/tanggal lahir',  '📅'],
                ['jk',         'Jenis kelamin',         '⚧'],
                ['pekerjaan',  'Pekerjaan',             '💼'],
                ['alamat',     'Alamat lengkap',        '📍'],
                ['rt',         'Nomor RT',              '🏠'],
                ['rw',         'Nomor RW',              '🚩'],
                ['desa',       'Nama desa',             '🏛️'],
                ['keperluan',  'Keperluan surat',       '📋'],
                ['tanggal',    'Tanggal hari ini',      '📅'],
            ];
            
            $placeholders_usaha = [
                ['nama_usaha',   'Nama usaha',    '🏪'],
                ['jenis_usaha',  'Jenis usaha',   '🏷️'],
                ['alamat_usaha', 'Alamat usaha',  '📍'],
            ];
            ?>

            <!-- DATA PRIBADI -->
            <div class="mb-4">
                <div class="text-[10px] font-bold text-blue-700 uppercase tracking-wider mb-2">👤 Data Pribadi</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <?php foreach ($placeholders_wajib as $p): ?>
                        <div class="flex items-center gap-2 p-2 bg-white rounded-lg border border-blue-100">
                            <span class="text-base"><?= $p[2] ?></span>
                            <code class="bg-blue-700 text-white px-2 py-1 rounded text-[11px] font-bold font-mono"><?= $p[0] ?></code>
                            <span class="text-[11px] text-slate-600 font-medium"><?= $p[1] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- KHUSUS USAHA -->
            <div>
                <div class="text-[10px] font-bold text-cyan-700 uppercase tracking-wider mb-2 flex items-center gap-2">
                    🏪 Khusus Surat Usaha
                    <span class="text-[9px] bg-cyan-600 text-white px-2 py-0.5 rounded-full">OPSIONAL</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <?php foreach ($placeholders_usaha as $p): ?>
                        <div class="flex items-center gap-2 p-2 bg-white rounded-lg border border-cyan-100">
                            <span class="text-base"><?= $p[2] ?></span>
                            <code class="bg-cyan-600 text-white px-2 py-1 rounded text-[11px] font-bold font-mono"><?= $p[0] ?></code>
                            <span class="text-[11px] text-slate-600 font-medium"><?= $p[1] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- SECTION 4: CONTOH -->
        <div class="bg-gradient-to-br from-emerald-50 to-emerald-100/50 rounded-2xl p-5 border-l-4 border-l-emerald-600">
            <h4 class="text-sm font-bold text-emerald-800 mb-3 flex items-center gap-2">
                ✅ Contoh Isi Template
                <button type="button" onclick="copyContoh()" 
                        class="ml-auto text-[10px] font-bold bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg transition-all">
                    📋 Copy
                </button>
            </h4>

            <div class="bg-white rounded-xl p-4 border border-emerald-200">
                <pre id="contohTemplate" style="font-family: 'Courier New', monospace; font-size: 12.5px; line-height: 1.8; color: #065f46; white-space: pre-wrap; margin: 0;">Yang bertanda tangan di bawah ini, Kepala Desa {desa}, menerangkan bahwa:

Nama Lengkap    : {nama}
NIK             : {nik}
Tempat/Tgl Lahir: {ttl}
Jenis Kelamin   : {jk}
Pekerjaan       : {pekerjaan}
Alamat          : {alamat}

Benar-benar berdomisili di {desa} RT {rt} RW {rw}.

Surat keterangan ini dibuat untuk keperluan: {keperluan}

Demikian surat keterangan ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.</pre>
            </div>

            <div class="mt-3 p-3 bg-blue-50 rounded-lg border border-blue-100 text-[11px] text-blue-800 flex items-start gap-2">
                <span>ℹ️</span>
                <span><strong>Ingat:</strong> Saat menulis di kolom "Isi Surat", gunakan <strong>kurung kurawal</strong>. Contoh: <code class="bg-blue-700 text-white px-1.5 py-0.5 rounded font-mono text-[10px]">{nama}</code> bukan <code class="bg-rose-600 text-white px-1.5 py-0.5 rounded font-mono text-[10px]">nama</code></span>
            </div>
        </div>

        <!-- BUTTONS -->
        <div class="flex gap-3 pt-5 border-t border-slate-100">
            <button type="submit" 
                    class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-bold shadow-md shadow-blue-700/25 transition-all">
                💾 <?= $is_edit ? 'Update Template' : 'Simpan Template' ?>
            </button>
            <a href="/surat" 
               class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold transition-all">
                Batal
            </a>
        </div>

    </form>
</div>

<script>
    // Icon selection
    document.querySelectorAll('input[name="icon"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('input[name="icon"] + div').forEach(el => {
                el.className = el.className.replace(/border-blue-600 bg-blue-50/g, 'border-slate-200 bg-white');
                el.querySelector('div:last-child').className = el.querySelector('div:last-child').className.replace(/text-blue-700/g, 'text-slate-500');
            });
            const parent = this.nextElementSibling;
            parent.className = parent.className.replace(/border-slate-200 bg-white/g, 'border-blue-600 bg-blue-50');
            parent.querySelector('div:last-child').className = parent.querySelector('div:last-child').className.replace(/text-slate-500/g, 'text-blue-700');
        });
    });

    // Copy contoh
    function copyContoh() {
        const text = document.getElementById('contohTemplate').innerText;
        navigator.clipboard.writeText(text).then(() => {
            const btn = event.target.closest('button');
            const original = btn.innerHTML;
            btn.innerHTML = '✅ Tersalin!';
            setTimeout(() => {
                btn.innerHTML = original;
            }, 1500);
        });
    }
</script>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>