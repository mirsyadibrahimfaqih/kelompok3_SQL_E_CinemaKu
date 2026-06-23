<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Silakan login terlebih dahulu!');
        header('Location: /ticketing_bioskop/login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        setFlashMessage('error', 'Akses ditolak!');
        header('Location: /ticketing_bioskop/index.php');
        exit();
    }
}

function restrictAdminFromBooking() {
    if (isAdmin()) {
        setFlashMessage('error', 'Admin tidak dapat memesan tiket. Gunakan akun customer.');
        header('Location: /ticketing_bioskop/admin/index.php');
        exit();
    }
}

function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'nama' => $_SESSION['user_nama'],
            'email' => $_SESSION['user_email']
        ];
    }
    return null;
}

// Log activity - untuk admin DAN user
function logAdminActivity($action, $details = '') {
    global $conn;
    
    if (!isLoggedIn()) return;
    
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['user_nama'] ?? 'Unknown';
    $is_admin = isAdmin() ? 1 : 0;
    
    try {
        $query = "INSERT INTO activity_log (id_pengguna, username, action, details, is_admin, created_at) 
                  VALUES (:id_pengguna, :username, :action, :details, :is_admin, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id_pengguna', $user_id);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':details', $details);
        $stmt->bindParam(':is_admin', $is_admin);
        $stmt->execute();
    } catch (PDOException $e) {
        // Silent fail
        error_log('Activity log error: ' . $e->getMessage());
    }
}
?>