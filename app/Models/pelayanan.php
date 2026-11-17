<?php
// app/Models/Pelayanan.php

class Pelayanan {
    public static function all() {
        global $pdo;
        $stmt = $pdo->query("SELECT pelayanan.*, desa.nama as desa_nama FROM pelayanan JOIN desa ON pelayanan.desa_id = desa.id ORDER BY desa.nama, pelayanan.nama");
        return $stmt->fetchAll();
    }

    public static function findByDesa($desa_id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM pelayanan WHERE desa_id = ? ORDER BY nama");
        $stmt->execute([$desa_id]);
        return $stmt->fetchAll();
    }

    public static function find($id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM pelayanan WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function create($data) {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO pelayanan (desa_id, nama, deskripsi, syarat) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['desa_id'], $data['nama'], $data['deskripsi'], $data['syarat']]);
        return $pdo->lastInsertId();
    }

    public static function update($id, $data) {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE pelayanan SET desa_id = ?, nama = ?, deskripsi = ?, syarat = ? WHERE id = ?");
        return $stmt->execute([$data['desa_id'], $data['nama'], $data['deskripsi'], $data['syarat'], $id]);
    }

    public static function delete($id) {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM pelayanan WHERE id = ?");
        return $stmt->execute([$id]);
    }
}