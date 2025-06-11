<?php
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
    die("Connection failed: " . $e->getMessage());
}

// Hitung rata-rata untuk jam terakhir yang sudah lewat
// Contoh: jam sekarang 14:10, maka hitung agregasi untuk jam 13:00:00 sampai 13:59:59
$lastHour = date('Y-m-d H:00:00', strtotime('-1 hour'));
$nextHour = date('Y-m-d H:59:59', strtotime('-1 hour'));

// Cek apakah data sudah pernah diaggregate untuk jam ini
$stmt = $pdo->prepare("SELECT COUNT(*) FROM sensor_hourly_avg WHERE hour = :hour");
$stmt->execute(['hour' => $lastHour]);
$exists = $stmt->fetchColumn();

if ($exists) {
    echo "Data untuk jam $lastHour sudah diaggregate.\n";
    exit;
}

// Hitung rata-rata per parameter dari data per detik
$stmt = $pdo->prepare("
    SELECT 
        AVG(tds) AS avg_tds,
        AVG(kelembaban) AS avg_kelembaban,
        AVG(suhu) AS avg_suhu,
        AVG(ph) AS avg_ph
    FROM sensor_data
    WHERE timestamp BETWEEN :start AND :end
");
$stmt->execute(['start' => $lastHour, 'end' => $nextHour]);
$avg = $stmt->fetch(PDO::FETCH_ASSOC);

if ($avg['avg_tds'] === null) {
    echo "Tidak ada data untuk jam $lastHour\n";
    exit;
}

// Simpan hasil agregasi
$stmt = $pdo->prepare("
    INSERT INTO sensor_hourly_avg (hour, avg_tds, avg_kelembaban, avg_suhu, avg_ph)
    VALUES (:hour, :avg_tds, :avg_kelembaban, :avg_suhu, :avg_ph)
");
$stmt->execute([
    'hour' => $lastHour,
    'avg_tds' => $avg['avg_tds'],
    'avg_kelembaban' => $avg['avg_kelembaban'],
    'avg_suhu' => $avg['avg_suhu'],
    'avg_ph' => $avg['avg_ph'],
]);

// Hapus data per detik untuk jam itu (optional)
// $stmt = $pdo->prepare("DELETE FROM sensor_data WHERE timestamp BETWEEN :start AND :end");
// $stmt->execute(['start' => $lastHour, 'end' => $nextHour]);

echo "Agregasi untuk jam $lastHour selesai.\n";