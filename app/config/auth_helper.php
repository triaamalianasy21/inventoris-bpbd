<?php
// Helper autentikasi & role
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../models/Koneksi.php';

function currentUserId()
{
    return $_SESSION['user_id'] ?? null;
}

function currentUserRole()
{
    return $_SESSION['role'] ?? null;
}

/**
 * Cek apakah user saat ini punya role tertentu.
 * Argumen $roles bisa string (satu role) atau array of roles.
 */
function hasRole($roles)
{
    $r = currentUserRole();
    if (!$r) return false;
    if (is_array($roles)) {
        return in_array($r, $roles);
    }
    return $r === $roles;
}

/**
 * Require role(s) or redirect ke login / tampilkan 403
 */
function requireRole($roles, $redirectToLogin = true)
{
    if (!hasRole($roles)) {
        if ($redirectToLogin) {
            header('Location: index.php?page=login');
            exit;
        }
        header('HTTP/1.1 403 Forbidden');
        echo 'Akses ditolak.';
        exit;
    }
}

// Notifikasi in-app sederhana
function notifyUser($userId, $type, $payload = [])
{
    $conn = Koneksi::connect();
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, payload, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
    $json = json_encode($payload);
    $stmt->bind_param('iss', $userId, $type, $json);
    return $stmt->execute();
}

function getNotifications($userId, $onlyUnread = false)
{
    $conn = Koneksi::connect();
    $sql = "SELECT * FROM notifications WHERE user_id = ?" . ($onlyUnread ? " AND is_read = 0" : "") . " ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    return $rows;
}

function markNotificationRead($id, $userId = null)
{
    $conn = Koneksi::connect();
    if ($userId) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param('ii', $id, $userId);
    } else {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->bind_param('i', $id);
    }
    return $stmt->execute();
}
