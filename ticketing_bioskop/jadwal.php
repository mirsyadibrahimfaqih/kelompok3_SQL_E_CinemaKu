<?php
require_once 'includes/header.php';

$id_film = isset($_GET['id_film']) ? (int)$_GET['id_film'] : 0;

if ($id_film <= 0) {
    header('Location: films.php');
    exit;
}

// Ambil detail film
$stmt = $conn->prepare("SELECT * FROM film WHERE id_film = :id AND status_tayang = 1");
$stmt->execute([':id' => $id_film]);
$film = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$film) {
    header('Location: films.php');
    exit;
}

// Ambil jadwal yang AKTIF untuk film ini
$query = "
    SELECT j.*, s.nama_studio, s.kapasitas
    FROM jadwal j
    INNER JOIN studio s ON s.id_studio = j.id_studio
    WHERE j.id_film = :id_film
      AND j.status = 'aktif'
      AND j.tanggal >= CURDATE()
    ORDER BY j.tanggal ASC, j.jam ASC
";
$stmt = $conn->prepare($query);
$stmt->execute([':id_film' => $id_film]);
$jadwalList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group jadwal berdasarkan tanggal
$groupedJadwal = [];
foreach ($jadwalList as $j) {
    $tanggal = $j['tanggal'];
    if (!isset($groupedJadwal[$tanggal])) {
        $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $groupedJadwal[$tanggal] = [
            'tanggal_formatted' => date('d M Y', strtotime($tanggal)),
            'hari' => $dayNames[date('w', strtotime($tanggal))],
            'items' => []
        ];
    }
    $groupedJadwal[$tanggal]['items'][] = $j;
}

// Helper poster
function getPosterUrlJadwal($poster) {
    if (empty($poster)) return null;
    if (strpos($poster, 'http://') === 0 || strpos($poster, 'https://') === 0) return $poster;
    return '/ticketing_bioskop/' . ltrim($poster, '/');
}

$posterUrl = getPosterUrlJadwal($film['poster']);
?>

<!-- Breadcrumb -->
<div style="padding: 1rem 0; color: var(--text-secondary);">
    <a href="index.php" style="color: var(--primary);">
        <i class="fas fa-home"></i> Beranda
    </a>
    <i class="fas fa-chevron-right" style="margin: 0 0.5rem; font-size: 0.8rem;"></i>
    <a href="films.php" style="color: var(--primary);">Film</a>
    <i class="fas fa-chevron-right" style="margin: 0 0.5rem; font-size: 0.8rem;"></i>
    <span><?php echo htmlspecialchars($film['judul']); ?></span>
</div>

<!-- Film Detail Card -->
<div style="background: var(--bg-card); border-radius: 16px; padding: 2rem; border: 1px solid var(--border-color); margin-bottom: 2rem; display: flex; gap: 2rem; flex-wrap: wrap;">
    <div style="flex: 0 0 200px; height: 300px; border-radius: 12px; overflow: hidden; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; font-size: 5rem; color: white;">
        <?php if ($posterUrl): ?>
            <img src="<?php echo htmlspecialchars($posterUrl); ?>" 
                 alt="<?php echo htmlspecialchars($film['judul']); ?>"
                 style="width: 100%; height: 100%; object-fit: cover;"
                 onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\'fas fa-film\'></i>';">
        <?php else: ?>
            <i class="fas fa-film"></i>
        <?php endif; ?>
    </div>
    <div style="flex: 1; min-width: 250px;">
        <h1 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($film['judul']); ?></h1>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem; color: var(--text-secondary);">
            <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($film['genre']); ?></span>
            <span><i class="fas fa-clock"></i> <?php echo $film['durasi_menit']; ?> menit</span>
            <span style="background: var(--primary); color: white; padding: 0.25rem 0.75rem; border-radius: 5px; font-size: 0.85rem; font-weight: bold;">
                <?php echo htmlspecialchars($film['rating_umur']); ?>
            </span>
        </div>
        <?php if (!empty($film['sinopsis'])): ?>
            <p style="color: var(--text-secondary); line-height: 1.7;">
                <?php echo htmlspecialchars($film['sinopsis']); ?>
            </p>
        <?php endif; ?>
    </div>
</div>

<!-- Jadwal Section -->
<div class="page-header">
    <h2><i class="fas fa-calendar-alt"></i> Pilih Jadwal Tayang</h2>
    <p style="color: var(--text-secondary); margin-top: 0.5rem;">Klik jadwal yang kamu inginkan untuk memilih kursi</p>
</div>

<?php if (empty($groupedJadwal)): ?>
    <div style="text-align: center; padding: 4rem 2rem; background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-color);">
        <i class="fas fa-calendar-times" style="font-size: 5rem; color: var(--text-secondary); opacity: 0.5;"></i>
        <h3 style="margin: 1.5rem 0 1rem 0;">Belum Ada Jadwal Tersedia</h3>
        <p style="color: var(--text-secondary); margin-bottom: 2rem;">Jadwal untuk film ini belum tersedia saat ini.</p>
        <a href="films.php" class="btn">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar Film
        </a>
    </div>
<?php else: ?>
    <?php foreach ($groupedJadwal as $tanggal => $group): ?>
        <div style="margin-bottom: 2rem;">
            <h3 style="margin-bottom: 1rem; color: var(--primary);">
                <i class="fas fa-calendar-day"></i> 
                <?php echo $group['hari']; ?>, <?php echo $group['tanggal_formatted']; ?>
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem;">
                <?php foreach ($group['items'] as $jadwal): 
                    // Cek kursi yang sudah terisi untuk jadwal ini
                    $stmtKursi = $conn->prepare("
                        SELECT COUNT(DISTINCT k.id_kursi) as total_kursi
                        FROM kursi k
                        LEFT JOIN tiket t ON t.id_kursi = k.id_kursi AND t.id_jadwal = :id_jadwal
                        WHERE k.id_studio = :id_studio AND t.id_tiket IS NULL
                    ");
                    $stmtKursi->execute([
                        ':id_jadwal' => $jadwal['id_jadwal'],
                        ':id_studio' => $jadwal['id_studio']
                    ]);
                    $kursiTersedia = $stmtKursi->fetch(PDO::FETCH_ASSOC)['total_kursi'];
                    $isFull = $kursiTersedia == 0;
                ?>
                    <?php if ($isFull): ?>
                        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; opacity: 0.5; cursor: not-allowed;">
                            <div style="font-size: 1.5rem; font-weight: bold; margin-bottom: 0.5rem;">
                                <?php echo substr($jadwal['jam'], 0, 5); ?> WIB
                            </div>
                            <div style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.5rem;">
                                <i class="fas fa-video"></i> <?php echo htmlspecialchars($jadwal['nama_studio']); ?>
                            </div>
                            <div style="color: var(--danger, #e94560); font-size: 0.85rem; font-weight: bold;">
                                <i class="fas fa-times-circle"></i> Penuh
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="booking.php?id_jadwal=<?php echo $jadwal['id_jadwal']; ?>" 
                           style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; text-decoration: none; color: inherit; transition: all 0.3s; display: block; cursor: pointer;"
                           onmouseover="this.style.transform='translateY(-3px)'; this.style.borderColor='var(--primary)'; this.style.boxShadow='0 10px 30px rgba(102, 126, 234, 0.2)';"
                           onmouseout="this.style.transform=''; this.style.borderColor='var(--border-color)'; this.style.boxShadow='';">
                            <div style="font-size: 1.5rem; font-weight: bold; margin-bottom: 0.5rem; color: var(--primary);">
                                <?php echo substr($jadwal['jam'], 0, 5); ?> WIB
                            </div>
                            <div style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.5rem;">
                                <i class="fas fa-video"></i> <?php echo htmlspecialchars($jadwal['nama_studio']); ?>
                            </div>
                            <div style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 0.5rem;">
                                <i class="fas fa-chair"></i> <?php echo $kursiTersedia; ?> kursi tersedia
                            </div>
                            <?php if (isset($jadwal['harga'])): ?>
                                <div style="color: var(--accent-gold, #ffd700); font-weight: bold; font-size: 0.9rem;">
                                    Rp <?php echo number_format($jadwal['harga'], 0, ',', '.'); ?>
                                </div>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>