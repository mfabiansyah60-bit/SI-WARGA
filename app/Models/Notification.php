<?php
// app/Models/Notification.php

class Notification {
    
    /** Bikin notif untuk 1 user */
    public static function create($userId, $type, $title, $message, $link = null) {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$userId, $type, $title, $message, $link]);
    }

    /** Bikin notif untuk banyak user (based on role) */
    public static function notifyByRole($roles, $type, $title, $message, $link = null, $excludeUserId = null) {
        global $pdo;
        $roles = (array) $roles;
        if (empty($roles)) return false;

        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $sql = "SELECT id FROM users WHERE role IN ($placeholders)";
        $params = $roles;

        if ($excludeUserId) {
            $sql .= " AND id != ?";
            $params[] = $excludeUserId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($users)) return false;

        $values = [];
        $bind   = [];
        foreach ($users as $uid) {
            $values[] = "(?, ?, ?, ?, ?)";
            array_push($bind, $uid, $type, $title, $message, $link);
        }
        $sql = "INSERT INTO notifications (user_id, type, title, message, link) VALUES " . implode(',', $values);
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($bind);
    }

    /** Hitung notif belum dibaca */
    public static function getUnreadCount($userId) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    /** Ambil N notif terbaru */
    public static function getLatest($userId, $limit = 10) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT " . (int)$limit);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Tandai 1 notif udah dibaca */
    public static function markAsRead($id, $userId) {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    /** Tandai semua udah dibaca */
    public static function markAllAsRead($userId) {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        return $stmt->execute([$userId]);
    }

    /** Hapus semua notif user */
    public static function clearAll($userId) {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
}
