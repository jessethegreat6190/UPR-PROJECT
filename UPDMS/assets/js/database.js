// UPS Database System - Triple Layer Fallback
// 1. Firebase (cloud) - if configured
// 2. PHP/MySQL (local XAMPP) - if available  
// 3. localStorage (offline) - always works

const UPS_DB = {
  // Initialize and determine which backend to use
  async init() {
    // Try Firebase first
    this.firebaseReady = false;
    try {
      if (typeof firebase !== 'undefined') {
        const fb = await initFirebase();
        if (fb && fb.db) {
          this.firebaseReady = true;
          console.log('Using Firebase backend');
          return 'firebase';
        }
      }
    } catch(e) {
      console.log('Firebase not available');
    }
    
    // Try PHP API (XAMPP)
    try {
      const test = await fetch('api/dashboard.php?action=test', { method: 'GET' });
      if (test.ok) {
        this.phpReady = true;
        console.log('Using PHP/MySQL backend');
        return 'php';
      }
    } catch(e) {
      console.log('PHP API not available');
    }
    
    // Fall back to localStorage
    this.localReady = true;
    console.log('Using localStorage backend');
    return 'localStorage';
  },
  
  getBackend() {
    if (this.firebaseReady) return 'firebase';
    if (this.phpReady) return 'php';
    return 'localStorage';
  },

  // ===== VEHICLES =====
  async getVehicles() {
    if (this.firebaseReady) {
      const fb = await initFirebase();
      const snap = await fb.db.collection('vehicles').get();
      return snap.docs.map(d => ({ id: d.id, ...d.data() }));
    }
    
    if (this.phpReady) {
      const res = await fetch('api/delivery.php?action=list_vehicles');
      const data = await res.json();
      return data.vehicles || [];
    }
    
    // localStorage fallback
    return JSON.parse(localStorage.getItem('ups_vehicles') || '[]');
  },
  
  async addVehicle(vehicle) {
    const data = { 
      ...vehicle, 
      createdAt: new Date().toISOString(),
      status: 'active'
    };
    
    if (this.firebaseReady) {
      const fb = await initFirebase();
      const doc = await fb.db.collection('vehicles').add(data);
      return { id: doc.id, ...data };
    }
    
    if (this.phpReady) {
      const res = await fetch('api/delivery.php?action=register_vehicle', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(vehicle)
      });
      return await res.json();
    }
    
    // localStorage fallback
    const vehicles = JSON.parse(localStorage.getItem('ups_vehicles') || '[]');
    data.id = 'v-' + Date.now();
    vehicles.push(data);
    localStorage.setItem('ups_vehicles', JSON.stringify(vehicles));
    return data;
  },
  
  // ===== VISITORS =====
  async getVisitors() {
    if (this.firebaseReady) {
      const fb = await initFirebase();
      const snap = await fb.db.collection('frequent_visitors').get();
      return snap.docs.map(d => ({ id: d.id, ...d.data() }));
    }
    
    if (this.phpReady) {
      const res = await fetch('api/delivery.php?action=list_visitors');
      const data = await res.json();
      return data.visitors || [];
    }
    
    return JSON.parse(localStorage.getItem('ups_visitors') || '[]');
  },
  
  async addVisitor(visitor) {
    const data = { ...visitor, createdAt: new Date().toISOString(), status: 'active' };
    
    if (this.firebaseReady) {
      const fb = await initFirebase();
      const doc = await fb.db.collection('frequent_visitors').add(data);
      return { id: doc.id, ...data };
    }
    
    if (this.phpReady) {
      const res = await fetch('api/delivery.php?action=register_visitor', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(visitor)
      });
      return await res.json();
    }
    
    const visitors = JSON.parse(localStorage.getItem('ups_visitors') || '[]');
    data.id = 'f-' + Date.now();
    visitors.push(data);
    localStorage.setItem('ups_visitors', JSON.stringify(visitors));
    return data;
  },
  
  // ===== VISITS / DELIVERIES =====
  async getActiveDeliveries() {
    if (this.firebaseReady) {
      const fb = await initFirebase();
      const snap = await fb.db.collection('deliveries').where('status', '==', 'inside').get();
      return snap.docs.map(d => ({ id: d.id, ...d.data() }));
    }
    
    if (this.phpReady) {
      const res = await fetch('api/delivery.php?action=get_inside');
      const data = await res.json();
      return data.deliveries || [];
    }
    
    return JSON.parse(localStorage.getItem('ups_deliveries') || '[]')
      .filter(d => d.status === 'inside');
  },
  
  async recordEntry(entryData) {
    const data = { ...entryData, entryTime: new Date().toISOString(), status: 'inside' };
    
    if (this.firebaseReady) {
      const fb = await initFirebase();
      const doc = await fb.db.collection('deliveries').add(data);
      return { id: doc.id, ...data };
    }
    
    if (this.phpReady) {
      const res = await fetch('api/delivery.php?action=record_entry', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(entryData)
      });
      return await res.json();
    }
    
    const deliveries = JSON.parse(localStorage.getItem('ups_deliveries') || '[]');
    data.id = 'd-' + Date.now();
    deliveries.push(data);
    localStorage.setItem('ups_deliveries', JSON.stringify(deliveries));
    return data;
  },
  
  async recordExit(codeOrPlate) {
    if (this.firebaseReady) {
      const fb = await initFirebase();
      const snap = await fb.db.collection('deliveries').where('status', '==', 'inside').get();
      for (const doc of snap.docs) {
        const d = doc.data();
        if (d.vehiclePlate === codeOrPlate || d.qrCode === codeOrPlate) {
          await fb.db.collection('deliveries').doc(doc.id).update({
            status: 'exited',
            exitTime: new Date().toISOString()
          });
          return { id: doc.id, ...d };
        }
      }
      return null;
    }
    
    if (this.phpReady) {
      const res = await fetch('api/delivery.php?action=record_exit', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ vehicle_plate: codeOrPlate, qr_code: codeOrPlate })
      });
      return await res.json();
    }
    
    // localStorage fallback
    const deliveries = JSON.parse(localStorage.getItem('ups_deliveries') || '[]');
    for (const d of deliveries) {
      if (d.status === 'inside' && (d.vehiclePlate === codeOrPlate || d.qrCode === codeOrPlate)) {
        d.status = 'exited';
        d.exitTime = new Date().toISOString();
        localStorage.setItem('ups_deliveries', JSON.stringify(deliveries));
        return d;
      }
    }
    return null;
  },
  
  // ===== CHECK REGISTRATION =====
  async checkVehicle(plate) {
    const vehicles = await this.getVehicles();
    return vehicles.find(v => 
      v.vehicle_plate?.toUpperCase() === plate.toUpperCase() && v.status === 'active'
    );
  },
  
  async checkVisitor(codeOrId) {
    const visitors = await this.getVisitors();
    return visitors.find(v => 
      (v.qr_code === codeOrId || v.id_number === codeOrId) && v.status === 'active'
    );
  }
};

// Make globally available
window.UPS_DB = UPS_DB;