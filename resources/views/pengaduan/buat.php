<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
$page_title = 'Buat Pengaduan';
include __DIR__ . '/../layouts/dashboard_layout.php';
?>

<div class="mb-5">
    <a href="/pengaduan" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold transition-all">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span> Kembali
    </a>
</div>

<div class="glass-card rounded-2xl p-6 sm:p-8 max-w-2xl">
    <div class="flex items-center gap-3 mb-6 pb-5 border-b border-slate-100">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-rose-500 to-red-700 text-white flex items-center justify-center shadow-lg shadow-rose-700/25">
            <span class="material-symbols-outlined text-[24px]">campaign</span>
        </div>
        <div>
            <h2 class="text-xl font-extrabold text-slate-900">Buat Pengaduan</h2>
            <p class="text-xs text-slate-500">Sampaikan keluhan atau masalah Anda</p>
        </div>
    </div>

    <div class="p-4 rounded-xl bg-blue-50 border border-blue-100 mb-6 flex items-start gap-3">
        <span class="material-symbols-outlined text-blue-600 text-[20px] flex-shrink-0">info</span>
        <p class="text-xs text-blue-800 leading-relaxed">Pastikan pengaduan Anda jelas dan detail agar dapat ditindaklanjuti dengan cepat.</p>
    </div>

    <form method="POST" action="/pengaduan?action=buat" enctype="multipart/form-data" class="space-y-5">
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Judul Pengaduan <span class="text-rose-500">*</span></label>
            <input type="text" name="judul" placeholder="Contoh: Lampu Jalan Mati di RT 03" required maxlength="200" class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Kategori</label>
                <select name="kategori" class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
                    <option value="umum">Umum</option>
                    <option value="infrastruktur">Infrastruktur (Jalan, Lampu)</option>
                    <option value="kebersihan">Kebersihan & Sampah</option>
                    <option value="keamanan">Keamanan</option>
                    <option value="pelayanan">Pelayanan Desa</option>
                    <option value="bencana">Darurat Bencana</option>
                    <option value="lainnya">Lainnya</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Prioritas <span class="text-rose-500">*</span></label>
                <select name="prioritas" required class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
                    <option value="rendah">🟢 Rendah</option>
                    <option value="sedang" selected>🟡 Sedang</option>
                    <option value="tinggi">🟠 Tinggi</option>
                    <option value="darurat">🔴 Darurat</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Lokasi Kejadian</label>
            <input type="text" name="lokasi" placeholder="Contoh: RT 03 / Depan Balai Desa / Jl. Melati No. 12" maxlength="255" class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
        </div>

        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Isi Pengaduan <span class="text-rose-500">*</span></label>
            <textarea name="isi" rows="5" placeholder="Jelaskan detail masalah Anda..." required class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all"></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">📷 Bukti Gambar 1</label>
                <input type="file" name="bukti_gambar" accept="image/jpeg,image/png,image/webp" capture="environment" onchange="previewImg(this, 'preview1')" class="block w-full text-xs text-slate-600 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:bg-blue-700 file:text-white file:font-bold file:text-xs hover:file:bg-blue-800 cursor-pointer">
                <img id="preview1" class="mt-2 rounded-xl max-h-48 hidden border border-slate-200">
                <p class="text-[10px] text-slate-400 mt-1">Max 4MB. JPG/PNG/WEBP</p>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">📷 Bukti Gambar 2 (opsional)</label>
                <input type="file" name="bukti_gambar_2" accept="image/jpeg,image/png,image/webp" capture="environment" onchange="previewImg(this, 'preview2')" class="block w-full text-xs text-slate-600 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:bg-slate-600 file:text-white file:font-bold file:text-xs hover:file:bg-slate-700 cursor-pointer">
                <img id="preview2" class="mt-2 rounded-xl max-h-48 hidden border border-slate-200">
                <p class="text-[10px] text-slate-400 mt-1">Opsional, kalau butuh 2 angle</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">☎️ Kontak (opsional)</label>
                <input type="text" name="kontak" placeholder="08xxxxxxxxxx" maxlength="30" class="block w-full px-4 py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all">
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-3 px-4 py-3 rounded-xl bg-slate-50 border border-slate-300 cursor-pointer hover:bg-slate-100 transition-all w-full">
                    <input type="checkbox" name="is_anonymous" value="1" class="w-4 h-4 rounded">
                    <span class="text-xs font-bold text-slate-700">🕵️ Kirim sebagai Anonymous</span>
                </label>
            </div>
        </div>

        <div class="flex gap-3 pt-5 border-t border-slate-100">
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-bold shadow-md transition-all">
                <span class="material-symbols-outlined text-[18px]">send</span> Kirim Pengaduan
            </button>
            <a href="/pengaduan" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold transition-all">Batal</a>
        </div>
    </form>
</div>

<script>
function previewImg(input, targetId) {
    const img = document.getElementById(targetId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { img.src = e.target.result; img.classList.remove('hidden'); };
        reader.readAsDataURL(input.files[0]);
    } else {
        img.src = ''; img.classList.add('hidden');
    }
}
</script>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>