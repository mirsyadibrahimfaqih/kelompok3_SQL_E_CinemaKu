<?php 
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

// Query untuk statistik - Pastikan kolom ada
try {
    // Total Film
    $stmt = $conn->query("SELECT COUNT(*) as total FROM film");
    $stats['total_film'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total Transaksi
    $stmt = $conn->query("SELECT COUNT(*) as total FROM pemesanan");
    $stats['total_transaksi'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total Pendapatan - Gunakan COALESCE untuk handle NULL
    $stmt = $conn->query("SELECT COALESCE(SUM(total_harga), 0) as total FROM pemesanan WHERE id_pemesanan IN (SELECT id_pemesanan FROM pembayaran WHERE status_bayar = 'Lunas')");
    $stats['total_pendapatan'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total User
    $stmt = $conn->query("SELECT COUNT(*) as total FROM pengguna WHERE is_admin = 0");
    $stats['total_user'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
} catch(PDOException $e) {
    // Fallback jika ada error
    $stats = [
        'total_film' => 0,
        'total_transaksi' => 0,
        'total_pendapatan' => 0,
        'total_user' => 0
    ];
    setFlashMessage('error', 'Error loading stats: ' . $e->getMessage());
}

// User paling aktif
try {
    $top_users = $conn->query("
        SELECT u.nama, u.email, COUNT(p.id_pemesanan) as total_transaksi, 
               COALESCE(SUM(p.total_harga), 0) as total_spent
        FROM pengguna u 
        LEFT JOIN pemesanan p ON u.id_pengguna = p.id_pengguna 
        WHERE u.is_admin = 0 
        GROUP BY u.id_pengguna 
        ORDER BY total_transaksi DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $top_users = [];
}

// Transaksi terbaru
try {
    $recent_transactions = $conn->query("
        SELECT p.id_pemesanan, p.tanggal_pesan, p.total_harga, p.kode_booking,
               u.nama as nama_user,
               f.judul as nama_film
        FROM pemesanan p
        LEFT JOIN pengguna u ON p.id_pengguna = u.id_pengguna
        LEFT JOIN tiket t ON p.id_pemesanan = t.id_pemesanan
        LEFT JOIN jadwal j ON t.id_jadwal = j.id_jadwal
        LEFT JOIN film f ON j.id_film = f.id_film
        ORDER BY p.tanggal_pesan DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $recent_transactions = [];
}

// Pendapatan 7 hari terakhir
try {
    $revenue_7days = $conn->query("
        SELECT DATE(tanggal_pesan) as tanggal, 
               SUM(total_harga) as total,
               COUNT(*) as count
        FROM pemesanan 
        WHERE tanggal_pesan >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        GROUP BY DATE(tanggal_pesan) 
        ORDER BY tanggal ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $revenue_7days = [];
}

// Film terlaris
try {
    $top_films = $conn->query("
        SELECT f.judul, COUNT(t.id_tiket) as total_terjual,
               COALESCE(SUM(t.harga), 0) as pendapatan
        FROM film f 
        LEFT JOIN jadwal j ON f.id_film = j.id_film 
        LEFT JOIN tiket t ON j.id_jadwal = t.id_jadwal 
        GROUP BY f.id_film 
        ORDER BY total_terjual DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $top_films = [];
}
?>

<div class="page-header">
    <h2><i class="fas fa-tachometer-alt"></i> Dashboard Admin</h2>
    <p>Selamat datang, <?php echo sanitize($_SESSION['user_nama']); ?>! 👋</p>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card gradient-purple">
        <div class="stat-icon"><i class="fas fa-film"></i></div>
        <div class="stat-info">
            <h4>Total Film</h4>
            <h2><?php echo $stats['total_film']; ?></h2>
        </div>
    </div>
    
    <div class="stat-card gradient-pink">
        <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
        <div class="stat-info">
            <h4>Total Transaksi</h4>
            <h2><?php echo $stats['total_transaksi']; ?></h2>
        </div>
    </div>
    
    <div class="stat-card gradient-blue">
        <div class="stat-icon"><i class="fas fa-wallet"></i></div>
        <div class="stat-info">
            <h4>Total Pendapatan</h4>
            <h2><?php echo formatRupiah($stats['total_pendapatan']); ?></h2>
        </div>
    </div>
    
    <div class="stat-card gradient-green">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h4>Total User</h4>
            <h2><?php echo $stats['total_user']; ?></h2>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 2rem; margin-top: 2rem;">
    
    <!-- User Teraktif -->
    <div class="glass-card">
        <div class="card-header">
            <h3><i class="fas fa-crown"></i> User Teraktif</h3>
            <button onclick="exportTable('topUsersTable', 'User_Teraktif')" class="btn-export-small">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
        <div id="topUsersTable">
            <?php if (empty($top_users)): ?>
                <p style="text-align: center; color: var(--text-secondary); padding: 2rem;">Belum ada data user</p>
            <?php else: ?>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Transaksi</th>
                            <th>Total Belanja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($top_users as $user): 
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><strong><?php echo sanitize($user['nama']); ?></strong></td>
                            <td><?php echo sanitize($user['email']); ?></td>
                            <td><span class="badge badge-primary"><?php echo $user['total_transaksi']; ?>x</span></td>
                            <td><strong style="color: var(--success);"><?php echo formatRupiah($user['total_spent']); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Film Terlaris -->
    <div class="glass-card">
        <div class="card-header">
            <h3><i class="fas fa-trophy"></i> Film Terlaris</h3>
            <button onclick="exportTable('topFilmsTable', 'Film_Terlaris')" class="btn-export-small">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
        <div id="topFilmsTable">
            <?php if (empty($top_films)): ?>
                <p style="text-align: center; color: var(--text-secondary); padding: 2rem;">Belum ada data film</p>
            <?php else: ?>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Judul Film</th>
                            <th>Tiket Terjual</th>
                            <th>Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($top_films as $film): 
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><strong><?php echo sanitize($film['judul']); ?></strong></td>
                            <td><span class="badge badge-success"><?php echo $film['total_terjual']; ?> tiket</span></td>
                            <td><strong style="color: var(--primary);"><?php echo formatRupiah($film['pendapatan']); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Pendapatan 7 Hari -->
    <div class="glass-card" style="grid-column: 1 / -1;">
        <div class="card-header">
            <h3><i class="fas fa-chart-bar"></i> Pendapatan 7 Hari Terakhir</h3>
            <button onclick="exportTable('revenueTable', 'Pendapatan_7_Hari')" class="btn-export-small">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
        <div id="revenueTable">
            <?php if (empty($revenue_7days)): ?>
                <p style="text-align: center; color: var(--text-secondary); padding: 2rem;">Belum ada data transaksi</p>
            <?php else: ?>
                <div class="simple-bar-chart">
                    <?php 
                    $max_revenue = max(array_column($revenue_7days, 'total'));
                    foreach ($revenue_7days as $day): 
                        $percentage = ($max_revenue > 0) ? ($day['total'] / $max_revenue * 100) : 0;
                    ?>
                    <div class="bar-item">
                        <div class="bar-label"><?php echo formatTanggal($day['tanggal']); ?></div>
                        <div class="bar-container">
                            <div class="bar-fill" style="width: <?php echo $percentage; ?>%;">
                                <span class="bar-value"><?php echo formatRupiah($day['total']); ?></span>
                            </div>
                        </div>
                        <div class="bar-count"><?php echo $day['count']; ?> transaksi</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Transaksi Terbaru -->
    <div class="glass-card" style="grid-column: 1 / -1;">
        <div class="card-header">
            <h3><i class="fas fa-clock"></i> Transaksi Terbaru</h3>
            <button onclick="exportTable('recentTransTable', 'Transaksi_Terbaru')" class="btn-export-small">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
        <div id="recentTransTable">
            <?php if (empty($recent_transactions)): ?>
                <p style="text-align: center; color: var(--text-secondary); padding: 2rem;">Belum ada transaksi</p>
            <?php else: ?>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Kode Booking</th>
                            <th>User</th>
                            <th>Film</th>
                            <th>Tanggal</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_transactions as $trans): ?>
                        <tr>
                            <td><code style="background: rgba(229, 9, 20, 0.1); padding: 0.3rem 0.6rem; border-radius: 5px;"><?php echo sanitize($trans['kode_booking'] ?? '-'); ?></code></td>
                            <td><?php echo sanitize($trans['nama_user'] ?? '-'); ?></td>
                            <td><?php echo sanitize($trans['nama_film'] ?? '-'); ?></td>
                            <td><?php echo $trans['tanggal_pesan'] ? date('d/m/Y H:i', strtotime($trans['tanggal_pesan'])) : '-'; ?></td>
                            <td><strong><?php echo formatRupiah($trans['total_harga'] ?? 0); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <h3><i class="fas fa-bolt"></i> Menu Cepat</h3>
    <div class="action-grid">
        <a href="manage_films.php" class="action-card">
            <i class="fas fa-film"></i>
            <span>Kelola Film</span>
        </a>
        <a href="manage_schedules.php" class="action-card">
            <i class="fas fa-calendar-alt"></i>
            <span>Kelola Jadwal</span>
        </a>
        <a href="manage_users.php" class="action-card">
            <i class="fas fa-users"></i>
            <span>Kelola User</span>
        </a>
        <a href="manage_bookings.php" class="action-card">
            <i class="fas fa-ticket-alt"></i>
            <span>Data Transaksi</span>
        </a>
        <a href="reports.php" class="action-card">
            <i class="fas fa-chart-line"></i>
            <span>Laporan</span>
        </a>
        <a href="activity_log.php" class="action-card">
            <i class="fas fa-history"></i>
            <span>Activity Log</span>
        </a>
    </div>
</div>

<script>
function exportTable(tableId, filename) {
    const table = document.querySelector('#' + tableId + ' table');
    if (!table) {
        alert('Tabel tidak ditemukan!');
        return;
    }
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let row of rows) {
        const cols = row.querySelectorAll('td, th');
        let csvRow = [];
        for (let col of cols) {
            csvRow.push('"' + col.textContent.trim() + '"');
        }
        csv.push(csvRow.join(','));
    }
    
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename + '_' + new Date().toISOString().split('T')[0] + '.csv';
    link.click();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>