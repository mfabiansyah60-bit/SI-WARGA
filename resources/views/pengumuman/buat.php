<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
$page_title = 'Buat Pengumuman';
include __DIR__ . '/../layouts/dashboard_layout.php';
?>

<!-- HEADER (Dibikin Lebih Sejajar) -->
<div class="max-w-6xl mx-auto mb-8">
    <div class="flex items-center gap-3 mb-4">
        <a href="/pengumuman" class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-blue-600 hover:border-blue-300 hover:shadow-sm transition-all">
            <span class="material-symbols-outlined text-[20px]">arrow_back</span>
        </a>
        <span class="text-sm font-bold text-slate-400">Kembali ke Daftar</span>
    </div>
    
    <div>
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 border border-indigo-200 mb-3">
            <span class="material-symbols-outlined text-indigo-600 text-[14px]">campaign</span>
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-indigo-700">Buat Baru</span>
        </div>
        <h1 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight leading-tight mb-2">Terbitkan Pengumuman</h1>
        <p class="text-[15px] text-slate-500 font-medium max-w-xl">Sampaikan informasi penting kepada seluruh warga desa dengan jelas dan rapi.</p>
    </div>
</div>

<!-- KONTEN UTAMA (Grid Seimbang) -->
<div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
    
    <!-- KOLOM KIRI: FORM (Makan 8/12 porsi) -->
    <div class="lg:col-span-8">
        <form method="POST" action="/pengumuman?action=buat" class="bg-white rounded-[24px] border border-slate-200 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden flex flex-col">
            
            <div class="p-6 sm:p-8 space-y-6 flex-1">
                <!-- Input Judul -->
                <div>
                    <label class="block text-[13px] font-extrabold uppercase tracking-wider text-slate-700 mb-2">
                        Judul Pengumuman <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                            <span class="material-symbols-outlined text-[20px]">title</span>
                        </div>
                        <input type="text" name="judul" required placeholder="Contoh: Kerja Bakti Bersih Desa" 
                               class="w-full pl-12 pr-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 text-[16px] font-bold placeholder-slate-400 placeholder:font-medium focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-500/15 focus:border-blue-500 transition-all shadow-sm">
                    </div>
                </div>

                <!-- Textarea Isi -->
                <div>
                    <label class="block text-[13px] font-extrabold uppercase tracking-wider text-slate-700 mb-2">
                        Isi Pengumuman <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <textarea name="isi" rows="10" required placeholder="Tulis detail informasi di sini... (Waktu, Tempat, Acara, dll)" 
                                  class="w-full px-5 py-5 bg-slate-50 border border-slate-200 rounded-2xl text-slate-700 text-[15px] leading-relaxed font-medium placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-500/15 focus:border-blue-500 transition-all resize-none shadow-sm"></textarea>
                        
                        <!-- Ikon dekorasi di pojok -->
                        <div class="absolute bottom-4 right-4 text-slate-300 pointer-events-none">
                            <span class="material-symbols-outlined text-[24px]">edit_note</span>
                        </div>
                    </div>
                </div>

                <!-- Rincian Opsional: Lokasi, Jadwal, Imbauan -->
                <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:p-5 transition-all">
                    <button type="button" onclick="toggleExtraFields()" class="flex items-center justify-between w-full text-left font-extrabold text-[13px] uppercase tracking-wider text-slate-700 hover:text-blue-600 transition-colors">
                        <span class="inline-flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px] text-blue-600">tune</span>
                            Rincian Lokasi, Jadwal & Imbauan (Opsional)
                        </span>
                        <span class="material-symbols-outlined text-[20px] text-slate-400 transition-transform duration-200" id="extraFieldsChevron">expand_more</span>
                    </button>
                    <p class="text-xs text-slate-500 mt-1">Dapat diisi jika ingin memisahkan detail secara terstruktur pada notifikasi WhatsApp, atau cukup tuliskan di dalam kotak Isi Pengumuman di atas (sistem akan mendeteksinya otomatis).</p>
                    
                    <div id="extraFieldsContainer" class="hidden mt-4 space-y-4 pt-3 border-t border-slate-200">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">📍 Tempat / Lokasi Kegiatan</label>
                            <input type="text" name="lokasi" placeholder="Contoh: Balai Warga RW 03 / Lapangan Desa" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">⏰ Jadwal / Waktu Pelaksanaan</label>
                            <input type="text" name="jadwal" placeholder="Contoh: Minggu, 15 November 2026 pukul 07.30 WIB s.d Selesai" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">⚠️ Imbauan & Catatan Khusus Warga</label>
                            <input type="text" name="imbauan" placeholder="Contoh: Harap membawa peralatan kebersihan dan mengenakan pakaian olahraga" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-sm">
                        </div>
                    </div>
                </div>

                <script>
                function toggleExtraFields() {
                    const c = document.getElementById('extraFieldsContainer');
                    const chevron = document.getElementById('extraFieldsChevron');
                    if (c.classList.contains('hidden')) {
                        c.classList.remove('hidden');
                        if (chevron) chevron.style.transform = 'rotate(180deg)';
                    } else {
                        c.classList.add('hidden');
                        if (chevron) chevron.style.transform = 'rotate(0deg)';
                    }
                }
                </script>

                <!-- Pilihan Saluran Pengiriman Notifikasi -->
                <div>
                    <label class="block text-[13px] font-extrabold uppercase tracking-wider text-slate-700 mb-1">
                        Pilihan Saluran Pengiriman Notifikasi <span class="text-rose-500">*</span>
                    </label>
                    <p class="text-xs text-slate-500 mb-3">Pilih saluran yang digunakan untuk menyebarkan pengumuman kepada warga:</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                        <!-- Opsi 1: WhatsApp & Gmail Sekaligus (Rekomendasi) -->
                        <label class="relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition-all border-blue-500 bg-blue-50/40 text-slate-900 shadow-sm channel-card" id="card-channel-both">
                            <input type="radio" name="saluran" value="both" checked class="sr-only" onchange="selectChannel('both')">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-1.5 text-blue-600">
                                    <span class="material-symbols-outlined text-[20px] text-emerald-600">chat</span>
                                    <span class="text-xs font-bold text-slate-400">+</span>
                                    <span class="material-symbols-outlined text-[20px] text-blue-600">mail</span>
                                </div>
                                <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 border border-blue-200">Rekomendasi</span>
                            </div>
                            <div class="font-extrabold text-[14px] text-slate-900 mb-1">WhatsApp & Gmail</div>
                            <div class="text-[12px] text-slate-500 leading-snug font-medium">Kirim ke nomor WhatsApp dan surel Gmail warga sekaligus.</div>
                        </label>

                        <!-- Opsi 2: WhatsApp Saja -->
                        <label class="relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition-all border-slate-200 bg-white text-slate-700 hover:border-emerald-300 channel-card" id="card-channel-wa">
                            <input type="radio" name="saluran" value="whatsapp" class="sr-only" onchange="selectChannel('whatsapp')">
                            <div class="flex items-center justify-between mb-2">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100">
                                    <span class="material-symbols-outlined text-[20px]">chat</span>
                                </div>
                                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">Fonnte</span>
                            </div>
                            <div class="font-extrabold text-[14px] text-slate-900 mb-1">WhatsApp Saja</div>
                            <div class="text-[12px] text-slate-500 leading-snug font-medium">Kirim notifikasi pengumuman khusus ke nomor WhatsApp warga.</div>
                        </label>

                        <!-- Opsi 3: Gmail/Email Saja -->
                        <label class="relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition-all border-slate-200 bg-white text-slate-700 hover:border-indigo-300 channel-card" id="card-channel-email">
                            <input type="radio" name="saluran" value="email" class="sr-only" onchange="selectChannel('email')">
                            <div class="flex items-center justify-between mb-2">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100">
                                    <span class="material-symbols-outlined text-[20px]">mail</span>
                                </div>
                                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">SMTP</span>
                            </div>
                            <div class="font-extrabold text-[14px] text-slate-900 mb-1">Gmail / Email Saja</div>
                            <div class="text-[12px] text-slate-500 leading-snug font-medium">Kirim surat resmi pengumuman hanya ke alamat surel Gmail warga.</div>
                        </label>
                    </div>
                </div>
            </div>

            <script>
            function selectChannel(val) {
                const cardBoth = document.getElementById('card-channel-both');
                const cardWa = document.getElementById('card-channel-wa');
                const cardEmail = document.getElementById('card-channel-email');
                
                [cardBoth, cardWa, cardEmail].forEach(c => {
                    if (c) c.className = "relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition-all border-slate-200 bg-white text-slate-700 channel-card";
                });

                if (val === 'both' && cardBoth) {
                    cardBoth.className = "relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition-all border-blue-500 bg-blue-50/40 text-slate-900 shadow-sm channel-card";
                } else if (val === 'whatsapp' && cardWa) {
                    cardWa.className = "relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition-all border-emerald-500 bg-emerald-50/40 text-slate-900 shadow-sm channel-card";
                } else if (val === 'email' && cardEmail) {
                    cardEmail.className = "relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition-all border-indigo-500 bg-indigo-50/40 text-slate-900 shadow-sm channel-card";
                }
            }
            </script>

            <!-- Tombol Aksi -->
            <div class="px-6 py-5 sm:px-8 bg-slate-50/80 border-t border-slate-200 flex flex-col-reverse sm:flex-row gap-3 sm:justify-end">
                <a href="/pengumuman" class="px-6 py-3.5 rounded-xl bg-white border-2 border-slate-200 hover:bg-slate-100 text-slate-700 text-[14px] font-extrabold transition-all text-center">
                    Batal
                </a>
                <button type="submit" class="px-8 py-3.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 text-white text-[14px] font-extrabold shadow-lg shadow-blue-700/30 hover:shadow-xl hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">send</span>
                    Terbitkan Sekarang
                </button>
            </div>
        </form>
    </div>

    <!-- KOLOM KANAN: PANDUAN (Makan 4/12 porsi) -->
    <div class="lg:col-span-4 sticky top-24 space-y-5">
        
        <!-- Card Tips (DESAIN BARU: Putih Bersih, Kontras Tinggi) -->
        <div class="bg-white rounded-[24px] p-6 border border-slate-200 shadow-sm relative overflow-hidden">
            <!-- Hiasan background tipis -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-50 rounded-bl-full pointer-events-none -mr-10 -mt-10"></div>

            <div class="relative z-10">
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mb-4 border border-indigo-100 shadow-sm">
                    <span class="material-symbols-outlined text-[24px]">lightbulb</span>
                </div>
                <h3 class="text-[17px] font-extrabold text-slate-900 mb-2">Tips Pengumuman</h3>
                <p class="text-[13px] text-slate-500 leading-relaxed mb-5 font-medium">
                    Agar warga mudah memahami informasi, perhatikan hal berikut:
                </p>
                <ul class="space-y-4">
                    <li class="flex items-start gap-3 text-[13.5px] font-medium text-slate-700">
                        <span class="material-symbols-outlined text-[18px] text-emerald-500 mt-0.5 flex-shrink-0" style="font-variation-settings:'FILL' 1;">check_circle</span>
                        Sertakan <strong class="text-slate-900 font-bold">Waktu & Tanggal</strong> yang jelas.
                    </li>
                    <li class="flex items-start gap-3 text-[13.5px] font-medium text-slate-700">
                        <span class="material-symbols-outlined text-[18px] text-emerald-500 mt-0.5 flex-shrink-0" style="font-variation-settings:'FILL' 1;">check_circle</span>
                        Tuliskan <strong class="text-slate-900 font-bold">Lokasi</strong> secara detail.
                    </li>
                    <li class="flex items-start gap-3 text-[13.5px] font-medium text-slate-700">
                        <span class="material-symbols-outlined text-[18px] text-emerald-500 mt-0.5 flex-shrink-0" style="font-variation-settings:'FILL' 1;">check_circle</span>
                        Gunakan bahasa yang <strong class="text-slate-900 font-bold">sopan & mudah dimengerti</strong>.
                    </li>
                </ul>
            </div>
        </div>

        <!-- Card Info Tambahan -->
        <div class="bg-white rounded-[20px] p-5 border border-slate-200 shadow-sm flex items-start gap-3">
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-500 flex items-center justify-center flex-shrink-0 border border-amber-100">
                <span class="material-symbols-outlined text-[18px]">info</span>
            </div>
            <div>
                <h4 class="text-[13px] font-extrabold text-slate-900 mb-1">Catatan Penting</h4>
                <p class="text-[12px] text-slate-500 leading-relaxed font-medium">Pengumuman akan langsung muncul di halaman utama warga setelah Anda menekan tombol terbitkan.</p>
            </div>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>