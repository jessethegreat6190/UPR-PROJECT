# Firebase Setup Instructions

## IMPORTANT: Triple-Layer Backup System

This system has **3 automatic fallbacks**:

1. **Firebase** (Cloud) - If configured with your Firebase project
2. **PHP/MySQL** (Local XAMPP) - Works immediately on XAMPP
3. **localStorage** (Offline) - Always works, no server needed

The system automatically detects which backend to use!

## Step 1: Create Firebase Project
1. Go to [firebase.google.com](https://firebase.google.com)
2. Click "Go to Console"
3. Click "Add Project"
4. Name it: `uganda-prisons-dms`
5. Disable Google Analytics (optional)
6. Create Project

## Step 2: Get Configuration
1. In Firebase Console, go to **Project Settings** (gear icon)
2. Scroll to **Your apps** and click **</>** (web)
3. Register app (name: UPDMS)
4. Copy the `firebaseConfig` object

## Step 3: Update Configuration
Open `assets/js/firebase.js` and replace with your credentials:
```javascript
const firebaseConfig = {
  apiKey: "YOUR_API_KEY",
  authDomain: "YOUR_PROJECT.firebaseapp.com",
  projectId: "YOUR_PROJECT_ID",
  storageBucket: "YOUR_PROJECT.appspot.com",
  messagingSenderId: "YOUR_SENDER_ID",
  appId: "YOUR_APP_ID"
};
```

## Step 4: Enable Firestore
1. In Firebase Console, go to **Firestore Database**
2. Click **Create Database**
3. Start in **Test Mode** (allows read/write for 30 days)
4. Choose location (nearest to Uganda: `europe-west1` or `us-central1`)

## Step 5: Deploy to GitHub Pages + Firebase
1. In Firebase Console, go to **Hosting**
2. Click "Get Started"
3. Run: `firebase init hosting` in your project folder
4. Connect to GitHub and select your repository
5. Set up as single-page app: Yes
6. Override index.html: Yes (use existing)
7. Run: `firebase deploy`

## Alternative: Manual Firebase Hosting
```bash
npm install -g firebase-tools
firebase login
firebase init hosting
firebase deploy
```

## Environment Variables (for GitHub Actions)
In your GitHub repo Settings → Secrets, add:
- `FIREBASE_TOKEN` - Get from: `firebase login:ci`
- `FIREBASE_PROJECT_ID` - Your project ID (e.g., uganda-prisons-dms)

## Testing Locally
Without Firebase (uses localStorage fallback):
- Just open `first_screen/index.html` in browser
- Works without any server!

With Firebase:
```bash
firebase serve
# Open http://localhost:5000
```

## File Structure for Firebase Hosting
```
UPDMS/
├── first_screen/          # Main entry
├── delivery/              # Vehicle registration & gate control
├── hospital/              # Hospital registration
├── official_visits/       # Official visits
├── visiting_inmate/       # Inmate visits (static only)
├── assets/
│   ├── css/
│   ├── js/
│   │   ├── firebase.js   # Firebase configuration
│   │   └── registration.js
│   └── images/
├── .github/workflows/
│   └── firebase-deploy.yml
├── firebase.json
├── firestore.rules
└── firestore.indexes.json
```

## Features That Work Without Backend
- ✅ Local registration in localStorage
- ✅ QR code scanning
- ✅ Vehicle/Visitor registration forms
- ✅ Gate control interface

## Features Requiring Firebase
- ✅ Cloud data persistence across devices
- ✅ Real-time sync between multiple gate terminals
- ✅ Historical visit logs
- ✅ Admin dashboard

## Troubleshooting
- **CORS errors**: Check firestore.rules allow access
- **Auth errors**: Run in test mode or set up Authentication
- **Deploy errors**: Check .gitignore excludes needed files