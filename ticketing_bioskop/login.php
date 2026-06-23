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
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        
        $query = "SELECT * FROM pengguna WHERE email = :email";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id_pengguna'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            // LOG ACTIVITY
            $role = $user['is_admin'] ? 'Admin' : 'User';
            logAdminActivity('Login', "$role login: $email");
            
            if ($user['is_admin']) {
                header('Location: admin/index.php');
            } else {
                header('Location: index.php');
            }
            exit();
        } else {
            setFlashMessage('error', 'Email atau password salah!');
        }
    }
}

$csrf_token = generateCSRFToken();
?>

<div class="auth-container">
    <h2><i class="fas fa-sign-in-alt"></i> Login</h2>
    
    <form method="POST" action="" class="auth-form">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        
        <button type="submit" class="btn btn-block">Login</button>
        
        <p class="text-center" style="margin-top: 1rem;">
            Belum punya akun? <a href="register.php">Daftar</a>
        </p>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>