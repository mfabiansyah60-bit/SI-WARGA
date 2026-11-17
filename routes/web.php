<?php
// routes/web.php
// ⚠️ File ini BUKAN router aktif — hanya dokumentasi.
// Router asli ada di public/index.php

return [
    // ============================================
    // PUBLIC ROUTES (tanpa login)
    // ============================================
    'GET  /'          => 'landing.php',
    'GET  /login'     => 'auth/login.php',
    'POST /login'     => 'auth/login.php (proses)',
    'GET  /register'  => 'auth/register.php',
    'POST /register'  => 'auth/register.php (proses)',
    'GET  /logout'    => 'logout.php',

    // ============================================
    // PROTECTED ROUTES (harus login)
    // ============================================
    'GET  /dashboard' => 'dashboard/{role}.php',

    // ============================================
    // MODUL ROUTES (auto-load controller)
    // ============================================
    'GET  /wilayah'    => 'WilayahController',
    'GET  /rt_rw'      => 'RtRwController',
    'GET  /users'      => 'UserController',
    'GET  /warga'      => 'WargaController',
    'GET  /pelayanan'  => 'PelayananController',
    'GET  /pengaduan'  => 'PengaduanController',
    'GET  /surat'      => 'SuratController',
    'GET  /laporan'    => 'LaporanController',
    'GET  /ai'         => 'AIController (khusus warga)',

    // ============================================
    // AKSI VIA QUERY STRING (?action=...)
    // ============================================
    'GET  /pengaduan?action=buat'     => 'PengaduanController::buat',
    'POST /pengaduan?action=buat'     => 'PengaduanController::simpan',
    'GET  /pengaduan?action=detail&id=N' => 'PengaduanController::detail',
    'POST /pengaduan?action=tanggapi&id=N' => 'PengaduanController::tanggapi',
    'GET  /pengaduan?action=hapus&id=N' => 'PengaduanController::hapus',
];  