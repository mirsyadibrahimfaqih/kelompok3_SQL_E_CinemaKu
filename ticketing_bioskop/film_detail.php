<?php
require_once 'includes/header.php';

// Helper function untuk handle URL poster
function getPosterUrl($poster) {
    if (empty($poster)) {
        return null;
    }
    if (strpos($poster, 'http://') === 0 || strpos($poster, 'https://') === 0) {
        return $poster;
    }
    return '/ticketing_bioskop/' . ltrim($poster, '/');
}

$id_film = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Query Film dengan Relasi Jadwal dan Studio (hanya jadwal yang belum lewat)
$query = "SELECT f.*, 
          j.id_jadwal, j.tanggal, j.jam, j.harga,
          s.nama_studio, s.tipe
          FROM film f
          LEFT JOIN jadwal j ON f.id_film = j.id_film 
            AND (j.tanggal > CURDATE() OR (j.tanggal = CURDATE() AND j.jam > CURTIME()))
          LEFT JOIN studio s ON j.id_studio = s.id_studio
          WHERE f.id_film = :id_film AND f.status_tayang = 1
          ORDER BY j.tanggal ASC, j.jam ASC";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id_film', $id_film, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($results)) {
    setFlashMessage('error', 'Film tidak ditemukan!');
    header('Location: films.php');
    exit();
}

$film = $results[0];
$posterUrl = getPosterUrl($film['poster']);

// Group jadwal by tanggal
$grouped_jadwal = [];
foreach ($results as $row) {
    if ($row['id_jadwal']) {
        $grouped_jadwal[$row['tanggal']][] = $row;
    }
}
?>

<div class="film-detail-container" style="margin-top: 2rem;">
    <div style="display: grid; grid-template-columns: 350px 1fr; gap: 3rem; margin-bottom: 3rem;">
        <!-- Poster - Full Height -->
        <div style="border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5); background: #000;">
            <?php if ($posterUrl): ?>
                <img src="<?php echo htmlspecialchars($posterUrl); ?>" 
                     alt="<?php echo htmlspecialchars($film['judul']); ?>" 
                     style="width: 100%; height: 100%; min-height: 520px; object-fit: cover; object-position: center 20%; display: block;"
                     onerror="this.style.display='none'; this.parentElement.style.background='linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; this.parentElement.innerHTML='<div style=\'width:100%; height:520px; display: flex; align-items: center; justify-content: center; font-size: 6rem; color: white;\'><i class=\'fas fa-film\'></i></div>';">
            <?php else: ?>
                <div style="width: 100%; height: 520px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; font-size: 6rem; color: white;">
                    <i class="fas fa-film"></i>
                </div>
            <?php endif; ?>
        </div>

        <!-- Film Info -->
        <div>
            <h1 style="font-size: 2.5rem; margin-bottom: 1rem;"><?php echo htmlspecialchars($film['judul']); ?></h1>
            
            <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; margin-bottom: 2rem; color: var(--text-secondary);">
                <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($film['genre']); ?></span>
                <span><i class="fas fa-clock"></i> <?php echo $film['durasi_menit']; ?> Menit</span>
                <span><i class="fas fa-shield-alt"></i> Rating: <?php echo htmlspecialchars($film['rating_umur']); ?></span>
            </div>

            <?php if ($film['sinopsis']): ?>
                <div style="margin-bottom: 2rem;">
                    <h3 style="margin-bottom: 1rem; color: var(--primary);"><i class="fas fa-info-circle"></i> Sinopsis</h3>
                    <p style="line-height: 1.8; color: var(--text-secondary);"><?php echo nl2br(htmlspecialchars($film['sinopsis'])); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($grouped_jadwal)): ?>
                <div>
                    <h3 style="margin-bottom: 1.5rem; color: var(--primary);">
                        <i class="fas fa-calendar-alt"></i> Pilih Jadwal Tayang
                    </h3>
                    
                    <?php foreach ($grouped_jadwal as $tanggal => $jadwal_list): ?>
                        <div style="margin-bottom: 2rem;">
                            <h4 style="color: var(--primary); margin-bottom: 1rem; font-size: 1.2rem;">
                                <i class="fas fa-calendar-day"></i> <?php echo formatTanggal($tanggal); ?>
                            </h4>
                            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                                <?php foreach ($jadwal_list as $j): ?>
                                    <a href="booking.php?id_jadwal=<?php echo $j['id_jadwal']; ?>" 
                                       class="btn" 
                                       style="min-width: 150px; text-align: center; padding: 1.2rem 1.5rem;">
                                        <div style="font-size: 1.2rem; font-weight: bold; margin-bottom: 0.3rem;">
                                            <?php echo formatJam($j['jam']); ?>
                                        </div>
                                        <div style="font-size: 0.9rem; opacity: 0.9;">
                                            <?php echo htmlspecialchars($j['nama_studio']); ?>
                                        </div>
                                        <div style="font-size: 0.85rem; margin-top: 0.5rem; color: var(--success); font-weight: bold;">
                                            <?php echo formatRupiah($j['harga']); ?>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="background: var(--bg-card); padding: 2rem; border-radius: 12px; text-align: center; border: 1px solid var(--border-color);">
                    <i class="fas fa-calendar-times" style="font-size: 3rem; color: var(--text-secondary); opacity: 0.5; margin-bottom: 1rem;"></i>
                    <p style="color: var(--text-secondary);">Belum ada jadwal tayang untuk film ini.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
@media (max-width: 768px) {
    .film-detail-container > div {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>