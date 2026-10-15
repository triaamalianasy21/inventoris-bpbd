<?php

require_once __DIR__ . '/../models/Koneksi.php';
require_once __DIR__ . '/../config/auth_helper.php';

class NotificationsController
{
    private $conn;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->conn = Koneksi::connect();
    }

    public function index()
    {
        $uid = currentUserId();
        if (!$uid) {
            header('Location: index.php?page=login');
            exit;
        }

        $notifs = getNotifications($uid);
        include __DIR__ . '/../views/notifications/index.php';
    }

    public function markRead()
    {
        $id = intval($_GET['id'] ?? 0);
        $uid = currentUserId();
        if (!$uid || !$id) {
            header('Location: index.php?page=notifications');
            exit;
        }
        markNotificationRead($id, $uid);
        header('Location: index.php?page=notifications');
        exit;
    }
}