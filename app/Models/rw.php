<?php
// app/Models/Rw.php

class Rw {
    public static function all() {
        global $pdo;
        $stmt = $pdo->query("SELECT rw.*, desa.nama as desa_nama FROM rw JOIN desa ON rw.desa_id = desa.id ORDER BY desa.nama, rw.nomor");
        return $stmt->fetchAll();
    }

    public static function findByDesa($desa_id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM rw WHERE desa_id = ? ORDER BY nomor");
        $stmt->execute([$desa_id]);
        return $stmt->fetchAll();
    }

    public static function find($id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM rw WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function create($data) {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO rw (desa_id, nomor, ketua) VALUES (?, ?, ?)");
        $stmt->execute([$data['desa_id'], $data['nomor'], $data['ketua']]);
        return $pdo->lastInsertId();
    }

    public static function update($id, $data) {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE rw SET desa_id = ?, nomor = ?, ketua = ? WHERE id = ?");
        return $stmt->execute([$data['desa_id'], $data['nomor'], $data['ketua'], $id]);
    }

    public static function delete($id) {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM rw WHERE id = ?");
        return $stmt->execute([$id]);
    }
}