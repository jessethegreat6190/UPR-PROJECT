// Offline capability - works with localStorage when offline
// Add this script to all pages

(function() {
  const OFFLINE_KEY = 'ups_offline_mode';
  let isOffline = !navigator.onLine;
  
  function updateUI() {
    // Create or update offline indicator
    let indicator = document.getElementById('offlineIndicator');
    if (!indicator) {
      indicator = document.createElement('div');
      indicator.id = 'offlineIndicator';
      indicator.innerHTML = '<i class="fas fa-wifi"></i> OFFLINE MODE';
      indicator.style.cssText = `
        position: fixed; bottom: 20px; right: 20px; 
        background: #FFC107; color: #333; 
        padding: 12px 20px; border-radius: 8px;
        font-weight: 700; font-size: 0.85rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        z-index: 10000;
      `;
      document.body.appendChild(indicator);
    }
    
    indicator.style.display = isOffline ? 'block' : 'none';
    
    // Update all forms to show offline badge
    document.querySelectorAll('.form-card h3').forEach(h3 => {
      if (!h3.querySelector('.offline-badge')) {
        const badge = document.createElement('span');
        badge.className = 'offline-badge';
        badge.innerHTML = ' <i class="fas fa-cloud-download-alt"></i>';
        badge.style.fontSize = '0.8rem';
        h3.appendChild(badge);
      }
    });
  }
  
  function handleOnline() {
    isOffline = false;
    localStorage.setItem(OFFLINE_KEY, 'false');
    updateUI();
    console.log('UPS: Back online');
  }
  
  function handleOffline() {
    isOffline = true;
    localStorage.setItem(OFFLINE_KEY, 'true');
    updateUI();
    console.log('UPS: Working offline');
  }
  
  // Listen for status changes
  window.addEventListener('online', handleOnline);
  window.addEventListener('offline', handleOffline);
  
  // Check initial state
  isOffline = !navigator.onLine;
  localStorage.setItem(OFFLINE_KEY, isOffline ? 'true' : 'false');
  updateUI();
  
  // Extend localStorage for offline data sync
  const originalSetItem = localStorage.setItem.bind(localStorage);
  localStorage.setItem = function(key, value) {
    originalSetItem(key, value);
    if (key.startsWith('ups_')) {
      // Sync to other browser tabs
      window.dispatchEvent(new StorageEvent('storage', {
        key: key,
        newValue: value
      }));
    }
  };
  
  window.UPS_OFFLINE = {
    isOnline: () => !isOffline,
    isOffline: () => isOffline,
    getData: (key) => {
      try {
        return JSON.parse(localStorage.getItem(key) || []);
      } catch (e) {
        return [];
      }
    },
    saveData: (key, data) => {
      localStorage.setItem(key, JSON.stringify(data));
    }
  };
})();