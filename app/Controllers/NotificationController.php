<?php
// app/Controllers/NotificationController.php

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/functions.php';
require_once __DIR__ . '/../Models/Notification.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$user   = auth();
$action = $_GET['action'] ?? 'list';

// LIST NOTIF (dropdown)
if ($action === 'list') {
    $notifs = Notification::getLatest($user['id'], 10);
    $unread = Notification::getUnreadCount($user['id']);
    echo json_encode(['notifications' => $notifs, 'unread' => $unread]);
    exit;
}

// HITUNG UNREAD (polling)
if ($action === 'count') {
    echo json_encode(['unread' => Notification::getUnreadCount($user['id'])]);
    exit;
}

// MARK AS READ (1 notif)
if ($action === 'read' && !empty($_GET['id'])) {
    Notification::markAsRead($_GET['id'], $user['id']);
    echo json_encode(['success' => true]);
    exit;
}

// MARK ALL AS READ
if ($action === 'read_all') {
    Notification::markAllAsRead($user['id']);
    echo json_encode(['success' => true]);
    exit;
}

// CLEAR ALL
if ($action === 'clear') {
    Notification::clearAll($user['id']);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'unknown action']);
