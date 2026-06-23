<?php
require_once 'includes/header.php';

// Query: Ambil film yang sedang tayang (status_tayang = 1) 
// DAN punya jadwal aktif di masa depan
$query = "
    SELECT DISTINCT f.* 
    FROM film f
    INNER JOIN jadwal j ON j.id_film = f.id_film
    WHERE f.status_tayang = 1 
      AND j.status = 'aktif'
      AND j.tanggal >= CURDATE()
    ORDER BY f.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->execute();
$films = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
?>

<div class="page-header">
    <h2><i class="fas fa-film"></i> Daftar Semua Film</h2>
    <p style="color: var(--text-secondary); margin-top: 0.5rem;">Temukan film favoritmu dan pesan tiketnya sekarang!</p>
</div>

<?php if (empty($films)): ?>
    <div style="text-align: center; padding: 4rem 2rem; background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-color);">
        <i class="fas fa-film" style="font-size: 5rem; color: var(--text-secondary); opacity: 0.5;"></i>
        <h3 style="margin: 1.5rem 0 1rem 0;">Belum Ada Film yang Tayang</h3>
        <p style="color: var(--text-secondary); margin-bottom: 2rem;">Film akan segera hadir. Stay tuned!</p>
        <a href="index.php" class="btn">Kembali ke Beranda</a>
    </div>
<?php else: ?>
    <div class="film-grid">
        <?php foreach ($films as $film): 
            $posterUrl = getPosterUrl($film['poster']);
        ?>
            <div class="film-card" onclick="window.location='film_detail.php?id=<?php echo $film['id_film']; ?>'">
                <div class="film-poster" style="background: <?php echo $posterUrl ? 'none' : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; ?>; display: flex; align-items: center; justify-content: center; font-size: 5rem; color: white; overflow: hidden; position: relative;">
                    <?php if ($posterUrl): ?>
                        <img src="<?php echo htmlspecialchars($posterUrl); ?>" 
                             alt="<?php echo htmlspecialchars($film['judul']); ?>" 
                             style="width: 100%; height: 100%; object-fit: cover; object-position: center 20%;"
                             onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\'fas fa-film\'></i>';">
                    <?php else: ?>
                        <i class="fas fa-film"></i>
                    <?php endif; ?>
                    <div style="position: absolute; top: 10px; right: 10px; background: var(--primary); color: white; padding: 0.4rem 0.8rem; border-radius: 5px; font-weight: bold; font-size: 0.9rem;">
                        <?php echo htmlspecialchars($film['rating_umur']); ?>
                    </div>
                </div>
                <div class="film-info">
                    <h3 class="film-title"><?php echo htmlspecialchars($film['judul']); ?></h3>
                    <div class="film-meta">
                        <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($film['genre']); ?></span>
                        <span><i class="fas fa-clock"></i> <?php echo $film['durasi_menit']; ?> menit</span>
                    </div>
                    <?php if ($film['sinopsis']): ?>
                        <p style="margin-top: 1rem; color: var(--text-secondary); font-size: 0.9rem; line-height: 1.6;">
                            <?php echo htmlspecialchars(substr($film['sinopsis'], 0, 100)); ?><?php echo strlen($film['sinopsis']) > 100 ? '...' : ''; ?>
                        </p>
                    <?php endif; ?>
                    <a href="film_detail.php?id=<?php echo $film['id_film']; ?>" class="btn" style="width: 100%; margin-top: 1rem; display: block; text-align: center;">
                        <i class="fas fa-ticket-alt"></i> Pesan Tiket
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>