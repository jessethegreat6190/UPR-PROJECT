// Main Kiosk Selector JavaScript
document.addEventListener('DOMContentLoaded', function() {
    updateClock();
    setInterval(updateClock, 1000);
});

function updateClock() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
    const clockEl = document.getElementById('clock');
    if (clockEl) clockEl.textContent = timeStr;
    const kioskClock = document.getElementById('kioskTime');
    if (kioskClock) kioskClock.textContent = timeStr;
}

function showToast(message) {
    const toast = document.getElementById('toast');
    if (toast) {
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }
}

function loadBooking() {
    const ref = document.getElementById('bookingRef').value.trim();
    if (!ref) {
        showToast('Please enter a reference number');
        return;
    }
    showToast('Looking up booking: ' + ref + '...');
    setTimeout(() => {
        alert('Booking: ' + ref + '\n\nFeature coming soon.\nPlease select your visit type above.');
    }, 500);
}
