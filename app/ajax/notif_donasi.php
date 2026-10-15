<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../models/DonasiModel.php';

header('Content-Type: application/json');

$role = $_SESSION['role'] ?? '';
$pending = 0;

if ($role === 'admin') {
    $donasiModel = new DonasiModel();
    $pending = $donasiModel->countPending();
}

echo json_encode(['pending' => $pending]);
