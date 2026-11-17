<?php
// app/Models/SuratTemplate.php

class SuratTemplate {
    
    public static function all() {
        global $pdo;
        $stmt = $pdo->query("
            SELECT st.*, d.nama as desa_nama 
            FROM surat_template st 
            LEFT JOIN desa d ON st.desa_id = d.id 
            ORDER BY st.id ASC
        ");
        return $stmt->fetchAll();
    }

    public static function findByDesa($desa_id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM surat_template WHERE desa_id = ? ORDER BY id ASC");
        $stmt->execute([$desa_id]);
        return $stmt->fetchAll();
    }

    public static function find($id) {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT st.*, d.nama as desa_nama 
            FROM surat_template st 
            LEFT JOIN desa d ON st.desa_id = d.id 
            WHERE st.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function create($data) {
        global $pdo;
        $stmt = $pdo->prepare("
            INSERT INTO surat_template (desa_id, nama, judul, isi_template, icon) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['desa_id'],
            $data['nama'],
            $data['judul'],
            $data['isi_template'],
            $data['icon'] ?? 'fa-file-alt'
        ]);
        return $pdo->lastInsertId();
    }

    public static function update($id, $data) {
        global $pdo;
        $stmt = $pdo->prepare("
            UPDATE surat_template 
            SET desa_id = ?, nama = ?, judul = ?, isi_template = ?, icon = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['desa_id'],
            $data['nama'],
            $data['judul'],
            $data['isi_template'],
            $data['icon'] ?? 'fa-file-alt',
            $id
        ]);
    }

    public static function delete($id) {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM surat_template WHERE id = ?");
        return $stmt->execute([$id]);
    }
}