<?php
// config.php
declare(strict_types=1);
session_start();

define('DB_HOST','127.0.0.1');
define('DB_NAME','slot_booking');
define('DB_USER','root');
define('DB_PASS',''); // <<< update this

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo "DB connection error: " . htmlspecialchars($e->getMessage());
    exit;
}

function json_res($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function sanitize_date($d) {
    $t = date_create($d);
    return $t ? $t->format('Y-m-d') : null;
}

function sanitize_time($t) {
    if ($t === null || $t === '') {
        return null;
    }
    $p = date_create_from_format('H:i', $t) ?: date_create_from_format('H:i:s', $t);
    return $p ? $p->format('H:i:s') : null;
}