<?php
require_once 'includes/header.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Token tidak valid!');
    } else {
        $nama = sanitize($_POST['nama']);
        $email = sanitize($_POST['email']);
        $no_hp = sanitize($_POST['no_hp']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        $errors = [];
        
        if (!validateEmail($email)) {
            $errors[] = 'Email tidak valid!';
        }
        
        if (strlen($password) < 6) {
            $errors[] = 'Password minimal 6 karakter!';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Password tidak cocok!';
        }
        
        $checkQuery = "SELECT id_pengguna FROM pengguna WHERE email = :email";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindParam(':email', $email);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            $errors[] = 'Email sudah terdaftar!';
        }
        
        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $query = "INSERT INTO pengguna (nama, email, no_hp, password) 
                      VALUES (:nama, :email, :no_hp, :password)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':nama', $nama);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':no_hp', $no_hp);
            $stmt->bindParam(':password', $hashed_password);
            
            if ($stmt->execute()) {
                // LOG ACTIVITY
                logAdminActivity('Registrasi User', "User baru mendaftar: $nama ($email)");
                
                setFlashMessage('success', 'Registrasi berhasil! Silakan login.');
                header('Location: login.php');
                exit();
            } else {
                setFlashMessage('error', 'Terjadi kesalahan!');
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

<div class="auth-container">
    <h2><i class="fas fa-user-plus"></i> Daftar Akun</h2>
    
    <form method="POST" action="" class="auth-form">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        
        <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" name="nama" class="form-control" required 
                   value="<?php echo isset($_POST['nama']) ? sanitize($_POST['nama']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required 
                   value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label>No. HP</label>
            <input type="text" name="no_hp" class="form-control" required 
                   value="<?php echo isset($_POST['no_hp']) ? sanitize($_POST['no_hp']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label>Konfirmasi Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
        </div>
        
        <button type="submit" class="btn btn-block">Daftar</button>
        
        <p class="text-center" style="margin-top: 1rem;">
            Sudah punya akun? <a href="login.php">Login</a>
        </p>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>