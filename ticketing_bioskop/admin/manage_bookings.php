<?php 
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

// Query Optimization: Mengambil data lengkap transaksi dalam 1 query (Mencegah N+1 Problem)
$query = "SELECT p.id_pemesanan, p.tanggal_pesan, p.total_harga,
                 u.nama as nama_pembeli, u.email,
                 pb.metode, pb.status_bayar,
                 f.judul as nama_film,
                 j.tanggal, j.jam,
                 s.nama_studio,
                 GROUP_CONCAT(k.nomor_kursi ORDER BY k.nomor_kursi ASC SEPARATOR ', ') as nomor_kursi,
                 COUNT(t.id_tiket) as jumlah_tiket
          FROM pemesanan p
          JOIN pengguna u ON p.id_pengguna = u.id_pengguna
          LEFT JOIN pembayaran pb ON p.id_pemesanan = pb.id_pemesanan
          JOIN tiket t ON p.id_pemesanan = t.id_pemesanan
          JOIN jadwal j ON t.id_jadwal = j.id_jadwal
          JOIN film f ON j.id_film = f.id_film
          JOIN studio s ON j.id_studio = s.id_studio
          JOIN kursi k ON t.id_kursi = k.id_kursi
          GROUP BY p.id_pemesanan
          ORDER BY p.tanggal_pesan DESC";

$stmt = $conn->prepare($query);
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <h2><i class="fas fa-receipt"></i> Data Transaksi & Pemesanan</h2>
</div>

<div style="background: #1a1a1a; border-radius: 10px; overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
        <thead style="background: #0a0a0a;">
            <tr>
                <th style="padding: 1rem; text-align: left;">ID Order</th>
                <th style="padding: 1rem; text-align: left;">Pembeli</th>
                <th style="padding: 1rem; text-align: left;">Film & Jadwal</th>
                <th style="padding: 1rem; text-align: left;">Kursi</th>
                <th style="padding: 1rem; text-align: left;">Total</th>
                <th style="padding: 1rem; text-align: left;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bookings as $b): ?>
                <tr style="border-top: 1px solid #333;">
                    <td style="padding: 1rem;">#<?php echo str_pad($b['id_pemesanan'], 6, '0', STR_PAD_LEFT); ?></td>
                    <td style="padding: 1rem;">
                        <strong><?php echo sanitize($b['nama_pembeli']); ?></strong><br>
                        <small style="color: #999;"><?php echo sanitize($b['email']); ?></small>
                    </td>
                    <td style="padding: 1rem;">
                        <?php echo sanitize($b['nama_film']); ?><br>
                        <small style="color: #999;"><?php echo formatTanggal($b['tanggal']); ?> | <?php echo formatJam($b['jam']); ?></small><br>
                        <small style="color: #999;"><?php echo sanitize($b['nama_studio']); ?></small>
                    </td>
                    <td style="padding: 1rem;">
                        <?php echo sanitize($b['nomor_kursi']); ?>
                        <br><small>(<?php echo $b['jumlah_tiket']; ?> tiket)</small>
                    </td>
                    <td style="padding: 1rem; font-weight: bold; color: var(--primary);">
                        <?php echo formatRupiah($b['total_harga']); ?>
                    </td>
                    <td style="padding: 1rem;">
                        <span style="padding: 0.3rem 0.8rem; border-radius: 5px; background: <?php echo $b['status_bayar'] == 'Lunas' ? 'rgba(70,211,105,0.2)' : 'rgba(255,165,0,0.2)'; ?>; color: <?php echo $b['status_bayar'] == 'Lunas' ? 'var(--success)' : 'var(--warning)'; ?>;">
                            <?php echo sanitize($b['status_bayar']); ?>
                        </span>
                        <br><small style="color: #999;"><?php echo sanitize($b['metode']); ?></small>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>