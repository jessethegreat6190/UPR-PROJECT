// UPS Registration System - Unified Backend
// Works with: Firebase → PHP/MySQL → localStorage

const UPS_REG = window.UPS_DB || {
  STORAGE_KEY: 'ups_active_visits',
  
  async init() { return 'localStorage'; },
  
  getActive() {
    return JSON.parse(localStorage.getItem(this.STORAGE_KEY) || '[]');
  },
  
  async signIn(data) {
    if (window.UPS_DB && window.UPS_DB.recordEntry) {
      return await window.UPS_DB.recordEntry({
        ref: data.ref || 'VIS-' + Date.now(),
        name: data.name,
        phone: data.phone,
        module: data.module,
        destination: data.destination || '',
        entryTime: new Date().toISOString(),
        status: 'active'
      });
    }
    
    // localStorage fallback
    const regs = this.getActive();
    const ref = data.ref || this.generateRef(data.module);
    const newReg = {
      ref,
      name: data.name,
      phone: data.phone,
      module: data.module,
      destination: data.destination || '',
      timeIn: new Date().toISOString(),
      status: 'active'
    };
    regs.push(newReg);
    localStorage.setItem(this.STORAGE_KEY, JSON.stringify(regs));
    return newReg;
  },
  
  async signOut(ref) {
    if (window.UPS_DB && window.UPS_DB.recordExit) {
      return await window.UPS_DB.recordExit(ref);
    }
    
    // localStorage fallback
    const regs = this.getActive();
    const idx = regs.findIndex(r => r.ref === ref && r.status === 'active');
    if (idx === -1) return null;
    
    regs[idx].status = 'signed_out';
    regs[idx].timeOut = new Date().toISOString();
    localStorage.setItem(this.STORAGE_KEY, JSON.stringify(regs));
    return regs[idx];
  },
  
  findByRef(ref) {
    return this.getActive().find(r => r.ref === ref);
  },
  
  generateRef(module) {
    const date = new Date();
    const dateStr = date.getFullYear() + 
      String(date.getMonth() + 1).padStart(2, '0') + 
      String(date.getDate()).padStart(2, '0');
    const rand = Math.floor(Math.random() * 9000 + 1000);
    return `${module?.toUpperCase() || 'VIS'}-${dateStr}-${rand}`;
  },
  
  formatDuration(timeIn) {
    const start = new Date(timeIn);
    const now = new Date();
    const mins = Math.floor((now - start) / 60000);
    if (mins < 60) return `${mins}min`;
    const hrs = Math.floor(mins / 60);
    const remMins = mins % 60;
    return `${hrs}h ${remMins}m`;
  },
  
  formatTime(isoString) {
    return new Date(isoString).toLocaleTimeString('en-GB', { 
      hour: '2-digit', minute: '2-digit' 
    });
  }
};

function renderActivePanel(containerId) {
  const container = document.getElementById(containerId);
  if (!container) return;
  
  // Use unified database
  const getAndRender = async () => {
    let regs = [];
    
    if (window.UPS_DB && window.UPS_DB.getActiveDeliveries) {
      const deliveries = await window.UPS_DB.getActiveDeliveries();
      regs = deliveries.map(d => ({
        ref: d.vehiclePlate || d.qrCode || d.id,
        name: d.name || d.visitorName || d.vehiclePlate || 'Visitor',
        timeIn: d.entryTime || d.entry_time,
        module: d.type || 'delivery'
      }));
    } else {
      // localStorage fallback
      regs = UPS_REG.getActive().filter(r => r.status === 'active');
    }
    
    if (regs.length === 0) {
      container.innerHTML = '<div style="color: #999; text-align: center; padding: 20px;">No active visits</div>';
      return;
    }
    
    container.innerHTML = regs.map(r => `
      <div style="background: rgba(255,255,255,0.1); padding: 12px; border-radius: 8px; margin-bottom: 10px; border-left: 3px solid var(--prison-gold, #FFD700);">
        <div style="display: flex; justify-content: space-between; align-items: start;">
          <div>
            <div style="font-weight: 700; color: #fff; font-size: 0.9rem;">${r.name}</div>
            <div style="color: #FFD700; font-size: 0.75rem; font-family: monospace;">${r.ref}</div>
            <div style="color: #aaa; font-size: 0.7rem;">${UPS_REG.formatTime(r.timeIn || r.entryTime)} • ${UPS_REG.formatDuration(r.timeIn || r.entryTime)}</div>
          </div>
          <button onclick="signOutAndRefresh('${r.ref}', '${containerId}')" 
            style="background: #ef4444; color: #fff; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.75rem;">
            Sign Out
          </button>
        </div>
      </div>
    `).join('');
  };
  
  getAndRender();
}

async function signOutAndRefresh(ref, containerId) {
  await UPS_REG.signOut(ref);
  toast('Signed out: ' + ref);
  renderActivePanel(containerId);
}

// Auto-refresh panel every 30 seconds
setInterval(() => {
  document.querySelectorAll('[id$="-panel"]').forEach(el => {
    if (el.dataset.autoRefresh !== 'false') {
      renderActivePanel(el.id);
    }
  });
}, 30000);