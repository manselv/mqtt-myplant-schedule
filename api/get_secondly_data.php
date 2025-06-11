<?php
header('Content-Type: application/json');

// Koneksi ke database
$host = 'localhost';
$db = 'sibe5579_myplant';
$user = 'sibe5579_cbux';
$pass = '1NvgEHFnwvDN96';

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Ambil 7 data terakhir berdasarkan tanggal DESC, lalu dibalik urutannya (ASC)
$stmt = $pdo->query("
    SELECT * FROM `sensor_data`
    ORDER BY timestamp ASC
");

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kembalikan data sebagai JSON
echo json_encode($data);