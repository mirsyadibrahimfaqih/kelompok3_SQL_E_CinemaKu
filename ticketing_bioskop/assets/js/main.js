// ==========================================
// UTILITY FUNCTIONS
// ==========================================
function formatRupiah(angka) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka);
}

function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${message}
    `;
    
    document.querySelector('main').prepend(alertDiv);
    setTimeout(() => alertDiv.remove(), 5000);
}

// ==========================================
// SEAT SELECTION CLASS
// ==========================================
class SeatSelector {
    constructor() {
        this.selectedSeats = [];
        this.maxSeats = 6;
    }
    
    toggleSeat(seatId, element) {
        if (element.classList.contains('occupied')) return;
        
        const index = this.selectedSeats.indexOf(seatId);
        
        if (index > -1) {
            this.selectedSeats.splice(index, 1);
            element.classList.remove('selected');
        } else {
            if (this.selectedSeats.length >= this.maxSeats) {
                showAlert(`Maksimal ${this.maxSeats} kursi per pemesanan!`, 'error');
                return;
            }
            this.selectedSeats.push(seatId);
            element.classList.add('selected');
        }
        
        this.updateSummary();
    }
    
    updateSummary() {
        const summary = document.getElementById('seatSummary');
        if (summary) {
            summary.textContent = `Kursi dipilih: ${this.selectedSeats.join(', ') || '-'}`;
        }
        
        const count = document.getElementById('seatCount');
        if (count) {
            count.textContent = `${this.selectedSeats.length} kursi`;
        }
    }
    
    getSelectedSeats() {
        return this.selectedSeats;
    }
}

// ==========================================
// COUNTDOWN TIMER
// ==========================================
function startCountdown(minutes, callback) {
    let seconds = minutes * 60;
    
    const timer = setInterval(() => {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        
        callback(`${mins}:${secs.toString().padStart(2, '0')}`);
        
        if (seconds <= 0) {
            clearInterval(timer);
            return false;
        }
        
        seconds--;
        return true;
    }, 1000);
}

// ==========================================
// FORM VALIDATION
// ==========================================
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = 'var(--primary)';
            isValid = false;
        } else {
            input.style.borderColor = '#444';
        }
    });
    
    return isValid;
}

// ==========================================
// AJAX HELPER
// ==========================================
async function fetchData(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });
        
        return await response.json();
    } catch (error) {
        console.error('Error:', error);
        showAlert('Terjadi kesalahan!', 'error');
        return null;
    }
}

// ==========================================
// THEME TOGGLE (Dark/Light Mode)
// ==========================================
function toggleTheme() {
    const body = document.body;
    const icon = document.getElementById('themeIcon');
    
    if (body.classList.contains('light-theme')) {
        body.classList.remove('light-theme');
        localStorage.setItem('theme', 'dark');
        if (icon) icon.className = 'fas fa-sun';
    } else {
        body.classList.add('light-theme');
        localStorage.setItem('theme', 'light');
        if (icon) icon.className = 'fas fa-moon';
    }
}

// ==========================================
// MOBILE MENU TOGGLE (Hamburger)
// ==========================================
function toggleMobileMenu() {
    const navMenu = document.getElementById('navMenu');
    const menuIcon = document.getElementById('menuIcon');
    
    if (navMenu && menuIcon) {
        navMenu.classList.toggle('active');
        
        if (navMenu.classList.contains('active')) {
            menuIcon.classList.remove('fa-bars');
            menuIcon.classList.add('fa-times');
        } else {
            menuIcon.classList.remove('fa-times');
            menuIcon.classList.add('fa-bars');
        }
    }
}

// ==========================================
// INITIALIZE ON PAGE LOAD
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    // Load saved theme
    const savedTheme = localStorage.getItem('theme');
    const icon = document.getElementById('themeIcon');
    
    if (savedTheme === 'light') {
        document.body.classList.add('light-theme');
        if (icon) icon.className = 'fas fa-moon';
    }
    
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
    
    // Close mobile menu when clicking on a link
    const navLinks = document.querySelectorAll('.nav-menu a');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            const navMenu = document.getElementById('navMenu');
            const menuIcon = document.getElementById('menuIcon');
            if (navMenu && menuIcon) {
                navMenu.classList.remove('active');
                menuIcon.classList.remove('fa-times');
                menuIcon.classList.add('fa-bars');
            }
        });
    });
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', (e) => {
        const navMenu = document.getElementById('navMenu');
        const menuToggle = document.querySelector('.mobile-menu-toggle');
        
        if (navMenu && navMenu.classList.contains('active')) {
            if (!navMenu.contains(e.target) && !menuToggle.contains(e.target)) {
                navMenu.classList.remove('active');
                const menuIcon = document.getElementById('menuIcon');
                if (menuIcon) {
                    menuIcon.classList.remove('fa-times');
                    menuIcon.classList.add('fa-bars');
                }
            }
        }
    });
});