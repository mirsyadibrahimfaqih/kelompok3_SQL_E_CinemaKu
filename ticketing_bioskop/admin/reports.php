<?php 
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

// 1. Laporan Pendapatan per Film (Query Optimization: Aggregation)
$query_film = "SELECT f.judul, COUNT(t.id_tiket) as total_terjual, COALESCE(SUM(t.harga), 0) as total_pendapatan
               FROM film f
               LEFT JOIN jadwal j ON f.id_film = j.id_film
               LEFT JOIN tiket t ON j.id_jadwal = t.id_jadwal
               GROUP BY f.id_film, f.judul
               ORDER BY total_pendapatan DESC";
$report_film = $conn->query($query_film)->fetchAll(PDO::FETCH_ASSOC);

// 2. Laporan Okupansi Studio
$query_studio = "SELECT s.nama_studio, COUNT(t.id_tiket) as total_tiket_terjual, 
                        COALESCE(SUM(t.harga), 0) as pendapatan
                 FROM studio s
                 LEFT JOIN jadwal j ON s.id_studio = j.id_studio
                 LEFT JOIN tiket t ON j.id_jadwal = t.id_jadwal
                 GROUP BY s.id_studio, s.nama_studio";
$report_studio = $conn->query($query_studio)->fetchAll(PDO::FETCH_ASSOC);

// 3. Total Keseluruhan
$total_rev = $conn->query("SELECT COALESCE(SUM(total_harga), 0) FROM pemesanan")->fetchColumn();
$total_tix = $conn->query("SELECT COUNT(*) FROM tiket")->fetchColumn();
?>

<div class="page-header">
    <h2><i class="fas fa-chart-line"></i> Laporan & Analitik</h2>
</div>

<!-- Summary Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <div style="background: #1a1a1a; padding: 1.5rem; border-radius: 10px; border-left: 5px solid var(--success);">
        <h4 style="color: #999;">Total Pendapatan</h4>
        <h2 style="color: var(--success);"><?php echo formatRupiah($total_rev); ?></h2>
    </div>
    <div style="background: #1a1a1a; padding: 1.5rem; border-radius: 10px; border-left: 5px solid var(--primary);">
        <h4 style="color: #999;">Total Tiket Terjual</h4>
        <h2 style="color: var(--primary);"><?php echo number_format($total_tix); ?> Tiket</h2>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem;">
    
    <!-- Revenue per Film -->
    <div style="background: #1a1a1a; padding: 1.5rem; border-radius: 10px;">
        <h3 style="margin-bottom: 1rem; border-bottom: 1px solid #333; padding-bottom: 0.5rem;">
            <i class="fas fa-film"></i> Pendapatan per Film
        </h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; color: #999;">
                    <th style="padding: 0.5rem;">Judul Film</th>
                    <th style="padding: 0.5rem;">Terjual</th>
                    <th style="padding: 0.5rem; text-align: right;">Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report_film as $rf): ?>
                    <tr style="border-top: 1px solid #333;">
                        <td style="padding: 0.5rem;"><?php echo sanitize($rf['judul']); ?></td>
                        <td style="padding: 0.5rem;"><?php echo $rf['total_terjual']; ?></td>
                        <td style="padding: 0.5rem; text-align: right; color: var(--success); font-weight: bold;">
                            <?php echo formatRupiah($rf['total_pendapatan']); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Revenue per Studio -->
    <div style="background: #1a1a1a; padding: 1.5rem; border-radius: 10px;">
        <h3 style="margin-bottom: 1rem; border-bottom: 1px solid #333; padding-bottom: 0.5rem;">
            <i class="fas fa-building"></i> Performa Studio
        </h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; color: #999;">
                    <th style="padding: 0.5rem;">Nama Studio</th>
                    <th style="padding: 0.5rem;">Tiket Terjual</th>
                    <th style="padding: 0.5rem; text-align: right;">Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report_studio as $rs): ?>
                    <tr style="border-top: 1px solid #333;">
                        <td style="padding: 0.5rem;"><?php echo sanitize($rs['nama_studio']); ?></td>
                        <td style="padding: 0.5rem;"><?php echo $rs['total_tiket_terjual']; ?></td>
                        <td style="padding: 0.5rem; text-align: right; color: var(--primary); font-weight: bold;">
                            <?php echo formatRupiah($rs['pendapatan']); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>