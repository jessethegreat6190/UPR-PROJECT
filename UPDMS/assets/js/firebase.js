// =====================================================
// UGANDA PRISONS DIGITAL MANAGEMENT SYSTEM - FIREBASE CONFIG
// =====================================================

// INSTRUCTIONS TO ENABLE FIREBASE:
// 1. Go to https://console.firebase.google.com
// 2. Create a new project: "uganda-prisons-dms"
// 3. In Project Settings → General → Your Apps → Web (</>)
// 4. Copy the firebaseConfig and paste below
// 5. In Firestore Database → Create Database → Start in Test Mode

const firebaseConfig = {
  // REPLACE THESE WITH YOUR FIREBASE PROJECT CONFIG
  apiKey: "YOUR_API_KEY_HERE",
  authDomain: "YOUR_PROJECT.firebaseapp.com",
  projectId: "YOUR_PROJECT_ID",
  storageBucket: "YOUR_PROJECT.appspot.com",
  messagingSenderId: "YOUR_SENDER_ID",
  appId: "YOUR_APP_ID"
};

// If you see "YOUR_API_KEY_HERE" above, Firebase is NOT configured
// The system will automatically use XAMPP (PHP/MySQL) or localStorage instead

let app = null;
let db = null;

async function initFirebase() {
  // Check if Firebase is properly configured
  if (firebaseConfig.apiKey === "YOUR_API_KEY_HERE") {
    console.log("Firebase not configured - using fallback");
    return null;
  }
  
  try {
    if (typeof firebase !== 'undefined') {
      app = firebase.initializeApp(firebaseConfig);
      db = firebase.firestore();
      
      // Enable offline persistence
      try {
        await db.enablePersistence({ synchronizeTabs: true });
      } catch(e) {
        console.log("Persistence not available:", e.code);
      }
      
      console.log("Firebase connected successfully!");
      return { app, db };
    }
  } catch (e) {
    console.log("Firebase initialization failed:", e.message);
  }
  
  return null;
}

// Export for use in other scripts
window.initFirebase = initFirebase;

// =====================================================
// GET YOUR FIREBASE CONFIG:
// =====================================================
// 
// 1. Go to: https://console.firebase.google.com
// 
// 2. Create new project or select existing
// 
// 3. Click ⚙️ (Settings) → General
// 
// 4. Scroll to "Your apps" → Click Web icon (</>)
// 
// 5. Register app → Copy the config object
// 
// 6. Replace the values above with your config
// 
// 7. ENABLE FIRESTORE:
//    - Go to "Firestore Database" in sidebar
//    - Click "Create Database"
//    - Start in "Test mode" (allows read/write for 30 days)
//    - Select location: europe-west1 (closest to Uganda)
// 
// =====================================================