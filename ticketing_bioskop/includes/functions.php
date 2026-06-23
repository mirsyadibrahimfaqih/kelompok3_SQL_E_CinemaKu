<?php
// Security Functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Format functions
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

function formatTanggal($tanggal) {
    // Jika tanggal kosong atau null, return string default
    if (empty($tanggal)) {
        return 'Tanggal tidak tersedia';
    }
    
    // Coba pecah dengan format YYYY-MM-DD
    $pecahkan = explode('-', $tanggal);
    
    // Jika tidak ada 3 bagian (YYYY-MM-DD), coba format DD/MM/YYYY
    if (count($pecahkan) !== 3) {
        $pecahkan = explode('/', $tanggal);
        if (count($pecahkan) === 3) {
            // Format DD/MM/YYYY -> ubah jadi array [YYYY, MM, DD]
            $pecahkan = [$pecahkan[2], $pecahkan[1], $pecahkan[0]];
        } else {
            return 'Format tanggal tidak valid';
        }
    }
    
    // Validasi array memiliki 3 elemen
    if (!isset($pecahkan[0], $pecahkan[1], $pecahkan[2])) {
        return 'Format tanggal tidak valid';
    }
    
    $tahun = $pecahkan[0];
    $bulan_num = (int)$pecahkan[1];
    $hari = $pecahkan[2];
    
    // Validasi bulan
    if ($bulan_num < 1 || $bulan_num > 12) {
        return 'Bulan tidak valid';
    }
    
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    return $hari . ' ' . $bulan[$bulan_num] . ' ' . $tahun;
}

function formatJam($jam) {
    return substr($jam, 0, 5);
}

// Flash message
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Transaction helper
function beginTransaction($conn) {
    $conn->beginTransaction();
}

function commitTransaction($conn) {
    $conn->commit();
}

function rollbackTransaction($conn) {
    $conn->rollBack();
}
?>