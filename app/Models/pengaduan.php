<?php
// app/Models/Pengaduan.php

class Pengaduan {
    public static function all($role = 'super_admin', $rt = null, $rw = null) {
        global $pdo;
        
        $sql = "SELECT p.*, u.name as user_name, u.rt, u.rw 
                FROM pengaduan p 
                JOIN users u ON p.user_id = u.id 
                WHERE 1=1";
        $params = [];

        // Filter berdasarkan Role
        if ($role === 'rt') {
            $sql .= " AND u.rt = ? AND u.rw = ?";
            $params[] = $rt;
            $params[] = $rw;
        } elseif ($role === 'rw') {
            $sql .= " AND u.rw = ?";
            $params[] = $rw;
        }
        // Desa & Super Admin tidak pakai filter tambahan (lihat semua)

        $sql .= " ORDER BY 
            CASE p.prioritas 
                WHEN 'darurat' THEN 1 
                WHEN 'tinggi' THEN 2 
                WHEN 'sedang' THEN 3 
                WHEN 'rendah' THEN 4 
                ELSE 5 
            END ASC, 
            p.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function findByUser($user_id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM pengaduan WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    public static function find($id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT p.*, u.name as user_name, u.rt, u.rw FROM pengaduan p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // ... (Fungsi create, updateStatus, delete biarkan sama seperti sebelumnya)
    public static function create($data) {
        global $pdo;
        $stmt = $pdo->prepare("
            INSERT INTO pengaduan 
            (user_id, judul, isi, kategori, prioritas, lokasi, kontak, is_anonymous, bukti_gambar, bukti_gambar_2) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['user_id'], $data['judul'], $data['isi'],
            $data['kategori'] ?? 'umum', $data['prioritas'] ?? 'sedang',
            $data['lokasi'] ?? null, $data['kontak'] ?? null,
            $data['is_anonymous'] ?? 0, $data['bukti_gambar'] ?? null,
            $data['bukti_gambar_2'] ?? null,
        ]);
        return $pdo->lastInsertId();
    }

    public static function updateStatus($id, $status, $tanggapan = null, $bukti1 = null, $bukti2 = null) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT bukti_tanggapan_1, bukti_tanggapan_2 FROM pengaduan WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch() ?: [];
        
        if ($bukti1) {
            if (!empty($existing['bukti_tanggapan_1'])) @unlink(__DIR__ . '/../../public/' . $existing['bukti_tanggapan_1']);
            $finalBukti1 = $bukti1;
        } else { $finalBukti1 = $existing['bukti_tanggapan_1'] ?? null; }
        
        if ($bukti2) {
            if (!empty($existing['bukti_tanggapan_2'])) @unlink(__DIR__ . '/../../public/' . $existing['bukti_tanggapan_2']);
            $finalBukti2 = $bukti2;
        } else { $finalBukti2 = $existing['bukti_tanggapan_2'] ?? null; }
        
        $stmt = $pdo->prepare("UPDATE pengaduan SET status = ?, tanggapan = ?, bukti_tanggapan_1 = ?, bukti_tanggapan_2 = ? WHERE id = ?");
        return $stmt->execute([$status, $tanggapan, $finalBukti1, $finalBukti2, $id]);
    }

    public static function delete($id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT bukti_gambar, bukti_gambar_2, bukti_tanggapan_1, bukti_tanggapan_2 FROM pengaduan WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            foreach (['bukti_gambar', 'bukti_gambar_2', 'bukti_tanggapan_1', 'bukti_tanggapan_2'] as $col) {
                if (!empty($row[$col])) @unlink(__DIR__ . '/../../public/' . $row[$col]);
            }
        }
        $stmt = $pdo->prepare("DELETE FROM pengaduan WHERE id = ?");
        return $stmt->execute([$id]);
    }
}