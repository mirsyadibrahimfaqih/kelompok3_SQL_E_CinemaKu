<?php
require_once 'includes/header.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Get user data
$query = "SELECT * FROM pengguna WHERE id_pengguna = :id_pengguna";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id_pengguna', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Token tidak valid!');
    } else {
        $nama = sanitize($_POST['nama']);
        $email = sanitize($_POST['email']);
        $no_hp = sanitize($_POST['no_hp']);
        
        $errors = [];
        
        if (!validateEmail($email)) {
            $errors[] = 'Email tidak valid!';
        }
        
        // Check if email already used by another user
        $checkQuery = "SELECT id_pengguna FROM pengguna WHERE email = :email AND id_pengguna != :id_pengguna";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindParam(':email', $email);
        $checkStmt->bindParam(':id_pengguna', $user_id);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            $errors[] = 'Email sudah digunakan pengguna lain!';
        }
        
        if (empty($errors)) {
            $updateQuery = "UPDATE pengguna SET nama = :nama, email = :email, no_hp = :no_hp WHERE id_pengguna = :id_pengguna";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bindParam(':nama', $nama);
            $updateStmt->bindParam(':email', $email);
            $updateStmt->bindParam(':no_hp', $no_hp);
            $updateStmt->bindParam(':id_pengguna', $user_id);
            
            if ($updateStmt->execute()) {
                // Update session
                $_SESSION['user_nama'] = $nama;
                $_SESSION['user_email'] = $email;
                
                setFlashMessage('success', 'Profil berhasil diperbarui!');
                header('Location: profile.php');
                exit();
            } else {
                setFlashMessage('error', 'Gagal memperbarui profil!');
            }
        } else {
            foreach ($errors as $error) {
                setFlashMessage('error', $error);
            }
        }
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Token tidak valid!');
    } else {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        $errors = [];
        
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = 'Password saat ini salah!';
        }
        
        if (strlen($new_password) < 6) {
            $errors[] = 'Password baru minimal 6 karakter!';
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = 'Konfirmasi password tidak cocok!';
        }
        
        if (empty($errors)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $updateQuery = "UPDATE pengguna SET password = :password WHERE id_pengguna = :id_pengguna";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bindParam(':password', $hashed_password);
            $updateStmt->bindParam(':id_pengguna', $user_id);
            
            if ($updateStmt->execute()) {
                setFlashMessage('success', 'Password berhasil diubah!');
                header('Location: profile.php');
                exit();
            }
        } else {
            foreach ($errors as $error) {
                setFlashMessage('error', $error);
            }
        }
    }
}

$csrf_token = generateCSRFToken();
?>

<div class="page-header">
    <h2><i class="fas fa-user"></i> Profil Saya</h2>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem;">
    <!-- Profile Info -->
    <div style="background: #1a1a1a; padding: 2rem; border-radius: 10px;">
        <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-user-edit"></i> Informasi Profil</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" required 
                       value="<?php echo sanitize($user['nama']); ?>">
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required 
                       value="<?php echo sanitize($user['email']); ?>">
            </div>
            
            <div class="form-group">
                <label>No. HP</label>
                <input type="text" name="no_hp" class="form-control" required 
                       value="<?php echo sanitize($user['no_hp']); ?>">
            </div>
            
            <button type="submit" class="btn" style="width: 100%;">
                <i class="fas fa-save"></i> Simpan Perubahan
            </button>
        </form>
    </div>
    
    <!-- Change Password -->
    <div style="background: #1a1a1a; padding: 2rem; border-radius: 10px;">
        <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-lock"></i> Ubah Password</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="change_password" value="1">
            
            <div class="form-group">
                <label>Password Saat Ini</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Password Baru</label>
                <input type="password" name="new_password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Konfirmasi Password Baru</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-secondary" style="width: 100%;">
                <i class="fas fa-key"></i> Ubah Password
            </button>
        </form>
    </div>
</div>



<?php require_once 'includes/footer.php'; ?>