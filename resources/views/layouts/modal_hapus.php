<!-- MODAL KONFIRMASI HAPUS - CIVIC HORIZON -->
<div id="modalHapus" class="fixed inset-0 z-[9999] hidden items-center justify-center p-4" style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl transform transition-all scale-95 opacity-0" id="modalContent" style="border: 1px solid rgba(226, 232, 240, 0.9);">
        
        <!-- ICON -->
        <div class="w-20 h-20 rounded-full mx-auto mb-5 flex items-center justify-center" style="background: linear-gradient(135deg, #fee2e2, #fecaca);">
            <span class="text-4xl">⚠️</span>
        </div>

        <!-- TITLE -->
        <h2 class="text-2xl font-extrabold text-slate-900 text-center mb-2">
            Yakin Hapus Data?
        </h2>

        <!-- SUBTITLE -->
        <p class="text-sm text-slate-500 text-center mb-6 leading-relaxed">
            Data yang dihapus <strong class="text-rose-600">tidak dapat dikembalikan</strong>. Pastikan Anda sudah yakin sebelum melanjutkan.
        </p>

        <!-- DATA YANG DIHAPUS -->
        <div class="bg-slate-50 rounded-2xl p-4 mb-6 border border-slate-100" id="dataInfo">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Data yang akan dihapus:</div>
            <div class="text-sm font-bold text-slate-700" id="dataName">-</div>
        </div>

        <!-- BUTTONS -->
        <div class="flex gap-3">
            <button onclick="closeModalHapus()" 
                    class="flex-1 py-3.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold transition-all">
                ❌ Batal
            </button>
            <a href="#" id="btnConfirmHapus"
               class="flex-1 py-3.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold transition-all text-center shadow-lg shadow-rose-600/25">
                🗑️ Ya, Hapus
            </a>
        </div>

    </div>
</div>

<style>
    #modalHapus.show {
        display: flex;
    }
    #modalHapus.show #modalContent {
        transform: scale(1);
        opacity: 1;
    }
</style>

<script>
    function confirmHapus(url, dataName) {
        const modal = document.getElementById('modalHapus');
        const modalContent = document.getElementById('modalContent');
        const dataNameEl = document.getElementById('dataName');
        const btnConfirm = document.getElementById('btnConfirmHapus');

        // Set data
        dataNameEl.textContent = dataName || 'Data ini';
        btnConfirm.href = url;

        // Show modal
        modal.classList.remove('hidden');
        modal.classList.add('show');
        
        // Animate
        setTimeout(() => {
            modalContent.style.transform = 'scale(1)';
            modalContent.style.opacity = '1';
        }, 10);

        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }

    function closeModalHapus() {
        const modal = document.getElementById('modalHapus');
        const modalContent = document.getElementById('modalContent');

        modalContent.style.transform = 'scale(0.95)';
        modalContent.style.opacity = '0';

        setTimeout(() => {
            modal.classList.remove('show');
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }, 200);
    }

    // Close on ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModalHapus();
        }
    });

    // Close on overlay click
    document.getElementById('modalHapus').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModalHapus();
        }
    });
</script>