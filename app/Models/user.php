<?php
// app/Models/User.php

class User {

    public static function find($id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function findByEmail($email) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        global $pdo;
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role, desa_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['name'],
            $data['email'],
            $hash,
            $data['role'] ?? 'warga',
            $data['desa_id'] ?? null
        ]);
        return $pdo->lastInsertId();
    }

    public static function update($id, $data) {
        global $pdo;
        $stmt = $pdo->prepare("
            UPDATE users SET
                name = ?,
                email = ?,
                role = ?,
                desa_id = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['email'],
            $data['role'],
            $data['desa_id'],
            $id
        ]);
    }

    public static function delete($id) {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function all() {
        global $pdo;
        $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}