<?php
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

// Get activity logs
$query = "SELECT * FROM activity_log 
          ORDER BY created_at DESC 
          LIMIT 100";
$logs = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Clear old logs (older than 30 days)
$conn->query("DELETE FROM activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
?>

<div class="page-header">
    <h2><i class="fas fa-history"></i> Activity Log</h2>
    <p>Riwayat aktivitas admin di sistem</p>
</div>

<div class="glass-card">
    <div class="card-header">
        <h3><i class="fas fa-list"></i> Log Aktivitas</h3>
        <button onclick="exportTable('activityLogTable', 'Activity_Log')" class="btn-export-small">
            <i class="fas fa-download"></i> Export
        </button>
    </div>
    
    <div id="activityLogTable">
        <?php if (empty($logs)): ?>
            <div style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                <i class="fas fa-inbox" style="font-size: 3rem; opacity: 0.5; margin-bottom: 1rem;"></i>
                <p>Belum ada aktivitas yang tercatat</p>
            </div>
        <?php else: ?>
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Admin</th>
                        <th>Aksi</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td>
                            <i class="far fa-clock"></i> 
                            <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?>
                        </td>
                        <td>
                            <strong><?php echo sanitize($log['username']); ?></strong>
                            <br><small style="color: var(--text-secondary);">ID: <?php echo $log['id_pengguna']; ?></small>
                        </td>
                        <td>
                            <?php
                            $badge_class = 'badge-primary';
                            $icon = 'fa-info-circle';
                            
                            if (strpos($log['action'], 'Tambah') !== false) {
                                $badge_class = 'badge-success';
                                $icon = 'fa-plus-circle';
                            } elseif (strpos($log['action'], 'Edit') !== false || strpos($log['action'], 'Update') !== false) {
                                $badge_class = 'badge-warning';
                                $icon = 'fa-edit';
                            } elseif (strpos($log['action'], 'Hapus') !== false || strpos($log['action'], 'Delete') !== false) {
                                $badge_class = 'badge-danger';
                                $icon = 'fa-trash';
                            } elseif (strpos($log['action'], 'Login') !== false) {
                                $badge_class = 'badge-info';
                                $icon = 'fa-sign-in-alt';
                            }
                            ?>
                            <span class="badge <?php echo $badge_class; ?>">
                                <i class="fas <?php echo $icon; ?>"></i> <?php echo sanitize($log['action']); ?>
                            </span>
                        </td>
                        <td><?php echo sanitize($log['details']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Statistics -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
    <?php
    $today_logs = $conn->query("SELECT COUNT(*) FROM activity_log WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $this_week = $conn->query("SELECT COUNT(*) FROM activity_log WHERE YEARWEEK(created_at) = YEARWEEK(NOW())")->fetchColumn();
    $top_admin = $conn->query("SELECT username, COUNT(*) as total FROM activity_log GROUP BY username ORDER BY total DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    ?>
    
    <div class="stat-card gradient-purple" style="padding: 1.5rem;">
        <div class="stat-info">
            <h4>Log Hari Ini</h4>
            <h2><?php echo $today_logs; ?></h2>
        </div>
    </div>
    
    <div class="stat-card gradient-blue" style="padding: 1.5rem;">
        <div class="stat-info">
            <h4>Log Minggu Ini</h4>
            <h2><?php echo $this_week; ?></h2>
        </div>
    </div>
    
    <div class="stat-card gradient-green" style="padding: 1.5rem;">
        <div class="stat-info">
            <h4>Admin Teraktif</h4>
            <h2><?php echo $top_admin ? sanitize($top_admin['username']) : '-'; ?></h2>
            <small><?php echo $top_admin ? $top_admin['total'] . ' aksi' : ''; ?></small>
        </div>
    </div>
</div>

<script>
function exportTable(tableId, filename) {
    const table = document.querySelector('#' + tableId + ' table');
    if (!table) {
        alert('Tabel tidak ditemukan!');
        return;
    }
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let row of rows) {
        const cols = row.querySelectorAll('td, th');
        let csvRow = [];
        for (let col of cols) {
            csvRow.push('"' + col.textContent.trim() + '"');
        }
        csv.push(csvRow.join(','));
    }
    
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename + '_' + new Date().toISOString().split('T')[0] + '.csv';
    link.click();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>