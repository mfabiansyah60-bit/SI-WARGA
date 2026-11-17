# DOKUMEN PERANCANGAN KEBUTUHAN APLIKASI
# PRD / SRS SIWARGA

## Sistem Informasi Pelayanan Desa Berbasis AI

**Proyek Akhir Mata Kuliah Rekayasa Perangkat Lunak**

| Identitas | Keterangan |
|---|---|
| Disusun oleh | Muhammad Fabiansyah |
| NIM | 41257127 |
| Kelas | TI 1B 2025 |
| Versi Dokumen | Revisi 3.0 |
| Jenis Dokumen | Dokumen Perancangan Aplikasi Web |

---

# PENJELASAN DOKUMEN

Dokumen PRD/SRS ini menjelaskan kebutuhan aplikasi SiWarga, meliputi permasalahan yang ingin diselesaikan, tujuan, ruang lingkup, pengguna, fitur, kualitas yang diharapkan, integrasi, serta metode pengujian.

Dokumen ini menjadi acuan untuk tahap desain sistem (SDD), pembuatan diagram, desain antarmuka, pengembangan, dan pengujian aplikasi.

**Catatan penting:** Seluruh fitur dalam dokumen ini merupakan kebutuhan atau target rancangan. Keberadaan fitur dan keberhasilannya harus dibuktikan melalui pemeriksaan serta pengujian. Dokumen ini tidak menyatakan bahwa seluruh kebutuhan telah selesai diimplementasikan.

---

# BAB 1. LATAR BELAKANG

## 1.1 Gambaran Masalah

Pelayanan administrasi desa masih dapat melibatkan proses manual, seperti warga datang ke kantor desa, meminta pemeriksaan pengurus RT/RW, menyerahkan berkas, dan menanyakan perkembangan permohonan secara langsung.

Informasi pengumuman, pengaduan, dan data warga juga dapat tersebar di beberapa media pencatatan.

Kondisi tersebut dapat menyebabkan:
- Pelayanan membutuhkan waktu lebih lama.
- Warga kesulitan memantau status permohonan.
- Pekerjaan perangkat desa menjadi lebih banyak.
- Warga kesulitan memperoleh informasi layanan dan persyaratannya.

## 1.2 Solusi yang Dirancang

SiWarga dirancang sebagai aplikasi web yang menghubungkan warga dengan perangkat desa dan pengurus RT/RW.

Melalui aplikasi ini, pengguna dapat:
- Mengakses informasi layanan desa.
- Mengajukan permohonan surat.
- Menyampaikan pengaduan.
- Membaca pengumuman desa.
- Memantau status permohonan.
- Memperoleh bantuan informasi melalui asisten AI.

## 1.3 Tujuan Pengembangan

Tujuan pengembangan aplikasi SiWarga adalah:

1. Memudahkan warga mengakses informasi dan layanan desa melalui web.
2. Membantu proses pengajuan dan pemeriksaan surat secara bertahap.
3. Menyediakan pencatatan pengaduan agar status tindak lanjut dapat dipantau.
4. Membantu penyebaran pengumuman dan notifikasi kepada pengguna.
5. Menyediakan data dan ringkasan pelayanan untuk perangkat desa.
6. Menyediakan asisten AI untuk menjawab pertanyaan seputar informasi dan layanan SiWarga.

---

# BAB 2. RUANG LINGKUP APLIKASI

## 2.1 Ruang Lingkup yang Termasuk

Ruang lingkup aplikasi SiWarga meliputi:

1. Aplikasi web responsif yang dapat dibuka melalui komputer maupun ponsel.
2. Pengelolaan akun dan hak akses berdasarkan peran pengguna.
3. Pengelolaan data warga serta pengelompokan wilayah sampai tingkat RW dan RT.
4. Informasi layanan, pengajuan surat, pemeriksaan, dan penerbitan dokumen.
5. Pengaduan warga dan pemantauan status tindak lanjut.
6. Pengumuman desa serta notifikasi aplikasi, email, dan WhatsApp sesuai konfigurasi.
7. Asisten AI untuk menjawab pertanyaan terkait informasi desa dan layanan.
8. Dashboard dan laporan sesuai hak akses pengguna.

## 2.2 Batasan Ruang Lingkup

Aplikasi dirancang untuk satu wilayah Desa/Kelurahan.

Pengelolaan berhenti pada tingkat:
- Desa/Kelurahan.
- RW.
- RT.
- Warga.

Kecamatan atau Kabupaten hanya dapat muncul sebagai bagian dari alamat administratif, bukan sebagai pengguna, pengelola, atau tingkat persetujuan di dalam aplikasi.

Pembayaran online dan tanda tangan elektronik tersertifikasi tidak termasuk kebutuhan utama. Keduanya dapat dipertimbangkan sebagai pengembangan di masa mendatang.

## 2.3 Bentuk Aplikasi

SiWarga merupakan aplikasi berbasis web yang diakses melalui browser menggunakan akun masing-masing.

Tampilan dirancang responsif agar dapat digunakan melalui:
- Komputer.
- Laptop.
- Tablet.
- Ponsel.

---

# BAB 3. PENGGUNA DAN HAK AKSES

Setiap pengguna memiliki menu dan kewenangan berbeda. Pembatasan hak akses bertujuan agar pengguna hanya melihat dan mengelola informasi sesuai tugasnya.

## 3.1 Identifikasi Pengguna

| No. | Role | Tugas Utama dan Hak Akses |
|---|---|---|
| 1 | Super Admin | Mengelola pengaturan teknis aplikasi, akun, konfigurasi, dan pemantauan sistem. |
| 2 | Admin Desa | Mengelola data warga, layanan surat, pengaduan, pengumuman, laporan, dan administrasi desa. |
| 3 | Ketua RW | Melihat data dan melakukan pemeriksaan yang berkaitan dengan warga di wilayah RW yang menjadi tanggung jawabnya. |
| 4 | Ketua RT | Melihat data dan melakukan pemeriksaan yang berkaitan dengan warga di wilayah RT yang menjadi tanggung jawabnya. |
| 5 | Warga | Mengelola profil sendiri, melihat informasi, mengajukan surat, membuat pengaduan, dan memantau status layanan miliknya. |

## 3.2 Prinsip Hak Akses

1. Pengguna harus masuk ke aplikasi sebelum membuka menu yang memerlukan akun.
2. Ketua RT hanya dapat mengakses data dan pekerjaan pada wilayah RT yang menjadi tanggung jawabnya.
3. Ketua RW hanya dapat mengakses data dan pekerjaan pada wilayah RW yang menjadi tanggung jawabnya.
4. Warga hanya dapat melihat data pribadi dan permohonan miliknya sendiri.
5. Admin Desa dan Super Admin memiliki akses lebih luas sesuai fungsi masing-masing.
6. Pembatasan akses harus diterapkan pada proses di sisi server, bukan hanya dengan menyembunyikan menu pada tampilan.

---

# BAB 4. KEBUTUHAN FUNGSIONAL

Kebutuhan fungsional menjelaskan tindakan yang harus dapat dilakukan aplikasi.

Daftar berikut merupakan kebutuhan rancangan, bukan pernyataan bahwa seluruh fitur telah selesai dibuat.

## 4.1 Daftar Kebutuhan Fungsional

| Kode | Nama Fitur | Kebutuhan Aplikasi |
|---|---|---|
| FR-001 | Login dan Logout | Pengguna dapat masuk dan keluar dari aplikasi menggunakan akun. |
| FR-002 | Registrasi Warga | Warga dapat membuat akun dan melakukan verifikasi email menggunakan OTP. |
| FR-003 | Verifikasi Akun Warga | Petugas berwenang dapat memeriksa serta menyetujui atau menolak pendaftaran warga. |
| FR-004 | Pengelolaan Wilayah | Admin dapat mengelola data Desa, RW, dan RT. |
| FR-005 | Informasi Layanan | Aplikasi menampilkan jenis layanan dan persyaratan yang dibutuhkan. |
| FR-006 | Pengajuan Surat | Warga dapat mengisi dan mengirim permohonan surat melalui aplikasi. |
| FR-007 | Bantuan Pengajuan Melalui AI | Asisten AI membantu warga memahami kebutuhan dan langkah pengajuan surat. |
| FR-008 | Pemeriksaan Surat | Permohonan diperiksa petugas sesuai alur RT/RW/Desa yang ditentukan. |
| FR-009 | Pembuatan PDF | Aplikasi menghasilkan dokumen surat dalam format PDF setelah diproses sesuai kewenangan. |
| FR-010 | Pengiriman Surat | Dokumen atau informasi surat dapat dikirim melalui email dan WhatsApp jika saluran tersedia. |
| FR-011 | Pengaduan Warga | Warga dapat mengirim pengaduan dan bukti pendukung. |
| FR-012 | Tindak Lanjut Pengaduan | Petugas dapat memberikan tanggapan dan memperbarui status pengaduan. |
| FR-013 | Pengumuman Desa | Admin dapat membuat dan menerbitkan pengumuman untuk warga. |
| FR-014 | Antrean WhatsApp | Aplikasi mengatur antrean dan jeda pengiriman pesan WhatsApp. |
| FR-015 | Persetujuan Notifikasi WhatsApp | Warga dapat memilih apakah bersedia menerima notifikasi WhatsApp. |
| FR-016 | Asisten AI | Pengguna dapat bertanya mengenai informasi dan layanan desa melalui chatbot. |
| FR-017 | Pembatasan Informasi AI | AI mengikuti hak akses pengguna dan tidak menampilkan data yang tidak boleh diakses. |
| FR-018 | Pengaturan AI | Pengelola berwenang dapat mengatur model dan konfigurasi AI. |
| FR-019 | Pengaturan WhatsApp | Pengelola berwenang dapat mengatur koneksi dan memantau status layanan WhatsApp. |
| FR-020 | Dashboard dan Laporan | Aplikasi menampilkan ringkasan data sesuai peran pengguna. |
| FR-021 | Riwayat Aktivitas | Aplikasi mencatat aktivitas penting pengguna dan perubahan data. |
| FR-022 | Lupa Kata Sandi | Pengguna dapat meminta pengaturan ulang kata sandi melalui verifikasi email. |

## 4.2 Alur Pengajuan Surat

Alur umum pengajuan surat dirancang sebagai berikut:

1. Warga memilih jenis surat dan mengisi formulir pengajuan.
2. Aplikasi menyimpan permohonan dan menampilkan status kepada warga.
3. Permohonan diperiksa Ketua RT sesuai alur yang ditetapkan.
4. Permohonan dilanjutkan ke Ketua RW jika jenis layanan memerlukan pemeriksaan RW.
5. Admin Desa melakukan pemeriksaan akhir dan memproses penerbitan surat.
6. Warga menerima informasi perubahan status dan dapat mengunduh dokumen jika surat telah diterbitkan.

**Catatan:** Urutan pemeriksaan dapat disesuaikan dengan jenis surat dan proses yang ditetapkan pihak desa. Tidak semua jenis surat harus melewati tahapan yang sama.

---

# BAB 5. KEBUTUHAN NON-FUNGSIONAL

Kebutuhan non-fungsional menjelaskan kualitas yang diharapkan dari aplikasi.

Target berikut menjadi acuan desain dan pengujian. Angka atau standar yang belum ditetapkan dapat disepakati kembali pada tahap desain.

## 5.1 Daftar Kebutuhan Non-Fungsional

| Aspek | Kebutuhan dan Kriteria Pemeriksaan |
|---|---|
| Kemudahan Penggunaan | Menu, tombol, formulir, dan pesan menggunakan istilah yang mudah dipahami. Pengguna dapat menemukan fungsi utama tanpa instruksi rumit. |
| Responsif | Tampilan menyesuaikan layar komputer dan ponsel. Menu dan formulir utama tetap dapat digunakan tanpa terpotong. |
| Keamanan Akun | Kata sandi disimpan dalam bentuk hash. Halaman privat memerlukan autentikasi dan sesi pengguna ditangani dengan aman. |
| Hak Akses | Setiap peran hanya dapat membuka data dan fitur sesuai kewenangan. Pengujian dilakukan dengan akun dari setiap peran. |
| Kinerja | Halaman dan proses utama memberikan respons dalam waktu wajar pada kondisi jaringan dan perangkat uji yang dicatat. |
| Keandalan | Jika proses atau koneksi gagal, aplikasi menampilkan pesan yang jelas dan tidak menyatakan transaksi berhasil tanpa konfirmasi. |
| Kemudahan Pemeliharaan | Struktur program dan konfigurasi dibuat teratur agar mudah diperiksa, diperbaiki, dan dikembangkan. |
| Konsistensi Data | Perubahan data penting tersimpan dengan benar, tidak membuat data ganda akibat pengiriman ulang, dan dapat ditelusuri. |
| Kompatibilitas | Fungsi utama diuji pada browser modern yang digunakan saat pengujian. |

---

# BAB 6. RANCANGAN FITUR AI

## 6.1 Tujuan Asisten AI

Asisten AI SiWarga membantu pengguna memperoleh informasi tentang:
- Layanan desa.
- Persyaratan surat.
- Pengumuman.
- Status layanan yang boleh diakses akun tersebut.

AI merupakan pendamping informasi, bukan pengganti keputusan petugas desa.

## 6.2 Cara Kerja yang Direncanakan

1. Pengguna mengirim pertanyaan melalui chatbot.
2. Aplikasi mengenali akun dan peran pengguna.
3. Aplikasi mengambil informasi yang sesuai dengan hak akses pengguna.
4. Pertanyaan sederhana dapat dijawab menggunakan aturan atau data aplikasi.
5. Jika diperlukan, pertanyaan dan konteks yang telah dibatasi diteruskan ke layanan AI eksternal.
6. Jawaban ditampilkan dengan bahasa yang mudah dipahami.

## 6.3 Skenario Pertanyaan AI Berdasarkan Role Pengguna

Asisten AI dirancang untuk memahami pertanyaan berdasarkan identitas dan hak akses akun yang sedang login.

Contoh berikut merupakan skenario pertanyaan yang perlu dapat dipahami AI. Jawaban harus menggunakan data aplikasi yang tersedia dan hanya menampilkan informasi yang boleh diakses oleh role tersebut.

### A. Super Admin

Contoh pertanyaan:
1. Berapa jumlah seluruh akun pengguna?
2. Apakah ada integrasi sistem yang mengalami gangguan?
3. Bagaimana status konfigurasi AI?
4. Berapa jumlah surat dan pengaduan yang tercatat di seluruh desa yang dikelola aplikasi?

### B. Admin Desa

Contoh pertanyaan:
1. Berapa jumlah warga yang terdaftar di desa?
2. Berapa surat yang menunggu verifikasi?
3. Pengaduan apa saja yang belum ditindaklanjuti?
4. Siapa Kepala Desa?
5. Apa saja pengumuman yang sedang aktif?

### C. Ketua RW

Contoh pertanyaan:
1. Berapa jumlah warga di RW yang menjadi tanggung jawab saya?
2. Siapa saja Ketua RT di wilayah RW saya?
3. Berapa surat yang menunggu pemeriksaan RW?
4. Apakah ada pengaduan dari warga RW saya?
5. Siapa saja warga di RW saya yang datanya belum lengkap?

### D. Ketua RT

Contoh pertanyaan:
1. Berapa jumlah warga di RT saya?
2. Siapa saja warga di RT saya yang datanya belum lengkap?
3. Berapa pengajuan surat yang menunggu verifikasi RT?
4. Apakah ada pengaduan dari warga RT saya?
5. Bagaimana status pemeriksaan surat di RT saya?

### E. Warga

Contoh pertanyaan:
1. Bagaimana cara mengajukan surat domisili?
2. Apa persyaratan untuk mengajukan surat?
3. Bagaimana status pengajuan surat saya?
4. Bagaimana cara membuat pengaduan?
5. Siapa Ketua RT dan RW saya?
6. Apa pengumuman terbaru dari desa?

## 6.4 Aturan Pemahaman Konteks dan Hak Akses

1. Kata seperti "saya", "wilayah saya", "RT saya", "RW saya", "surat saya", dan "pengaduan saya" harus dihubungkan dengan identitas serta wilayah akun yang sedang login.
2. Ketua RT dan Ketua RW hanya memperoleh informasi dalam wilayah yang menjadi tanggung jawabnya.
3. Warga hanya memperoleh informasi pribadi dan status permohonan miliknya sendiri, serta informasi umum desa yang memang ditujukan kepada warga.
4. Admin Desa dan Super Admin memperoleh informasi sesuai kewenangan masing-masing. Akses Super Admin terhadap data operasional tetap harus mengikuti pengaturan keamanan aplikasi.
5. Jika data tidak tersedia, tidak ditemukan, atau pengguna tidak memiliki izin, AI harus menjelaskan keterbatasan tersebut dan tidak boleh menebak, mengarang, atau membocorkan data.
6. Contoh pertanyaan pada bagian ini merupakan skenario pengujian dan tidak berarti seluruh data atau fitur sudah tersedia. Setiap skenario perlu diuji menggunakan akun dari role terkait.

## 6.5 Batasan Asisten AI

1. AI hanya membantu topik yang berkaitan dengan informasi dan layanan SiWarga.
2. AI tidak boleh menampilkan data pribadi atau data wilayah yang tidak boleh diakses pengguna.
3. AI tidak berwenang menyetujui atau menolak surat dan pengaduan.
4. Jawaban AI dapat dipengaruhi ketersediaan koneksi dan layanan AI eksternal.
5. Jika informasi tidak tersedia, AI harus menyampaikan bahwa informasi belum ditemukan, bukan mengarang jawaban.

## 6.6 Penanganan Gangguan

Jika layanan AI utama mengalami gangguan atau batas penggunaan, aplikasi dapat mencoba konfigurasi cadangan yang tersedia.

Jika seluruh layanan AI tidak dapat digunakan, aplikasi menampilkan pesan kendala dan pengguna tetap dapat menggunakan menu layanan biasa.

---

# BAB 7. KEBUTUHAN INTEGRASI

Integrasi merupakan komponen pendukung yang direncanakan atau digunakan sesuai konfigurasi aplikasi.

Setiap integrasi harus diuji secara terpisah. Permintaan yang berhasil dikirim ke penyedia layanan tidak otomatis berarti pesan telah diterima pengguna.

## 7.1 Daftar Integrasi

| Komponen | Kegunaan | Hal yang Perlu Diuji |
|---|---|---|
| MySQL/MariaDB | Menyimpan akun, data warga, wilayah, surat, pengaduan, pengumuman, dan riwayat. | Data tersimpan dan terbaca konsisten; relasi dan hak akses sesuai; kegagalan transaksi ditangani. |
| Google Gemini API | Mendukung pemrosesan pertanyaan asisten AI. | Permintaan dan jawaban berhasil; konteks sesuai peran; kesalahan, batas penggunaan, dan gangguan ditangani. |
| Fonnte WhatsApp Gateway | Mendukung pengiriman notifikasi WhatsApp jika dikonfigurasi. | Nomor tujuan valid dan menyetujui notifikasi; status antrean dan respons penyedia dicatat; status terkirim tidak disimpulkan hanya dari permintaan diterima. |
| Gmail SMTP | Mendukung pengiriman OTP dan email pemberitahuan. | Email tujuan, kode OTP, masa berlaku, dan hasil pengiriman diuji. |
| Dompdf | Membantu menghasilkan dokumen PDF. | Isi PDF, format, nama file, dan kemampuan unduh diperiksa. |

## 7.2 Keamanan Integrasi

Kunci API dan kredensial integrasi harus disimpan pada konfigurasi yang tidak ditampilkan kepada pengguna.

Ketersediaan integrasi bergantung pada:
- Konfigurasi aplikasi.
- Koneksi internet.
- Ketersediaan layanan penyedia.

---

# BAB 8. BATASAN DAN ASUMSI

## 8.1 Asumsi

1. Pengguna memiliki perangkat yang dapat membuka browser.
2. Pengguna memiliki koneksi internet untuk mengakses aplikasi web.
3. Data awal warga dan wilayah disediakan oleh pihak pengelola desa.
4. Petugas desa, Ketua RT, dan Ketua RW menggunakan akun sesuai perannya.
5. Layanan eksternal seperti email, WhatsApp, dan AI bergantung pada ketersediaan penyedia layanan.

## 8.2 Batasan

1. Aplikasi dirancang untuk satu Desa/Kelurahan dan tidak mengelola banyak desa.
2. Aplikasi tidak menyediakan akun atau dashboard khusus Kecamatan.
3. Aplikasi tidak menggantikan keputusan dan pemeriksaan yang menjadi kewenangan petugas.
4. Fitur yang belum dibuat atau belum diuji harus diberi status belum tersedia atau belum diuji.
5. Pembayaran online dan tanda tangan elektronik tersertifikasi tidak termasuk ruang lingkup utama.

---

# BAB 9. KRITERIA KEBERHASILAN DAN PENGUJIAN

Aplikasi dinilai memenuhi kebutuhan apabila fungsi utama dapat dijalankan sesuai peran pengguna dan hasilnya didukung bukti pengujian.

Pengujian dilakukan menggunakan skenario yang mewakili kebutuhan pada Bab 4 sampai Bab 7.

## 9.1 Kriteria Keberhasilan

| Area Pengujian | Kriteria Keberhasilan dan Bukti |
|---|---|
| Akun dan Login | Pengguna dapat registrasi, verifikasi, login, logout, dan reset kata sandi sesuai alur yang tersedia. |
| Hak Akses | Setiap peran hanya dapat membuka menu dan data yang menjadi kewenangannya; akses di luar kewenangan ditolak. |
| Data Warga dan Wilayah | Data tersimpan dan ditampilkan sesuai wilayah serta akun yang berwenang. |
| Pengajuan Surat | Permohonan dapat dibuat, diperiksa, status diperbarui, dan PDF dihasilkan jika disetujui. |
| Pengaduan | Pengaduan dapat dikirim, ditanggapi, dan statusnya dapat dipantau oleh pihak terkait. |
| Pengumuman dan Notifikasi | Pengumuman tampil kepada sasaran yang benar; email/WhatsApp diuji dan hasil aktual dicatat. |
| Asisten AI | AI menjawab pertanyaan dalam ruang lingkup, mengikuti hak akses, dan tidak mengarang saat data tidak tersedia. |
| Tampilan Web | Halaman dan fungsi utama dapat digunakan pada ukuran layar komputer dan ponsel. |
| Riwayat Aktivitas | Aktivitas penting tercatat dan dapat ditelusuri oleh peran yang berwenang. |

## 9.2 Status Hasil Pengujian

| Status | Arti |
|---|---|
| Berhasil | Fitur telah dicoba dan hasilnya sesuai kebutuhan; bukti pengujian tersedia. |
| Sebagian | Fitur dapat digunakan, tetapi masih ada bagian yang belum sesuai. |
| Belum tersedia | Fitur belum dibuat atau belum dapat digunakan. |
| Belum diuji | Belum ada bukti pengujian yang cukup. |

## 9.3 Format Pencatatan Pengujian

| ID Uji | Kebutuhan Terkait | Langkah / Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|
| U-01 | FR-001 | Login dengan akun valid. | Pengguna masuk ke dashboard sesuai peran. | Diisi saat pengujian. | Belum diuji |
| U-02 | FR-017 | Warga mencoba membuka data warga lain. | Akses ditolak. | Diisi saat pengujian. | Belum diuji |
| U-03 | FR-006, FR-008 | Ajukan surat dan proses sesuai alur. | Status permohonan berubah sesuai tindakan petugas. | Diisi saat pengujian. | Belum diuji |
| U-04 | FR-016, FR-017 | Tanyakan informasi sesuai dan di luar hak akses. | AI menjawab informasi yang diizinkan dan menolak atau membatasi informasi terlarang. | Diisi saat pengujian. | Belum diuji |
| U-05 | FR-010, FR-014, FR-015 | Kirim notifikasi ke nomor yang telah menyetujui. | Status pengiriman dicatat sesuai bukti dari penyedia atau penerima. | Diisi saat pengujian. | Belum diuji |
| U-06 | Bab 6.3 | Ajukan pertanyaan yang sesuai role menggunakan akun Super Admin, Admin Desa, Ketua RW, Ketua RT, dan Warga. | AI menjawab sesuai data dan hak akses setiap role; permintaan di luar izin ditolak atau dibatasi. | Diisi saat pengujian. | Belum diuji |

**Catatan:** Tabel ini merupakan contoh skenario awal. Hasil aktual dan status harus diisi setelah pengujian benar-benar dilakukan. Jangan mengubah status menjadi berhasil tanpa bukti.

---

# BAB 10. PENUTUP

Dokumen PRD/SRS ini menjadi dasar perancangan aplikasi web SiWarga.

Dokumen menjelaskan tujuan, batasan ruang lingkup, pengguna, hak akses, kebutuhan fitur, kualitas yang diharapkan, rancangan AI, integrasi, serta kriteria pengujian.

Tahap berikutnya adalah menyusun desain sistem secara lebih rinci melalui:
1. Diagram UML.
2. Rancangan basis data (ERD).
3. Arsitektur aplikasi.
4. Desain antarm