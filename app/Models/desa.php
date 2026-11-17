<?php
// app/Models/Desa.php

class Desa {
    public static function all() {
        global $pdo;
        $stmt = $pdo->query("SELECT * FROM desa ORDER BY nama ASC");
        return $stmt->fetchAll();
    }

    public static function find($id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM desa WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function create($data) {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO desa (nama, alamat, telepon) VALUES (?, ?, ?)");
        $stmt->execute([$data['nama'], $data['alamat'], $data['telepon']]);
        return $pdo->lastInsertId();
    }

    public static function update($id, $data) {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE desa SET nama = ?, alamat = ?, telepon = ? WHERE id = ?");
        return $stmt->execute([$data['nama'], $data['alamat'], $data['telepon'], $id]);
    }

    public static function delete($id) {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM desa WHERE id = ?");
        return $stmt->execute([$id]);
    }
}