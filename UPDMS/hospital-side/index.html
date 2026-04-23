<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>UPDS — Hospital Registration System v5.0</title>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=IBM+Plex+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"/>
<script src="https://unpkg.com/react@18/umd/react.development.js"></script>
<script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
<script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
<style>
:root {
  --black:    #0a0a0a;
  --gray-900: #111111;
  --gray-800: #1a1a1a;
  --gray-700: #2a2a2a;
  --gray-600: #3d3d3d;
  --gray-500: #5a5a5a;
  --gray-400: #888888;
  --gray-300: #b0b0b0;
  --gray-200: #d4d4d4;
  --gray-100: #e8e8e8;
  --gray-50:  #f5f5f5;
  --white:    #ffffff;
  --red:      #cc0000;
  --red-lite: #fff0f0;
  --amber:    #b45309;
  --amber-lite:#fffbeb;
  --blue:     #1d4ed8;
  --blue-lite:#eff6ff;
  --green:    #166534;
  --green-lite:#f0fdf4;
  --purple:   #7c3aed;
  --purple-lite:#faf5ff;

  --font-mono: 'IBM Plex Mono', 'Courier New', monospace;
  --font-body: 'IBM Plex Sans', -apple-system, sans-serif;
  --r-sm: 4px; --r-md: 8px;
  --shadow: 0 1px 3px rgba(0,0,0,.12);
  --shadow-md: 0 4px 12px rgba(0,0,0,.15);
  --trans: 0.15s ease;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{ font-family:var(--font-body);background:var(--gray-50);min-height:100vh;color:var(--black); }

/* SHELL */
#app-shell{ max-width:900px;margin:0 auto;box-shadow:0 0 0 1px var(--gray-200); }

/* HEADER */
.app-header{ background:var(--black);position:sticky;top:0;z-index:100; }
.header-top{ padding:16px 26px;display:flex;align-items:center;justify-content:space-between;gap:16px;border-bottom:1px solid var(--gray-800); }
.logo-block{ display:flex;align-items:center;gap:14px; }
.logo-mark{ width:42px;height:42px;border:2px solid var(--white);display:flex;align-items:center;justify-content:center;font-family:var(--font-mono);font-size:11px;font-weight:700;color:var(--white);letter-spacing:-1px;flex-shrink:0; }
.logo-title{ font-family:var(--font-mono);font-size:14px;font-weight:700;color:var(--white);letter-spacing:1px; }
.logo-sub{ font-size:10px;color:var(--gray-500);margin-top:2px;letter-spacing:2px;text-transform:uppercase; }
.header-pills{ display:flex;gap:8px; }
.h-pill{ text-align:center;border:1px solid var(--gray-700);padding:6px 14px; }
.h-pill-num{ font-family:var(--font-mono);font-size:18px;font-weight:700;color:var(--white); }
.h-pill-lbl{ font-size:9px;color:var(--gray-500);margin-top:2px;text-transform:uppercase;letter-spacing:1px; }
.clock-bar{ padding:5px 26px;display:flex;align-items:center;justify-content:space-between;font-family:var(--font-mono);font-size:10px;color:var(--gray-600); }
.live-dot{ width:5px;height:5px;border-radius:50%;background:var(--white);animation:pulse 2s infinite;display:inline-block;margin-right:6px; }
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.25}}

/* TABS */
.tab-bar{ background:var(--gray-900);display:flex;overflow-x:auto;scrollbar-width:none;border-bottom:1px solid var(--gray-800); }
.tab-bar::-webkit-scrollbar{display:none;}
.tab-btn{ padding:11px 18px;border:none;background:none;cursor:pointer;font-family:var(--font-mono);font-size:10px;font-weight:600;color:var(--gray-500);display:flex;align-items:center;gap:7px;white-space:nowrap;border-bottom:2px solid transparent;transition:all var(--trans);letter-spacing:.8px;text-transform:uppercase; }
.tab-btn:hover{ color:var(--gray-200);background:rgba(255,255,255,.03); }
.tab-btn.active{ color:var(--white);border-bottom-color:var(--white); }
.tab-badge{ background:var(--red);color:#fff;padding:1px 6px;font-size:9px;font-weight:700;min-width:16px;text-align:center; }

/* CONTENT */
.content-area{ background:var(--white);min-height:540px;padding:26px; }
.fade-in{ animation:fadeIn .2s ease; }
@keyframes fadeIn{from{opacity:0;transform:translateY(5px)}to{opacity:1;transform:none}}
@keyframes rowIn{from{opacity:0;transform:translateY(5px)}to{opacity:1;transform:none}}

/* SECTION LABELS */
.sec-label{ font-family:var(--font-mono);font-size:9px;font-weight:700;color:var(--gray-400);letter-spacing:2px;text-transform:uppercase;display:flex;align-items:center;gap:8px;margin-bottom:16px; }
.sec-label::before{ content:'';display:inline-block;width:14px;height:2px;background:var(--black); }

/* METRICS */
.metric-grid{ display:grid;gap:1px;background:var(--gray-200);margin-bottom:22px; }
.metric-grid-4{ grid-template-columns:repeat(4,1fr); }
.metric-grid-3{ grid-template-columns:repeat(3,1fr); }
.metric-card{ background:var(--white);padding:16px 14px;text-align:center; }
.metric-num{ font-family:var(--font-mono);font-size:28px;font-weight:700;line-height:1;color:var(--black); }
.metric-lbl{ font-size:10px;color:var(--gray-400);margin-top:5px;font-weight:500;text-transform:uppercase;letter-spacing:.8px; }

/* BADGE */
.badge{ display:inline-flex;align-items:center;gap:4px;padding:2px 9px;font-family:var(--font-mono);font-size:9px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;border:1px solid; }
.badge-active{    background:var(--black);color:var(--white);border-color:var(--black); }
.badge-completed{ background:var(--white);color:var(--gray-400);border-color:var(--gray-300); }
.badge-rejected{  background:var(--red-lite);color:var(--red);border-color:#fca5a5; }
.badge-community{ background:var(--green-lite);color:var(--green);border-color:#86efac; }
.badge-staff{     background:var(--blue-lite);color:var(--blue);border-color:#93c5fd; }
.badge-inmate{    background:var(--purple-lite);color:var(--purple);border-color:#c4b5fd; }
.badge-returning{ background:var(--gray-100);color:var(--gray-600);border-color:var(--gray-300); }

/* BUTTONS */
.btn{ font-family:var(--font-mono);font-weight:600;font-size:11px;padding:10px 20px;cursor:pointer;border:none;transition:all var(--trans);display:inline-flex;align-items:center;justify-content:center;gap:7px;letter-spacing:.5px;text-transform:uppercase; }
.btn-primary{ background:var(--black);color:var(--white); }
.btn-primary:hover{ background:var(--gray-700); }
.btn-danger{ background:var(--red);color:var(--white); }
.btn-danger:hover{ background:#aa0000; }
.btn-ghost{ background:var(--white);color:var(--black);border:1.5px solid var(--gray-300); }
.btn-ghost:hover{ border-color:var(--black); }
.btn-block{ width:100%; }
.btn-sm{ padding:6px 12px;font-size:10px; }

/* FORMS */
.form-group{ display:flex;flex-direction:column;gap:5px;margin-bottom:14px; }
.form-label{ font-family:var(--font-mono);font-size:10px;font-weight:700;color:var(--gray-600);letter-spacing:.8px;text-transform:uppercase; }
.req{ color:var(--red);margin-left:2px; }
.opt{ color:var(--gray-400);font-weight:400;margin-left:4px;font-size:9px;text-transform:lowercase; }
.form-input,.form-select{ width:100%;border:1.5px solid var(--gray-200);padding:10px 12px;font-family:var(--font-body);font-size:13px;color:var(--black);background:var(--white);outline:none;transition:border-color var(--trans); }
.form-input:focus,.form-select:focus{ border-color:var(--black); }
.form-input.error{ border-color:var(--red); }
.form-input:disabled{ background:var(--gray-50);color:var(--gray-500);cursor:not-allowed; }
.form-error{ font-size:11px;color:var(--red);font-weight:500; }
.form-grid-2{ display:grid;grid-template-columns:1fr 1fr;gap:0 14px; }

/* INFO PANELS */
.info-box{ border:1px solid var(--gray-200);padding:14px 16px;margin-bottom:16px;background:var(--gray-50); }
.info-box-black{ border:2px solid var(--black);padding:12px 14px;margin-bottom:18px;font-family:var(--font-mono);font-size:11px;line-height:1.6; }
.loaded-banner{ border-left:4px solid var(--black);background:var(--gray-50);padding:12px 16px;margin-bottom:18px;display:flex;align-items:flex-start;gap:10px;animation:rowIn .2s ease; }
.loaded-check{ width:20px;height:20px;background:var(--black);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px; }

/* PROFILE LOADED GRID */
.loaded-grid{ display:grid;grid-template-columns:1fr 1fr;gap:10px 20px;background:var(--gray-50);border:1px solid var(--gray-200);padding:14px 16px;margin-bottom:16px; }
.loaded-field-lbl{ font-family:var(--font-mono);font-size:9px;color:var(--gray-400);text-transform:uppercase;letter-spacing:1px;font-weight:700;margin-bottom:3px; }
.loaded-field-val{ font-size:13px;font-weight:600;color:var(--black); }

/* KIOSK */
.kiosk-wrap{ max-width:520px;margin:0 auto; }
.kiosk-title{ font-family:var(--font-mono);font-size:20px;font-weight:700;color:var(--black);margin-bottom:6px;letter-spacing:-1px; }
.kiosk-sub{ font-size:13px;color:var(--gray-500);margin-bottom:24px;line-height:1.5; }
.kiosk-grid{ display:grid;grid-template-columns:1fr 1fr;gap:1px;background:var(--gray-200);margin-bottom:18px; }
.kiosk-opt{ background:var(--white);padding:20px 16px;cursor:pointer;text-align:left;transition:background var(--trans);border:none; }
.kiosk-opt:hover{ background:var(--black); }
.kiosk-opt:hover .ko-title,.kiosk-opt:hover .ko-sub{ color:var(--white); }
.kiosk-opt:hover .ko-tag{ border-color:var(--gray-600);color:var(--gray-400); }
.ko-icon{ font-size:26px;margin-bottom:10px;display:block; }
.ko-title{ font-family:var(--font-mono);font-size:12px;font-weight:700;color:var(--black);text-transform:uppercase;letter-spacing:.5px; }
.ko-sub{ font-size:11px;color:var(--gray-500);margin-top:5px;line-height:1.4; }
.ko-tag{ display:inline-block;margin-top:8px;font-family:var(--font-mono);font-size:9px;font-weight:700;border:1px solid var(--gray-200);padding:2px 7px;color:var(--gray-500);letter-spacing:.5px;text-transform:uppercase;transition:all var(--trans); }

/* VISITOR ROWS */
.vrow{ border:1px solid var(--gray-200);padding:13px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;transition:border-color var(--trans);animation:rowIn .2s ease both; }
.vrow:hover{ border-color:var(--gray-500); }
.vrow+.vrow{ margin-top:8px; }
.vrow-name{ font-weight:600;font-size:14px; }
.vrow-meta{ font-size:12px;color:var(--gray-500);margin-top:3px; }
.vrow-ref{ font-family:var(--font-mono);font-size:10px;color:var(--gray-400);margin-top:2px; }

/* INCOMING */
.incoming-card{ border:2px solid var(--black);margin-bottom:20px;animation:rowIn .25s ease; }
.incoming-hdr{ background:var(--black);color:var(--white);padding:9px 18px;display:flex;align-items:center;gap:8px;font-family:var(--font-mono);font-size:9px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase; }
.incoming-body{ padding:18px; }
.detail-grid{ display:grid;grid-template-columns:1fr 1fr;gap:12px 22px;margin-bottom:16px; }
.detail-lbl{ font-family:var(--font-mono);font-size:9px;color:var(--gray-400);text-transform:uppercase;letter-spacing:1px;font-weight:700;margin-bottom:3px; }
.detail-val{ font-size:13px;font-weight:600;color:var(--black); }

/* DOCTOR PANEL */
.doctor-panel{ border:1px solid var(--gray-200);padding:13px 15px;margin-bottom:14px;background:var(--gray-50); }
.doctor-name-sm{ font-weight:600;font-size:13px; }
.doctor-dept-sm{ font-family:var(--font-mono);font-size:10px;color:var(--gray-500);margin-top:2px; }
.dot-on{ width:7px;height:7px;border-radius:50%;background:#16a34a;display:inline-block; }
.dot-off{ width:7px;height:7px;border-radius:50%;background:var(--gray-300);display:inline-block; }

/* CONFIRM */
.confirm-wrap{ text-align:center;padding:24px 16px; }
.confirm-icon{ width:68px;height:68px;background:var(--black);display:flex;align-items:center;justify-content:center;margin:0 auto 18px;animation:confirmPop .35s ease; }
@keyframes confirmPop{from{transform:scale(0)}to{transform:scale(1)}}
.ref-box{ background:var(--black);padding:16px 22px;text-align:center;margin:0 auto 18px;max-width:400px; }
.ref-num{ font-family:var(--font-mono);font-size:22px;font-weight:700;color:var(--white);letter-spacing:3px; }
.ref-lbl{ font-size:9px;color:var(--gray-500);margin-bottom:5px;text-transform:uppercase;letter-spacing:1.5px;font-family:var(--font-mono); }
.confirm-card{ border:1px solid var(--gray-200);padding:18px 22px;max-width:420px;margin:0 auto 18px;text-align:left; }
.confirm-row{ display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--gray-100);font-size:13px; }
.confirm-row:last-child{ border-bottom:none; }
.confirm-key{ color:var(--gray-500);font-family:var(--font-mono);font-size:10px;text-transform:uppercase;letter-spacing:.5px; }
.confirm-val{ font-weight:600;max-width:230px;text-align:right; }

/* SEARCH */
.search-row{ display:flex;gap:8px;margin-bottom:14px; }
.search-wrap{ flex:1;position:relative; }
.search-icon{ position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray-400);pointer-events:none; }

/* WARD BARS */
.ward-row-ui{ display:flex;align-items:center;gap:10px;margin-bottom:8px; }
.ward-lbl-ui{ font-family:var(--font-mono);font-size:10px;width:120px;flex-shrink:0;color:var(--gray-700); }
.ward-track{ flex:1;background:var(--gray-100);height:9px; }
.ward-fill{ height:100%;background:var(--black);transition:width .5s ease; }
.ward-count-ui{ width:22px;text-align:right;font-family:var(--font-mono);font-size:11px;font-weight:700; }

/* ACTIVITY */
.activity-row{ display:flex;align-items:center;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--gray-100);font-size:13px; }
.activity-row:last-child{ border-bottom:none; }

/* MISC */
.back-btn{ background:none;border:none;cursor:pointer;font-family:var(--font-mono);font-size:10px;font-weight:700;color:var(--gray-500);display:inline-flex;align-items:center;gap:6px;padding:0;margin-bottom:20px;transition:color var(--trans);text-transform:uppercase;letter-spacing:.5px; }
.back-btn:hover{ color:var(--black); }
.consent-box{ border-left:3px solid var(--gray-300);background:var(--gray-50);padding:10px 14px;font-size:11px;color:var(--gray-500);line-height:1.6;margin-top:14px; }
.divider{ height:1px;background:var(--gray-200);margin:18px 0; }
.app-footer{ background:var(--black);padding:9px 26px;display:flex;justify-content:space-between;align-items:center;font-family:var(--font-mono);font-size:9px;color:var(--gray-600);letter-spacing:.5px; }
.conf-tag{ border:1px solid var(--gray-700);color:var(--gray-500);padding:2px 8px;font-size:9px;font-weight:700;letter-spacing:1px;text-transform:uppercase; }

@media(max-width:560px){
  .header-pills{display:none;}
  .form-grid-2{grid-template-columns:1fr;}
  .detail-grid{grid-template-columns:1fr;}
  .metric-grid-4{grid-template-columns:repeat(2,1fr);}
  .kiosk-grid{grid-template-columns:1fr;}
  .content-area{padding:18px 14px;}
}
</style>
</head>
<body>
<div id="root"></div>
<script type="text/babel">
const { useState, useEffect } = React;

// ── CONSTANTS ──────────────────────────────────────────────────────────────
const WARDS = ["Female Ward","Male Ward","ICU","Maternity","Paediatrics","Surgical Ward","Outpatients","A&E"];
const DEPTS  = ["General Surgery","Internal Medicine","Paediatrics","Obstetrics & Gynaecology","Orthopaedics","ICU","A&E","Laboratory","Pharmacy","Radiology","Administration","Maintenance","Catering"];
const COMPLAINTS = ["General Consultation","Fever / Malaria","Injury / Wound","Maternal / Antenatal","Eye / ENT","Dental","Chest / Respiratory","Abdominal Pain","Mental Health","Follow-Up Appointment","Referral from Another Facility","Other"];

// ── MOCK INMATE DATABASE (keyed by inmate number) ──────────────────────────
const INMATE_DB = {
  "INM-001-2024": { name:"John Tendo",       dob:"12 Mar 1988", block:"Block C",    cell:"C-14", condition:"Hypertension, Diabetes", assignedDoctor:"Dr. Sarah Nakato", allergies:"Penicillin", lastVisit:"14 Apr 2026", emergencyContact:"Mary Tendo — 0782 341 092" },
  "INM-002-2024": { name:"David Ssemakula",  dob:"05 Jul 1992", block:"Block A",    cell:"A-07", condition:"Asthma",                 assignedDoctor:"Dr. Grace Auma",   allergies:"None known",  lastVisit:"10 Apr 2026", emergencyContact:"Agnes Ssemakula — 0701 234 567" },
  "INM-003-2024": { name:"Robert Mugisha",   dob:"22 Jan 1979", block:"Block B",    cell:"B-03", condition:"None on file",            assignedDoctor:"Dr. James Okello", allergies:"None known",  lastVisit:"—",           emergencyContact:"Esther Mugisha — 0756 890 123" },
  "INM-004-2024": { name:"Grace Atim",       dob:"30 Sep 1995", block:"Female Wing",cell:"F-02", condition:"Anaemia, Malnutrition",   assignedDoctor:"Dr. Mercy Atim",   allergies:"Sulpha drugs", lastVisit:"01 Apr 2026", emergencyContact:"James Atim — 0789 012 345" },
};

// ── MOCK STAFF DATABASE (keyed by staff ID) ────────────────────────────────
const STAFF_DB = {
  "STF-D001": { name:"Dr. James Okello",    dept:"General Surgery",     role:"Doctor",          phone:"0756789012" },
  "STF-N001": { name:"Sr. Rita Nakamya",    dept:"ICU",                 role:"Nurse",           phone:"0789234567" },
  "STF-L001": { name:"Moses Kizito",        dept:"Laboratory",          role:"Lab Technician",  phone:"0701234567" },
  "STF-A001": { name:"Joyce Namukasa",      dept:"Administration",      role:"Admin Staff",     phone:"0772345678" },
  "STF-M001": { name:"Peter Ssali",         dept:"Maintenance",         role:"Maintenance",     phone:"0784567890" },
  "STF-C001": { name:"Doreen Apio",         dept:"Catering",            role:"Catering Staff",  phone:"0701112233" },
};

// ── MOCK VISITOR PROFILES (keyed by reference number) ─────────────────────
const VISITOR_PROFILES = {
  "VIS-20260315-4821": { name:"Aisha Nalwoga",  phone:"0784567890", idNum:"CM9010001234", visits:14 },
  "VIS-20260318-7734": { name:"Peter Opiyo",    phone:"0772345678", idNum:"CM8812209876", visits:6  },
};

// ── DOCTORS ────────────────────────────────────────────────────────────────
const DOCTORS = [
  { id:"D001", name:"Dr. Sarah Nakato",    dept:"Internal Medicine",           ward:"Male Ward",     on:true  },
  { id:"D002", name:"Dr. James Okello",    dept:"General Surgery",             ward:"Surgical Ward", on:true  },
  { id:"D003", name:"Dr. Mercy Atim",      dept:"Paediatrics",                 ward:"Paediatrics",   on:false },
  { id:"D004", name:"Dr. Robert Ssali",    dept:"Obstetrics & Gynaecology",    ward:"Maternity",     on:true  },
  { id:"D005", name:"Dr. Grace Auma",      dept:"ICU",                         ward:"ICU",           on:true  },
  { id:"D006", name:"Dr. Paul Mutebi",     dept:"A&E",                         ward:"A&E",           on:false },
  { id:"D007", name:"Dr. Florence Akello", dept:"General Outpatients",         ward:"Outpatients",   on:true  },
];

const NURSE_STATIONS = {
  "Female Ward":"Sr. Assumpta Nakayiza",
  "Male Ward":"Sr. John Mugisha",
  "ICU":"Sr. Rita Nakamya",
  "Maternity":"Sr. Doreen Apio",
  "Paediatrics":"Sr. Beatrice Nantongo",
  "Surgical Ward":"Sr. Henry Okot",
  "Outpatients":"Sr. Lucy Namukasa",
  "A&E":"Sr. Denis Wasswa",
};

// ── HELPERS ────────────────────────────────────────────────────────────────
function genRef(pfx="VIS"){ const n=new Date(); return `${pfx}-${n.getFullYear()}${String(n.getMonth()+1).padStart(2,"0")}${String(n.getDate()).padStart(2,"0")}-${Math.floor(Math.random()*9000)+1000}`; }
const fmtTime = d => new Date(d).toLocaleTimeString("en-UG",{hour:"2-digit",minute:"2-digit"});
const fmtDate = d => new Date(d).toLocaleDateString("en-UG",{day:"2-digit",month:"short",year:"numeric"});
function fmtDuration(entry,exit){ const ms=(exit?new Date(exit):new Date())-new Date(entry); const h=Math.floor(ms/3600000),m=Math.floor((ms%3600000)/60000); return h+"h "+String(m).padStart(2,"0")+"m"; }
const fmtClock = () => new Date().toLocaleTimeString("en-UG",{hour:"2-digit",minute:"2-digit",second:"2-digit"});
function getDutyDoc(ward){ return DOCTORS.find(d=>d.ward===ward&&d.on)||DOCTORS.find(d=>d.ward===ward)||null; }

// ── SAMPLE LOG ─────────────────────────────────────────────────────────────
function makeInitLog(){
  const now=Date.now();
  return [
    { ref:genRef("VIS"), type:"community", name:"Aisha Nalwoga",   idNum:"CM9010001234", phone:"0784567890", ward:"Outpatients", doctor:"Dr. Florence Akello", nurse:"Sr. Lucy Namukasa", complaint:"General Consultation", entryTime:new Date(now-72*60000).toISOString(), exitTime:new Date(now-12*60000).toISOString(), status:"COMPLETED", isReturn:false },
    { ref:genRef("VIS"), type:"community", name:"Peter Opiyo",     idNum:"CM8812209876", phone:"0772345678", ward:"ICU",         doctor:"Dr. Grace Auma",       nurse:"Sr. Rita Nakamya",  complaint:"Post-Surgery Check",   entryTime:new Date(now-43*60000).toISOString(), exitTime:null,                                status:"ACTIVE",    isReturn:true  },
    { ref:genRef("INM"), type:"inmate",    name:"John Tendo",       inmateNum:"INM-001-2024", block:"Block C", cell:"C-14", doctor:"Dr. Sarah Nakato", complaint:"Hypertension Review",  entryTime:new Date(now-30*60000).toISOString(), exitTime:null, status:"ACTIVE"    },
    { ref:genRef("STF"), type:"staff",     name:"Joyce Namukasa",   staffId:"STF-A001",   dept:"Administration", role:"Admin Staff", phone:"0772345678", complaint:"Fever / Malaria",   doctor:"Dr. Florence Akello", entryTime:new Date(now-15*60000).toISOString(), exitTime:null, status:"ACTIVE"    },
  ];
}

// ── COMPONENTS ─────────────────────────────────────────────────────────────
function Badge({status,type}){
  const typeMap={community:"community",inmate:"inmate",staff:"staff",returning:"returning"};
  const statusMap={ACTIVE:"active",COMPLETED:"completed",REJECTED:"rejected"};
  if(type) return <span className={"badge badge-"+(typeMap[type]||"community")}>{type==="community"?"COMMUNITY":type==="inmate"?"INMATE":type==="staff"?"STAFF":"RETURNING"}</span>;
  return <span className={"badge badge-"+(statusMap[status]||"completed")}>{status}</span>;
}

function LiveClock(){
  const [t,setT]=useState(fmtClock());
  useEffect(()=>{ const id=setInterval(()=>setT(fmtClock()),1000); return()=>clearInterval(id); },[]);
  return(
    <div className="clock-bar">
      <span><span className="live-dot"></span>{fmtDate(new Date())} · {t}</span>
      <span>UPDS HOSPITAL v5.0 · CONFIDENTIAL</span>
    </div>
  );
}

function DoctorPanel({ward}){
  const doc=getDutyDoc(ward);
  const nurse=NURSE_STATIONS[ward];
  if(!doc&&!nurse) return null;
  return(
    <div className="doctor-panel">
      <div style={{fontFamily:"var(--font-mono)",fontSize:9,fontWeight:700,color:"var(--gray-400)",letterSpacing:1.5,textTransform:"uppercase",marginBottom:10}}>Ward Medical Team</div>
      {doc&&<div style={{display:"flex",alignItems:"center",justifyContent:"space-between",marginBottom:nurse?8:0}}>
        <div><div className="doctor-name-sm">{doc.name}</div><div className="doctor-dept-sm">{doc.dept}</div></div>
        <div style={{display:"flex",alignItems:"center",gap:5,fontFamily:"var(--font-mono)",fontSize:9,fontWeight:700}}>
          <span className={doc.on?"dot-on":"dot-off"}></span>
          <span style={{color:doc.on?"#16a34a":"var(--gray-400)"}}>{doc.on?"ON DUTY":"OFF SHIFT"}</span>
        </div>
      </div>}
      {nurse&&doc&&<div style={{height:1,background:"var(--gray-200)",margin:"8px 0"}}></div>}
      {nurse&&<div style={{display:"flex",alignItems:"center",justifyContent:"space-between"}}>
        <div><div className="doctor-name-sm" style={{fontWeight:500}}>{nurse}</div><div className="doctor-dept-sm">Charge Nurse / Ward Sister</div></div>
        <div style={{display:"flex",alignItems:"center",gap:5,fontFamily:"var(--font-mono)",fontSize:9,fontWeight:700}}><span className="dot-on"></span><span style={{color:"#16a34a"}}>ON WARD</span></div>
      </div>}
    </div>
  );
}

// ── KIOSK HOME ─────────────────────────────────────────────────────────────
function KioskHome({onSelect}){
  return(
    <div className="kiosk-wrap fade-in">
      <div style={{paddingTop:4,marginBottom:26}}>
        <div className="kiosk-title">HOSPITAL GATE<br/>REGISTRATION</div>
        <div className="kiosk-sub">Select your category to begin. Please have your ID or reference number ready.</div>
      </div>
      <div className="kiosk-grid">
        {[
          { action:"community", icon:"🏘", title:"Community Patient",   sub:"From outside — coming for treatment at the hospital", tag:"New or returning" },
          { action:"inmate",    icon:"🔒", title:"Inmate / Detainee",   sub:"Facility inmate coming for hospital treatment", tag:"Inmate number required" },
          { action:"staff",     icon:"🩺", title:"Staff Member",        sub:"Facility staff coming for personal treatment", tag:"Staff ID required" },
          { action:"returning", icon:"↩",  title:"Returning Visitor",   sub:"Coming to visit a patient — use your reference number or register", tag:"Ref. number or walk-in" },
        ].map(o=>(
          <button key={o.action} className="kiosk-opt" onClick={()=>onSelect(o.action)}>
            <span className="ko-icon">{o.icon}</span>
            <div className="ko-title">{o.title}</div>
            <div className="ko-sub">{o.sub}</div>
            <span className="ko-tag">{o.tag}</span>
          </button>
        ))}
      </div>
      <div style={{border:"1px solid var(--gray-200)",padding:"11px 14px",fontSize:11,color:"var(--gray-500)",lineHeight:1.6,fontFamily:"var(--font-mono)"}}>
        ⓘ Community patients and returning visitors who registered before receive a reference number. Use it to sign in instantly next time.
      </div>
    </div>
  );
}

// ── COMMUNITY PATIENT FORM ─────────────────────────────────────────────────
function CommunityForm({onSubmit,onBack}){
  const [form,setForm]=useState({name:"",idNum:"",phone:"",ward:WARDS[0],complaint:COMPLAINTS[0]});
  const [errors,setErrors]=useState({});
  const set=(k,v)=>setForm(f=>({...f,[k]:v}));
  const doc=getDutyDoc(form.ward);
  const nurse=NURSE_STATIONS[form.ward];

  const validate=()=>{
    const e={};
    if(!form.name.trim())  e.name="Required";
    if(!form.idNum.trim()) e.idNum="Required";
    if(!form.phone.trim()) e.phone="Required";
    setErrors(e); return !Object.keys(e).length;
  };

  const submit=()=>{
    if(!validate()) return;
    onSubmit({ ...form, ref:genRef("VIS"), type:"community", isReturn:false,
      doctor:doc?doc.name:"Duty Doctor", nurse:nurse||"",
      entryTime:new Date().toISOString(), exitTime:null, status:"ACTIVE" });
  };

  const FG=({label,fid,required,hint,mono})=>(
    <div className="form-group">
      <label className="form-label">{label}{required&&<span className="req">*</span>}</label>
      <input type="text" className={"form-input"+(errors[fid]?" error":"")} value={form[fid]}
        onChange={e=>set(fid,e.target.value)} placeholder={hint}
        style={mono?{fontFamily:"var(--font-mono)",letterSpacing:.5}:{}} />
      {errors[fid]&&<span className="form-error">{errors[fid]}</span>}
    </div>
  );

  return(
    <div className="kiosk-wrap fade-in">
      <button className="back-btn" onClick={onBack}>← Back</button>
      <div className="kiosk-title" style={{fontSize:17,marginBottom:4}}>Community Patient Registration</div>
      <div className="kiosk-sub" style={{marginBottom:18}}>Complete all required fields. A reference number will be issued for future visits.</div>

      <DoctorPanel ward={form.ward}/>

      <div className="form-grid-2">
        <FG label="Full Name" fid="name" required hint="As on National ID"/>
        <FG label="National ID Number" fid="idNum" required hint="e.g. CM9010001234" mono/>
      </div>
      <div className="form-grid-2">
        <FG label="Phone Number" fid="phone" required hint="e.g. 0771234567"/>
        <div className="form-group">
          <label className="form-label">Going To (Ward)<span className="req">*</span></label>
          <select className="form-select" value={form.ward} onChange={e=>set("ward",e.target.value)}>
            {WARDS.map(w=><option key={w}>{w}</option>)}
          </select>
        </div>
      </div>
      <div className="form-group">
        <label className="form-label">Reason for Visit<span className="req">*</span></label>
        <select className="form-select" value={form.complaint} onChange={e=>set("complaint",e.target.value)}>
          {COMPLAINTS.map(c=><option key={c}>{c}</option>)}
        </select>
      </div>
      <div className="consent-box">By submitting you consent to the facility recording your visit for hospital administration and security purposes. A reference number will be issued to speed up future registrations.</div>
      <button className="btn btn-primary btn-block" style={{marginTop:16,padding:"12px"}} onClick={submit}>Register &amp; Get Reference Number →</button>
    </div>
  );
}

// ── INMATE PATIENT FORM ────────────────────────────────────────────────────
function InmateForm({onSubmit,onBack}){
  const [inmateNum,setInmateNum]=useState("");
  const [found,setFound]=useState(null);
  const [notFound,setNotFound]=useState(false);
  const [complaint,setComplaint]=useState(COMPLAINTS[0]);
  const [step,setStep]=useState(1); // 1=lookup, 2=confirm+complaint
  const [escorted,setEscorted]=useState("");

  const lookup=()=>{
    const rec=INMATE_DB[inmateNum.trim().toUpperCase()];
    if(rec){ setFound(rec); setNotFound(false); setStep(2); }
    else   { setNotFound(true); setFound(null); }
  };

  const submit=()=>{
    if(!found) return;
    const doc=DOCTORS.find(d=>d.name===found.assignedDoctor);
    onSubmit({
      ref:genRef("INM"), type:"inmate", name:found.name, inmateNum:inmateNum.trim().toUpperCase(),
      block:found.block, cell:found.cell, condition:found.condition, allergies:found.allergies,
      assignedDoctor:found.assignedDoctor, doctor:found.assignedDoctor,
      escortOfficer:escorted, complaint,
      entryTime:new Date().toISOString(), exitTime:null, status:"ACTIVE",
    });
  };

  return(
    <div className="kiosk-wrap fade-in">
      <button className="back-btn" onClick={onBack}>← Back</button>
      <div className="kiosk-title" style={{fontSize:17,marginBottom:4}}>Inmate Medical Registration</div>
      <div className="kiosk-sub" style={{marginBottom:18}}>Enter the inmate number. All medical details will load automatically from their record.</div>

      {step===1 && <>
        <div className="form-group">
          <label className="form-label">Inmate Number<span className="req">*</span></label>
          <input className="form-input" value={inmateNum} onChange={e=>setInmateNum(e.target.value.toUpperCase())}
            placeholder="e.g. INM-001-2024" style={{fontFamily:"var(--font-mono)",fontSize:16,letterSpacing:1.5}} />
        </div>
        {notFound&&<div style={{background:"var(--red-lite)",border:"1px solid #fca5a5",padding:"10px 14px",fontSize:12,color:"var(--red)",marginBottom:14,fontWeight:500}}>
          ✕ No record found for that inmate number. Please check and try again or call the facility office.
        </div>}
        <div style={{border:"1px solid var(--gray-200)",padding:"10px 12px",fontSize:11,fontFamily:"var(--font-mono)",color:"var(--gray-400)",marginBottom:14}}>
          DEMO: Try INM-001-2024 · INM-002-2024 · INM-003-2024 · INM-004-2024
        </div>
        <button className="btn btn-primary btn-block" style={{padding:"12px"}} onClick={lookup}>Look Up Inmate Record →</button>
      </>}

      {step===2 && found && <>
        <div style={{border:"2px solid var(--black)",marginBottom:16}}>
          <div style={{background:"var(--black)",color:"var(--white)",padding:"8px 14px",fontFamily:"var(--font-mono)",fontSize:9,fontWeight:700,letterSpacing:1.5,textTransform:"uppercase"}}>
            ✓ Inmate Record Loaded — {inmateNum}
          </div>
          <div className="loaded-grid">
            {[
              ["Full Name",         found.name],
              ["Date of Birth",     found.dob],
              ["Block / Cell",      found.block+" · "+found.cell],
              ["Assigned Doctor",   found.assignedDoctor],
              ["Known Condition",   found.condition],
              ["Allergies",         found.allergies],
              ["Last Hospital Visit",found.lastVisit],
              ["Emergency Contact", found.emergencyContact],
            ].map(([k,v])=>(
              <div key={k}>
                <div className="loaded-field-lbl">{k}</div>
                <div className="loaded-field-val" style={k==="Allergies"&&v!=="None known"?{color:"var(--red)"}:{}}>{v}</div>
              </div>
            ))}
          </div>
        </div>

        <div className="form-group">
          <label className="form-label">Reason for Hospital Visit<span className="req">*</span></label>
          <select className="form-select" value={complaint} onChange={e=>setComplaint(e.target.value)}>
            {COMPLAINTS.map(c=><option key={c}>{c}</option>)}
          </select>
        </div>
        <div className="form-group">
          <label className="form-label">Escorting Officer Name<span className="req">*</span></label>
          <input className="form-input" value={escorted} onChange={e=>setEscorted(e.target.value)} placeholder="Name of officer accompanying inmate" />
        </div>
        <div className="consent-box">This inmate's medical visit is being recorded in the facility security and hospital system. The assigned doctor ({found.assignedDoctor}) will be notified.</div>
        <button className="btn btn-primary btn-block" style={{marginTop:14,padding:"12px"}} onClick={submit}>Submit to Guard Station →</button>
        <button className="back-btn" style={{marginTop:10,marginBottom:0}} onClick={()=>{setStep(1);setFound(null);setNotFound(false);}}>← Search again</button>
      </>}
    </div>
  );
}

// ── STAFF PATIENT FORM ─────────────────────────────────────────────────────
function StaffForm({onSubmit,onBack}){
  const [staffId,setStaffId]=useState("");
  const [found,setFound]=useState(null);
  const [notFound,setNotFound]=useState(false);
  const [complaint,setComplaint]=useState(COMPLAINTS[0]);
  const [ward,setWard]=useState(WARDS[0]);
  const [step,setStep]=useState(1);

  const lookup=()=>{
    const rec=STAFF_DB[staffId.trim().toUpperCase()];
    if(rec){ setFound(rec); setNotFound(false); setStep(2); }
    else   { setNotFound(true); setFound(null); }
  };

  const submit=()=>{
    if(!found) return;
    const doc=getDutyDoc(ward);
    onSubmit({
      ref:genRef("STF"), type:"staff", name:found.name, staffId:staffId.trim().toUpperCase(),
      dept:found.dept, role:found.role, phone:found.phone,
      ward, doctor:doc?doc.name:"Duty Doctor", nurse:NURSE_STATIONS[ward]||"",
      complaint,
      entryTime:new Date().toISOString(), exitTime:null, status:"ACTIVE",
    });
  };

  return(
    <div className="kiosk-wrap fade-in">
      <button className="back-btn" onClick={onBack}>← Back</button>
      <div className="kiosk-title" style={{fontSize:17,marginBottom:4}}>Staff Medical Registration</div>
      <div className="kiosk-sub" style={{marginBottom:18}}>Enter your Staff ID to load your details, then select your complaint.</div>

      {step===1 && <>
        <div className="form-group">
          <label className="form-label">Staff / Employee ID<span className="req">*</span></label>
          <input className="form-input" value={staffId} onChange={e=>setStaffId(e.target.value.toUpperCase())}
            placeholder="e.g. STF-D001" style={{fontFamily:"var(--font-mono)",fontSize:16,letterSpacing:1.5}} />
        </div>
        {notFound&&<div style={{background:"var(--red-lite)",border:"1px solid #fca5a5",padding:"10px 14px",fontSize:12,color:"var(--red)",marginBottom:14,fontWeight:500}}>
          ✕ Staff ID not found. Check the ID or contact HR.
        </div>}
        <div style={{border:"1px solid var(--gray-200)",padding:"10px 12px",fontSize:11,fontFamily:"var(--font-mono)",color:"var(--gray-400)",marginBottom:14}}>
          DEMO: Try STF-D001 · STF-N001 · STF-L001 · STF-A001 · STF-M001 · STF-C001
        </div>
        <button className="btn btn-primary btn-block" style={{padding:"12px"}} onClick={lookup}>Load My Staff Record →</button>
      </>}

      {step===2 && found && <>
        <div style={{border:"2px solid var(--black)",marginBottom:16}}>
          <div style={{background:"var(--black)",color:"var(--white)",padding:"8px 14px",fontFamily:"var(--font-mono)",fontSize:9,fontWeight:700,letterSpacing:1.5,textTransform:"uppercase"}}>
            ✓ Staff Record Loaded — {staffId}
          </div>
          <div className="loaded-grid">
            {[
              ["Full Name",   found.name],
              ["Role",        found.role],
              ["Department",  found.dept],
              ["Phone",       found.phone],
            ].map(([k,v])=>(
              <div key={k}>
                <div className="loaded-field-lbl">{k}</div>
                <div className="loaded-field-val">{v}</div>
              </div>
            ))}
          </div>
        </div>

        <DoctorPanel ward={ward}/>

        <div className="form-grid-2">
          <div className="form-group">
            <label className="form-label">Going To (Ward)<span className="req">*</span></label>
            <select className="form-select" value={ward} onChange={e=>setWard(e.target.value)}>
              {WARDS.map(w=><option key={w}>{w}</option>)}
            </select>
          </div>
          <div className="form-group">
            <label className="form-label">Reason for Visit<span className="req">*</span></label>
            <select className="form-select" value={complaint} onChange={e=>setComplaint(e.target.value)}>
              {COMPLAINTS.map(c=><option key={c}>{c}</option>)}
            </select>
          </div>
        </div>
        <div className="consent-box">This visit is recorded for hospital administration purposes only. Medical staff are bound by confidentiality.</div>
        <button className="btn btn-primary btn-block" style={{marginTop:14,padding:"12px"}} onClick={submit}>Submit to Guard Station →</button>
        <button className="back-btn" style={{marginTop:10,marginBottom:0}} onClick={()=>{setStep(1);setFound(null);setNotFound(false);}}>← Search again</button>
      </>}
    </div>
  );
}

// ── RETURNING VISITOR FORM ─────────────────────────────────────────────────
function ReturningForm({onSubmit,onBack}){
  const [hasRef,setHasRef]=useState(null); // null=choosing, true=has ref, false=no ref
  const [ref,setRef]=useState("");
  const [nameInput,setNameInput]=useState("");
  const [found,setFound]=useState(null);
  const [notFound,setNotFound]=useState(false);
  const [step,setStep]=useState(1);
  // for no-ref path
  const [form,setForm]=useState({name:"",idNum:"",phone:"",ward:WARDS[0],complaint:COMPLAINTS[0]});
  const [errors,setErrors]=useState({});
  const setF=(k,v)=>setForm(f=>({...f,[k]:v}));
  const doc=getDutyDoc(form.ward);

  const lookup=()=>{
    const p=VISITOR_PROFILES[ref.trim().toUpperCase()];
    if(p && p.name.toLowerCase()===nameInput.trim().toLowerCase()){
      setFound(p); setNotFound(false); setStep(2);
    } else { setNotFound(true); setFound(null); }
  };

  const [visitWard,setVisitWard]=useState(WARDS[0]);
  const [visitComplaint,setVisitComplaint]=useState(COMPLAINTS[0]);

  const submitWithRef=()=>{
    if(!found) return;
    const d=getDutyDoc(visitWard);
    onSubmit({
      ref:ref.trim().toUpperCase(), type:"returning", name:found.name,
      phone:found.phone, idNum:found.idNum,
      ward:visitWard, doctor:d?d.name:"Duty Doctor", nurse:NURSE_STATIONS[visitWard]||"",
      complaint:visitComplaint, isReturn:true,
      entryTime:new Date().toISOString(), exitTime:null, status:"ACTIVE",
    });
  };

  const submitNoRef=()=>{
    const e={};
    if(!form.name.trim())  e.name="Required";
    if(!form.idNum.trim()) e.idNum="Required";
    if(!form.phone.trim()) e.phone="Required";
    setErrors(e);
    if(Object.keys(e).length) return;
    onSubmit({
      ref:genRef("VIS"), type:"community", name:form.name, idNum:form.idNum,
      phone:form.phone, ward:form.ward, complaint:form.complaint, isReturn:false,
      doctor:doc?doc.name:"Duty Doctor", nurse:NURSE_STATIONS[form.ward]||"",
      entryTime:new Date().toISOString(), exitTime:null, status:"ACTIVE",
    });
  };

  const FG=({label,fid,required,hint,mono})=>(
    <div className="form-group">
      <label className="form-label">{label}{required&&<span className="req">*</span>}</label>
      <input type="text" className={"form-input"+(errors[fid]?" error":"")} value={form[fid]}
        onChange={e=>setF(fid,e.target.value)} placeholder={hint}
        style={mono?{fontFamily:"var(--font-mono)",letterSpacing:.5}:{}} />
      {errors[fid]&&<span className="form-error">{errors[fid]}</span>}
    </div>
  );

  return(
    <div className="kiosk-wrap fade-in">
      <button className="back-btn" onClick={onBack}>← Back</button>
      <div className="kiosk-title" style={{fontSize:17,marginBottom:4}}>Visitor Registration</div>
      <div className="kiosk-sub" style={{marginBottom:20}}>Coming to visit a patient? Use your reference number if you have one — or register as a new visitor.</div>

      {hasRef===null && <>
        <div style={{display:"grid",gridTemplateColumns:"1fr 1fr",gap:1,background:"var(--gray-200)",marginBottom:18}}>
          <button className="kiosk-opt" onClick={()=>setHasRef(true)}>
            <span className="ko-icon">🔖</span>
            <div className="ko-title">I Have a Reference No.</div>
            <div className="ko-sub">Sign in instantly using your existing reference number</div>
          </button>
          <button className="kiosk-opt" onClick={()=>setHasRef(false)}>
            <span className="ko-icon">📝</span>
            <div className="ko-title">No Reference Number</div>
            <div className="ko-sub">First-time visitor — fill in your details to register</div>
          </button>
        </div>
      </>}

      {hasRef===true && step===1 && <>
        <div className="form-group">
          <label className="form-label">Your Reference Number<span className="req">*</span></label>
          <input className="form-input" value={ref} onChange={e=>setRef(e.target.value.toUpperCase())}
            placeholder="e.g. VIS-20260315-4821" style={{fontFamily:"var(--font-mono)",fontSize:15,letterSpacing:1}} />
        </div>
        <div className="form-group">
          <label className="form-label">Your Full Name<span className="req">*</span></label>
          <input className="form-input" value={nameInput} onChange={e=>setNameInput(e.target.value)} placeholder="As on your National ID" />
        </div>
        {notFound&&<div style={{background:"var(--red-lite)",border:"1px solid #fca5a5",padding:"10px 14px",fontSize:12,color:"var(--red)",marginBottom:14,fontWeight:500}}>
          ✕ No match found. Check your reference number and name exactly as registered.
        </div>}
        <div style={{border:"1px solid var(--gray-200)",padding:"10px 12px",fontSize:11,fontFamily:"var(--font-mono)",color:"var(--gray-400)",marginBottom:14}}>
          DEMO: Try VIS-20260315-4821 + name Aisha Nalwoga
        </div>
        <div style={{display:"flex",gap:10}}>
          <button className="btn btn-ghost" onClick={()=>{setHasRef(null);setNotFound(false);}}>← Back</button>
          <button className="btn btn-primary" style={{flex:1,padding:"12px"}} onClick={lookup}>Look Up My Record →</button>
        </div>
      </>}

      {hasRef===true && step===2 && found && <>
        <div className="loaded-banner">
          <div className="loaded-check"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="3"><polyline points="20 6 9 17 4 12"/></svg></div>
          <div>
            <div style={{fontWeight:700,fontSize:14}}>Welcome back, {found.name}</div>
            <div style={{fontSize:11,color:"var(--gray-500)",marginTop:2}}>{found.visits} previous visits on record · Ref: {ref}</div>
          </div>
        </div>

        <DoctorPanel ward={visitWard}/>

        <div className="form-grid-2">
          <div className="form-group">
            <label className="form-label">Going To (Ward)<span className="req">*</span></label>
            <select className="form-select" value={visitWard} onChange={e=>setVisitWard(e.target.value)}>
              {WARDS.map(w=><option key={w}>{w}</option>)}
            </select>
          </div>
          <div className="form-group">
            <label className="form-label">Purpose of Visit<span className="req">*</span></label>
            <select className="form-select" value={visitComplaint} onChange={e=>setVisitComplaint(e.target.value)}>
              {["Visiting a Patient","Consultation Support","Bringing Supplies","Post-Surgery Check","Religious Support","Other"].map(p=><option key={p}>{p}</option>)}
            </select>
          </div>
        </div>
        <button className="btn btn-primary btn-block" style={{padding:"12px"}} onClick={submitWithRef}>Submit to Guard Station →</button>
        <button className="back-btn" style={{marginTop:10,marginBottom:0}} onClick={()=>setStep(1)}>← Search again</button>
      </>}

      {hasRef===false && <>
        <div style={{border:"1px solid var(--gray-200)",padding:"11px 14px",fontSize:11,color:"var(--gray-500)",marginBottom:18,fontFamily:"var(--font-mono)"}}>
          ⓘ After registering today, you will receive a reference number. Use it next time to sign in instantly.
        </div>
        <DoctorPanel ward={form.ward}/>
        <div className="form-grid-2">
          <FG label="Full Name" fid="name" required hint="As on National ID"/>
          <FG label="National ID Number" fid="idNum" required hint="e.g. CM9010001234" mono/>
        </div>
        <div className="form-grid-2">
          <FG label="Phone Number" fid="phone" required hint="e.g. 0771234567"/>
          <div className="form-group">
            <label className="form-label">Going To (Ward)<span className="req">*</span></label>
            <select className="form-select" value={form.ward} onChange={e=>setF("ward",e.target.value)}>
              {WARDS.map(w=><option key={w}>{w}</option>)}
            </select>
          </div>
        </div>
        <div className="form-group">
          <label className="form-label">Purpose of Visit<span className="req">*</span></label>
          <select className="form-select" value={form.complaint} onChange={e=>setF("complaint",e.target.value)}>
            {["Visiting a Patient","Consultation Support","Bringing Supplies","Post-Surgery Check","Religious Support","Other"].map(p=><option key={p}>{p}</option>)}
          </select>
        </div>
        <div className="consent-box">By submitting you consent to the facility recording your visit for hospital administration purposes. A reference number will be issued for your next visit.</div>
        <div style={{display:"flex",gap:10,marginTop:14}}>
          <button className="btn btn-ghost" onClick={()=>setHasRef(null)}>← Back</button>
          <button className="btn btn-primary" style={{flex:1,padding:"12px"}} onClick={submitNoRef}>Register &amp; Get Reference Number →</button>
        </div>
      </>}
    </div>
  );
}

// ── KIOSK CONFIRM ──────────────────────────────────────────────────────────
function KioskConfirm({record,onDone}){
  const labels={community:"Community Patient",inmate:"Inmate Patient",staff:"Staff Patient",returning:"Visitor"};
  return(
    <div className="confirm-wrap fade-in">
      <div className="confirm-icon">
        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <div style={{fontFamily:"var(--font-mono)",fontSize:18,fontWeight:700,marginBottom:6,letterSpacing:-1}}>
        {record.isReturn?"SIGN-IN SUBMITTED":"REGISTRATION COMPLETE"}
      </div>
      <div style={{fontSize:13,color:"var(--gray-500)",marginBottom:20,maxWidth:400,margin:"0 auto 18px",lineHeight:1.5}}>
        Present yourself to the guard station. {!record.isReturn&&"Note your reference number below."}
      </div>

      {!record.isReturn&&(
        <div className="ref-box">
          <div className="ref-lbl">Your Reference Number</div>
          <div className="ref-num">{record.ref}</div>
          <div style={{fontSize:9,color:"var(--gray-500)",marginTop:6,fontFamily:"var(--font-mono)"}}>Keep this — use it for faster sign-in next time</div>
        </div>
      )}

      <div className="confirm-card">
        {[
          ["Category",        labels[record.type]||record.type],
          ["Name",            record.name],
          ...(record.inmateNum  ? [["Inmate No.",    record.inmateNum]]  : []),
          ...(record.staffId    ? [["Staff ID",      record.staffId]]    : []),
          ...(record.block      ? [["Block / Cell",  record.block+" · "+(record.cell||"")]] : []),
          ...(record.ward       ? [["Ward",          record.ward]]       : []),
          ...(record.dept       ? [["Department",    record.dept]]       : []),
          ["Reason",          record.complaint||"—"],
          ...(record.doctor     ? [["Doctor",        record.doctor]]     : []),
          ...(record.nurse      ? [["Charge Nurse",  record.nurse]]      : []),
          ...(record.escortOfficer&&record.escortOfficer.trim() ? [["Escort Officer",record.escortOfficer]] : []),
          ["Entry Time",      fmtTime(record.entryTime)],
        ].map(([k,v])=>(
          <div key={k} className="confirm-row">
            <span className="confirm-key">{k}</span>
            <span className="confirm-val">{v}</span>
          </div>
        ))}
      </div>
      <button className="btn btn-ghost" onClick={onDone}>← New Registration</button>
    </div>
  );
}

// ── GUARD STATION ──────────────────────────────────────────────────────────
function GuardStation({pending,visitors,onApprove,onReject,onExit}){
  const active=visitors.filter(v=>!v.exitTime);
  const exited=visitors.filter(v=>v.exitTime);
  const cur=pending[0];
  const tLabel={community:"Community Patient",inmate:"Inmate Patient",staff:"Staff Patient",returning:"Visitor"};

  return(
    <div className="fade-in">
      <div className="metric-grid metric-grid-3" style={{marginBottom:22}}>
        {[["Awaiting Approval",pending.length,"var(--amber)"],["Active On Site",active.length,"var(--black)"],["Exited Today",exited.length,"var(--gray-400)"]].map(([l,v,c])=>(
          <div key={l} className="metric-card"><div className="metric-num" style={{color:c}}>{v}</div><div className="metric-lbl">{l}</div></div>
        ))}
      </div>

      {cur?(
        <div className="incoming-card">
          <div className="incoming-hdr">
            <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
            Incoming {tLabel[cur.type]||"Person"} — Awaiting Approval
            <Badge type={cur.type} style={{marginLeft:"auto"}}/>
            <span style={{marginLeft:"auto",border:"1px solid var(--gray-700)",padding:"1px 7px",fontSize:9,fontFamily:"var(--font-mono)"}}>{cur.ref}</span>
          </div>
          <div className="incoming-body">
            <div className="detail-grid">
              {[
                ["Full Name",       cur.name],
                cur.inmateNum  ? ["Inmate No.",    cur.inmateNum]    : null,
                cur.staffId    ? ["Staff ID",      cur.staffId]      : null,
                cur.idNum&&!cur.inmateNum&&!cur.staffId ? ["National ID",cur.idNum] : null,
                cur.block      ? ["Block / Cell",  cur.block+(cur.cell?" · "+cur.cell:"")] : null,
                cur.dept       ? ["Department",    cur.dept]         : null,
                cur.role       ? ["Role",          cur.role]         : null,
                cur.ward       ? ["Going To Ward", cur.ward]         : null,
                cur.condition  ? ["Known Condition",cur.condition]   : null,
                cur.allergies  ? ["Allergies",     cur.allergies]    : null,
                ["Reason",         cur.complaint||"—"],
                cur.escortOfficer&&cur.escortOfficer.trim() ? ["Escort Officer",cur.escortOfficer] : null,
                ["Arrived",        fmtTime(cur.entryTime)],
              ].filter(Boolean).map(([k,v])=>(
                <div key={k}>
                  <div className="detail-lbl">{k}</div>
                  <div className="detail-val" style={k==="Allergies"&&v!=="None known"?{color:"var(--red)"}:{}}>{v}</div>
                </div>
              ))}
            </div>

            {(cur.doctor||cur.nurse)&&(
              <div style={{background:"var(--gray-50)",border:"1px solid var(--gray-200)",padding:"11px 14px",marginBottom:14}}>
                <div style={{fontFamily:"var(--font-mono)",fontSize:9,fontWeight:700,color:"var(--gray-400)",letterSpacing:1.5,marginBottom:8}}>MEDICAL TEAM ASSIGNED</div>
                {cur.doctor&&<div style={{display:"flex",justifyContent:"space-between",alignItems:"center",marginBottom:cur.nurse?6:0}}>
                  <span style={{fontWeight:600,fontSize:13}}>{cur.doctor}</span>
                  <span style={{fontFamily:"var(--font-mono)",fontSize:9,color:"var(--green)",fontWeight:700}}>NOTIFIED</span>
                </div>}
                {cur.nurse&&<div style={{fontSize:12,color:"var(--gray-500)"}}>Charge Nurse: {cur.nurse}</div>}
              </div>
            )}

            {cur.type==="inmate"&&<div style={{background:"var(--purple-lite)",border:"1px solid #c4b5fd",padding:"9px 12px",fontSize:11,fontFamily:"var(--font-mono)",marginBottom:14}}>
              ⚠ &nbsp;INMATE PATIENT — Confirm escort officer is present before approving entry.
            </div>}

            {cur.isReturn&&<div style={{background:"var(--gray-50)",border:"1px solid var(--gray-200)",padding:"8px 12px",fontSize:11,fontFamily:"var(--font-mono)",marginBottom:14}}>
              ↩ RETURNING — Profile on file · {found&&found.visits+" visits"}
            </div>}

            <div style={{display:"flex",gap:10}}>
              <button className="btn btn-primary" style={{flex:1}} onClick={()=>onApprove(cur)}>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Approve &amp; Allow Entry
              </button>
              <button className="btn btn-danger" style={{minWidth:110}} onClick={()=>onReject(cur)}>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                Reject
              </button>
            </div>
          </div>
        </div>
      ):(
        <div style={{border:"2px dashed var(--gray-200)",padding:"26px",textAlign:"center",marginBottom:20,color:"var(--gray-400)",fontFamily:"var(--font-mono)",fontSize:12}}>
          <div style={{fontSize:24,marginBottom:8}}>✓</div>
          NO PENDING — QUEUE CLEAR
        </div>
      )}

      <div style={{display:"flex",alignItems:"center",justifyContent:"space-between",marginBottom:14}}>
        <div className="sec-label" style={{marginBottom:0}}>Currently On Site</div>
        <span style={{fontFamily:"var(--font-mono)",fontSize:10,color:"var(--gray-500)"}}>{active.length} ACTIVE</span>
      </div>
      {active.length===0?(
        <div style={{textAlign:"center",padding:"24px",color:"var(--gray-400)",fontFamily:"var(--font-mono)",fontSize:12}}>No one currently on site</div>
      ):active.map(v=>(
        <div key={v.ref} className="vrow">
          <div style={{flex:1,minWidth:0}}>
            <div style={{display:"flex",alignItems:"center",gap:8,marginBottom:3}}>
              <span className="vrow-name">{v.name}</span>
              <Badge type={v.type}/>
            </div>
            <div className="vrow-meta">
              {v.ward&&v.ward+" · "}{v.dept&&v.dept+" · "}{v.block&&v.block+" · "}
              {v.complaint&&v.complaint+" · "}
              {v.doctor&&v.doctor!=="self"&&v.doctor+" · "}
              {fmtDuration(v.entryTime,null)} on site
            </div>
            <div className="vrow-ref">{v.ref}</div>
          </div>
          <div style={{display:"flex",alignItems:"center",gap:10,flexShrink:0}}>
            <Badge status="ACTIVE"/>
            <button className="btn btn-ghost btn-sm" onClick={()=>onExit(v)}>Exit →</button>
          </div>
        </div>
      ))}
    </div>
  );
}

// ── VISITOR LOG ────────────────────────────────────────────────────────────
function VisitorLog({visitors}){
  const [search,setSearch]=useState("");
  const [typeF,setTypeF]=useState("ALL");
  const [statusF,setStatusF]=useState("ALL");
  const list=visitors.filter(v=>
    (typeF==="ALL"||v.type===typeF)&&
    (statusF==="ALL"||v.status===statusF)&&
    [v.name,v.ref,v.ward||"",v.dept||"",v.complaint||"",v.doctor||"",v.inmateNum||"",v.staffId||""].some(f=>f.toLowerCase().includes(search.toLowerCase()))
  );
  return(
    <div className="fade-in">
      <div className="search-row">
        <div className="search-wrap">
          <span className="search-icon"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
          <input type="text" className="form-input" style={{paddingLeft:34}} value={search} onChange={e=>setSearch(e.target.value)} placeholder="Search name, reference, doctor, ward, inmate no…"/>
        </div>
        <select className="form-select" style={{width:120}} value={typeF} onChange={e=>setTypeF(e.target.value)}>
          <option value="ALL">All Types</option>
          <option value="community">Community</option>
          <option value="inmate">Inmate</option>
          <option value="staff">Staff</option>
          <option value="returning">Visitor</option>
        </select>
        <select className="form-select" style={{width:110}} value={statusF} onChange={e=>setStatusF(e.target.value)}>
          {["ALL","ACTIVE","COMPLETED"].map(f=><option key={f}>{f}</option>)}
        </select>
      </div>
      <div style={{fontFamily:"var(--font-mono)",fontSize:10,color:"var(--gray-400)",marginBottom:14,letterSpacing:.5}}>{list.length} RECORD{list.length!==1?"S":""} FOUND</div>
      {list.length===0?(
        <div style={{textAlign:"center",padding:"40px",color:"var(--gray-400)",fontFamily:"var(--font-mono)",fontSize:12}}>NO RECORDS MATCH</div>
      ):list.slice().reverse().map((v,i)=>(
        <div key={v.ref} style={{border:"1px solid var(--gray-200)",padding:"18px",marginBottom:10,animationDelay:(i*.04)+"s",animation:"rowIn .2s ease both"}}>
          <div style={{display:"flex",justifyContent:"space-between",alignItems:"flex-start",marginBottom:12}}>
            <div>
              <div style={{display:"flex",alignItems:"center",gap:8,marginBottom:4}}>
                <span style={{fontWeight:700,fontSize:15}}>{v.name}</span>
                <Badge type={v.type}/>
              </div>
              <div style={{fontFamily:"var(--font-mono)",fontSize:10,color:"var(--gray-400)"}}>{v.ref}</div>
            </div>
            <Badge status={v.status}/>
          </div>
          <div style={{display:"grid",gridTemplateColumns:"1fr 1fr 1fr",gap:"8px 16px",fontSize:12}}>
            {[
              v.ward       ?["Ward",v.ward]                   :null,
              v.dept       ?["Dept",v.dept]                   :null,
              v.inmateNum  ?["Inmate No.",v.inmateNum]         :null,
              v.staffId    ?["Staff ID",v.staffId]             :null,
              v.block      ?["Block",v.block+(v.cell?" · "+v.cell:"")] :null,
              v.condition  ?["Condition",v.condition]          :null,
              v.allergies  ?["Allergies",v.allergies]          :null,
              v.complaint  ?["Reason",v.complaint]             :null,
              v.doctor&&v.doctor!=="self"?["Doctor",v.doctor]  :null,
              v.nurse      ?["Nurse",v.nurse]                  :null,
              ["Entry",fmtTime(v.entryTime)],
              ["Exit",v.exitTime?fmtTime(v.exitTime):"On site"],
              ["Duration",fmtDuration(v.entryTime,v.exitTime)],
            ].filter(Boolean).map(([k,val])=>(
              <div key={k}>
                <div style={{color:"var(--gray-400)",fontFamily:"var(--font-mono)",fontSize:9,textTransform:"uppercase",letterSpacing:1,fontWeight:700,marginBottom:2}}>{k}</div>
                <div style={{fontWeight:600,color:k==="Allergies"&&val!=="None known"?"var(--red)":"var(--black)"}}>{val}</div>
              </div>
            ))}
          </div>
        </div>
      ))}
    </div>
  );
}

// ── DOCTOR BOARD ───────────────────────────────────────────────────────────
function DoctorBoard({visitors}){
  const active=visitors.filter(v=>!v.exitTime);
  return(
    <div className="fade-in">
      <div style={{display:"flex",alignItems:"center",justifyContent:"space-between",marginBottom:18}}>
        <div className="sec-label" style={{marginBottom:0}}>On-Duty Doctors</div>
        <span style={{fontFamily:"var(--font-mono)",fontSize:10,color:"var(--gray-500)"}}>{DOCTORS.filter(d=>d.on).length} ON DUTY</span>
      </div>
      {DOCTORS.map(doc=>{
        const wardPts=active.filter(v=>(v.ward===doc.ward && (v.type==="community"||v.type==="returning"))||(v.assignedDoctor===doc.name && v.type==="inmate"));
        return(
          <div key={doc.id} style={{border:"1px solid var(--gray-200)",padding:"14px 16px",marginBottom:8}}>
            <div style={{display:"flex",alignItems:"center",justifyContent:"space-between",marginBottom:wardPts.length?10:0}}>
              <div>
                <div style={{fontWeight:700,fontSize:14}}>{doc.name}</div>
                <div style={{fontFamily:"var(--font-mono)",fontSize:10,color:"var(--gray-500)",marginTop:2}}>{doc.dept} · {doc.ward}</div>
              </div>
              <div style={{display:"flex",alignItems:"center",gap:5,fontFamily:"var(--font-mono)",fontSize:9,fontWeight:700}}>
                <span className={doc.on?"dot-on":"dot-off"}></span>
                <span style={{color:doc.on?"#16a34a":"var(--gray-400)"}}>{doc.on?"ON DUTY":"OFF SHIFT"}</span>
              </div>
            </div>
            {wardPts.length>0&&(
              <div style={{borderTop:"1px solid var(--gray-100)",paddingTop:10,display:"flex",gap:6,flexWrap:"wrap"}}>
                {wardPts.map(v=>(
                  <div key={v.ref} style={{fontFamily:"var(--font-mono)",fontSize:10,background:"var(--gray-50)",border:"1px solid var(--gray-200)",padding:"3px 9px",display:"flex",gap:6,alignItems:"center"}}>
                    <Badge type={v.type}/>
                    <span>{v.name}</span>
                    {v.inmateNum&&<span style={{color:"var(--gray-400)"}}>{v.inmateNum}</span>}
                    {v.complaint&&<span style={{color:"var(--gray-500)"}}>· {v.complaint}</span>}
                  </div>
                ))}
              </div>
            )}
            {wardPts.length===0&&<div style={{fontFamily:"var(--font-mono)",fontSize:10,color:"var(--gray-300)",marginTop:6}}>No active patients in ward</div>}
          </div>
        );
      })}

      <div style={{height:1,background:"var(--gray-200)",margin:"20px 0"}}></div>
      <div className="sec-label">Nurse Stations</div>
      {Object.entries(NURSE_STATIONS).map(([ward,nurse])=>{
        const cnt=active.filter(v=>v.ward===ward).length;
        return(
          <div key={ward} style={{display:"flex",alignItems:"center",gap:12,paddingBottom:8,marginBottom:8,borderBottom:"1px solid var(--gray-100)"}}>
            <div style={{fontFamily:"var(--font-mono)",fontSize:10,width:110,flexShrink:0,color:"var(--gray-600)"}}>{ward}</div>
            <div style={{flex:1,fontSize:12,fontWeight:500}}>{nurse}</div>
            <div style={{fontFamily:"var(--font-mono)",fontSize:10,color:"var(--gray-500)",minWidth:60,textAlign:"right"}}>{cnt} on site</div>
          </div>
        );
      })}
    </div>
  );
}

// ── ADMIN DASHBOARD ────────────────────────────────────────────────────────
function AdminDashboard({visitors}){
  const active=visitors.filter(v=>!v.exitTime);
  const completed=visitors.filter(v=>v.exitTime);
  const comm=active.filter(v=>v.type==="community"||v.type==="returning");
  const inm=active.filter(v=>v.type==="inmate");
  const stf=active.filter(v=>v.type==="staff");
  return(
    <div className="fade-in">
      <div className="metric-grid metric-grid-4" style={{marginBottom:22}}>
        {[["On Site Now",active.length,"var(--black)"],["Community",comm.length,"var(--green)"],["Inmates",inm.length,"var(--purple)"],["Staff Patients",stf.length,"var(--blue)"]].map(([l,v,c])=>(
          <div key={l} className="metric-card"><div className="metric-num" style={{color:c}}>{v}</div><div className="metric-lbl">{l}</div></div>
        ))}
      </div>

      <div style={{border:"1px solid var(--gray-200)",padding:"18px",marginBottom:14}}>
        <div className="sec-label">Ward Occupancy — Active Patients</div>
        {WARDS.map(w=>{
          const cnt=active.filter(v=>v.ward===w).length;
          return(
            <div key={w} className="ward-row-ui">
              <div className="ward-lbl-ui">{w}</div>
              <div className="ward-track"><div className="ward-fill" style={{width:cnt?Math.min(cnt/6*100,100)+"%":"0%"}}></div></div>
              <div className="ward-count-ui">{cnt}</div>
            </div>
          );
        })}
      </div>

      <div style={{border:"1px solid var(--gray-200)",padding:"18px"}}>
        <div className="sec-label">All Activity Today</div>
        {visitors.length===0?(
          <div style={{textAlign:"center",padding:"16px 0",color:"var(--gray-400)",fontFamily:"var(--font-mono)",fontSize:11}}>No activity recorded</div>
        ):visitors.slice().reverse().map(v=>(
          <div key={v.ref} className="activity-row">
            <div style={{display:"flex",alignItems:"center",gap:8,flexWrap:"wrap"}}>
              <span style={{fontWeight:600,fontSize:13}}>{v.name}</span>
              <Badge type={v.type}/>
              {(v.ward||v.dept||v.block)&&<span style={{fontSize:12,color:"var(--gray-400)"}}>→ {v.ward||v.dept||v.block}</span>}
              {v.complaint&&<span style={{fontSize:11,color:"var(--gray-400)"}}>· {v.complaint}</span>}
            </div>
            <div style={{display:"flex",alignItems:"center",gap:10,flexShrink:0}}>
              <span style={{fontFamily:"var(--font-mono)",fontSize:10,color:"var(--gray-400)"}}>{fmtTime(v.entryTime)}</span>
              <Badge status={v.status}/>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

// ── MAIN APP ───────────────────────────────────────────────────────────────
function App(){
  const [tab,setTab]=useState("kiosk");
  const [kStep,setKStep]=useState("home");
  const [kMode,setKMode]=useState(null);
  const [pending,setPending]=useState([]);
  const [visitors,setVisitors]=useState(()=>makeInitLog());
  const [lastRec,setLastRec]=useState(null);
  const [,tick]=useState(0);

  useEffect(()=>{ const t=setInterval(()=>tick(x=>x+1),30000); return()=>clearInterval(t); },[]);

  const onKioskSubmit=r=>{ setPending(p=>[...p,r]); setLastRec(r); setKStep("confirm"); };
  const onApprove=r=>{ setPending(p=>p.filter(v=>v.ref!==r.ref)); setVisitors(v=>[...v,{...r,status:"ACTIVE"}]); };
  const onReject=r=>setPending(p=>p.filter(v=>v.ref!==r.ref));
  const onExit=r=>setVisitors(v=>v.map(vis=>vis.ref===r.ref?{...vis,exitTime:new Date().toISOString(),status:"COMPLETED"}:vis));

  const pBadge=pending.length;
  const activeCount=visitors.filter(v=>!v.exitTime).length;

  const TABS=[
    {id:"kiosk",  label:"Gate Kiosk",    icon:"⬜", badge:0},
    {id:"guard",  label:"Guard Station", icon:"⬛", badge:pBadge},
    {id:"log",    label:"Patient Log",   icon:"▤",  badge:0},
    {id:"doctors",label:"Medical Board", icon:"✚",  badge:0},
    {id:"admin",  label:"Dashboard",     icon:"◫",  badge:0},
  ];

  const renderKiosk=()=>{
    if(kStep==="home")    return <KioskHome onSelect={m=>{setKMode(m);setKStep("form");}}/>;
    if(kStep==="confirm") return <KioskConfirm record={lastRec} onDone={()=>setKStep("home")}/>;
    if(kStep==="form"){
      if(kMode==="community") return <CommunityForm onSubmit={onKioskSubmit} onBack={()=>setKStep("home")}/>;
      if(kMode==="inmate")    return <InmateForm    onSubmit={onKioskSubmit} onBack={()=>setKStep("home")}/>;
      if(kMode==="staff")     return <StaffForm     onSubmit={onKioskSubmit} onBack={()=>setKStep("home")}/>;
      if(kMode==="returning") return <ReturningForm onSubmit={onKioskSubmit} onBack={()=>setKStep("home")}/>;
    }
  };

  return(
    <div id="app-shell">
      <div className="app-header">
        <div className="header-top">
          <div className="logo-block">
            <div className="logo-mark">UPDS</div>
            <div>
              <div className="logo-title">HOSPITAL REGISTRATION SYSTEM</div>
              <div className="logo-sub">Unified Patient &amp; Visitor Digital System · v5.0</div>
            </div>
          </div>
          <div className="header-pills">
            <div className="h-pill"><div className="h-pill-num">{activeCount}</div><div className="h-pill-lbl">On Site</div></div>
            <div className="h-pill"><div className="h-pill-num">{pBadge}</div><div className="h-pill-lbl">Pending</div></div>
            <div className="h-pill"><div className="h-pill-num">{visitors.filter(v=>v.exitTime).length}</div><div className="h-pill-lbl">Exited</div></div>
          </div>
        </div>
        <LiveClock/>
      </div>

      <div className="tab-bar">
        {TABS.map(t=>(
          <button key={t.id} className={"tab-btn"+(tab===t.id?" active":"")}
            onClick={()=>{ setTab(t.id); if(t.id==="kiosk") setKStep("home"); }}>
            {t.icon} {t.label}
            {t.badge>0&&<span className="tab-badge">{t.badge}</span>}
          </button>
        ))}
      </div>

      <div className="content-area">
        {tab==="kiosk"   && renderKiosk()}
        {tab==="guard"   && <GuardStation pending={pending} visitors={visitors} onApprove={onApprove} onReject={onReject} onExit={onExit}/>}
        {tab==="log"     && <VisitorLog   visitors={visitors}/>}
        {tab==="doctors" && <DoctorBoard  visitors={visitors}/>}
        {tab==="admin"   && <AdminDashboard visitors={visitors}/>}
      </div>

      <div className="app-footer">
        <span>UPDS Hospital Registration · v5.0 · {fmtDate(new Date())}</span>
        <span className="conf-tag">CONFIDENTIAL</span>
      </div>
    </div>
  );
}

ReactDOM.createRoot(document.getElementById("root")).render(<App/>);
</script>
</body>
</html>
