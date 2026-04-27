const API_BASE = 'http://localhost:3000/api';

let currentStep = 1;
let streams = { front: null, back: null, face: null };
let photos = { front: null, back: null, face: null };

function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function selectRole(role) {
    document.getElementById('roleSelection').classList.add('hidden');
    document.getElementById('registrationForm').classList.remove('hidden');
    currentStep = 1;
    showStep(1);
    updateProgress();
}

function goBack() {
    stopAllStreams();
    resetForm();
    currentStep = 1;
    document.getElementById('registrationForm').classList.add('hidden');
    document.getElementById('roleSelection').classList.remove('hidden');
}

function resetForm() {
    document.getElementById('mainForm').reset();
    photos = { front: null, back: null, face: null };
    
    ['front', 'back', 'face'].forEach(type => {
        const camera = document.getElementById('camera' + capitalize(type));
        const preview = document.getElementById('preview' + capitalize(type));
        const overlay = camera.querySelector('.camera-overlay');
        
        camera.classList.add('hidden');
        preview.classList.add('hidden');
        overlay.classList.remove('hidden');
        
        document.getElementById('btnStart' + capitalize(type)).classList.remove('hidden');
        document.getElementById('btnStart' + capitalize(type)).disabled = false;
        document.getElementById('btnCapture' + capitalize(type)).classList.add('hidden');
        document.getElementById('btnRetake' + capitalize(type)).classList.add('hidden');
    });
    
    document.getElementById('btnContinueStep2').disabled = true;
    document.getElementById('btnSubmit').disabled = true;
}

function showStep(step) {
    document.querySelectorAll('.form-step').forEach(el => {
        el.classList.remove('active');
        el.style.display = 'none';
    });
    document.querySelector(`.form-step[data-step="${step}"]`).style.display = 'block';
    document.querySelector(`.form-step[data-step="${step}"]`).classList.add('active');
}

function updateProgress() {
    document.getElementById('progressFill').style.width = ((currentStep - 1) / 3 * 100) + '%';
    document.querySelectorAll('.step').forEach((el, i) => {
        el.classList.remove('active', 'completed');
        if (i + 1 < currentStep) el.classList.add('completed');
        else if (i + 1 === currentStep) el.classList.add('active');
    });
}

function nextStep() {
    if (currentStep === 1) {
        if (!validateStep1()) return;
        currentStep = 2;
        showStep(2);
        updateProgress();
    } else if (currentStep === 2) {
        if (!photos.front || !photos.back) {
            showToast('Please take or upload both ID photos');
            return;
        }
        currentStep = 3;
        showStep(3);
        updateProgress();
    }
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
        updateProgress();
    }
}

function validateStep1() {
    const fields = ['firstName', 'surname', 'nin', 'dateOfBirth', 'gender', 'phoneNumber', 'relationship', 'selectInmate'];
    let valid = true;
    fields.forEach(id => {
        const input = document.getElementById(id);
        if (!input.value.trim()) {
            input.classList.add('error');
            valid = false;
        } else {
            input.classList.remove('error');
        }
    });
    if (!valid) showToast('Please fill all fields');
    return valid;
}

async function startCamera(type) {
    const video = document.getElementById('video' + capitalize(type));
    const camera = document.getElementById('camera' + capitalize(type));
    const overlay = camera.querySelector('.camera-overlay');
    const startBtn = document.getElementById('btnStart' + capitalize(type));
    const captureBtn = document.getElementById('btnCapture' + capitalize(type));
    
    startBtn.disabled = true;
    startBtn.textContent = 'Starting...';
    
    try {
        if (streams[type]) {
            streams[type].getTracks().forEach(t => t.stop());
            streams[type] = null;
        }
        
        const stream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                facingMode: 'user',
                width: { ideal: 1280 },
                height: { ideal: 720 }
            }, 
            audio: false 
        });
        
        streams[type] = stream;
        video.srcObject = stream;
        
        video.onloadedmetadata = () => {
            video.play().then(() => {
                overlay.classList.add('hidden');
                camera.classList.remove('hidden');
                startBtn.classList.add('hidden');
                captureBtn.classList.remove('hidden');
                captureBtn.disabled = false;
                captureBtn.textContent = 'Capture Photo';
                showToast('Camera ready! Click Capture Photo');
            });
        };
        
    } catch (err) {
        console.error(err);
        startBtn.disabled = false;
        startBtn.textContent = 'Start Camera';
        if (err.name === 'NotAllowedError') {
            showToast('Camera blocked! Allow camera in browser settings.');
        } else {
            showToast('Camera error. Use Upload button.');
        }
    }
}

async function capturePhoto(type) {
    const video = document.getElementById('video' + capitalize(type));
    const canvas = document.getElementById('canvas' + capitalize(type));
    const camera = document.getElementById('camera' + capitalize(type));
    const preview = document.getElementById('preview' + capitalize(type));
    const previewImg = document.getElementById('preview' + capitalize(type) + 'Img');
    const captureBtn = document.getElementById('btnCapture' + capitalize(type));
    const retakeBtn = document.getElementById('btnRetake' + capitalize(type));
    
    if (!video || !video.srcObject) {
        showToast('Camera not started. Click Start Camera first.');
        return;
    }
    
    captureBtn.textContent = 'Capturing...';
    captureBtn.disabled = true;
    
    try {
        if (video.readyState < 2) {
            await new Promise(resolve => {
                video.onloadeddata = resolve;
                setTimeout(resolve, 500);
            });
        }
        
        if (!video.videoWidth || !video.videoHeight || video.videoWidth === 0) {
            showToast('Camera not ready. Try again.');
            captureBtn.textContent = 'Capture Photo';
            captureBtn.disabled = false;
            return;
        }
        
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#000';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        photos[type] = canvas.toDataURL('image/jpeg', 0.85);
        
        const testImg = new Image();
        testImg.onload = () => {
            previewImg.src = photos[type];
            camera.classList.add('hidden');
            preview.classList.remove('hidden');
            captureBtn.classList.add('hidden');
            retakeBtn.classList.remove('hidden');
            
            if (streams[type]) {
                streams[type].getTracks().forEach(t => t.stop());
                streams[type] = null;
            }
            
            showToast(capitalize(type) + ' photo captured!');
            checkPhotos(type);
        };
        testImg.onerror = () => {
            showToast('Capture failed. Try again.');
            captureBtn.textContent = 'Capture Photo';
            captureBtn.disabled = false;
        };
        testImg.src = photos[type];
        
    } catch (err) {
        console.error(err);
        showToast('Capture failed. Try again.');
        captureBtn.textContent = 'Capture Photo';
        captureBtn.disabled = false;
    }
}

function uploadPhoto(type, input) {
    const file = input.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = (e) => {
        photos[type] = e.target.result;
        document.getElementById('preview' + capitalize(type) + 'Img').src = photos[type];
        document.getElementById('preview' + capitalize(type)).classList.remove('hidden');
        document.getElementById('camera' + capitalize(type)).classList.add('hidden');
        document.getElementById('btnStart' + capitalize(type)).classList.add('hidden');
        document.getElementById('btnRetake' + capitalize(type)).classList.remove('hidden');
        
        showToast(capitalize(type) + ' uploaded!');
        checkPhotos(type);
    };
    reader.readAsDataURL(file);
}

function retakePhoto(type) {
    photos[type] = null;
    
    const camera = document.getElementById('camera' + capitalize(type));
    const preview = document.getElementById('preview' + capitalize(type));
    const overlay = camera.querySelector('.camera-overlay');
    
    preview.classList.add('hidden');
    document.getElementById('btnRetake' + capitalize(type)).classList.add('hidden');
    camera.classList.remove('hidden');
    overlay.classList.remove('hidden');
    document.getElementById('btnStart' + capitalize(type)).classList.remove('hidden');
    document.getElementById('btnStart' + capitalize(type)).disabled = false;
    document.getElementById('btnStart' + capitalize(type)).textContent = 'Start Camera';
    
    checkPhotos(type);
}

function checkPhotos(type) {
    if (type === 'front' || type === 'back') {
        document.getElementById('btnContinueStep2').disabled = !(photos.front && photos.back);
    } else if (type === 'face') {
        document.getElementById('btnSubmit').disabled = !photos.face;
    }
}

function stopAllStreams() {
    Object.values(streams).forEach(s => {
        if (s) s.getTracks().forEach(t => t.stop());
    });
    streams = { front: null, back: null, face: null };
}

function convertDate(dateStr) {
    const parts = dateStr.split('/');
    if (parts.length === 3) return parts[2] + '-' + parts[1] + '-' + parts[0];
    return dateStr;
}

async function submitRegistration() {
    if (!photos.face) {
        showToast('Please take or upload selfie');
        return;
    }
    
    showLoading('Submitting registration...');
    
    const dob = document.getElementById('dateOfBirth').value;
    
    const data = {
        firstName: document.getElementById('firstName').value.trim(),
        surname: document.getElementById('surname').value.trim(),
        otherName: document.getElementById('otherName').value.trim(),
        nin: document.getElementById('nin').value.trim(),
        dateOfBirth: convertDate(dob),
        gender: document.getElementById('gender').value,
        phoneNumber: document.getElementById('phoneNumber').value.trim(),
        relationship: document.getElementById('relationship').value,
        inmateName: document.getElementById('selectInmate').value.trim(),
        idFront: photos.front,
        idBack: photos.back,
        faceImage: photos.face
    };
    
    try {
        const res = await fetch(API_BASE + '/visitors/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await res.json();
        
        if (res.ok) {
            currentStep = 4;
            showStep(4);
            updateProgress();
            document.getElementById('successContainer').classList.remove('hidden');
            document.getElementById('errorContainer').classList.add('hidden');
            document.getElementById('successMessage').textContent = result.message || 'Registration Successful!';
        } else {
            showToast(result.error || 'Registration failed');
        }
    } catch (err) {
        console.error(err);
        showToast('Server error. Make sure server is running.');
    } finally {
        hideLoading();
    }
}

function retryRegistration() {
    resetForm();
    currentStep = 1;
    showStep(1);
    updateProgress();
}

function startNewRegistration() {
    resetForm();
    goBack();
}

function showLoading(msg) {
    document.getElementById('loadingMessage').textContent = msg;
    document.getElementById('loadingOverlay').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.add('hidden');
}

function showToast(msg) {
    document.getElementById('toastMessage').textContent = msg;
    document.getElementById('toast').classList.remove('hidden');
    setTimeout(() => document.getElementById('toast').classList.add('hidden'), 3000);
}

document.getElementById('dateOfBirth').addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g, '');
    if (v.length >= 2) v = v.substring(0,2) + '/' + v.substring(2);
    if (v.length >= 5) v = v.substring(0,5) + '/' + v.substring(5,9);
    e.target.value = v.substring(0, 10);
});

window.addEventListener('beforeunload', stopAllStreams);

console.log('UGANDA PRISONS - LUZIRA REGISTRATION');
