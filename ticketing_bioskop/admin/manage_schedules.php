<?php
session_start();

// Koneksi database manual
$host = 'localhost';
$dbname = 'ticketing_bioskop';
$username = 'root';
$password = '';

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Handle actions
$message = '';
$messageType = '';
$action = $_GET['action'] ?? '';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    // SOFT DELETE
    if ($action === 'cancel' && $id) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tiket WHERE id_jadwal = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        
        $stmt = $conn->prepare("UPDATE jadwal SET status = 'dibatalkan' WHERE id_jadwal = :id");
        $stmt->execute([':id' => $id]);
        
        if ($result['total'] > 0) {
            $message = "Jadwal dibatalkan ({$result['total']} tiket tetap tersimpan untuk laporan)";
            $messageType = 'warning';
        } else {
            $message = 'Jadwal berhasil dibatalkan!';
            $messageType = 'success';
        }
    }
    
    // RESTORE
    if ($action === 'restore' && $id) {
        $stmt = $conn->prepare("UPDATE jadwal SET status = 'aktif' WHERE id_jadwal = :id");
        $stmt->execute([':id' => $id]);
        $message = 'Jadwal berhasil dipulihkan!';
        $messageType = 'success';
    }
    
    // HARD DELETE
    if ($action === 'delete' && $id) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tiket WHERE id_jadwal = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        
        if ($result['total'] > 0) {
            $message = 'Tidak bisa hapus! Ada tiket yang sudah terjual.';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("DELETE FROM jadwal WHERE id_jadwal = :id");
            $stmt->execute([':id' => $id]);
            $message = 'Jadwal berhasil dihapus permanen!';
            $messageType = 'success';
        }
    }
    
    // POST: Tambah/Edit
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $actionType = $_POST['action_type'] ?? '';
        
        if ($actionType === 'add') {
            $stmt = $conn->prepare("
                INSERT INTO jadwal (id_film, id_studio, tanggal, jam, harga, status)
                VALUES (:id_film, :id_studio, :tanggal, :jam, :harga, 'aktif')
            ");
            $stmt->execute([
                ':id_film' => $_POST['id_film'],
                ':id_studio' => $_POST['id_studio'],
                ':tanggal' => $_POST['tanggal'],
                ':jam' => $_POST['jam'],
                ':harga' => $_POST['harga']
            ]);
            $message = 'Jadwal berhasil ditambahkan!';
            $messageType = 'success';
        }
        
        if ($actionType === 'edit' && isset($_POST['id_jadwal'])) {
            $stmt = $conn->prepare("
                UPDATE jadwal SET
                    id_film = :id_film,
                    id_studio = :id_studio,
                    tanggal = :tanggal,
                    jam = :jam,
                    harga = :harga
                WHERE id_jadwal = :id
            ");
            $stmt->execute([
                ':id' => $_POST['id_jadwal'],
                ':id_film' => $_POST['id_film'],
                ':id_studio' => $_POST['id_studio'],
                ':tanggal' => $_POST['tanggal'],
                ':jam' => $_POST['jam'],
                ':harga' => $_POST['harga']
            ]);
            $message = 'Jadwal berhasil diupdate!';
            $messageType = 'success';
        }
    }
    
    // JSON untuk edit modal
    if ($action === 'get_json' && $id) {
        $stmt = $conn->prepare("SELECT * FROM jadwal WHERE id_jadwal = :id");
        $stmt->execute([':id' => $id]);
        header('Content-Type: application/json');
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        exit;
    }
    
    // Fetch data
    $filter = $_GET['filter'] ?? 'aktif';
    
    if ($filter === 'all') {
        $stmt = $conn->prepare("
            SELECT j.*, f.judul as film_judul, s.nama_studio,
                   COUNT(t.id_tiket) as total_tiket
            FROM jadwal j
            INNER JOIN film f ON f.id_film = j.id_film
            INNER JOIN studio s ON s.id_studio = j.id_studio
            LEFT JOIN tiket t ON t.id_jadwal = j.id_jadwal
            GROUP BY j.id_jadwal
            ORDER BY j.tanggal DESC, j.jam DESC
        ");
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("
            SELECT j.*, f.judul as film_judul, s.nama_studio,
                   COUNT(t.id_tiket) as total_tiket
            FROM jadwal j
            INNER JOIN film f ON f.id_film = j.id_film
            INNER JOIN studio s ON s.id_studio = j.id_studio
            LEFT JOIN tiket t ON t.id_jadwal = j.id_jadwal
            WHERE j.status = :status
            GROUP BY j.id_jadwal
            ORDER BY j.tanggal DESC, j.jam DESC
        ");
        $stmt->execute([':status' => $filter]);
    }
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $films = $conn->query("SELECT id_film, judul FROM film WHERE status_tayang = 1 ORDER BY judul")->fetchAll(PDO::FETCH_ASSOC);
    $studios = $conn->query("SELECT id_studio, nama_studio FROM studio ORDER BY nama_studio")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $message = 'Error: ' . $e->getMessage();
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jadwal - CinemaKu Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- GANTI path ini sesuai lokasi CSS kamu -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-page">
    <!-- NAVBAR ADMIN -->
    <nav class="navbar admin-navbar">
        <div class="container">
            <a href="index.php" class="logo">
                <i class="fas fa-ticket-alt"></i> CinemaTicket
                <span class="admin-badge">ADMIN</span>
            </a>
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_films.php"><i class="fas fa-film"></i> Film</a></li>
                <li><a href="manage_schedules.php" class="active"><i class="fas fa-calendar"></i> Jadwal</a></li>
                <li><a href="manage_users.php"><i class="fas fa-users"></i> User</a></li>
                <li><a href="manage_transactions.php"><i class="fas fa-ticket-alt"></i> Transaksi</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-line"></i> Laporan</a></li>
                <li><a href="logs.php"><i class="fas fa-history"></i> Log</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <main>
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h2><i class="fas fa-calendar-alt"></i> Kelola Jadwal</h2>
                <button class="btn" onclick="openModal('addModal')">
                    <i class="fas fa-plus"></i> Tambah Jadwal
                </button>
            </div>

            <!-- Alert -->
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType === 'warning' ? 'error' : $messageType ?>">
                    <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'times-circle') ?>"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Filter Tabs -->
            <div class="glass-card">
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem;">
                    <a href="?filter=aktif" class="btn <?= $filter === 'aktif' ? '' : 'btn-secondary' ?>">
                        <i class="fas fa-check-circle"></i> Aktif
                    </a>
                    <a href="?filter=dibatalkan" class="btn <?= $filter === 'dibatalkan' ? '' : 'btn-secondary' ?>">
                        <i class="fas fa-times-circle"></i> Dibatalkan
                    </a>
                    <a href="?filter=selesai" class="btn <?= $filter === 'selesai' ? '' : 'btn-secondary' ?>">
                        <i class="fas fa-flag-checkered"></i> Selesai
                    </a>
                    <a href="?filter=all" class="btn <?= $filter === 'all' ? '' : 'btn-secondary' ?>">
                        <i class="fas fa-list"></i> Semua
                    </a>
                </div>

                <!-- Table -->
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Film</th>
                            <th>Studio</th>
                            <th>Tanggal</th>
                            <th>Jam</th>
                            <th>Harga</th>
                            <th>Tiket Terjual</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($schedules)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                                    <i class="fas fa-inbox" style="font-size: 3rem; opacity: 0.5; display: block; margin-bottom: 1rem;"></i>
                                    Tidak ada data jadwal
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($schedules as $s): ?>
                                <tr>
                                    <td>#<?= $s['id_jadwal'] ?></td>
                                    <td><?= htmlspecialchars($s['film_judul']) ?></td>
                                    <td><?= htmlspecialchars($s['nama_studio']) ?></td>
                                    <td><?= date('d M Y', strtotime($s['tanggal'])) ?></td>
                                    <td><?= substr($s['jam'], 0, 5) ?> WIB</td>
                                    <td>Rp <?= number_format($s['harga'], 0, ',', '.') ?></td>
                                    <td>
                                        <?php if ($s['total_tiket'] > 0): ?>
                                            <span style="color: var(--primary); font-weight: 600;">
                                                <i class="fas fa-ticket-alt"></i> <?= $s['total_tiket'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: var(--text-secondary);">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($s['status'] === 'aktif'): ?>
                                            <span class="badge badge-success">Aktif</span>
                                        <?php elseif ($s['status'] === 'dibatalkan'): ?>
                                            <span class="badge badge-danger">Dibatalkan</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning"><?= ucfirst($s['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($s['status'] === 'aktif'): ?>
                                                <button onclick="openEdit(<?= $s['id_jadwal'] ?>)" class="btn-action btn-edit" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?action=cancel&id=<?= $s['id_jadwal'] ?>" 
                                                   onclick="return confirm('Batalkan jadwal ini? Tiket yang sudah terjual akan tetap tersimpan.')"
                                                   class="btn-action btn-warning" title="Batalkan">
                                                    <i class="fas fa-ban"></i>
                                                </a>
                                            <?php elseif ($s['status'] === 'dibatalkan'): ?>
                                                <a href="?action=restore&id=<?= $s['id_jadwal'] ?>" 
                                                   onclick="return confirm('Pulihkan jadwal ini?')"
                                                   class="btn-action btn-edit" title="Pulihkan" style="background: rgba(70, 211, 105, 0.2); color: var(--success);">
                                                    <i class="fas fa-undo"></i>
                                                </a>
                                                <?php if ($s['total_tiket'] == 0): ?>
                                                    <a href="?action=delete&id=<?= $s['id_jadwal'] ?>" 
                                                       onclick="return confirm('HAPUS PERMANEN? Tindakan ini tidak bisa dibatalkan!')"
                                                       class="btn-action btn-delete" title="Hapus Permanen">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- MODAL TAMBAH -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Tambah Jadwal</h3>
                <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action_type" value="add">
                
                <div class="form-group">
                    <label><i class="fas fa-film"></i> Film <span class="required">*</span></label>
                    <select name="id_film" class="form-control" required>
                        <option value="">-- Pilih Film --</option>
                        <?php foreach ($films as $f): ?>
                            <option value="<?= $f['id_film'] ?>"><?= htmlspecialchars($f['judul']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-video"></i> Studio <span class="required">*</span></label>
                    <select name="id_studio" class="form-control" required>
                        <option value="">-- Pilih Studio --</option>
                        <?php foreach ($studios as $s): ?>
                            <option value="<?= $s['id_studio'] ?>"><?= htmlspecialchars($s['nama_studio']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Tanggal <span class="required">*</span></label>
                        <input type="date" name="tanggal" class="form-control" 
                               value="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                               min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Jam Tayang <span class="required">*</span></label>
                        <input type="time" name="jam" class="form-control" value="13:00" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-money-bill-wave"></i> Harga Tiket (Rp) <span class="required">*</span></label>
                    <input type="number" name="harga" class="form-control" 
                           value="50000" min="0" step="1000" required>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Jadwal</h3>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action_type" value="edit">
                <input type="hidden" name="id_jadwal" id="edit_id_jadwal">
                
                <div class="form-group">
                    <label><i class="fas fa-film"></i> Film <span class="required">*</span></label>
                    <select name="id_film" id="edit_id_film" class="form-control" required>
                        <?php foreach ($films as $f): ?>
                            <option value="<?= $f['id_film'] ?>"><?= htmlspecialchars($f['judul']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-video"></i> Studio <span class="required">*</span></label>
                    <select name="id_studio" id="edit_id_studio" class="form-control" required>
                        <?php foreach ($studios as $s): ?>
                            <option value="<?= $s['id_studio'] ?>"><?= htmlspecialchars($s['nama_studio']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Tanggal <span class="required">*</span></label>
                        <input type="date" name="tanggal" id="edit_tanggal" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Jam Tayang <span class="required">*</span></label>
                        <input type="time" name="jam" id="edit_jam" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-money-bill-wave"></i> Harga Tiket (Rp) <span class="required">*</span></label>
                    <input type="number" name="harga" id="edit_harga" class="form-control" 
                           min="0" step="1000" required>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
        }
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }
        function openEdit(id) {
            fetch('?action=get_json&id=' + id)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('edit_id_jadwal').value = data.id_jadwal;
                    document.getElementById('edit_id_film').value = data.id_film;
                    document.getElementById('edit_id_studio').value = data.id_studio;
                    document.getElementById('edit_tanggal').value = data.tanggal;
                    document.getElementById('edit_jam').value = data.jam.substring(0, 5);
                    document.getElementById('edit_harga').value = data.harga;
                    openModal('editModal');
                })
                .catch(err => {
                    alert('Gagal memuat data: ' + err);
                });
        }
        function toggleMobileMenu() {
            document.getElementById('navMenu').classList.toggle('active');
        }
        // Close modal when click outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>