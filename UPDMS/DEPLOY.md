# UPDMS - Deployment Guide

This guide covers deploying the Uganda Prisons Digital Management System to:
1. **GitHub Pages** (Free, static hosting)
2. **Firebase Hosting** (Free tier + cloud database)

---

## OPTION 1: GITHUB PAGES (Recommended for Start)

### Prerequisites
- GitHub account
- Repository with code pushed

### Steps to Deploy

1. **Enable GitHub Pages:**
   - Go to your GitHub repository
   - Settings → Pages (left sidebar)
   - Source: Select **Deploy from a branch**
   - Branch: Select **main** (or master)
   - Folder: **/(root)**
   - Click Save

2. **Workflow is already configured!**
   - File: `.github/workflows/github-pages.yml`
   - It will auto-deploy on every push to main

3. **Your site will be live at:**
   ```
   https://YOUR_USERNAME.github.io/REPOSITORY_NAME/
   ```

### What Works on GitHub Pages
- ✅ All static pages (HTML/CSS/JS)
- ✅ localStorage fallback (no database needed)
- ✅ Works offline!
- ⚠️ PHP features won't work (use localStorage instead)

---

## OPTION 2: FIREBASE HOSTING (With Cloud Database)

### Step 1: Create Firebase Project

1. Go to [firebase.google.com](https://firebase.google.com)
2. Click **Go to Console**
3. Click **Add Project**
4. Name: `uganda-prisons-dms` (or any name)
5. Disable Google Analytics (optional)
6. Click **Create Project**

### Step 2: Get Configuration

1. Click **Project Settings** (⚙️ icon)
2. Scroll to **Your apps**
3. Click **Web** (</>) icon
4. App nickname: `UPDMS`
5. Click **Register app**
6. **Copy the `firebaseConfig` object**

### Step 3: Update Config File

Open `assets/js/firebase.js` and replace:

```javascript
const firebaseConfig = {
  apiKey: "PASTE_YOUR_API_KEY",
  authDomain: "PASTE_YOUR_PROJECT.firebaseapp.com",
  projectId: "PASTE_YOUR_PROJECT_ID",
  storageBucket: "PASTE_YOUR_PROJECT.appspot.com",
  messagingSenderId: "PASTE_YOUR_SENDER_ID",
  appId: "PASTE_YOUR_APP_ID"
};
```

### Step 4: Enable Firestore Database

1. In Firebase Console, click **Firestore Database** (left sidebar)
2. Click **Create Database**
3. Location: `europe-west1` (closest to Uganda)
4. Start in: **Test Mode** (allows read/write for 30 days)
5. Click **Enable**

### Step 5: Deploy to Firebase

**Option A: Using Firebase CLI**
```bash
# Install Firebase CLI
npm install -g firebase-tools

# Login to Firebase
firebase login

# Initialize Firebase in project
firebase init hosting

# Deploy
firebase deploy
```

**Option B: Using GitHub Actions** (will auto-deploy)
- In Firebase Console → Hosting → Connect GitHub
- Follow the wizard to connect your repo
- It will auto-deploy on push!

### Your Firebase URL will be:
```
https://YOUR_PROJECT_ID.web.app
```

---

## QUICK START (No Setup Required!)

The system works **without any server**:

1. **Local Storage (Offline)**
   - Just open `index.html` in any browser
   - Everything saves to browser localStorage

2. **XAMPP (Local Server)**
   - Start Apache & MySQL in XAMPP
   - Database `ups_delivery` auto-creates
   - Access: `http://localhost/UPDMS`

3. **GitHub Pages (Cloud Static)**
   - Just push to GitHub + enable Pages
   - Works fully with localStorage

---

## FEATURE COMPARISON

| Feature | GitHub Pages | Firebase | XAMPP |
|---------|-------------|----------|-------|
| Hosting Cost | Free | Free tier | Free |
| Database | localStorage | Firestore | MySQL |
| Works Offline | ✅ | ❌ | ❌ |
| Real-time Sync | ❌ | ✅ | Local only |
| Setup Time | 5 min | 15 min | 5 min |

---

## NEED HELP?

- **GitHub Issues**: https://github.com/jessethegreat6190/UPR-PROJECT/issues
- **Firebase Docs**: https://firebase.google.com/docs/hosting
- **GitHub Pages Docs**: https://docs.github.com/pages