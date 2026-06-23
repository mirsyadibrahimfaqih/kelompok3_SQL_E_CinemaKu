<?php
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Token tidak valid!');
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add') {
            $nama = sanitize($_POST['nama']);
            $email = sanitize($_POST['email']);
            $no_hp = sanitize($_POST['no_hp']);
            $password = $_POST['password'];
            $is_admin = isset($_POST['is_admin']) ? 1 : 0;
            
            // Validation
            $errors = [];
            if (!validateEmail($email)) {
                $errors[] = 'Email tidak valid!';
            }
            if (strlen($password) < 6) {
                $errors[] = 'Password minimal 6 karakter!';
            }
            
            // Check email exists
            $check = $conn->prepare("SELECT id_pengguna FROM pengguna WHERE email = :email");
            $check->bindParam(':email', $email);
            $check->execute();
            if ($check->rowCount() > 0) {
                $errors[] = 'Email sudah terdaftar!';
            }
            
            if (empty($errors)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query = "INSERT INTO pengguna (nama, email, no_hp, password, is_admin) 
                          VALUES (:nama, :email, :no_hp, :password, :is_admin)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':nama', $nama);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':no_hp', $no_hp);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':is_admin', $is_admin);
                
                if ($stmt->execute()) {
                    logAdminActivity('Tambah User', "Menambah user: $nama ($email)");
                    setFlashMessage('success', 'User berhasil ditambahkan!');
                } else {
                    setFlashMessage('error', 'Gagal menambahkan user!');
                }
            } else {
                foreach ($errors as $error) {
                    setFlashMessage('error', $error);
                }
            }
            
            header('Location: manage_users.php');
            exit();
            
        } elseif ($action === 'edit') {
            $id_pengguna = (int)$_POST['id_pengguna'];
            $nama = sanitize($_POST['nama']);
            $email = sanitize($_POST['email']);
            $no_hp = sanitize($_POST['no_hp']);
            $is_admin = isset($_POST['is_admin']) ? 1 : 0;
            
            // Check email exists (except current user)
            $check = $conn->prepare("SELECT id_pengguna FROM pengguna WHERE email = :email AND id_pengguna != :id");
            $check->bindParam(':email', $email);
            $check->bindParam(':id', $id_pengguna);
            $check->execute();
            
            if ($check->rowCount() > 0) {
                setFlashMessage('error', 'Email sudah digunakan user lain!');
            } else {
                $query = "UPDATE pengguna SET nama = :nama, email = :email, no_hp = :no_hp, is_admin = :is_admin 
                          WHERE id_pengguna = :id_pengguna";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':nama', $nama);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':no_hp', $no_hp);
                $stmt->bindParam(':is_admin', $is_admin);
                $stmt->bindParam(':id_pengguna', $id_pengguna);
                
                if ($stmt->execute()) {
                    logAdminActivity('Edit User', "Mengedit user: $nama ($email)");
                    setFlashMessage('success', 'User berhasil diperbarui!');
                } else {
                    setFlashMessage('error', 'Gagal memperbarui user!');
                }
            }
            
            header('Location: manage_users.php');
            exit();
            
        } elseif ($action === 'reset_password') {
            $id_pengguna = (int)$_POST['id_pengguna'];
            $new_password = $_POST['new_password'];
            
            if (strlen($new_password) < 6) {
                setFlashMessage('error', 'Password minimal 6 karakter!');
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $query = "UPDATE pengguna SET password = :password WHERE id_pengguna = :id_pengguna";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':id_pengguna', $id_pengguna);
                
                if ($stmt->execute()) {
                    logAdminActivity('Reset Password', "Reset password user ID: $id_pengguna");
                    setFlashMessage('success', 'Password berhasil direset!');
                } else {
                    setFlashMessage('error', 'Gagal reset password!');
                }
            }
            
            header('Location: manage_users.php');
            exit();
            
        } elseif ($action === 'delete') {
            $id_pengguna = (int)$_POST['id_pengguna'];
            
            // Check if has transactions
            $check = $conn->prepare("SELECT COUNT(*) FROM pemesanan WHERE id_pengguna = :id");
            $check->bindParam(':id', $id_pengguna);
            $check->execute();
            
            if ($check->fetchColumn() > 0) {
                setFlashMessage('error', 'User tidak bisa dihapus karena memiliki riwayat transaksi!');
            } else {
                $query = "DELETE FROM pengguna WHERE id_pengguna = :id_pengguna";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id_pengguna', $id_pengguna);
                
                if ($stmt->execute()) {
                    logAdminActivity('Hapus User', "Menghapus user ID: $id_pengguna");
                    setFlashMessage('success', 'User berhasil dihapus!');
                } else {
                    setFlashMessage('error', 'Gagal menghapus user!');
                }
            }
            
            header('Location: manage_users.php');
            exit();
        }
    }
}

// Get all users
$query = "SELECT * FROM pengguna ORDER BY id_pengguna DESC";
$users = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);

$csrf_token = generateCSRFToken();
?>

<div class="page-header">
    <h2><i class="fas fa-users"></i> Kelola User</h2>
    <p>Kelola semua user yang terdaftar di sistem</p>
</div>

<button onclick="openAddModal()" class="btn" style="margin-bottom: 2rem;">
    <i class="fas fa-user-plus"></i> Tambah User Baru
</button>

<div class="glass-card">
    <table class="modern-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama</th>
                <th>Email</th>
                <th>No. HP</th>
                <th>Role</th>
                <th>Terdaftar</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo $user['id_pengguna']; ?></td>
                <td><strong><?php echo sanitize($user['nama']); ?></strong></td>
                <td><?php echo sanitize($user['email']); ?></td>
                <td><?php echo sanitize($user['no_hp']); ?></td>
                <td>
                    <?php if ($user['is_admin']): ?>
                        <span class="badge badge-danger"><i class="fas fa-shield-alt"></i> Admin</span>
                    <?php else: ?>
                        <span class="badge badge-primary"><i class="fas fa-user"></i> Customer</span>
                    <?php endif; ?>
                </td>
                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                <td>
                    <div class="action-buttons">
                        <button onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                class="btn-action btn-edit" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="resetPassword(<?php echo $user['id_pengguna']; ?>, '<?php echo addslashes($user['nama']); ?>')" 
                                class="btn-action btn-warning" title="Reset Password">
                            <i class="fas fa-key"></i>
                        </button>
                        <?php if ($user['id_pengguna'] != $_SESSION['user_id']): ?>
                        <form method="POST" style="display: inline;" 
                              onsubmit="return confirm('Yakin ingin menghapus user <?php echo addslashes($user['nama']); ?>?')">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id_pengguna" value="<?php echo $user['id_pengguna']; ?>">
                            <button type="submit" class="btn-action btn-delete" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Tambah User Baru</h3>
            <button onclick="closeAddModal()" class="modal-close">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label>Nama Lengkap <span class="required">*</span></label>
                <input type="text" name="nama" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Email <span class="required">*</span></label>
                <input type="email" name="email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>No. HP <span class="required">*</span></label>
                <input type="text" name="no_hp" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Password <span class="required">*</span></label>
                <input type="password" name="password" class="form-control" required minlength="6">
                <small class="form-hint">Minimal 6 karakter</small>
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="is_admin" value="1">
                    <span>Set sebagai Admin</span>
                </label>
            </div>
            
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <button type="button" onclick="closeAddModal()" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-edit"></i> Edit User</h3>
            <button onclick="closeEditModal()" class="modal-close">&times;</button>
        </div>
        <form method="POST" id="editForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_pengguna" id="edit_id">
            
            <div class="form-group">
                <label>Nama Lengkap <span class="required">*</span></label>
                <input type="text" name="nama" id="edit_nama" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Email <span class="required">*</span></label>
                <input type="email" name="email" id="edit_email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>No. HP <span class="required">*</span></label>
                <input type="text" name="no_hp" id="edit_no_hp" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="is_admin" id="edit_is_admin" value="1">
                    <span>Set sebagai Admin</span>
                </label>
            </div>
            
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update
                </button>
                <button type="button" onclick="closeEditModal()" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="resetModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-key"></i> Reset Password</h3>
            <button onclick="closeResetModal()" class="modal-close">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="id_pengguna" id="reset_id">
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                Reset password untuk user: <strong id="reset_nama"></strong>
            </div>
            
            <div class="form-group">
                <label>Password Baru <span class="required">*</span></label>
                <input type="password" name="new_password" class="form-control" required minlength="6">
                <small class="form-hint">Minimal 6 karakter</small>
            </div>
            
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Reset Password
                </button>
                <button type="button" onclick="closeResetModal()" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('addModal').style.display = 'flex';
}

function closeAddModal() {
    document.getElementById('addModal').style.display = 'none';
}

function editUser(user) {
    document.getElementById('edit_id').value = user.id_pengguna;
    document.getElementById('edit_nama').value = user.nama;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_no_hp').value = user.no_hp;
    document.getElementById('edit_is_admin').checked = user.is_admin == 1;
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function resetPassword(id, nama) {
    document.getElementById('reset_id').value = id;
    document.getElementById('reset_nama').textContent = nama;
    document.getElementById('resetModal').style.display = 'flex';
}

function closeResetModal() {
    document.getElementById('resetModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>