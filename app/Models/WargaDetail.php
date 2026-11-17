<?php
// app/Models/WargaDetail.php

class WargaDetail {
    public static function findByUser($user_id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT wd.*, rt.nomor as rt_nomor, rw.nomor as rw_nomor, desa.nama as desa_nama FROM warga_detail wd JOIN rt ON wd.rt_id = rt.id JOIN rw ON rt.rw_id = rw.id JOIN desa ON rw.desa_id = desa.id WHERE wd.user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }

    public static function create($data) {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO warga_detail (user_id, nik, alamat, tanggal_lahir, pekerjaan, rt_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$data['user_id'], $data['nik'], $data['alamat'], $data['tanggal_lahir'], $data['pekerjaan'], $data['rt_id']]);
        return $pdo->lastInsertId();
    }

    public static function update($user_id, $data) {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE warga_detail SET nik = ?, alamat = ?, tanggal_lahir = ?, pekerjaan = ?, rt_id = ? WHERE user_id = ?");
        return $stmt->execute([$data['nik'], $data['alamat'], $data['tanggal_lahir'], $data['pekerjaan'], $data['rt_id'], $user_id]);
    }
}