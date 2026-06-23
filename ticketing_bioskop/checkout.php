<?php
require_once 'includes/header.php';
requireLogin();
restrictAdminFromBooking(); // ← TAMBAHKAN INI

// Ambil data dari URL GET
$id_jadwal = isset($_GET['id_jadwal']) ? (int)$_GET['id_jadwal'] : 0;
$kursi_ids_param = isset($_GET['kursi']) ? $_GET['kursi'] : '';

if (empty($kursi_ids_param) || !$id_jadwal) {
    setFlashMessage('error', 'Data pemesanan tidak lengkap!');
    header('Location: films.php');
    exit();
}

// Ubah string kursi jadi array
$ids_array = explode(',', $kursi_ids_param);

// Ambil data jadwal dan harga
$query = "SELECT j.harga, f.judul, j.tanggal, j.jam, s.nama_studio 
          FROM jadwal j 
          JOIN film f ON j.id_film = f.id_film 
          JOIN studio s ON j.id_studio = s.id_studio 
          WHERE j.id_jadwal = :id_jadwal";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id_jadwal', $id_jadwal);
$stmt->execute();
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    setFlashMessage('error', 'Jadwal tidak ditemukan!');
    header('Location: films.php');
    exit();
}

// Hitung total harga
$harga_per_tiket = $data['harga'];
$total_harga = $harga_per_tiket * count($ids_array);

// Ambil detail nomor kursi dari database
$kursi_query = "SELECT nomor_kursi FROM kursi WHERE id_kursi IN (" . str_repeat('?,', count($ids_array) - 1) . "?)";
$kursi_stmt = $conn->prepare($kursi_query);
$kursi_stmt->execute($ids_array);
$kursi_rows = $kursi_stmt->fetchAll(PDO::FETCH_COLUMN);
$nomor_kursi_str = implode(', ', $kursi_rows);

// Simpan data ke session untuk payment.php
$_SESSION['payment_data'] = [
    'id_jadwal' => $id_jadwal,
    'kursi_ids' => $ids_array,
    'total_harga' => $total_harga,
    'nomor_kursi' => $nomor_kursi_str,
    'jumlah_kursi' => count($ids_array)
];

$csrf_token = generateCSRFToken();
?>

<div class="page-header">
    <h2><i class="fas fa-receipt"></i> Konfirmasi Pesanan</h2>
</div>

<div style="max-width: 600px; margin: 0 auto;">
    <!-- Booking Summary -->
    <div style="background: var(--bg-card); padding: 2rem; border-radius: 12px; border: 1px solid var(--border-color); margin-bottom: 2rem;">
        <h3 style="margin-bottom: 1.5rem; color: var(--primary);"><i class="fas fa-info-circle"></i> Detail Pesanan</h3>
        <p style="margin-bottom: 0.8rem;"><strong>Film:</strong> <?php echo sanitize($data['judul']); ?></p>
        <p style="margin-bottom: 0.8rem;"><strong>Tanggal:</strong> <?php echo formatTanggal($data['tanggal']); ?> | <?php echo formatJam($data['jam']); ?></p>
        <p style="margin-bottom: 0.8rem;"><strong>Studio:</strong> <?php echo sanitize($data['nama_studio']); ?></p>
        <p style="margin-bottom: 0.8rem;"><strong>Jumlah Kursi:</strong> <?php echo count($ids_array); ?></p>
        <p style="margin-bottom: 1.5rem;"><strong>Nomor Kursi:</strong> <?php echo sanitize($nomor_kursi_str); ?></p>
        
        <hr style="border-color: var(--border-color); margin: 1.5rem 0;">
        
        <div style="text-align: center;">
            <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">Total Bayar</p>
            <h2 style="color: var(--primary); font-size: 2rem; margin: 0;"><?php echo formatRupiah($total_harga); ?></h2>
        </div>
    </div>

    <!-- Payment Method Form -->
    <div style="background: var(--bg-card); padding: 2rem; border-radius: 12px; border: 1px solid var(--border-color);">
        <h3 style="margin-bottom: 1.5rem; color: var(--primary);"><i class="fas fa-credit-card"></i> Pilih Metode Pembayaran</h3>
        
        <form method="POST" action="payment.php">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label for="metode_bayar">Metode Pembayaran</label>
                <select name="metode_bayar" id="metode_bayar" class="form-control" required>
                    <option value="QRIS">QRIS</option>
                    <option value="Transfer Bank">Transfer Bank (Virtual Account)</option>
                    <option value="E-Wallet">E-Wallet (GoPay/OVO/Dana)</option>
                    <option value="Kartu Kredit">Kartu Kredit/Debit</option>
                </select>
            </div>
            
            <button type="submit" class="btn" style="width: 100%; padding: 1.2rem; font-size: 1.1rem; margin-top: 1rem;">
                <i class="fas fa-lock"></i> Bayar Sekarang
            </button>
            
            <p style="text-align: center; margin-top: 1rem; color: var(--text-secondary); font-size: 0.9rem;">
                <i class="fas fa-shield-alt"></i> Pembayaran Anda aman dan terenkripsi
            </p>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>