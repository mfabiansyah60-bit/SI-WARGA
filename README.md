# SIWARGA — Platform Pelayanan Publik & Administrasi Kependudukan Desa Digital Terintegrasi AI Berbasis Web
### Tugas Proyek Akhir Mandiri Mata Kuliah Rekayasa Perangkat Lunak (TIKB2503021)
**Program Studi S1 Teknik Informatika — STMIK IKMI Cirebon (Semester 3)**  
**Dosen Pengampu: Yudhistira Arie Wijaya, M.Kom., CDS.**

---

## 📌 Identitas Mahasiswa & Pengembang
- **Nama Mahasiswa**: Muhammad Fabiansyah
- **NIM**: 41257127
- **Kelas / Kelompok**: TI-1B-2025
- **Tema Topik Kelompok**: *Smart Public Service & Public Health Intelligence (Layanan Administrasi Desa Cerdas Berbasis AI)*
- **Nama Sistem**: **SIWARGA (Sistem Informasi Pelayanan Warga & Administrasi Desa Digital)**
- **Repositori GitHub**: [https://github.com/mfabiansyah60-bit/SI-WARGA.git](https://github.com/mfabiansyah60-bit/SI-WARGA.git)

---

## 📺 Tautan Video Demo Final & Prototipe Desain
- **Tautan Video Demo Final**: [Tonton Video Demo Final SIWARGA (YouTube)](https://youtu.be/QcLK4wRJ98Y)
- **Tautan Prototipe Figma**: [Akses Prototipe UI/UX SIWARGA (Figma)](https://www.figma.com/design/Zd5Y2jV5gwGmKmpw8thXI2/Untitled?node-id=0-1&t=GxYNL7dGgwopdwVG-1)

---

## 🌟 Ringkasan Sistem & Arsitektur Fitur AI
**SIWARGA** adalah platform web tata kelola administrasi desa dan kependudukan modern yang dirancang untuk mengatasi inefisiensi birokrasi manual di tingkat desa. Dengan memadukan arsitektur modular **PHP MVC**, basis data relasional kependudukan, serta kecerdasan buatan terintegrasi **Google Gemini AI**, SIWARGA memangkas birokrasi pengurusan persuratan warga dari beberapa hari menjadi hitungan menit secara transparan, akuntabel, dan aman.

### ✨ 5 Peran Aktor (Role-Based Access Control / RBAC):
1. **Warga**: Mengajukan berbagai jenis surat keterangan secara mandiri (Domisili, SKTM, SKU, Pengantar SKCK, KTP, KK, dll), memantau status surat secara *real-time*, menyampaikan pengaduan masyarakat (dilengkapi opsi anonim), serta berinteraksi 24/7 dengan SIWARGA AI.
2. **Ketua RT (Rukun Tetangga)**: Memvalidasi domisili warga, memberikan verifikasi awal pada permohonan persuratan warga di lingkungannya, dan memantau penanganan aduan di tingkat RT.
3. **Ketua RW (Rukun Warga)**: Mengawasi koordinasi wilayah RW, memverifikasi alur permohonan surat bertingkat, serta memantau aspirasi kependudukan di tingkat RW.
4. **Pemerintah Desa (Admin Desa)**: Meninjau dan menyetujui penerbitan surat resmi desa secara digital, mencatat arsip permohonan, mengelola katalog layanan publik desa, serta menerbitkan pengumuman resmi desa.
5. **Super Admin**: Mengendalikan tata kelola pengguna secara menyeluruh, mengelola hak akses akun, memantau *audit log*, serta mengonfigurasi pengaturan AI (Multi-Key Failover) dan integrasi WhatsApp Gateway.

### 🤖 Fitur AI & Inovasi Unggulan:
1. **SIWARGA AI Multi-Key Failover & Intelligent Cooldown**: Arsitektur multi-key Google Gemini AI dengan mekanisme peralihan cerdas (*auto-failover*) dan *cooldown cache* (180 detik untuk kuota limit 429, 30 detik untuk *server overload*). Menjamin chatbot desa selalu siap sedia (*high availability*) tanpa terganggu habisnya kuota salah satu API Key.
2. **Fast-Track Database Intelligence & Zero-Quota FAQ**: Pertanyaan FAQ dasar seputar persyaratan surat, panduan pengajuan, informasi ketua RT/RW domisili, dan pengecekan status pribadi dijawab instan (<50ms) langsung dari basis data tanpa menghabiskan kuota LLM.
3. **Multimodal Vision AI**: Kemampuan menganalisis dan mengekstrak konteks berkas gambar atau foto bukti pengaduan yang diunggah warga secara otomatis.
4. **Context-Aware Semantic Understanding**: AI memahami sinonim, variasi bahasa percakapan sehari-hari, salah ketik (*typo*), serta mengingat konteks riwayat percakapan sebelumnya untuk menjawab pertanyaan lanjutan secara presisi.
5. **Automated WhatsApp Notification Gateway**: Terintegrasi dengan WhatsApp Gateway (Fonnte API) dan PHPMailer (SMTP) untuk pengiriman notifikasi otomatis saat surat disetujui, ditolak, atau pengaduan ditindaklanjuti.

---

## 🛠️ Panduan Instalasi & Eksekusi Lokal (Cara Menjalankan)

Berikut adalah panduan untuk mengkloning dan menjalankan proyek **SIWARGA** di lingkungan lokal Anda (mendukung Laragon, XAMPP, maupun PHP Built-in Server):

### Prasyarat Sistem:
- **PHP**: Versi 8.1, 8.2, atau 8.3 (Disarankan PHP 8.2+) dengan ekstensi `pdo_mysql`, `curl`, `mbstring`, `openssl`, dan `gd` aktif.
- **Web Server & Database**: Laragon atau XAMPP (Apache + MySQL / MariaDB).
- **Composer**: Dependency Manager untuk PHP.
- **Git**: Untuk kloning repositori.

---

### 1. Kloning Repositori & Masuk ke Direktori
Buka terminal di direktori web server Anda (misal: `C:\laragon\www` atau `C:\xampp\htdocs`):
```bash
git clone https://github.com/mfabiansyah60-bit/SI-WARGA.git
cd SI-WARGA
```

### 2. Instal Dependensi PHP via Composer
Jalankan perintah berikut di root folder proyek:
```bash
composer install
```

### 3. Konfigurasi Environment (`.env`)
Salin file template `.env.example` menjadi `.env` pada root direktori:
```bash
cp .env.example .env
```
Sesuaikan konfigurasi environment sesuai mesin lokal Anda:
```env
APP_NAME=SIWARGA
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=siwarga
DB_USERNAME=root
DB_PASSWORD=your_database_password

# Konfigurasi Google Gemini AI Studio (Gunakan API Key Pribadi Anda)
GEMINI_API_KEY=your_gemini_api_key_here
GEMINI_MODEL=gemini-3.6-flash,gemini-3.5-flash,gemini-flash-latest

# Konfigurasi Notifikasi Email (SMTP - Opsional)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_NAME="Pemerintah Desa"
```

### 4. Setup Database & Inisialisasi Data Awal
1. Buat database baru di MySQL dengan nama **`siwarga`** melalui phpMyAdmin, HeidiSQL, atau terminal MySQL:
   ```sql
   CREATE DATABASE siwarga CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
2. Jalankan skrip inisialisasi lokal untuk membuat struktur tabel dan data referensi awal:
   ```bash
   php scripts/seed_demo.php
   ```
   *(Catatan Keamanan: Seluruh data kependudukan pengujian digenerate secara lokal dan tidak memuat data sensitif publik).*

### 5. Menjalankan Server Aplikasi

Pilih salah satu metode yang paling sesuai dengan lingkungan lokal Anda:

#### Opsi A: Menggunakan PHP Built-in Server (Cepat & Mandiri)
Jalankan perintah ini di root folder proyek:
```bash
php -S localhost:8000 -t public
```
Aplikasi SIWARGA langsung dapat diakses di peramban (browser) melalui URL:  
👉 **`http://localhost:8000`**

#### Opsi B: Menggunakan Laragon Virtual Host
1. Letakkan folder proyek di `C:\laragon\www\SI-WARGA`.
2. Klik kanan pada Laragon tray $\to$ **Quick app / Reload**.
3. Buka URL: **`http://si-warga.test`** (pastikan *Document Root* mengarah ke folder `public/`).

---

### 6. Menjalankan Pengujian Unit Otomatis (Automated Testing)
Proyek ini dilengkapi rangkaian pengujian unit dan integrasi otomatis untuk memverifikasi logika bisnis, hak akses role, serta keandalan failover AI:
```bash
# Uji Fitur AI Role Warga, Privasi Data, & Simulasi Failover Cooldown:
php tests/WargaAITest.php

# Uji Kecerdasan AI Sidebar & Proteksi Akses Data:
php tests/AISidebarIntelligenceTest.php

# Uji Manajemen Konfigurasi Multi-Key & Masking Key:
php tests/AIConfigTest.php
```

---

## 📂 Struktur Direktori Proyek

```text
SI-WARGA/
├── app/                         # Inti Aplikasi (Arsitektur MVC & Logika Bisnis)
│   ├── AI/                      # Mesin Integrasi AI, Scope Filter, & Inspeksi Database
│   │   ├── DatabaseInspector.php# Analisis skema & agregasi data dinamis untuk AI
│   │   ├── RoleCapabilities.php # Definisi hak akses & kapabilitas AI per peran
│   │   └── ScopeFilter.php      # Filter isolasi data kependudukan & privasi
│   ├── Controllers/             # Controller Alur Aplikasi
│   │   ├── AIConfigController.php    # Konfigurasi model & multi-key AI
│   │   ├── AIController.php          # Otak utama chat AI, fast-track, & failover
│   │   ├── AuthController.php        # Autentikasi, registrasi, & manajemen sesi
│   │   ├── DashboardController.php   # Dashboard statistik kependudukan
│   │   ├── NotificationController.php# Notifikasi sistem & tanda lonceng
│   │   ├── PengaduanController.php   # Alur pelaporan & tindak lanjut aduan
│   │   ├── SuratController.php       # Permohonan, verifikasi, & cetak PDF surat
│   │   ├── UserController.php        # Manajemen pengguna & data kependudukan
│   │   ├── WhatsAppConfigController.php # Manajemen konfigurasi WhatsApp Gateway
│   │   └── WilayahController.php     # Manajemen master data RT/RW & desa
│   ├── Helpers/                 # Helper & Utility Pembantu
│   │   ├── AIConfig.php         # Manajemen Multi-Key AI & Model Rotator
│   │   ├── AIDataHelper.php     # Query data wilayah, kependudukan, & statistik AI
│   │   ├── AIKeyCooldown.php    # Manajemen Cooldown & Retry otomatis error 429/5xx
│   │   ├── functions.php        # Helper global, sanitasi input, & session
│   │   ├── Mailer.php           # Pengiriman surat resmi via PHPMailer (SMTP)
│   │   └── WhatsAppHelper.php   # Integrasi WhatsApp Gateway (Fonnte API)
│   ├── Middleware/              # Keamanan & Hak Akses Pengguna
│   │   └── AuthMiddleware.php   # Filter otentikasi & pengecekan role
│   ├── Models/                  # Model Akses Basis Data
│   │   ├── Database.php
│   │   ├── Pengaduan.php
│   │   ├── Surat.php
│   │   └── User.php
│   └── Services/                # Service Layer (Layanan Eksternal)
│       └── WhatsAppService.php  # Pengiriman pesan WhatsApp notifikasi
├── config/                      # Konfigurasi Aplikasi & Basis Data
│   ├── app.php
│   ├── database.php             # Koneksi PDO MySQL & Fallback Test
│   └── koneksi.php
├── docs/                        # Dokumentasi Perancangan Sistem
│   ├── PRD_SiWarga_Final.md     # Product Requirement Document (PRD/SRS IEEE 830)
│   ├── PRD_SiWargaa.docx        # Naskah Dokumen Akademik PRD
│   └── SDD/                     # Software Development Diagram (UML & Arsitektur)
├── public/                      # Web Root Publik (Assets, Script, & Entry Point)
│   ├── css/                     # Stylesheet & Tailwind / Custom UI
│   ├── images/                  # Aset visual, banner, & logo desa
│   ├── js/                      # Script interaktif frontend & AI Widget
│   ├── uploads/                 # Berkas lampiran surat & foto aduan warga
│   ├── index.php                # Front Controller / Entry Point Aplikasi
│   └── .htaccess                # Konfigurasi Apache URL Rewriting
├── resources/                   # Antarmuka Tampilan Pengguna (Views & Layouts)
│   └── views/
│       ├── admin/               # Tampilan manajemen desa & super admin
│       ├── auth/                # Halaman login, register, & reset password
│       ├── dashboard/           # Dashboard analitik per peran pengguna
│       ├── layouts/             # Master layout, header, footer, & sidebar
│       ├── pengaduan/           # Form, daftar, & monitoring aduan warga
│       ├── surat/               # Form pengajuan & tracking surat mandiri
│       └── warga/               # Verifikasi akun & data kependudukan
├── routes/                      # Definisi Jalur Rute Sistem
│   ├── api.php                  # Endpoint API SIWARGA
│   └── web.php                  # Rute Web MVC
├── scripts/                     # Skrip Otomasi & Pemeliharaan Database
│   ├── backup_db.php            # Skrip backup otomatis basis data MySQL
│   └── seed_demo.php            # Skrip seeding akun demo & data awal
├── storage/                     # Penyimpanan Data Dinamis & Konfigurasi JSON
│   ├── ai_config.json           # Konfigurasi Multi-Key & Model AI Gemini
│   ├── ai_key_cooldown.json     # Cache status cooldown & prioritisasi key AI
│   ├── whatsapp_config.json     # Konfigurasi token & gateway WhatsApp
│   └── backups/                 # Arsip cadangan lokal
├── tests/                       # Automated Testing Suite (Unit & Integration Test)
│   ├── AIConfigTest.php         # Pengujian konfigurasi & masking API key
│   ├── AISidebarIntelligenceTest.php # Pengujian kecerdasan AI sidebar & RBAC
│   ├── AuthTest.php             # Pengujian autentikasi & sesi
│   ├── WargaAITest.php          # Pengujian 9 skenario warga & failover cooldown
│   └── WhatsAppConfigTest.php   # Pengujian gateway notifikasi WhatsApp
├── .env                         # Konfigurasi Environment Lokal
├── composer.json                # Spesifikasi dependensi paket PHP
└── README.md                    # Dokumentasi Utama Repositori
```

---

## 📜 Lisensi & Standar Akademik
Proyek ini dikembangkan secara mandiri untuk pemenuhan Tugas Proyek Akhir Mata Kuliah **Rekayasa Perangkat Lunak (TIKB2503021)** Semester 3 Program Studi S1 Teknik Informatika STMIK IKMI Cirebon Tahun Akademik 2026/2027. Mengikuti standar dokumentasi kebutuhan perangkat lunak **IEEE 830 / ISO 29148** serta prinsip perancangan arsitektur berorientasi objek yang modular, terstruktur, dan bersih (*Clean Architecture / SOLID Principles*).
