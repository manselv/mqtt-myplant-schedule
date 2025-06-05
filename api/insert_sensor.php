<?php
// Set header agar hanya menerima JSON POST
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Optional: ubah sesuai domain asal
header("Access-Control-Allow-Methods: POST");

// Cek apakah metode yang digunakan adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Metode tidak diizinkan. Gunakan POST."]);
    exit;
}

// Ambil body JSON
$data = json_decode(file_get_contents("php://input"), true);

// Validasi input
if (!isset($data['tds'], $data['kelembaban'], $data['suhu'], $data['ph'])) {
    http_response_code(400);
    echo json_encode(["error" => "Data tidak lengkap."]);
    exit;
}

// Koneksi ke database
$host = "localhost";
$user = "sibe5579_cbux";
$password = "1NvgEHFnwvDN96";
$dbname = "sibe5579_myplant"; // Ganti dengan nama DB kamu

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Gagal koneksi database."]);
    exit;
}

// Siapkan dan eksekusi query
$timestamp = date('Y-m-d H:i:s');
$stmt = $conn->prepare("INSERT INTO sensor_data (timestamp, tds, kelembaban, suhu, ph) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sdddd", $timestamp, $data['tds'], $data['kelembaban'], $data['suhu'], $data['ph']);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Data berhasil disimpan."]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Gagal menyimpan data."]);
}

$stmt->close();
$conn->close();
?>
