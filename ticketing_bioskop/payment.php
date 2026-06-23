<?php
require_once 'includes/header.php';
requireLogin();

if (!isset($_SESSION['payment_data'])) {
    setFlashMessage('error', 'Data pembayaran tidak ditemukan!');
    header('Location: films.php');
    exit();
}

$payment_data = $_SESSION['payment_data'];
$id_jadwal = $payment_data['id_jadwal'];
$kursi_ids = $payment_data['kursi_ids'];
$total_harga = $payment_data['total_harga'];
$nomor_kursi = $payment_data['nomor_kursi'];
$jumlah_kursi = $payment_data['jumlah_kursi'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Token keamanan tidak valid!');
        header('Location: films.php');
        exit();
    }

    $metode_bayar = sanitize($_POST['metode_bayar']);
    $user_id = $_SESSION['user_id'];
    
    try {
        $conn->beginTransaction();

        // Generate Kode Booking
        $kode_booking = 'CT' . date('YmdHis') . rand(100, 999);

        // Insert pemesanan
        $query_pemesanan = "INSERT INTO pemesanan (id_pengguna, tanggal_pesan, total_harga, kode_booking) 
                            VALUES (:id_pengguna, NOW(), :total_harga, :kode_booking)";
        $stmt_p = $conn->prepare($query_pemesanan);
        $stmt_p->bindParam(':id_pengguna', $user_id);
        $stmt_p->bindParam(':total_harga', $total_harga);
        $stmt_p->bindParam(':kode_booking', $kode_booking);
        $stmt_p->execute();
        
        $id_pemesanan = $conn->lastInsertId();

        // Insert tiket
        $query_tiket = "INSERT INTO tiket (id_pemesanan, id_jadwal, id_kursi, harga) 
                        VALUES (:id_pemesanan, :id_jadwal, :id_kursi, :harga)";
        $stmt_t = $conn->prepare($query_tiket);
        
        $stmt_harga = $conn->prepare("SELECT harga FROM jadwal WHERE id_jadwal = :id_jadwal");
        $stmt_harga->bindParam(':id_jadwal', $id_jadwal);
        $stmt_harga->execute();
        $harga_per_tiket = $stmt_harga->fetchColumn();

        foreach ($kursi_ids as $id_kursi) {
            $stmt_t->bindParam(':id_pemesanan', $id_pemesanan);
            $stmt_t->bindParam(':id_jadwal', $id_jadwal);
            $stmt_t->bindParam(':id_kursi', $id_kursi);
            $stmt_t->bindParam(':harga', $harga_per_tiket);
            $stmt_t->execute();
        }

        // Insert pembayaran
        $query_bayar = "INSERT INTO pembayaran (id_pemesanan, metode, status_bayar, tanggal_bayar) 
                        VALUES (:id_pemesanan, :metode, 'Lunas', NOW())";
        $stmt_b = $conn->prepare($query_bayar);
        $stmt_b->bindParam(':id_pemesanan', $id_pemesanan);
        $stmt_b->bindParam(':metode', $metode_bayar);
        $stmt_b->execute();

        $conn->commit();
        
        // LOG ACTIVITY - PEMBELIAN TIKET
        logAdminActivity('Pembelian Tiket', "User membeli tiket jadwal ID $id_jadwal, kursi: $nomor_kursi, total: " . formatRupiah($total_harga));
        
        unset($_SESSION['payment_data']);

        header('Location: ticket.php?id=' . $id_pemesanan);
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        setFlashMessage('error', 'Transaksi gagal: ' . $e->getMessage());
        header('Location: checkout.php?id_jadwal=' . $id_jadwal . '&kursi=' . implode(',', $kursi_ids));
        exit();
    }
}

$metode_bayar = isset($_POST['metode_bayar']) ? sanitize($_POST['metode_bayar']) : 'QRIS';
$qr_code = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=SIMULASI-' . time();
$va_number = '8808' . str_pad(rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);
?>

<div class="page-header">
    <h2><i class="fas fa-credit-card"></i> Pembayaran</h2>
    <p style="color: var(--text-secondary);">*Ini hanya simulasi untuk tugas. Tidak ada uang asli yang ditransfer.</p>
</div>

<div style="max-width: 600px; margin: 0 auto;">
    <div style="background: var(--bg-card); padding: 2rem; border-radius: 12px; border: 1px solid var(--border-color); margin-bottom: 2rem;">
        <h3 style="margin-bottom: 1.5rem; color: var(--primary);"><i class="fas fa-receipt"></i> Detail Pesanan</h3>
        <p style="margin-bottom: 0.8rem;"><strong>Metode:</strong> <?php echo sanitize($metode_bayar); ?></p>
        <p style="margin-bottom: 0.8rem;"><strong>Jumlah Kursi:</strong> <?php echo $jumlah_kursi; ?></p>
        <p style="margin-bottom: 1.5rem;"><strong>Nomor Kursi:</strong> <?php echo sanitize($nomor_kursi); ?></p>
        <hr style="border-color: var(--border-color); margin: 1.5rem 0;">
        <h2 style="color: var(--primary); text-align: center; margin: 0;">Total: <?php echo formatRupiah($total_harga); ?></h2>
    </div>

    <div style="background: var(--bg-card); padding: 2rem; border-radius: 12px; border: 1px solid var(--border-color); text-align: center;">
        <?php if ($metode_bayar === 'QRIS'): ?>
            <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-qrcode"></i> Scan QR Code</h3>
            <img src="<?php echo $qr_code; ?>" alt="QR Code" style="width: 250px; height: 250px; border: 3px solid var(--primary); border-radius: 12px; padding: 10px; background: white;">
            <p style="margin-top: 1.5rem; color: var(--text-secondary);">Scan QR code di atas dengan e-wallet (Simulasi)</p>
        <?php elseif ($metode_bayar === 'Transfer Bank'): ?>
            <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-university"></i> Transfer Bank</h3>
            <div style="background: rgba(229, 9, 20, 0.1); padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <p style="font-size: 0.9rem; color: var(--text-secondary);">Nomor Virtual Account (Simulasi):</p>
                <p style="font-size: 1.8rem; font-weight: bold; letter-spacing: 2px; color: var(--primary);"><?php echo $va_number; ?></p>
            </div>
        <?php elseif ($metode_bayar === 'E-Wallet'): ?>
            <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-wallet"></i> E-Wallet</h3>
            <div style="background: rgba(70, 211, 105, 0.1); padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <p style="font-size: 0.9rem; color: var(--text-secondary);">Nomor E-Wallet (Simulasi):</p>
                <p style="font-size: 1.8rem; font-weight: bold; color: var(--success);">0812-8888-9999</p>
            </div>
        <?php elseif ($metode_bayar === 'Kartu Kredit'): ?>
            <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-credit-card"></i> Kartu Kredit</h3>
            <div style="background: rgba(0, 168, 255, 0.1); padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <p style="font-size: 0.9rem; color: var(--text-secondary);">Kartu akan diproses secara otomatis (Simulasi)</p>
            </div>
        <?php endif; ?>

        <form method="POST" action="" style="margin-top: 2rem;">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="confirm_payment" value="1">
            <input type="hidden" name="metode_bayar" value="<?php echo sanitize($metode_bayar); ?>">
            
            <button type="submit" class="btn" style="width: 100%; padding: 1.2rem; font-size: 1.1rem;">
                <i class="fas fa-check-circle"></i> Saya Sudah Bayar (Konfirmasi)
            </button>
        </form>
        
        <p style="margin-top: 1rem; font-size: 0.85rem; color: var(--text-secondary);">
            *Klik tombol di atas untuk menyelesaikan simulasi pembayaran.
        </p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>