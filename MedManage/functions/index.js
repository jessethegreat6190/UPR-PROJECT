const functions = require('firebase-functions');
const admin = require('firebase-admin');
const cors = require('cors')({ origin: true });

admin.initializeApp();
const db = admin.firestore();

exports.api = functions.https.onRequest((req, res) => {
  cors(req, res, () => {
    const path = req.path.split('/').filter(Boolean);
    const action = path[0] || req.query.action;

    switch (action) {
      case 'dashboard':
        handleDashboard(req, res);
        break;
      case 'medicines':
        handleMedicines(req, res);
        break;
      case 'sales':
        handleSales(req, res);
        break;
      default:
        res.status(404).json({ success: false, message: 'Invalid endpoint' });
    }
  });
});

async function handleDashboard(req, res) {
  try {
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const weekStart = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
    const monthStart = new Date(now.getFullYear(), now.getMonth(), 1);

    const [dailySnap, weeklySnap, monthlySnap, medicinesSnap, batchesSnap] = await Promise.all([
      db.collection('sales').where('saleDate', '>=', today.toISOString()).get(),
      db.collection('sales').where('saleDate', '>=', weekStart.toISOString()).get(),
      db.collection('sales').where('saleDate', '>=', monthStart.toISOString()).get(),
      db.collection('medicines').get(),
      db.collection('batches').where('remainingQty', '>', 0).get()
    ]);

    const daily = { total: 0, profit: 0 };
    dailySnap.forEach(doc => {
      const d = doc.data();
      daily.total += d.totalPrice || 0;
      daily.profit += d.profit || 0;
    });

    const weekly = { total: 0, profit: 0 };
    weeklySnap.forEach(doc => {
      const d = doc.data();
      weekly.total += d.totalPrice || 0;
      weekly.profit += d.profit || 0;
    });

    const monthly = { total: 0, profit: 0 };
    monthlySnap.forEach(doc => {
      const d = doc.data();
      monthly.total += d.totalPrice || 0;
      monthly.profit += d.profit || 0;
    });

    const lowStock = [];
    const expiryWarning = new Date(now.getTime() + 90 * 24 * 60 * 60 * 1000);
    const expiring = [];

    medicinesSnap.forEach(doc => {
      const m = doc.data();
      if (m.totalStock <= m.lowStockThreshold) {
        lowStock.push({ id: doc.id, ...m });
      }
    });

    const batchMap = {};
    batchesSnap.forEach(doc => {
      const b = doc.data();
      const medName = medicinesSnap.docs.find(d => d.id === b.medicineId)?.data().name || 'Unknown';
      if (new Date(b.expiryDate) <= expiryWarning && new Date(b.expiryDate) > today) {
        expiring.push({
          id: doc.id,
          medicineId: b.medicineId,
          medicineName: medName,
          batchNumber: b.batchNumber,
          expiryDate: b.expiryDate,
          remainingQty: b.remainingQty
        });
      }
    });

    res.json({
      success: true,
      data: {
        daily: { total: daily.total, profit: daily.profit },
        weekly: { total: weekly.total, profit: weekly.profit },
        monthly: { total: monthly.total, profit: monthly.profit },
        lowStock: lowStock.sort((a, b) => a.totalStock - b.totalStock),
        expiring: expiring.sort((a, b) => new Date(a.expiryDate) - new Date(b.expiryDate)),
        alertCount: lowStock.length + expiring.length
      }
    });
  } catch (error) {
    res.status(500).json({ success: false, message: error.message });
  }
}

async function handleMedicines(req, res) {
  const action = req.query.action;

  try {
    switch (action) {
      case 'getAll':
        await getAllMedicines(req, res);
        break;
      case 'search':
        await searchMedicines(req, res);
        break;
      case 'add':
        await addMedicine(req, res);
        break;
      case 'edit':
        await editMedicine(req, res);
        break;
      case 'delete':
        await deleteMedicine(req, res);
        break;
      case 'get':
        await getMedicine(req, res);
        break;
      default:
        res.json({ success: false, message: 'Invalid action' });
    }
  } catch (error) {
    res.status(500).json({ success: false, message: error.message });
  }
}

async function getAllMedicines(req, res) {
  const search = req.query.search || '';
  const page = parseInt(req.query.page) || 1;
  const limit = 10;
  const offset = (page - 1) * limit;

  let query = db.collection('medicines').orderBy('name');
  
  const snapshot = await query.get();
  let medicines = [];
  
  snapshot.forEach(doc => {
    medicines.push({ id: doc.id, ...doc.data() });
  });

  if (search) {
    medicines = medicines.filter(m => 
      m.name.toLowerCase().includes(search.toLowerCase())
    );
  }

  const total = medicines.length;
  medicines = medicines.slice(offset, offset + limit);

  res.json({
    success: true,
    data: {
      medicines,
      totalPages: Math.ceil(total / limit),
      currentPage: page
    }
  });
}

async function searchMedicines(req, res) {
  const search = req.query.q || '';
  const snapshot = await db.collection('medicines')
    .where('name', '>=', search)
    .where('name', '<=', search + '\uf8ff')
    .where('totalStock', '>', 0)
    .limit(10)
    .get();

  const medicines = [];
  const now = new Date();
  
  for (const doc of snapshot.docs) {
    const m = doc.data();
    const batchesSnap = await db.collection('batches')
      .where('medicineId', '==', doc.id)
      .where('remainingQty', '>', 0)
      .get();
    
    const validBatches = batchesSnap.docs.filter(b => {
      const bd = b.data();
      return new Date(bd.expiryDate) > now;
    });

    if (validBatches.length > 0) {
      medicines.push({ id: doc.id, ...m });
    }
  }

  res.json({ success: true, data: medicines });
}

async function addMedicine(req, res) {
  const data = req.body;
  
  if (!data.name || !data.unitPrice || !data.totalStock) {
    res.json({ success: false, message: 'Please fill all required fields' });
    return;
  }

  const medicineRef = db.collection('medicines').doc();
  const medicineId = medicineRef.id;

  await db.runTransaction(async (t) => {
    await t.set(medicineRef, {
      name: data.name,
      category: data.category || 'tablet',
      unitPrice: parseFloat(data.unitPrice),
      costPrice: parseFloat(data.costPrice) || 0,
      totalStock: parseInt(data.totalStock),
      lowStockThreshold: parseInt(data.lowStockThreshold) || 10,
      createdAt: admin.firestore.FieldValue.serverTimestamp()
    });

    const batchRef = db.collection('batches').doc();
    await t.set(batchRef, {
      medicineId: medicineId,
      batchNumber: data.batchNumber || '',
      expiryDate: data.expiryDate || new Date(Date.now() + 365 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
      quantity: parseInt(data.totalStock),
      remainingQty: parseInt(data.totalStock),
      createdAt: admin.firestore.FieldValue.serverTimestamp()
    });
  });

  res.json({ success: true, message: 'Medicine added successfully' });
}

async function editMedicine(req, res) {
  const data = req.body;
  
  if (!data.id || !data.name || !data.unitPrice) {
    res.json({ success: false, message: 'Please fill all required fields' });
    return;
  }

  await db.collection('medicines').doc(data.id).update({
    name: data.name,
    category: data.category || 'tablet',
    unitPrice: parseFloat(data.unitPrice),
    costPrice: parseFloat(data.costPrice) || 0,
    lowStockThreshold: parseInt(data.lowStockThreshold) || 10
  });

  res.json({ success: true, message: 'Medicine updated successfully' });
}

async function deleteMedicine(req, res) {
  const id = req.query.id;
  
  await db.collection('medicines').doc(id).delete();
  
  const batchesSnap = await db.collection('batches').where('medicineId', '==', id).get();
  const batchDeletes = batchesSnap.docs.map(d => d.ref.delete());
  await Promise.all(batchDeletes);

  res.json({ success: true, message: 'Medicine deleted' });
}

async function getMedicine(req, res) {
  const id = req.query.id;
  const doc = await db.collection('medicines').doc(id).get();
  
  if (!doc.exists) {
    res.json({ success: false, message: 'Medicine not found' });
    return;
  }

  res.json({ success: true, data: { id: doc.id, ...doc.data() } });
}

async function handleSales(req, res) {
  const action = req.query.action;

  try {
    switch (action) {
      case 'create':
        await createSale(req, res);
        break;
      case 'getAll':
        await getAllSales(req, res);
        break;
      default:
        res.json({ success: false, message: 'Invalid action' });
    }
  } catch (error) {
    res.status(500).json({ success: false, message: error.message });
  }
}

async function createSale(req, res) {
  const data = req.body;
  const medicineId = data.medicineId;
  const qtySold = parseInt(data.qtySold);
  const paymentMethod = data.paymentMethod || 'Cash';

  if (!medicineId || !qtySold || qtySold <= 0) {
    res.json({ success: false, message: 'Invalid input' });
    return;
  }

  const medicineDoc = await db.collection('medicines').doc(medicineId).get();
  if (!medicineDoc.exists) {
    res.json({ success: false, message: 'Medicine not found' });
    return;
  }

  const medicine = medicineDoc.data();

  if (medicine.totalStock < qtySold) {
    res.json({ success: false, message: `Insufficient stock. Available: ${medicine.totalStock}` });
    return;
  }

  const now = new Date();
  const expiredCheck = await db.collection('batches')
    .where('medicineId', '==', medicineId)
    .where('remainingQty', '>', 0)
    .get();

  let expiredQty = 0;
  expiredCheck.docs.forEach(doc => {
    const b = doc.data();
    if (new Date(b.expiryDate) <= now) {
      expiredQty += b.remainingQty;
    }
  });

  if (expiredQty > 0 && expiredQty >= medicine.totalStock) {
    res.json({ success: false, message: 'Cannot sell - medicine has expired stock. Please remove expired batches first.' });
    return;
  }

  const totalPrice = medicine.unitPrice * qtySold;
  const costAmount = medicine.costPrice * qtySold;
  const profit = totalPrice - costAmount;

  await db.runTransaction(async (t) => {
    const saleRef = db.collection('sales').doc();
    await t.set(saleRef, {
      medicineId,
      medicineName: medicine.name,
      qtySold,
      totalPrice,
      costAmount,
      profit,
      paymentMethod,
      saleDate: admin.firestore.FieldValue.serverTimestamp()
    });

    let remaining = qtySold;
    const batchesSnap = await db.collection('batches')
      .where('medicineId', '==', medicineId)
      .where('remainingQty', '>', 0)
      .orderBy('expiryDate')
      .get();

    for (const batchDoc of batchesSnap.docs) {
      if (remaining <= 0) break;
      const batch = batchDoc.data();
      const deduct = Math.min(batch.remainingQty, remaining);
      await t.update(batchDoc.ref, { remainingQty: batch.remainingQty - deduct });
      remaining -= deduct;
    }

    const newStock = medicine.totalStock - qtySold;
    await t.update(medicineDoc.ref, { totalStock: newStock });
  });

  res.json({ success: true, message: 'Sale recorded successfully', profit });
}

async function getAllSales(req, res) {
  const page = parseInt(req.query.page) || 1;
  const limit = 10;
  const offset = (page - 1) * limit;

  const snapshot = await db.collection('sales').orderBy('saleDate', 'desc').get();
  
  let sales = [];
  snapshot.forEach(doc => {
    sales.push({ id: doc.id, ...doc.data() });
  });

  const total = sales.length;
  sales = sales.slice(offset, offset + limit);

  res.json({
    success: true,
    data: {
      sales,
      totalPages: Math.ceil(total / limit),
      currentPage: page
    }
  });
}
