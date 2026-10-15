<?php
require_once __DIR__ . '/_common.php';

$input = api_input();
$action = $_GET['action'] ?? $_POST['action'] ?? ($input['action'] ?? null);
if (!$action) api_response(false, null, 'Parameter action tidak ditemukan.');

$action = strtolower(trim((string)$action));
$actionMap = [
    'petugas' => 'list',
    'read' => 'mark_read',
    'count' => 'unread_count',
    'unread' => 'unread_count'
];
$action = $actionMap[$action] ?? $action;

if ($action === 'list') {
    require_login_api();
    $onlyUnread = (int)($_GET['unread'] ?? ($input['unread'] ?? 0)) === 1;
    $rows = getNotifications(currentUserId(), $onlyUnread);
    foreach ($rows as &$row) {
        $payloadRaw = $row['payload'] ?? '';
        if ($payloadRaw) {
            $payload = json_decode($payloadRaw, true);
            if (is_array($payload)) {
                if (!empty($payload['message'])) $row['pesan'] = $payload['message'];
                if (!empty($payload['id'])) $row['ref_id'] = $payload['id'];
                if (!empty($payload['link'])) $row['link'] = $payload['link'];
            }
        }
    }
    unset($row);
    api_response(true, $rows, null);
}

if ($action === 'mark_read') {
    require_login_api();
    $id = (int)($_GET['id'] ?? ($input['id'] ?? 0));
    if ($id <= 0) api_response(false, null, 'ID tidak ditemukan.');
    markNotificationRead($id, currentUserId());
    api_response(true, ['id' => $id], null);
}

if ($action === 'unread_count') {
    require_login_api();
    $rows = getNotifications(currentUserId(), true);
    $count = is_array($rows) ? count($rows) : 0;
    api_response(true, ['count' => $count], null);
}

api_response(false, null, 'Action tidak valid.');
