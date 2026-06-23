<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

$id_jadwal = isset($_GET['id_jadwal']) ? (int)$_GET['id_jadwal'] : 0;

if (!$id_jadwal) {
    echo json_encode(['error' => 'ID Jadwal tidak valid']);
    exit();
}

// Ambil studio ID dari jadwal
$stmt_studio = $conn->prepare("SELECT id_studio FROM jadwal WHERE id_jadwal = :id_jadwal");
$stmt_studio->bindParam(':id_jadwal', $id_jadwal);
$stmt_studio->execute();
$studio = $stmt_studio->fetch(PDO::FETCH_ASSOC);

if (!$studio) {
    echo json_encode(['error' => 'Jadwal tidak ditemukan']);
    exit();
}

$id_studio = $studio['id_studio'];

// Query dengan LEFT JOIN untuk cek status kursi
$query = "SELECT k.id_kursi, k.nomor_kursi, 
          CASE WHEN t.id_tiket IS NOT NULL THEN 'occupied' ELSE 'available' END as status
          FROM kursi k
          LEFT JOIN tiket t ON k.id_kursi = t.id_kursi AND t.id_jadwal = :id_jadwal
          WHERE k.id_studio = :id_studio
          ORDER BY k.nomor_kursi ASC";

$stmt = $conn->prepare($query);
$stmt->bindParam(':id_jadwal', $id_jadwal);
$stmt->bindParam(':id_studio', $id_studio);
$stmt->execute();

$seats = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'seats' => $seats,
    'total_seats' => count($seats)
]);
?>