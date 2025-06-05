<?php
header('Content-Type: application/json');

// Koneksi ke database
$host = 'localhost';
$db   = 'sibe5579_myplant';
$user = 'sibe5579_cbux';
$pass = '1NvgEHFnwvDN96';

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Ambil parameter tanggal dari query string
$date = $_GET['date'] ?? date('Y-m-d');

// Ambil data dari tabel sensor_daily_avg
$stmt = $pdo->prepare("SELECT * FROM sensor_daily_avg WHERE date = :date LIMIT 1");
$stmt->execute(['date' => $date]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($data) {
    echo json_encode($data);
} else {
    echo json_encode([
        'date' => $date,
        'avg_tds' => null,
        'avg_kelembaban' => null,
        'avg_suhu' => null,
        'avg_ph' => null
    ]);
}
