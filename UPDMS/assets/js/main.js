const SITE_URL = '<?php echo SITE_URL; ?>';

function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    const container = document.querySelector('main') || document.body;
    container.prepend(alertDiv);
    setTimeout(() => alertDiv.remove(), 5000);
}

function formatDuration(entryTime) {
    const now = new Date();
    const entry = new Date(entryTime);
    const diff = Math.floor((now - entry) / 1000);
    
    const hours = Math.floor(diff / 3600);
    const minutes = Math.floor((diff % 3600) / 60);
    
    return `${hours}h ${minutes}m`;
}

function updateDurations() {
    document.querySelectorAll('[data-entry-time]').forEach(el => {
        const entryTime = el.dataset.entryTime;
        el.textContent = formatDuration(entryTime);
    });
}

setInterval(updateDurations, 60000);
document.addEventListener('DOMContentLoaded', updateDurations);

function capturePhoto(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    
    if (input && preview) {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
}

function checkPlate(plate) {
    return fetch(`${SITE_URL}/api/vehicles.php?action=check&plate=${encodeURIComponent(plate)}`)
        .then(r => r.json())
        .then(data => data);
}
