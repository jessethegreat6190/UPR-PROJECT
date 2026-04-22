// Vehicle Entry Kiosk JavaScript
let signatureCanvas, signatureCtx, isDrawing = false;
let currentVisitor = null;

document.addEventListener('DOMContentLoaded', function() {
    updateClock();
    setInterval(updateClock, 1000);
    initSignature();
});

function updateClock() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
    const clockEl = document.getElementById('clock');
    if (clockEl) clockEl.textContent = timeStr;
    const kioskClock = document.getElementById('kioskTime');
    if (kioskClock) kioskClock.textContent = timeStr;
}

function initSignature() {
    signatureCanvas = document.getElementById('signatureCanvas');
    if (!signatureCanvas) return;
    signatureCtx = signatureCanvas.getContext('2d');
    
    signatureCtx.strokeStyle = '#000';
    signatureCtx.lineWidth = 2;
    signatureCtx.lineCap = 'round';
    
    signatureCanvas.addEventListener('mousedown', startDrawing);
    signatureCanvas.addEventListener('mousemove', draw);
    signatureCanvas.addEventListener('mouseup', stopDrawing);
    signatureCanvas.addEventListener('mouseout', stopDrawing);
    
    signatureCanvas.addEventListener('touchstart', function(e) {
        e.preventDefault();
        const touch = e.touches[0];
        const rect = signatureCanvas.getBoundingClientRect();
        isDrawing = true;
        signatureCtx.beginPath();
        signatureCtx.moveTo(touch.clientX - rect.left, touch.clientY - rect.top);
    });
    signatureCanvas.addEventListener('touchmove', function(e) {
        e.preventDefault();
        const touch = e.touches[0];
        const rect = signatureCanvas.getBoundingClientRect();
        if (isDrawing) {
            signatureCtx.lineTo(touch.clientX - rect.left, touch.clientY - rect.top);
            signatureCtx.stroke();
        }
    });
    signatureCanvas.addEventListener('touchend', stopDrawing);
}

function startDrawing(e) {
    isDrawing = true;
    const rect = signatureCanvas.getBoundingClientRect();
    signatureCtx.beginPath();
    signatureCtx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
}

function draw(e) {
    if (!isDrawing) return;
    const rect = signatureCanvas.getBoundingClientRect();
    signatureCtx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
    signatureCtx.stroke();
}

function stopDrawing() {
    if (isDrawing) {
        document.getElementById('signatureData').value = signatureCanvas.toDataURL();
    }
    isDrawing = false;
}

function clearSignature() {
    if (signatureCtx) {
        signatureCtx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
        document.getElementById('signatureData').value = '';
    }
}

function toggleCategoryFields() {
    const category = document.getElementById('categorySelect').value;
    const categoryLabels = {
        'delivery': 'Delivery / Courier',
        'school': 'School / Education',
        'equipment': 'Equipment / Supplies',
        'legal': 'Legal Representative',
        'contractor': 'Contractor',
        'staff': 'Staff',
        'media': 'Media',
        'official': 'Official Visit',
        'other': 'Other'
    };
    
    if (category && categoryLabels[category]) {
        document.getElementById('fullName').placeholder = 'Enter ' + categoryLabels[category] + ' name';
    }
}

function lookupVisitor() {
    const phone = document.getElementById('phoneInput').value.trim();
    if (!phone) return;
    
    const savedVisitors = JSON.parse(localStorage.getItem('updms_vehicles') || '[]');
    const visitor = savedVisitors.find(v => v.phone === phone);
    
    if (visitor) {
        currentVisitor = visitor;
        document.getElementById('fullName').value = visitor.name || '';
        document.getElementById('nationalId').value = visitor.national_id || '';
        document.getElementById('plateNumber').value = visitor.plate_number || '';
        if (visitor.category) {
            document.getElementById('categorySelect').value = visitor.category;
        }
        document.getElementById('savedVisitorMsg').style.display = 'block';
        showToast('Welcome back! Details loaded.');
    } else {
        currentVisitor = null;
        document.getElementById('savedVisitorMsg').style.display = 'none';
    }
}

function saveVisitor(data) {
    const phone = data.phone;
    if (!phone) return;
    
    let savedVisitors = JSON.parse(localStorage.getItem('updms_vehicles') || '[]');
    const existingIndex = savedVisitors.findIndex(v => v.phone === phone);
    
    const visitorData = {
        phone: phone,
        name: data.full_name,
        national_id: data.national_id,
        plate_number: data.plate_number,
        category: data.category || document.getElementById('categorySelect').value,
        lastVisit: new Date().toISOString(),
        visitCount: 1
    };
    
    if (existingIndex >= 0) {
        visitorData.visitCount = (savedVisitors[existingIndex].visitCount || 0) + 1;
        savedVisitors[existingIndex] = visitorData;
    } else {
        savedVisitors.push(visitorData);
    }
    
    localStorage.setItem('updms_vehicles', JSON.stringify(savedVisitors));
}

function showToast(message) {
    const toast = document.getElementById('toast');
    if (toast) {
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }
}

document.getElementById('visitorForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData);

    const now = new Date();
    const year = now.getFullYear();
    const refNum = `VEH-${year}-${String(Math.floor(Math.random() * 9999) + 1).padStart(4, '0')}`;
    document.getElementById('refNumber').textContent = refNum;
    document.getElementById('regTime').textContent = now.toLocaleTimeString('en-GB');
    
    saveVisitor(data);

    try {
        const response = await fetch('../../api/visitors.php?action=kiosk_register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ...data, ref_number: refNum, registered_at: now.toISOString() })
        });
        await response.json();
    } catch (e) {
        console.log('Offline mode');
    }

    document.getElementById('step1').style.display = 'none';
    document.getElementById('step2').style.display = 'block';
    showToast('Gate opening...');
});
