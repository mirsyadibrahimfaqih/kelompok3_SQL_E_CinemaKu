<?php
require_once 'includes/header.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Query untuk mendapatkan semua pemesanan dengan detail
// Menggunakan JOIN untuk mengambil data terbaru dari jadwal/film (bukan data statis)
$query = "SELECT 
    p.id_pemesanan, 
    p.tanggal_pesan, 
    p.total_harga,
    p.kode_booking,
    pb.status_bayar, 
    pb.metode,
    f.id_film,
    f.judul, 
    f.genre,
    j.id_jadwal,
    j.tanggal, 
    j.jam,
    s.nama_studio,
    GROUP_CONCAT(k.nomor_kursi ORDER BY k.nomor_kursi ASC) as nomor_kursi,
    COUNT(t.id_tiket) as jumlah_tiket
FROM pemesanan p
LEFT JOIN pembayaran pb ON p.id_pemesanan = pb.id_pemesanan
LEFT JOIN tiket t ON p.id_pemesanan = t.id_pemesanan
LEFT JOIN jadwal j ON t.id_jadwal = j.id_jadwal
LEFT JOIN film f ON j.id_film = f.id_film
LEFT JOIN studio s ON j.id_studio = s.id_studio
LEFT JOIN kursi k ON t.id_kursi = k.id_kursi
WHERE p.id_pengguna = :user_id
GROUP BY p.id_pemesanan
ORDER BY 
    CASE 
        WHEN j.tanggal > CURDATE() OR (j.tanggal = CURDATE() AND j.jam > CURTIME()) THEN 0 
        ELSE 1 
    END,
    j.tanggal DESC, 
    j.jam DESC";

$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$all_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pisahkan tiket mendatang dan riwayat
$upcoming_bookings = [];
$past_bookings = [];

foreach ($all_bookings as $booking) {
    $is_past = false;
    
    if (empty($booking['tanggal'])) {
        $is_past = true;
    } else {
        $jadwal_datetime = strtotime($booking['tanggal'] . ' ' . $booking['jam']);
        $now_datetime = time();
        
        if ($jadwal_datetime < $now_datetime) {
            $is_past = true;
        }
    }
    
    if ($is_past) {
        $past_bookings[] = $booking;
    } else {
        $upcoming_bookings[] = $booking;
    }
}
?>

<div class="page-header">
    <h2><i class="fas fa-ticket-alt"></i> Pesanan Saya</h2>
</div>

<!-- TAB NAVIGATION -->
<div style="margin-bottom: 2rem;">
    <div style="display: flex; gap: 1rem; border-bottom: 2px solid var(--border-color);">
        <button onclick="switchTab('upcoming')" id="tab-upcoming" 
                style="padding: 1rem 2rem; background: var(--primary); color: white; border: none; border-radius: 8px 8px 0 0; cursor: pointer; font-weight: 600;">
            <i class="fas fa-calendar-check"></i> Tiket Mendatang (<?php echo count($upcoming_bookings); ?>)
        </button>
        <button onclick="switchTab('history')" id="tab-history" 
                style="padding: 1rem 2rem; background: transparent; color: var(--text-secondary); border: none; cursor: pointer; font-weight: 600;">
            <i class="fas fa-history"></i> Riwayat (<?php echo count($past_bookings); ?>)
        </button>
    </div>
</div>

<!-- TIKET MENDATANG -->
<div id="content-upcoming" style="display: block;">
    <?php if (empty($upcoming_bookings)): ?>
        <div style="text-align: center; padding: 3rem; background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-color);">
            <i class="fas fa-ticket-alt" style="font-size: 4rem; color: var(--text-secondary); opacity: 0.5;"></i>
            <p style="margin-top: 1rem; color: var(--text-secondary);">Belum ada tiket mendatang. Yuk pesan tiket sekarang!</p>
            <a href="films.php" class="btn" style="margin-top: 1rem;">Lihat Film</a>
        </div>
    <?php else: ?>
        <div style="display: grid; gap: 1.5rem;">
            <?php foreach ($upcoming_bookings as $booking): ?>
                <div style="background: var(--bg-card); border-radius: 12px; padding: 1.5rem; border-left: 5px solid var(--success); border: 1px solid var(--border-color);">
                    <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 1rem;">
                        <div style="flex: 1; min-width: 250px;">
                            <h3 style="color: var(--primary); margin-bottom: 0.5rem; font-size: 1.3rem;">
                                <?php echo sanitize($booking['judul'] ?? 'Film tidak tersedia'); ?>
                            </h3>
                            
                            <?php if (!empty($booking['tanggal']) && !empty($booking['jam'])): ?>
                                <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 0.5rem;">
                                    <i class="fas fa-calendar"></i> <?php echo formatTanggal($booking['tanggal']); ?> | 
                                    <i class="fas fa-clock"></i> <?php echo formatJam($booking['jam']); ?>
                                </p>
                                <p style="margin-top: 0.5rem;">
                                    <i class="fas fa-building"></i> <?php echo sanitize($booking['nama_studio'] ?? '-'); ?> | 
                                    <i class="fas fa-chair"></i> <?php echo sanitize($booking['nomor_kursi'] ?? '-'); ?>
                                </p>
                            <?php else: ?>
                                <p style="color: var(--text-secondary);"><i class="fas fa-info-circle"></i> Jadwal tidak tersedia</p>
                            <?php endif; ?>
                            
                            <p style="margin-top: 0.5rem; font-weight: 600;">
                                <?php echo $booking['jumlah_tiket']; ?> Tiket | 
                                Total: <strong style="color: var(--primary);"><?php echo formatRupiah($booking['total_harga']); ?></strong>
                            </p>
                        </div>
                        
                        <div style="text-align: right;">
                            <span style="display: inline-block; padding: 0.5rem 1rem; border-radius: 5px; background: rgba(70, 211, 105, 0.2); color: var(--success); font-weight: bold;">
                                <i class="fas fa-check-circle"></i> <?php echo sanitize($booking['status_bayar'] ?? 'Pending'); ?>
                            </span>
                            <p style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem;">
                                #<?php echo str_pad($booking['id_pemesanan'], 6, '0', STR_PAD_LEFT); ?>
                            </p>
                            <?php if ($booking['status_bayar'] == 'Lunas'): ?>
                                <a href="ticket.php?id=<?php echo $booking['id_pemesanan']; ?>" 
                                   class="btn" 
                                   style="margin-top: 0.5rem; font-size: 0.9rem; padding: 0.5rem 1rem;">
                                    <i class="fas fa-eye"></i> Lihat Tiket
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- RIWAYAT TIKET -->
<div id="content-history" style="display: none;">
    <?php if (empty($past_bookings)): ?>
        <div style="text-align: center; padding: 3rem; background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-color);">
            <i class="fas fa-history" style="font-size: 4rem; color: var(--text-secondary); opacity: 0.5;"></i>
            <p style="margin-top: 1rem; color: var(--text-secondary);">Belum ada riwayat pemesanan.</p>
        </div>
    <?php else: ?>
        <div style="display: grid; gap: 1.5rem;">
            <?php foreach ($past_bookings as $booking): ?>
                <div style="background: var(--bg-card); border-radius: 12px; padding: 1.5rem; border-left: 5px solid var(--secondary); border: 1px solid var(--border-color); opacity: 0.8;">
                    <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 1rem;">
                        <div style="flex: 1; min-width: 250px;">
                            <h3 style="color: var(--text-secondary); margin-bottom: 0.5rem; font-size: 1.3rem;">
                                <?php echo sanitize($booking['judul'] ?? 'Film tidak tersedia'); ?>
                            </h3>
                            
                            <?php if (!empty($booking['tanggal']) && !empty($booking['jam'])): ?>
                                <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 0.5rem;">
                                    <i class="fas fa-calendar"></i> <?php echo formatTanggal($booking['tanggal']); ?> | 
                                    <i class="fas fa-clock"></i> <?php echo formatJam($booking['jam']); ?>
                                </p>
                                <p style="margin-top: 0.5rem;">
                                    <i class="fas fa-building"></i> <?php echo sanitize($booking['nama_studio'] ?? '-'); ?> | 
                                    <i class="fas fa-chair"></i> <?php echo sanitize($booking['nomor_kursi'] ?? '-'); ?>
                                </p>
                            <?php else: ?>
                                <p style="color: var(--text-secondary);"><i class="fas fa-info-circle"></i> Jadwal sudah lewat</p>
                            <?php endif; ?>
                            
                            <p style="margin-top: 0.5rem; font-weight: 600; color: var(--text-secondary);">
                                <?php echo $booking['jumlah_tiket']; ?> Tiket | 
                                Total: <?php echo formatRupiah($booking['total_harga']); ?>
                            </p>
                        </div>
                        
                        <div style="text-align: right;">
                            <span style="display: inline-block; padding: 0.5rem 1rem; border-radius: 5px; background: rgba(153, 153, 153, 0.2); color: var(--text-secondary); font-weight: bold;">
                                <i class="fas fa-check-circle"></i> Selesai
                            </span>
                            <p style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem;">
                                #<?php echo str_pad($booking['id_pemesanan'], 6, '0', STR_PAD_LEFT); ?>
                            </p>
                            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.5rem;">
                                <i class="fas fa-info-circle"></i> Tiket sudah digunakan
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function switchTab(tab) {
    const upcomingContent = document.getElementById('content-upcoming');
    const historyContent = document.getElementById('content-history');
    const upcomingBtn = document.getElementById('tab-upcoming');
    const historyBtn = document.getElementById('tab-history');
    
    if (tab === 'upcoming') {
        upcomingContent.style.display = 'block';
        historyContent.style.display = 'none';
        upcomingBtn.style.background = 'var(--primary)';
        upcomingBtn.style.color = 'white';
        historyBtn.style.background = 'transparent';
        historyBtn.style.color = 'var(--text-secondary)';
    } else {
        upcomingContent.style.display = 'none';
        historyContent.style.display = 'block';
        upcomingBtn.style.background = 'transparent';
        upcomingBtn.style.color = 'var(--text-secondary)';
        historyBtn.style.background = 'var(--primary)';
        historyBtn.style.color = 'white';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>