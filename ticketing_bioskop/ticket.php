<?php 
require_once 'includes/header.php';
requireLogin();

$id_pemesanan = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Query Relasi Lengkap untuk E-Ticket (selalu ambil data terbaru)
$query = "SELECT p.id_pemesanan, p.tanggal_pesan, p.total_harga, p.kode_booking,
                 pb.metode, pb.status_bayar,
                 f.judul, f.genre,
                 j.tanggal, j.jam, 
                 s.nama_studio,
                 GROUP_CONCAT(k.nomor_kursi ORDER BY k.nomor_kursi ASC) as nomor_kursi
          FROM pemesanan p
          JOIN pembayaran pb ON p.id_pemesanan = pb.id_pemesanan
          JOIN tiket t ON p.id_pemesanan = t.id_pemesanan
          JOIN jadwal j ON t.id_jadwal = j.id_jadwal
          JOIN film f ON j.id_film = f.id_film
          JOIN studio s ON j.id_studio = s.id_studio
          JOIN kursi k ON t.id_kursi = k.id_kursi
          WHERE p.id_pemesanan = :id_pemesanan AND p.id_pengguna = :id_pengguna
          GROUP BY p.id_pemesanan";

$stmt = $conn->prepare($query);
$stmt->bindParam(':id_pemesanan', $id_pemesanan);
$stmt->bindParam(':id_pengguna', $_SESSION['user_id']);
$stmt->execute();
$tiket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tiket) {
    setFlashMessage('error', 'Tiket tidak ditemukan!');
    header('Location: my_bookings.php');
    exit();
}
?>

<div class="ticket-container" style="max-width: 500px; margin: 2rem auto; background: white; color: black; border-radius: 10px; overflow: hidden;">
    <div style="background: var(--primary); color: white; padding: 1.5rem; text-align: center;">
        <h2><i class="fas fa-ticket-alt"></i> E-TICKET</h2>
        <p>CinemaKu</p>
    </div>
    <div style="padding: 2rem;">
        <h1 style="text-align: center; margin-bottom: 1rem;"><?php echo sanitize($tiket['judul']); ?></h1>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
            <div><strong>Tanggal:</strong><br><?php echo formatTanggal($tiket['tanggal']); ?></div>
            <div><strong>Jam:</strong><br><?php echo formatJam($tiket['jam']); ?></div>
            <div><strong>Studio:</strong><br><?php echo sanitize($tiket['nama_studio']); ?></div>
            <div><strong>Kursi:</strong><br><?php echo sanitize($tiket['nomor_kursi']); ?></div>
        </div>
        <div style="text-align: center; border-top: 2px dashed #ccc; padding-top: 1rem;">
            <p style="font-size: 0.8rem; color: #666;">Order ID: #<?php echo str_pad($tiket['id_pemesanan'], 6, '0', STR_PAD_LEFT); ?></p>
            <p><strong>Status: <?php echo sanitize($tiket['status_bayar']); ?></strong></p>
        </div>
    </div>
</div>

<div style="text-align: center; margin-bottom: 2rem;">
    <a href="my_bookings.php" class="btn btn-secondary">Kembali ke Pesanan Saya</a>
</div>

<?php require_once 'includes/footer.php'; ?>