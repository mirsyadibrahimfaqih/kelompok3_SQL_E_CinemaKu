<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$id_jadwal = isset($data['id_jadwal']) ? (int)$data['id_jadwal'] : 0;
$kursi_ids = isset($data['kursi_ids']) ? $data['kursi_ids'] : [];
$total_harga = isset($data['total_harga']) ? (float)$data['total_harga'] : 0;
$metode_bayar = isset($data['metode_bayar']) ? sanitize($data['metode_bayar']) : 'QRIS';
$user_id = $_SESSION['user_id'];

if (empty($kursi_ids) || !$id_jadwal || !$total_harga) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit();
}

try {
    $conn->beginTransaction();
    
    // Generate kode booking
    $kode_booking = 'CT' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Insert pemesanan
    $query_pemesanan = "INSERT INTO pemesanan (id_pengguna, tanggal_pesan, total_harga, kode_booking) 
                        VALUES (:id_pengguna, NOW(), :total_harga, :kode_booking)";
    $stmt_p = $conn->prepare($query_pemesanan);
    $stmt_p->bindParam(':id_pengguna', $user_id);
    $stmt_p->bindParam(':total_harga', $total_harga);
    $stmt_p->bindParam(':kode_booking', $kode_booking);
    $stmt_p->execute();
    
    $id_pemesanan = $conn->lastInsertId();
    
    // Insert tiket
    $query_tiket = "INSERT INTO tiket (id_pemesanan, id_jadwal, id_kursi, harga) 
                    VALUES (:id_pemesanan, :id_jadwal, :id_kursi, :harga)";
    $stmt_t = $conn->prepare($query_tiket);
    
    $stmt_harga = $conn->prepare("SELECT harga FROM jadwal WHERE id_jadwal = :id_jadwal");
    $stmt_harga->bindParam(':id_jadwal', $id_jadwal);
    $stmt_harga->execute();
    $harga_tiket = $stmt_harga->fetchColumn();
    
    foreach ($kursi_ids as $id_kursi) {
        $stmt_t->bindParam(':id_pemesanan', $id_pemesanan);
        $stmt_t->bindParam(':id_jadwal', $id_jadwal);
        $stmt_t->bindParam(':id_kursi', $id_kursi);
        $stmt_t->bindParam(':harga', $harga_tiket);
        $stmt_t->execute();
    }
    
    // Insert pembayaran
    $query_bayar = "INSERT INTO pembayaran (id_pemesanan, metode, status_bayar, tanggal_bayar) 
                    VALUES (:id_pemesanan, :metode, 'Lunas', NOW())";
    $stmt_b = $conn->prepare($query_bayar);
    $stmt_b->bindParam(':id_pemesanan', $id_pemesanan);
    $stmt_b->bindParam(':metode', $metode_bayar);
    $stmt_b->execute();
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Pembayaran berhasil',
        'id_pemesanan' => $id_pemesanan,
        'kode_booking' => $kode_booking
    ]);
    
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>