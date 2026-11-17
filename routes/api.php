<?php
// routes/api.php
// ⚠️ Dokumentasi endpoint API (untuk AJAX/AI Chat)

return [
    // ============================================
    // AI CHAT
    // ============================================
    'POST /api/ai/chat'      => [
        'controller' => 'AIController',
        'method'     => 'chat',
        'auth'       => true,
        'role'       => ['warga'],
        'body'       => ['message: string', 'history: array (opsional)'],
        'response'   => ['reply: string'],
    ],

    // ============================================
    // PENGADUAN
    // ============================================
    'POST /api/pengaduan'    => [
        'controller' => 'PengaduanController',
        'method'     => 'store',
        'auth'       => true,
        'body'       => ['judul: string', 'isi: string', 'kategori: string'],
        'response'   => ['id: int', 'status: string'],
    ],

    // ============================================
    // WARGA
    // ============================================
    'GET  /api/warga/{id}'   => [
        'controller' => 'WargaController',
        'method'     => 'show',
        'auth'       => true,
        'role'       => ['super_admin', 'desa', 'rw', 'rt'],
    ],

    // ============================================
    // SURAT
    // ============================================
    'POST /api/surat/generate' => [
        'controller' => 'SuratController',
        'method'     => 'generate',
        'auth'       => true,
        'body'       => ['template_id: int', 'data: object'],
    ],

    // ============================================
    // STATISTIK
    // ============================================
    'GET  /api/stats/dashboard' => [
        'controller' => 'DashboardController',
        'method'     => 'stats',
        'auth'       => true,
        'response'   => [
            'total_warga: int',
            'total_surat: int',
            'pengaduan_pending: int',
        ],
    ],
];