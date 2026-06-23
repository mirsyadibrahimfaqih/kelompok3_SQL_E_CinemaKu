<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$id_kursi = isset($data['id_kursi']) ? (int)$data['id_kursi'] : 0;
$id_jadwal = isset($data['id_jadwal']) ? (int)$data['id_jadwal'] : 0;
$action = isset($data['action']) ? $data['action'] : 'lock'; // 'lock' or 'unlock'

$database = new Database();
$conn = $database->getConnection();

try {
    if ($action === 'lock') {
        // Check if seat is available
        $check_query = "SELECT id_tiket FROM tiket WHERE id_kursi = :id_kursi AND id_jadwal = :id_jadwal";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bindParam(':id_kursi', $id_kursi);
        $check_stmt->bindParam(':id_jadwal', $id_jadwal);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Kursi sudah terisi!']);
            exit();
        }
        
        echo json_encode(['success' => true, 'message' => 'Kursi berhasil dikunci']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Kursi berhasil dibuka']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>