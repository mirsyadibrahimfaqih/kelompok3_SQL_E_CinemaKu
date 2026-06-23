<?php
require_once 'includes/header.php';

// Ambil film-film unggulan (status_tayang = 1 + punya jadwal aktif)
$query = "
    SELECT DISTINCT f.* 
    FROM film f
    INNER JOIN jadwal j ON j.id_film = f.id_film
    WHERE f.status_tayang = 1 
      AND j.status = 'aktif'
      AND j.tanggal >= CURDATE()
    ORDER BY f.created_at DESC
    LIMIT 6
";
$stmt = $conn->prepare($query);
$stmt->execute();
$featuredFilms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function
function getPosterUrlIndex($poster) {
    if (empty($poster)) {
        return null;
    }
    if (strpos($poster, 'http://') === 0 || strpos($poster, 'https://') === 0) {
        return $poster;
    }
    return '/ticketing_bioskop/' . ltrim($poster, '/');
}
?>

<!-- Hero Section -->
<section class="hero" style="text-align: center; padding: 4rem 2rem; background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%); border-radius: 16px; margin-bottom: 3rem;">
    <h1 style="font-size: 2.5rem; margin-bottom: 1rem;">
        <i class="fas fa-film" style="color: var(--primary);"></i> 
        Selamat Datang di CinemaKu
    </h1>
    <p style="color: var(--text-secondary); font-size: 1.1rem; max-width: 600px; margin: 0 auto;">
        Nikmati pengalaman menonton terbaik dengan pemesanan tiket online yang mudah dan cepat!
    </p>
    <a href="films.php" class="btn" style="margin-top: 2rem; padding: 1rem 2rem; font-size: 1.1rem;">
        <i class="fas fa-list"></i> Lihat Semua Film
    </a>
</section>

<!-- Featured Films -->
<div class="page-header">
    <h2><i class="fas fa-star"></i> Film Sedang Tayang</h2>
    <p style="color: var(--text-secondary); margin-top: 0.5rem;">Pilihan film terbaik minggu ini</p>
</div>

<?php if (empty($featuredFilms)): ?>
    <div style="text-align: center; padding: 4rem 2rem; background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-color);">
        <i class="fas fa-calendar-times" style="font-size: 5rem; color: var(--text-secondary); opacity: 0.5;"></i>
        <h3 style="margin: 1.5rem 0 1rem 0;">Belum Ada Film Tayang</h3>
        <p style="color: var(--text-secondary);">Cek kembali nanti untuk film-film terbaru!</p>
    </div>
<?php else: ?>
    <div class="film-grid">
        <?php foreach ($featuredFilms as $film): 
            $posterUrl = getPosterUrlIndex($film['poster']);
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
                    <a href="film_detail.php?id=<?php echo $film['id_film']; ?>" class="btn" style="width: 100%; margin-top: 1rem; display: block; text-align: center;">
                        <i class="fas fa-ticket-alt"></i> Pesan Tiket
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Lihat Semua -->
    <div style="text-align: center; margin-top: 3rem;">
        <a href="films.php" class="btn btn-outline" style="padding: 1rem 2.5rem;">
            <i class="fas fa-arrow-right"></i> Lihat Semua Film
        </a>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>