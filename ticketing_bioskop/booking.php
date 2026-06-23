<?php
require_once 'includes/header.php';
requireLogin();
restrictAdminFromBooking(); // ← TAMBAHKAN INI

$id_jadwal = isset($_GET['id_jadwal']) ? (int)$_GET['id_jadwal'] : 0;

// Ambil detail jadwal
$query = "SELECT j.*, f.judul, s.nama_studio 
          FROM jadwal j 
          JOIN film f ON j.id_film = f.id_film 
          JOIN studio s ON j.id_studio = s.id_studio 
          WHERE j.id_jadwal = :id_jadwal";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id_jadwal', $id_jadwal);
$stmt->execute();
$jadwal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$jadwal) {
    setFlashMessage('error', 'Jadwal tidak ditemukan!');
    header('Location: films.php');
    exit();
}
?>

<div class="page-header">
    <h2>Pilih Kursi</h2>
    <p><?php echo sanitize($jadwal['judul']); ?> - <?php echo formatTanggal($jadwal['tanggal']); ?> | <?php echo formatJam($jadwal['jam']); ?> | <?php echo sanitize($jadwal['nama_studio']); ?></p>
</div>

<div class="seat-container">
    <div class="screen">LAYAR</div>
    
    <div class="seat-legend" style="display: flex; gap: 2.5rem; justify-content: center; margin: 3rem 0; flex-wrap: wrap;">
        <div class="legend-item" style="display: flex; align-items: center; gap: 0.8rem;">
            <div class="legend-box" style="width: 25px; height: 25px; border-radius: 5px; background: #444;"></div>
            <span>Tersedia</span>
        </div>
        <div class="legend-item" style="display: flex; align-items: center; gap: 0.8rem;">
            <div class="legend-box" style="width: 25px; height: 25px; border-radius: 5px; background: var(--success);"></div>
            <span>Dipilih</span>
        </div>
<div class="legend-item" style="display: flex; align-items: center; gap: 0.8rem;">
    <div class="legend-box" style="width: 25px; height: 25px; border-radius: 5px; background: #2a2a2a; border: 1px solid #555;"></div>
    <span style="color: #888;">Terisi</span>
</div>
    </div>

    <div id="seatsMap" style="max-width: 700px; margin: 0 auto;">
        <p style="text-align:center; color: var(--text-secondary);">Memuat kursi...</p>
    </div>

    <div style="margin-top: 3rem; text-align: center;">
        <h3 id="seatSummary" style="margin-bottom: 1rem;">Kursi dipilih: -</h3>
        <h2 id="totalPrice" style="color: var(--primary); margin: 1rem 0;">Total: Rp 0</h2>
        <button id="btnCheckout" class="btn" style="display: none; padding: 1rem 3rem; font-size: 1.1rem;" onclick="proceedToCheckout()">
            <i class="fas fa-shopping-cart"></i> Lanjut ke Checkout
        </button>
    </div>
</div>

<style>
/* Seat Row Container */
.seat-row {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-bottom: 12px;
    align-items: center;
}

/* Aisle (lorong) di antara section */
.aisle {
    width: 30px;
}

/* Individual Seat */
.seat-item {
    width: 50px;
    height: 50px;
    background: #444;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 0.85rem;
    font-weight: 600;
    color: white;
    border: 2px solid transparent;
}

.seat-item.available:hover {
    background: var(--primary);
    transform: scale(1.1);
    border-color: var(--primary);
}

.seat-item.selected {
    background: var(--success);
    transform: scale(1.15);
    box-shadow: 0 0 15px var(--success);
    border-color: var(--success);
}

.seat-item.occupied {
    background: var(--secondary);
    cursor: not-allowed;
    opacity: 0.4;
    position: relative;
}

.seat-item.occupied::after {
    content: '✕';
    position: absolute;
    font-size: 1.2rem;
    color: rgba(255, 255, 255, 0.7);
}

/* Row Label */
.row-label {
    width: 30px;
    text-align: center;
    font-weight: bold;
    color: var(--text-secondary);
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .seat-item {
        width: 40px;
        height: 40px;
        font-size: 0.75rem;
    }
    
    .aisle {
        width: 20px;
    }
    
    .seat-row {
        gap: 6px;
        margin-bottom: 8px;
    }
}

@media (max-width: 480px) {
    .seat-item {
        width: 32px;
        height: 32px;
        font-size: 0.7rem;
    }
    
    .aisle {
        width: 12px;
    }
}
</style>

<script>
    const idJadwal = <?php echo $id_jadwal; ?>;
    const hargaTiket = <?php echo $jadwal['harga']; ?>;
    let selectedSeats = [];
    const maxSeats = 6;

    // Parse nomor kursi menjadi {row: 'A', number: 1}
    function parseSeatNumber(nomor) {
        const match = nomor.match(/^([A-Z]+)(\d+)$/);
        if (match) {
            return { row: match[1], number: parseInt(match[2]) };
        }
        return { row: '?', number: 0 };
    }

    // Group seats by row
    function groupSeatsByRow(seats) {
        const groups = {};
        seats.forEach(seat => {
            const parsed = parseSeatNumber(seat.nomor_kursi);
            if (!groups[parsed.row]) {
                groups[parsed.row] = [];
            }
            groups[parsed.row].push({
                ...seat,
                row: parsed.row,
                seatNumber: parsed.number
            });
        });
        return groups;
    }

    async function loadSeats() {
        try {
            const response = await fetch(`api/check_seat.php?id_jadwal=${idJadwal}`);
            const data = await response.json();
            
            const seatsMap = document.getElementById('seatsMap');
            seatsMap.innerHTML = '';

            if (!data.seats || data.seats.length === 0) {
                seatsMap.innerHTML = '<p style="text-align:center; color: var(--text-secondary);">Tidak ada kursi tersedia.</p>';
                return;
            }

            // Group by row
            const grouped = groupSeatsByRow(data.seats);
            const sortedRows = Object.keys(grouped).sort();

            // Render each row with layout 3-4-3
            sortedRows.forEach(row => {
                const seatsInRow = grouped[row].sort((a, b) => a.seatNumber - b.seatNumber);
                
                const rowDiv = document.createElement('div');
                rowDiv.className = 'seat-row';
                
                // Row label (left)
                const labelLeft = document.createElement('div');
                labelLeft.className = 'row-label';
                labelLeft.textContent = row;
                rowDiv.appendChild(labelLeft);
                
                // Left section: seats 1-3
                const leftSection = seatsInRow.filter(s => s.seatNumber >= 1 && s.seatNumber <= 3);
                leftSection.forEach(seat => {
                    rowDiv.appendChild(createSeatElement(seat));
                });
                
                // Aisle 1
                const aisle1 = document.createElement('div');
                aisle1.className = 'aisle';
                rowDiv.appendChild(aisle1);
                
                // Middle section: seats 4-7
                const middleSection = seatsInRow.filter(s => s.seatNumber >= 4 && s.seatNumber <= 7);
                middleSection.forEach(seat => {
                    rowDiv.appendChild(createSeatElement(seat));
                });
                
                // Aisle 2
                const aisle2 = document.createElement('div');
                aisle2.className = 'aisle';
                rowDiv.appendChild(aisle2);
                
                // Right section: seats 8-10
                const rightSection = seatsInRow.filter(s => s.seatNumber >= 8 && s.seatNumber <= 10);
                rightSection.forEach(seat => {
                    rowDiv.appendChild(createSeatElement(seat));
                });
                
                // Row label (right)
                const labelRight = document.createElement('div');
                labelRight.className = 'row-label';
                labelRight.textContent = row;
                rowDiv.appendChild(labelRight);
                
                seatsMap.appendChild(rowDiv);
            });
        } catch (error) {
            console.error('Error loading seats:', error);
            document.getElementById('seatsMap').innerHTML = '<p style="text-align:center; color: var(--primary);">Gagal memuat kursi.</p>';
        }
    }

    function createSeatElement(seat) {
        const seatDiv = document.createElement('div');
        seatDiv.className = `seat-item ${seat.status}`;
        seatDiv.textContent = seat.nomor_kursi;
        seatDiv.dataset.id = seat.id_kursi;
        seatDiv.dataset.nomor = seat.nomor_kursi;
        
        if (seat.status === 'available') {
            seatDiv.onclick = () => toggleSeat(seatDiv);
        }
        
        return seatDiv;
    }

    function toggleSeat(element) {
        const id = element.dataset.id;
        const nomor = element.dataset.nomor;
        
        if (element.classList.contains('occupied')) return;
        
        const index = selectedSeats.findIndex(s => s.id === id);
        
        if (index > -1) {
            selectedSeats.splice(index, 1);
            element.classList.remove('selected');
        } else {
            if (selectedSeats.length >= maxSeats) {
                alert(`Maksimal ${maxSeats} kursi per pemesanan!`);
                return;
            }
            selectedSeats.push({ id: id, nomor: nomor });
            element.classList.add('selected');
        }
        updateSummary();
    }

    function updateSummary() {
        const summary = document.getElementById('seatSummary');
        const totalEl = document.getElementById('totalPrice');
        const btn = document.getElementById('btnCheckout');
        
        const nomors = selectedSeats.map(s => s.nomor).join(', ');
        summary.textContent = `Kursi dipilih: ${nomors || '-'}`;
        
        const total = selectedSeats.length * hargaTiket;
        totalEl.textContent = `Total: ${formatRupiah(total)}`;
        
        btn.style.display = selectedSeats.length > 0 ? 'inline-block' : 'none';
    }

    function proceedToCheckout() {
        const seatIds = selectedSeats.map(s => s.id).join(',');
        window.location.href = `checkout.php?id_jadwal=${idJadwal}&kursi=${seatIds}`;
    }

    document.addEventListener('DOMContentLoaded', loadSeats);
</script>

<?php require_once 'includes/footer.php'; ?>