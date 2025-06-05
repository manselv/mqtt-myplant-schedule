<?php
// koneksi PDO sama seperti sebelumnya...
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

$yesterday = date('Y-m-d', strtotime('-1 day'));

// cek sudah ada belum
$stmt = $pdo->prepare("SELECT COUNT(*) FROM sensor_daily_avg WHERE date = :date");
$stmt->execute(['date' => $yesterday]);
if ($stmt->fetchColumn()) {
    echo "Data untuk tanggal $yesterday sudah diaggregate.\n";
    exit;
}

// hitung rata-rata dari jam-jaman
$stmt = $pdo->prepare("
    SELECT 
        AVG(avg_tds) AS avg_tds,
        AVG(avg_kelembaban) AS avg_kelembaban,
        AVG(avg_suhu) AS avg_suhu,
        AVG(avg_ph) AS avg_ph
    FROM sensor_hourly_avg
    WHERE hour BETWEEN :start AND :end
");
$stmt->execute([
    'start' => $yesterday . ' 00:00:00',
    'end'   => $yesterday . ' 23:59:59'
]);
$avg = $stmt->fetch(PDO::FETCH_ASSOC);

if ($avg['avg_tds'] === null) {
    echo "Tidak ada data jam-jaman untuk tanggal $yesterday\n";
    exit;
}

// insert ke daily avg
$stmt = $pdo->prepare("
    INSERT INTO sensor_daily_avg (date, avg_tds, avg_kelembaban, avg_suhu, avg_ph)
    VALUES (:date, :avg_tds, :avg_kelembaban, :avg_suhu, :avg_ph)
");
$stmt->execute([
    'date' => $yesterday,
    'avg_tds' => $avg['avg_tds'],
    'avg_kelembaban' => $avg['avg_kelembaban'],
    'avg_suhu' => $avg['avg_suhu'],
    'avg_ph' => $avg['avg_ph'],
]);

echo "Agregasi harian untuk $yesterday selesai.\n";
