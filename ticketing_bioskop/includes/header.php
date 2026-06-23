<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$database = new Database();
$conn = $database->getConnection();
$flash = getFlashMessage();

// Cek apakah user adalah admin
$is_admin_page = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CinemaKu - Pesan Tiket Bioskop Online</title>
    <link rel="stylesheet" href="/ticketing_bioskop/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if ($is_admin_page): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
</head>
<body class="<?php echo $is_admin_page ? 'admin-page' : ''; ?>">
    <nav class="navbar <?php echo $is_admin_page ? 'admin-navbar' : ''; ?>">
        <div class="container">
            <a href="<?php echo $is_admin_page ? '/ticketing_bioskop/admin/index.php' : '/ticketing_bioskop/index.php'; ?>" class="logo">
                <i class="fas fa-film"></i> CinemaKu
                <?php if ($is_admin_page): ?>
                    <span class="admin-badge">ADMIN</span>
                <?php endif; ?>
            </a>
            
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()" aria-label="Toggle Menu">
                <i class="fas fa-bars" id="menuIcon"></i>
            </button>
            
            <ul class="nav-menu" id="navMenu">
               <?php if ($is_admin_page && isAdmin()): ?>
    <!-- Admin Menu - SATU KATA -->
    <li><a href="/ticketing_bioskop/admin/index.php" data-page="admin/index.php">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a></li>
    <li><a href="/ticketing_bioskop/admin/manage_films.php" data-page="admin/manage_films.php">
        <i class="fas fa-film"></i> Film
    </a></li>
    <li><a href="/ticketing_bioskop/admin/manage_schedules.php" data-page="admin/manage_schedules.php">
        <i class="fas fa-calendar-alt"></i> Jadwal
    </a></li>
    <li><a href="/ticketing_bioskop/admin/manage_users.php" data-page="admin/manage_users.php">
        <i class="fas fa-users"></i> User
    </a></li>
    <li><a href="/ticketing_bioskop/admin/manage_bookings.php" data-page="admin/manage_bookings.php">
        <i class="fas fa-ticket-alt"></i> Transaksi
    </a></li>
    <li><a href="/ticketing_bioskop/admin/reports.php" data-page="admin/reports.php">
        <i class="fas fa-chart-line"></i> Laporan
    </a></li>
    <li><a href="/ticketing_bioskop/admin/activity_log.php" data-page="admin/activity_log.php">
        <i class="fas fa-history"></i> Log
    </a></li>
    <li><a href="/ticketing_bioskop/logout.php">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a></li>
<?php else: ?>
                    <!-- Customer Menu -->
                    <li><a href="/ticketing_bioskop/index.php" data-page="index.php">Beranda</a></li>
                    <li><a href="/ticketing_bioskop/films.php" data-page="films.php">Film</a></li>
                    <li><a href="/ticketing_bioskop/jadwal.php" data-page="jadwal.php">Jadwal</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="/ticketing_bioskop/my_bookings.php" data-page="my_bookings.php">Pesanan Saya</a></li>
                        <li><a href="/ticketing_bioskop/profile.php" data-page="profile.php">Profil</a></li>
                        <li><a href="/ticketing_bioskop/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="/ticketing_bioskop/login.php" data-page="login.php">Login</a></li>
                        <li><a href="/ticketing_bioskop/register.php" data-page="register.php">Register</a></li>
                    <?php endif; ?>
                    <li>
                        <button class="theme-toggle" onclick="toggleTheme()" title="Ganti Tema">
                            <i class="fas fa-sun" id="themeIcon"></i>
                        </button>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <i class="fas fa-<?php echo $flash['type'] == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo sanitize($flash['message']); ?>
        </div>
    <?php endif; ?>

    <main class="container">