# 🏛️ SI-WARGA (Sistem Informasi Wilayah dan Pemerintahan Berbasis AI)

<p align="center">
  <b>Sistem Administrasi Desa, RT/RW, dan Pelayanan Warga Terintegrasi AI</b>
</p>

---

## 📌 Tentang Project
**SI-WARGA** adalah platform web administrasi desa dan kependudukan berbasis kustom PHP MVC yang dirancang untuk mempermudah pengelolaan data warga, pengaduan masyarakat, pelayanan surat menyurat digital, serta dilengkapi dengan asisten AI terintegrasi untuk membantu analisis wilayah dan pelayanan publik.

---

## 📂 Struktur Arsitektur Web
Project ini dibangun menggunakan pola modular **MVC (Model-View-Controller)** murni dengan struktur direktori sebagai berikut:

```text
SI-WARGA/
├── app/                  # Core Aplikasi (MVC & Logika Bisnis)
│   ├── AI/               # Modul Integrasi AI & Inspeksi Database
│   ├── Controllers/      # Pengatur Logika Alur (Controller)
│   ├── Helpers/          # Fungsi Pembantu (Mailer, Konfigurasi AI, WhatsApp)
│   ├── Middleware/       # Keamanan & Hak Akses (AuthMiddleware)
│   ├── Models/           # Pengelola Database & Tabel (Model)
│   └── Services/         # Layanan Eksternal (Notifikasi WhatsApp)
├── config/               # Konfigurasi Utama (Database, App, Koneksi)
├── docs/                 # Dokumentasi PRD & Software Development Diagram (SDD)
├── public/               # Root Akses Publik (Assets, JS, CSS, Uploads, index.php)
├── resources/            # Tampilan Antarmuka (Views: Admin, Auth, Dashboard, Warga, dll)
├── routes/               # Pengaturan Jalur URL (Web & API routes)
├── scripts/              # Skrip Pendukung & Database Seeder
├── tests/                # Unit Testing & Integrasi PHPUnit
└── composer.json         # Dependensi Composer PHP
```

---

## 👥 Hak Akses & Peran (Multi-Role)
Sistem ini mendukung 5 level hak akses pengguna yang berbeda:
1. **Super Admin**: Pengelolaan sistem penuh & konfigurasi AI/WhatsApp global.
2. **Desa**: Manajemen administrasi tingkat desa & verifikasi akhir.
3. **RW (Rukun Warga)**: Pengawasan wilayah RW dan rekapitulasi data.
4. **RT (Rukun Tetangga)**: Pengelolaan data warga tingkat RT & pengesahan awal.
5. **Warga**: Mengajukan pengaduan, pelayanan surat digital, dan interaksi dengan AI.

---

## ⚙️ Persyaratan Sistem
Sebelum menginstal, pastikan komputer/server Anda telah terpasang:
* **PHP** >= 8.1 (Disarankan menggunakan PHP 8.2+)
* **MySQL / MariaDB** (via Laragon / XAMPP)
* **Composer** (untuk manajemen package PHP)

---

## 🚀 Panduan Instalasi (Cara Menjalankan)

Ikuti langkah-langkah berikut untuk menjalankan project ini di komputer lokal Anda:

### 1. Clone Repository
Buka terminal/command prompt, lalu clone repository ini ke direktori web server lokal Anda (misal: `laragon/www` atau `htdocs`):
```bash
git clone https://github.com/mfabiansyah60-bit/SI-WARGA.git
cd SI-WARGA
```

### 2. Konfigurasi Database
1. Buat database baru di MySQL dengan nama `siwarga` (atau sesuai keinginan).
2. Import file SQL / jalankan migration database jika tersedia.
3. Sesuaikan konfigurasi koneksi database pada file **`config/database.php`** atau **`config/koneksi.php`**:
   ```php
   $host = 'localhost';
   $user = 'root';
   $pass = '';
   $db   = 'siwarga';
   ```

### 3. Install Dependensi (Composer)
Jalankan perintah berikut di terminal pada root folder project:
```bash
composer install
```

### 4. Jalankan Aplikasi
* Jika menggunakan **Laragon**, project akan otomatis dapat diakses melalui URL lokal: `http://siwarga.test` (atau sesuaikan dengan virtual host Anda).
* Atau arahkan web server Anda ke folder **`public/`** sebagai document root.

---

## 👨‍💻 Author
* **Muhammad Fabiansyah** (NIM: 41257127) - *Teknik Informatika STMIK IKMI Cirebon (Kelas TI 1B 2025)*