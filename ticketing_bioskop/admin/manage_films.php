<?php
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

// Upload Poster Function
function uploadPoster($file) {
    $target_dir = __DIR__ . "/../uploads/posters/";
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $allowed_extensions = array("jpg", "jpeg", "png", "gif", "webp");
    
    if ($file["error"] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error code: ' . $file["error"]];
    }
    
    if (!in_array($file_extension, $allowed_extensions)) {
        return ['success' => false, 'message' => 'Hanya file JPG, JPEG, PNG, GIF, dan WEBP yang diizinkan!'];
    }
    
    if ($file["size"] > 2000000) {
        return ['success' => false, 'message' => 'Ukuran file terlalu besar! Maksimal 2MB.'];
    }
    
    $new_filename = uniqid('poster_') . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'filename' => 'uploads/posters/' . $new_filename];
    } else {
        return ['success' => false, 'message' => 'Gagal mengupload file!'];
    }
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Token tidak valid!');
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add' || $action === 'edit') {
            $judul = sanitize($_POST['judul']);
            $genre = sanitize($_POST['genre']);
            $durasi = (int)$_POST['durasi_menit'];
            $rating = sanitize($_POST['rating_umur']);
            $sinopsis = sanitize($_POST['sinopsis']);
            $status_tayang = isset($_POST['status_tayang']) ? 1 : 0;
            
            $poster_path = NULL;
            if (isset($_FILES['poster']) && $_FILES['poster']['error'] == UPLOAD_ERR_OK) {
                $upload_result = uploadPoster($_FILES['poster']);
                if ($upload_result['success']) {
                    $poster_path = $upload_result['filename'];
                } else {
                    setFlashMessage('error', $upload_result['message']);
                }
            }
            
            if ($action === 'add') {
                $query = "INSERT INTO film (judul, genre, durasi_menit, rating_umur, sinopsis, poster, status_tayang) 
                          VALUES (:judul, :genre, :durasi, :rating, :sinopsis, :poster, :status_tayang)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':judul', $judul);
                $stmt->bindParam(':genre', $genre);
                $stmt->bindParam(':durasi', $durasi);
                $stmt->bindParam(':rating', $rating);
                $stmt->bindParam(':sinopsis', $sinopsis);
                $stmt->bindParam(':poster', $poster_path);
                $stmt->bindParam(':status_tayang', $status_tayang);
                
                if ($stmt->execute()) {
                    setFlashMessage('success', 'Film berhasil ditambahkan!');
                } else {
                    setFlashMessage('error', 'Gagal menambahkan film!');
                }
            } else {
                $id_film = (int)$_POST['id_film'];
                if ($poster_path) {
                    $query = "UPDATE film SET judul = :judul, genre = :genre, durasi_menit = :durasi, 
                              rating_umur = :rating, sinopsis = :sinopsis, poster = :poster, 
                              status_tayang = :status_tayang WHERE id_film = :id_film";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':judul', $judul);
                    $stmt->bindParam(':genre', $genre);
                    $stmt->bindParam(':durasi', $durasi);
                    $stmt->bindParam(':rating', $rating);
                    $stmt->bindParam(':sinopsis', $sinopsis);
                    $stmt->bindParam(':poster', $poster_path);
                    $stmt->bindParam(':status_tayang', $status_tayang);
                    $stmt->bindParam(':id_film', $id_film);
                } else {
                    $query = "UPDATE film SET judul = :judul, genre = :genre, durasi_menit = :durasi, 
                              rating_umur = :rating, sinopsis = :sinopsis, 
                              status_tayang = :status_tayang WHERE id_film = :id_film";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':judul', $judul);
                    $stmt->bindParam(':genre', $genre);
                    $stmt->bindParam(':durasi', $durasi);
                    $stmt->bindParam(':rating', $rating);
                    $stmt->bindParam(':sinopsis', $sinopsis);
                    $stmt->bindParam(':status_tayang', $status_tayang);
                    $stmt->bindParam(':id_film', $id_film);
                }
                
                if ($stmt->execute()) {
                    setFlashMessage('success', 'Film berhasil diperbarui!');
                } else {
                    setFlashMessage('error', 'Gagal memperbarui film!');
                }
            }
            
            header('Location: manage_films.php');
            exit();
        } elseif ($action === 'delete') {
            $id_film = (int)$_POST['id_film'];
            
            $check = $conn->prepare("SELECT COUNT(*) FROM jadwal WHERE id_film = :id_film");
            $check->bindParam(':id_film', $id_film);
            $check->execute();
            
            if ($check->fetchColumn() > 0) {
                setFlashMessage('error', 'Film tidak bisa dihapus karena memiliki jadwal!');
            } else {
                $query = "DELETE FROM film WHERE id_film = :id_film";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id_film', $id_film);
                
                if ($stmt->execute()) {
                    setFlashMessage('success', 'Film berhasil dihapus!');
                } else {
                    setFlashMessage('error', 'Gagal menghapus film!');
                }
            }
            
            header('Location: manage_films.php');
            exit();
        }
    }
}

// Get all films
$query = "SELECT * FROM film ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$films = $stmt->fetchAll(PDO::FETCH_ASSOC);

$csrf_token = generateCSRFToken();
?>

<div class="page-header">
    <h2><i class="fas fa-film"></i> Kelola Film</h2>
    <p style="color: var(--text-secondary); margin-top: 0.5rem;">Tambah, edit, atau hapus film yang tayang di bioskop</p>
</div>

<button onclick="document.getElementById('addModal').style.display='block'" class="btn" style="margin-bottom: 2rem;">
    <i class="fas fa-plus"></i> Tambah Film Baru
</button>

<div style="background: var(--bg-card); border-radius: 12px; overflow: hidden; border: 1px solid var(--border-color);">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: var(--dark);">
                <tr>
                    <th style="padding: 1rem; text-align: left;">Poster</th>
                    <th style="padding: 1rem; text-align: left;">Judul</th>
                    <th style="padding: 1rem; text-align: left;">Genre</th>
                    <th style="padding: 1rem; text-align: left;">Durasi</th>
                    <th style="padding: 1rem; text-align: left;">Rating</th>
                    <th style="padding: 1rem; text-align: left;">Status</th>
                    <th style="padding: 1rem; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($films)): ?>
                    <tr>
                        <td colspan="7" style="padding: 3rem; text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-film" style="font-size: 3rem; opacity: 0.5; display: block; margin-bottom: 1rem;"></i>
                            Belum ada film. Klik tombol "Tambah Film Baru" untuk menambahkan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($films as $film): ?>
                        <tr style="border-top: 1px solid var(--border-color);">
                            <td style="padding: 1rem;">
                                <?php if ($film['poster']): ?>
                                    <img src="/ticketing_bioskop/<?php echo htmlspecialchars($film['poster']); ?>" 
                                         alt="<?php echo htmlspecialchars($film['judul']); ?>" 
                                         style="width: 60px; height: 90px; object-fit: cover; border-radius: 5px;">
                                <?php else: ?>
                                    <div style="width: 60px; height: 90px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 5px; display: flex; align-items: center; justify-content: center; color: white;">
                                        <i class="fas fa-film"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1rem;">
                                <strong><?php echo htmlspecialchars($film['judul']); ?></strong>
                            </td>
                            <td style="padding: 1rem;"><?php echo htmlspecialchars($film['genre']); ?></td>
                            <td style="padding: 1rem;"><?php echo $film['durasi_menit']; ?> menit</td>
                            <td style="padding: 1rem;">
                                <span style="padding: 0.3rem 0.8rem; background: var(--primary); color: white; border-radius: 5px; font-size: 0.85rem; font-weight: 600;">
                                    <?php echo htmlspecialchars($film['rating_umur']); ?>
                                </span>
                            </td>
                            <td style="padding: 1rem;">
                                <?php if ($film['status_tayang']): ?>
                                    <span style="color: var(--success); font-weight: 600;">
                                        <i class="fas fa-check-circle"></i> Tayang
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary);">
                                        <i class="fas fa-times-circle"></i> Tidak Tayang
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1rem;">
                                <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                    <button onclick="editFilm(<?php echo htmlspecialchars(json_encode($film)); ?>)" 
                                            class="btn btn-secondary" 
                                            style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus film \'<?php echo addslashes($film['judul']); ?>\'?')">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id_film" value="<?php echo $film['id_film']; ?>">
                                        <button type="submit" class="btn" style="padding: 0.5rem 1rem; font-size: 0.9rem; background: #dc3545;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; overflow: auto;">
    <div style="background: var(--bg-card); max-width: 600px; margin: 2rem auto; padding: 2.5rem; border-radius: 12px; border: 1px solid var(--border-color);">
        <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-plus"></i> Tambah Film Baru</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label>Judul Film <span style="color: var(--primary);">*</span></label>
                <input type="text" name="judul" class="form-control" required placeholder="Contoh: Avengers Endgame">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label>Genre <span style="color: var(--primary);">*</span></label>
                    <input type="text" name="genre" class="form-control" required placeholder="Contoh: Action">
                </div>
                
                <div class="form-group">
                    <label>Durasi (menit) <span style="color: var(--primary);">*</span></label>
                    <input type="number" name="durasi_menit" class="form-control" required min="60" max="300" placeholder="Contoh: 180">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label>Rating Umur <span style="color: var(--primary);">*</span></label>
                    <select name="rating_umur" class="form-control" required>
                        <option value="">Pilih Rating</option>
                        <option value="SU">SU (Semua Umur)</option>
                        <option value="13+">13+</option>
                        <option value="17+">17+</option>
                        <option value="21+">21+</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="status_tayang" value="1" checked style="width: 20px; height: 20px;">
                        <span>Tayangkan Film</span>
                    </label>
                    <small style="color: var(--text-secondary); display: block; margin-top: 0.5rem;">Centang jika film ingin ditampilkan di website</small>
                </div>
            </div>
            
            <div class="form-group">
                <label>Sinopsis</label>
                <textarea name="sinopsis" class="form-control" rows="4" placeholder="Tuliskan sinopsis film..."></textarea>
            </div>
            
            <div class="form-group">
                <label>Poster Film</label>
                <input type="file" name="poster" class="form-control" accept="image/*">
                <small style="color: var(--text-secondary); display: block; margin-top: 0.5rem;">
                    <i class="fas fa-info-circle"></i> Format: JPG, PNG, GIF, WEBP (Max 2MB)
                </small>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn" style="flex: 1;">
                    <i class="fas fa-save"></i> Simpan Film
                </button>
                <button type="button" onclick="document.getElementById('addModal').style.display='none'" 
                        class="btn btn-secondary" style="flex: 1;">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; overflow: auto;">
    <div style="background: var(--bg-card); max-width: 600px; margin: 2rem auto; padding: 2.5rem; border-radius: 12px; border: 1px solid var(--border-color);">
        <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-edit"></i> Edit Film</h3>
        <form method="POST" enctype="multipart/form-data" id="editForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_film" id="edit_id">
            
            <div class="form-group">
                <label>Judul Film <span style="color: var(--primary);">*</span></label>
                <input type="text" name="judul" id="edit_judul" class="form-control" required>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label>Genre <span style="color: var(--primary);">*</span></label>
                    <input type="text" name="genre" id="edit_genre" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Durasi (menit) <span style="color: var(--primary);">*</span></label>
                    <input type="number" name="durasi_menit" id="edit_durasi" class="form-control" required min="60" max="300">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label>Rating Umur <span style="color: var(--primary);">*</span></label>
                    <select name="rating_umur" id="edit_rating" class="form-control" required>
                        <option value="SU">SU (Semua Umur)</option>
                        <option value="13+">13+</option>
                        <option value="17+">17+</option>
                        <option value="21+">21+</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="status_tayang" id="edit_status" value="1" style="width: 20px; height: 20px;">
                        <span>Tayangkan Film</span>
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label>Sinopsis</label>
                <textarea name="sinopsis" id="edit_sinopsis" class="form-control" rows="4"></textarea>
            </div>
            
            <div class="form-group">
                <label>Poster Film</label>
                <input type="file" name="poster" class="form-control" accept="image/*">
                <small style="color: var(--text-secondary); display: block; margin-top: 0.5rem;">
                    <i class="fas fa-info-circle"></i> Kosongkan jika tidak ingin mengubah poster
                </small>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn" style="flex: 1;">
                    <i class="fas fa-save"></i> Update Film
                </button>
                <button type="button" onclick="document.getElementById('editModal').style.display='none'" 
                        class="btn btn-secondary" style="flex: 1;">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editFilm(film) {
    document.getElementById('edit_id').value = film.id_film;
    document.getElementById('edit_judul').value = film.judul;
    document.getElementById('edit_genre').value = film.genre;
    document.getElementById('edit_durasi').value = film.durasi_menit;
    document.getElementById('edit_rating').value = film.rating_umur;
    document.getElementById('edit_sinopsis').value = film.sinopsis || '';
    document.getElementById('edit_status').checked = film.status_tayang == 1;
    document.getElementById('editModal').style.display = 'block';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.id === 'addModal') {
        document.getElementById('addModal').style.display = 'none';
    }
    if (event.target.id === 'editModal') {
        document.getElementById('editModal').style.display = 'none';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>