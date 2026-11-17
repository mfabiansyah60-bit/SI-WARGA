<?php
// app/Models/Rt.php

class Rt {
    public static function all() {
        global $pdo;
        $stmt = $pdo->query("SELECT rt.*, rw.nomor as rw_nomor, desa.nama as desa_nama FROM rt JOIN rw ON rt.rw_id = rw.id JOIN desa ON rw.desa_id = desa.id ORDER BY desa.nama, rw.nomor, rt.nomor");
        return $stmt->fetchAll();
    }

    public static function findByRw($rw_id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM rt WHERE rw_id = ? ORDER BY nomor");
        $stmt->execute([$rw_id]);
        return $stmt->fetchAll();
    }

    public static function find($id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM rt WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function create($data) {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO rt (rw_id, nomor, ketua) VALUES (?, ?, ?)");
        $stmt->execute([$data['rw_id'], $data['nomor'], $data['ketua']]);
        return $pdo->lastInsertId();
    }

    public static function update($id, $data) {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE rt SET rw_id = ?, nomor = ?, ketua = ? WHERE id = ?");
        return $stmt->execute([$data['rw_id'], $data['nomor'], $data['ketua'], $id]);
    }

    public static function delete($id) {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM rt WHERE id = ?");
        return $stmt->execute([$id]);
    }
}