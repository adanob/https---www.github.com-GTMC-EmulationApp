<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EmulationApp - Dashboard</title>
<style>
  :root {
    --bg-primary: #F8F9FA;
    --bg-secondary: #FFFFFF;
    --bg-card: #FFFFFF;
    --bg-input: #F1F3F5;
    --bg-hover: #E9ECEF;
    --border: #DEE2E6;
    --border-focus: #0077b6;
    --text-primary: #1A1A1A;
    --text-secondary: #495057;
    --text-muted: #6C757D;
    --accent: #0077b6;
    --accent-glow: rgba(0,119,182,0.1);
    --green: #22c55e;
    --green-bg: rgba(34,197,94,0.1);
    --green-border: rgba(34,197,94,0.3);
    --red: #EF4444;
    --red-bg: #FFF5F5;
    --red-border: rgba(239,68,68,0.3);
    --amber: #f59e0b;
    --amber-bg: rgba(245,158,11,0.1);
    --amber-border: rgba(245,158,11,0.3);
    --purple: #7c3aed;
    --radius: 10px;
  }
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background:var(--bg-primary); color:var(--text-primary); min-height:100vh; }

  .topbar { display:flex; align-items:center; justify-content:space-between; padding:0 28px; height:56px; background:var(--bg-secondary); border-bottom:1px solid var(--border); }
  .topbar-left { display:flex; align-items:center; gap:12px; }
  .topbar-logo { width:32px; height:32px; border-radius:8px; background:linear-gradient(135deg,var(--accent),#0ea5e9); display:flex; align-items:center; justify-content:center; font-size:16px; font-weight:700; color:#fff; }
  .topbar-title { font-size:15px; font-weight:600; }
  .topbar-title span { color:var(--text-muted); font-weight:400; }
  .topbar-right { display:flex; align-items:center; gap:12px; }
  .settings-label { font-size:14px; color:var(--text-secondary); font-weight:500; }
  .gear-btn {
    width:36px; height:36px; padding:0; display:flex; align-items:center; justify-content:center;
    border-radius:8px; background:transparent; border:1px solid var(--border); color:var(--text-secondary);
    cursor:pointer; transition:all 0.15s;
  }
  .gear-btn:hover { background:var(--bg-hover); color:var(--text-primary); border-color:var(--text-muted); }
  .gear-btn svg { width:18px; height:18px; }

  .layout { display:grid; grid-template-columns:1fr 400px; min-height:calc(100vh - 56px); }
  .main-panel { padding:28px 32px; overflow-y:auto; }
  .side-panel { background:var(--bg-secondary); border-left:1px solid var(--border); padding:24px; overflow-y:auto; }

  .section { margin-bottom:20px; }
  .section-header { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
  .section-number { width:24px; height:24px; border-radius:50%; background:var(--accent-glow); border:1.5px solid var(--accent); display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:var(--accent); flex-shrink:0; }
  .section-title { font-size:14px; font-weight:600; }
  .section-subtitle { font-size:12px; color:var(--text-muted); margin-left:auto; }

  .field { margin-bottom:10px; }
  .field-label { display:block; font-size:12px; font-weight:500; color:var(--text-secondary); margin-bottom:5px; }
  .field-input, .field-select {
    width:100%; padding:9px 13px; font-size:14px; font-family:inherit;
    background:var(--bg-input); border:1px solid var(--border); border-radius:var(--radius);
    color:var(--text-primary); outline:none; transition:border-color 0.15s;
  }
  .field-input:focus, .field-select:focus { border-color:var(--border-focus); box-shadow:0 0 0 3px var(--accent-glow); }
  .field-input::placeholder { color:var(--text-muted); }
  .field-select { appearance:none; cursor:pointer; }
  .field-select option { background:var(--bg-input); color:var(--text-primary); }

  .cred-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
  .password-wrap { position:relative; }
  .password-wrap .field-input { padding-right:100px; }
  .encrypt-badge {
    position:absolute; right:10px; top:50%; transform:translateY(-50%);
    background:var(--green-bg); border:1px solid var(--green-border); color:var(--green);
    font-size:10px; font-weight:600; padding:3px 8px; border-radius:20px;
  }

  .divider { height:1px; background:var(--border); margin:4px 0 16px; }

  .btn-row-3 { display:grid; grid-template-columns:auto 1fr 1fr; gap:12px; }
  .clear-btn {
    padding:13px 18px; font-size:14px; font-weight:600; font-family:inherit;
    background:transparent; border:1px solid var(--border); border-radius:var(--radius);
    color:var(--text-muted); cursor:pointer; transition:all 0.2s;
    display:flex; align-items:center; justify-content:center; gap:8px;
  }
  .clear-btn:hover { border-color:var(--red); color:var(--red); background:rgba(239,68,68,0.08); }
  .save-btn {
    padding:13px; font-size:14px; font-weight:600; font-family:inherit;
    background:var(--bg-input); border:1px solid var(--border); border-radius:var(--radius);
    color:var(--text-primary); cursor:pointer; transition:all 0.2s;
    display:flex; align-items:center; justify-content:center; gap:8px;
  }
  .save-btn:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-glow); }
  .run-btn {
    padding:13px; font-size:14px; font-weight:600; font-family:inherit;
    background:linear-gradient(135deg,var(--accent),#0ea5e9); border:none; border-radius:var(--radius);
    color:#fff; cursor:pointer; transition:all 0.2s; box-shadow:0 4px 20px rgba(0,119,182,0.3);
    display:flex; align-items:center; justify-content:center; gap:8px;
  }
  .run-btn:hover:not(:disabled) { transform:translateY(-1px); box-shadow:0 6px 28px rgba(0,119,182,0.45); }
  .run-btn:disabled { background:var(--bg-hover); color:var(--text-muted); cursor:not-allowed; box-shadow:none; opacity:0.6; }
  .launch-btn {
    padding:13px; font-size:14px; font-weight:600; font-family:inherit;
    background:linear-gradient(135deg,#22c55e,#16a34a); border:none; border-radius:var(--radius);
    color:#fff; cursor:pointer; transition:all 0.2s; box-shadow:0 4px 20px rgba(34,197,94,0.3);
    display:flex; align-items:center; justify-content:center; gap:8px;
  }
  .launch-btn:hover:not(:disabled) { transform:translateY(-1px); box-shadow:0 6px 28px rgba(34,197,94,0.45); }
  .launch-btn:disabled { background:var(--bg-hover); color:var(--text-muted); cursor:not-allowed; box-shadow:none; opacity:0.6; }
  .launch-btn-sm {
    background:none; border:1px solid rgba(34,197,94,0.4); color:var(--text-muted); font-size:13px;
    padding:2px 6px; border-radius:6px; cursor:pointer; transition:all 0.15s; text-decoration:none;
  }
  .launch-btn-sm:hover { border-color:#22c55e; color:#22c55e; }
  .btn-helpers-4 { display:grid; grid-template-columns:auto 1fr 1fr 1fr; gap:12px; margin-top:6px; }
  .btn-helpers-3 { display:grid; grid-template-columns:auto 1fr 1fr; gap:12px; margin-top:6px; }
  .btn-helper { font-size:11px; color:var(--text-muted); text-align:center; }
  .btn-helper.ready { color:var(--green); }
  .btn-helper.warning { color:var(--amber); }

  .script-options { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
  .script-card {
    background:var(--bg-input); border:1px solid var(--border); border-radius:12px;
    padding:18px; cursor:pointer; transition:all 0.15s; text-align:center;
  }
  .script-card:hover { border-color:var(--accent); background:var(--bg-hover); }
  .script-card.active { border-color:var(--accent); background:var(--accent-glow); }
  .script-card-icon { font-size:28px; margin-bottom:8px; }
  .script-card-title { font-size:14px; font-weight:600; margin-bottom:4px; }
  .script-card-desc { font-size:12px; color:var(--text-muted); line-height:1.5; }
  .script-panel { margin-top:12px; }

  .pagecast-cta {
    display:flex; align-items:center; gap:14px; padding:16px;
    background:var(--bg-input); border:1px solid var(--border); border-radius:var(--radius);
  }
  .pagecast-cta-icon { font-size:24px; flex-shrink:0; }
  .pagecast-cta-body { flex:1; }
  .pagecast-cta-title { font-size:14px; font-weight:600; margin-bottom:4px; }
  .pagecast-cta-desc { font-size:12px; color:var(--text-muted); line-height:1.5; }
  .pagecast-launch-btn {
    flex-shrink:0; padding:10px 18px; font-size:13px; font-weight:600; font-family:inherit;
    background:linear-gradient(135deg,var(--accent),#0ea5e9); border:none; border-radius:var(--radius);
    color:#fff; cursor:pointer; display:flex; align-items:center; gap:6px;
    transition:all 0.2s; box-shadow:0 2px 12px rgba(0,119,182,0.3); white-space:nowrap;
  }
  .pagecast-launch-btn:hover { transform:translateY(-1px); box-shadow:0 4px 20px rgba(0,119,182,0.45); }

  .developer-check {
    display:flex; align-items:center; gap:10px; margin-top:14px;
    cursor:pointer; font-size:13px; color:var(--text-secondary); user-select:none;
  }
  .developer-check input[type="checkbox"] { display:none; }
  .developer-check-box {
    width:18px; height:18px; border:1.5px solid var(--border); border-radius:4px;
    background:var(--bg-input); flex-shrink:0; position:relative; transition:all 0.15s;
  }
  .developer-check input:checked ~ .developer-check-box { background:var(--accent); border-color:var(--accent); }
  .developer-check input:checked ~ .developer-check-box::after {
    content:''; position:absolute; top:2px; left:5px; width:5px; height:9px;
    border:solid #fff; border-width:0 2px 2px 0; transform:rotate(45deg);
  }
  .developer-check:hover .developer-check-box { border-color:var(--accent); }
  .developer-msg {
    display:flex; align-items:flex-start; gap:12px; margin-top:12px;
    padding:14px; background:var(--amber-bg); border:1px solid var(--amber-border);
    border-radius:var(--radius); font-size:13px; color:var(--text-secondary); line-height:1.5;
  }
  .developer-msg-icon { font-size:20px; flex-shrink:0; }
  .developer-msg-body strong { color:var(--text-primary); }

  .token-table-wrap {
    border:1px solid var(--border); border-radius:var(--radius);
    background:var(--bg-card); max-height:240px; overflow-y:auto;
  }
  .token-table { width:100%; border-collapse:collapse; font-size:13px; }
  .token-table thead { position:sticky; top:0; background:var(--bg-input); z-index:1; }
  .token-table th { padding:8px 12px; text-align:left; font-weight:600; font-size:11px; color:var(--text-secondary); border-bottom:1px solid var(--border); }
  .token-table td { padding:8px 12px; border-bottom:1px solid var(--border); }
  .token-table tbody tr:last-child td { border-bottom:none; }
  .token-table tbody tr:hover { background:var(--bg-hover); }
  .token-table td:first-child { font-family:'SF Mono',Consolas,monospace; color:var(--accent); font-size:12px; }
  .token-table input {
    width:100%; padding:6px 8px; font-size:13px; font-family:inherit;
    background:var(--bg-input); border:1px solid var(--border); border-radius:6px;
    color:var(--text-primary); outline:none;
  }
  .token-table input:focus { border-color:var(--border-focus); box-shadow:0 0 0 2px var(--accent-glow); }

  .script-cue {
    display:flex; align-items:flex-start; gap:10px; margin-bottom:10px;
    padding:12px 14px; background:var(--bg-input); border:1px solid var(--border);
    border-radius:var(--radius); font-size:13px; line-height:1.5;
  }
  .script-cue-icon { font-size:18px; flex-shrink:0; margin-top:1px; }
  .script-cue-body strong { color:var(--text-primary); }
  .script-cue.info { background:rgba(0,119,182,0.05); border-color:rgba(0,119,182,0.2); }
  .script-cue.info .script-cue-icon { color:var(--accent); }
  .script-cue.check { background:var(--green-bg); border-color:var(--green-border); }
  .script-cue.check .script-cue-icon { color:var(--green); }

  .log-panel-header {
    display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;
    padding-bottom:12px; border-bottom:1px solid var(--border);
  }
  .log-title { font-size:14px; font-weight:600; }
  .status-row { display:flex; align-items:center; gap:12px; }
  .status-badge {
    padding:4px 10px; font-size:11px; font-weight:600; border-radius:6px;
    background:var(--red-bg); border:1px solid var(--red-border); color:var(--red);
  }
  .status-badge.blocked { background:#FFF5F5; border-color:rgba(239,68,68,0.3); color:#EF4444; }
  .status-badge.running { background:rgba(0,119,182,0.1); border-color:rgba(0,119,182,0.3); color:var(--accent); }
  .status-badge.success { background:var(--green-bg); border-color:var(--green-border); color:var(--green); }

  .env-label { font-size:11px; color:var(--text-muted); font-weight:500; margin-right:4px; }
  .env-pills { display:flex; gap:6px; }
  .env-pill {
    padding:4px 10px; font-size:11px; font-weight:600; border-radius:6px;
    border:1px solid; cursor:pointer; transition:all 0.15s;
  }
  .env-pill.dev { border-color:var(--green-border); color:var(--green); background:var(--green-bg); }
  .env-pill.qa { border-color:var(--amber-border); color:var(--amber); background:transparent; }
  .env-pill.prod { border-color:var(--red-border); color:var(--red); background:transparent; }
  .env-pill:hover { opacity:0.8; }

  .log-box {
    background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius);
    padding:16px; max-height:480px; overflow-y:auto; font-family:'SF Mono',Consolas,monospace; font-size:12px; line-height:1.6;
  }
  .log-entry { margin-bottom:6px; display:flex; align-items:flex-start; gap:10px; }
  .log-entry-time { color:var(--text-muted); flex-shrink:0; }
  .log-entry-msg { flex:1; }
  .log-entry.info { color:var(--accent); }
  .log-entry.success { color:var(--green); }
  .log-entry.warn { color:var(--amber); }
  .log-entry.error { color:var(--red); }
  .log-entry.halt { color:var(--red); font-weight:600; }
  .log-entry.system { color:var(--text-muted); }

  .log-legend {
    margin-top:12px; padding-top:12px; border-top:1px solid var(--border);
    display:flex; gap:16px; flex-wrap:wrap; font-size:11px;
  }
  .log-legend-item { display:flex; align-items:center; gap:6px; color:var(--text-muted); }
  .log-legend-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
  .log-legend-dot.info { background:var(--accent); }
  .log-legend-dot.success { background:var(--green); }
  .log-legend-dot.warn { background:var(--amber); }
  .log-legend-dot.error { background:var(--red); }
  .log-legend-dot.halt { background:var(--red); }
  .log-legend-dot.system { background:var(--text-muted); }

  .preview-panel {
    background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius);
    max-height:320px; overflow:hidden; margin-top:16px;
  }
  .preview-header {
    padding:12px 16px; background:var(--bg-input); border-bottom:1px solid var(--border);
    display:flex; align-items:center; justify-content:space-between; font-size:13px; font-weight:600;
  }
  .preview-header-title { color:var(--text-primary); }
  .preview-header-lang { font-size:11px; color:var(--text-muted); font-weight:500; font-family:'SF Mono',Consolas,monospace; }
  .preview-body {
    padding:14px; overflow-y:auto; max-height:264px;
    background:var(--bg-card); font-family:'SF Mono',Consolas,monospace; font-size:12px; line-height:1.6;
  }
  .preview-empty {
    padding:40px 20px; text-align:center; color:var(--text-muted); font-size:13px; font-family:inherit;
  }
  .script-preview-code { margin:0; color:var(--text-primary); white-space:pre-wrap; word-wrap:break-word; }
  .script-preview-code .kw { color:#7c3aed; font-weight:600; }
  .script-preview-code .str { color:#22c55e; }
  .script-preview-code .cmt { color:var(--text-muted); font-style:italic; }
  .script-preview-code .fn { color:var(--accent); font-weight:600; }
  .script-preview-code .num { color:#f59e0b; }

  .upload-inline {
    margin-top:12px; padding:16px; background:var(--bg-input); border:1px dashed var(--border);
    border-radius:var(--radius); text-align:center; font-size:13px; color:var(--text-secondary); cursor:pointer;
    transition:all 0.15s; position:relative; overflow:hidden;
  }
  .upload-inline:hover { border-color:var(--accent); background:var(--accent-glow); }
  .upload-inline strong { color:var(--accent); }
  .upload-inline input[type="file"] { position:absolute; width:1px; height:1px; opacity:0; }
  .upload-inline-btn {
    display:none; margin-top:10px; padding:8px 16px; font-size:13px; font-weight:600;
    background:var(--accent); color:#fff; border:none; border-radius:var(--radius); cursor:pointer;
  }
  .upload-inline-btn:hover { background:#006a9e; }

  @media (max-width:1200px) {
    .layout { grid-template-columns:1fr; }
    .side-panel { border-left:none; border-top:1px solid var(--border); }
  }
</style>
</head>
<body>

<!-- Top Bar -->
<div class="topbar">
  <div class="topbar-left">
    <div class="topbar-logo">E</div>
    <div class="topbar-title">Emulation Dashboard <span>v0.9</span></div>
  </div>
  <div class="topbar-right">
    <span class="settings-label">Settings</span>
    <button class="gear-btn" onclick="alert('Settings panel coming soon')">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
      </svg>
    </button>
  </div>
</div>

<!-- Main Layout -->
<div class="layout">

  <!-- Left Panel: Job Configuration -->
  <div class="main-panel">
    <form id="payloadForm" method="POST" action="{{ route('payload.store') }}">
      @csrf
      <input type="hidden" name="payload_name" value="my_job_{{ substr(md5(uniqid()),0,5) }}">

      <!-- Section 1: Job Details -->
      <div class="section">
        <div class="section-header">
          <div class="section-number">1</div>
          <div class="section-title">Job Details</div>
        </div>
        <div class="field">
          <label class="field-label">Job Name</label>
          <input type="text" name="payload_name" class="field-input" placeholder="e.g. availity_claims_download_a3b7c" required>
        </div>
        <div class="field">
          <label class="field-label">Target URL</label>
          <input type="url" id="targetUrlInput" name="target_url" class="field-input" placeholder="https://apps.availity.com/..." required>
        </div>
      </div>

      <div class="divider"></div>

      <!-- Section 2: Navigation Script -->
      <div class="section">
        <div class="section-header">
          <div class="section-number">2</div>
          <div class="section-title">Navigation Script</div>
          <div class="section-subtitle">How to navigate the website</div>
        </div>

        <div class="script-options">
          <div class="script-card active" id="cardExisting" onclick="switchMode('existing')">
            <div class="script-card-icon">📜</div>
            <div class="script-card-title">Use Existing Script</div>
            <div class="script-card-desc">Select a pre-built navigation script</div>
          </div>
          <div class="script-card" id="cardPageCast" onclick="switchMode('pagecast')">
            <div class="script-card-icon">🎥</div>
            <div class="script-card-title">Record with PageCast</div>
            <div class="script-card-desc">Record your navigation live</div>
          </div>
        </div>

        <div class="script-panel" id="panelExisting">
          <div class="field">
            <label class="field-label">Select Script</label>
            <select id="scriptSelect" name="navigation_script" class="field-select">
              <option value="">-- Choose a script --</option>
              @foreach($scripts as $script)
                <option value="{{ $script }}" {{ old('navigation_script') == $script ? 'selected' : '' }}>
                  {{ $script }}
                </option>
              @endforeach
            </select>
          </div>

          <div id="scriptCues" style="display:none; margin-top:14px;">
            <div id="cueTargetUrl" class="script-cue info" style="display:none;">
              <div class="script-cue-icon">🌐</div>
              <div class="script-cue-body"><strong>Target URL detected:</strong> <span id="cueTargetUrlValue"></span></div>
            </div>
            <div id="cueCreds" class="script-cue check" style="display:none;">
              <div class="script-cue-icon">✓</div>
              <div class="script-cue-body"><strong>Credentials found</strong> for this portal</div>
            </div>
          </div>

          <div id="tokenTableWrap" class="token-table-wrap" style="display:none; margin-top:14px;">
            <table class="token-table">
              <thead>
                <tr>
                  <th style="width:35%">Token</th>
                  <th>Value</th>
                </tr>
              </thead>
              <tbody id="tokenTableBody">
                <!-- Dynamically populated -->
              </tbody>
            </table>
          </div>

          <div class="upload-inline" onclick="document.getElementById('hiddenFileInput').click()">
            <input type="file" id="hiddenFileInput" accept=".py,.json" onchange="handleInlineFileSelect(this)">
            <div id="inlineUploadText">Or <strong>upload a .py script</strong> or <strong>.json config</strong></div>
            <button type="button" id="inlineUploadBtn" class="upload-inline-btn" onclick="event.stopPropagation(); doInlineUpload()">Upload Now</button>
          </div>
        </div>

        <div class="script-panel" id="panelPageCast" style="display:none;">
          <div class="pagecast-cta">
            <div class="pagecast-cta-icon">🎬</div>
            <div class="pagecast-cta-body">
              <div class="pagecast-cta-title">Record your navigation</div>
              <div class="pagecast-cta-desc">Opens a browser window where you navigate the site. Your actions are recorded and converted into a reusable script.</div>
            </div>
            <button type="button" class="pagecast-launch-btn" onclick="alert('PageCast recording will launch here')">
              <span>🎥</span> Launch PageCast
            </button>
          </div>
        </div>
      </div>

      <div class="divider"></div>

      <!-- Section 3: Credentials -->
      <div class="section">
        <div class="section-header">
          <div class="section-number">3</div>
          <div class="section-title">Portal Credentials</div>
          <div class="section-subtitle">Optional</div>
        </div>
        <div class="cred-row">
          <div class="field">
            <label class="field-label">Username</label>
            <input type="text" name="username" class="field-input" placeholder="user@example.com">
          </div>
          <div class="field password-wrap">
            <label class="field-label">Password</label>
            <input type="password" name="password" class="field-input" placeholder="••••••••">
            <div class="encrypt-badge">🔒 ENCRYPTED</div>
          </div>
        </div>
      </div>

      <div class="divider"></div>

      <!-- Section 4: Developer Handoff -->
      <div class="section">
        <div class="section-header">
          <div class="section-number">4</div>
          <div class="section-title">Developer Handoff</div>
        </div>
        <label class="developer-check">
          <input type="checkbox" id="devCheck" name="is_developer_config">
          <div class="developer-check-box"></div>
          <span>I'm creating this configuration for a developer</span>
        </label>
        <div id="devMsg" class="developer-msg" style="display:none;">
          <div class="developer-msg-icon">💡</div>
          <div class="developer-msg-body">
            <strong>Developer mode:</strong> Your configuration will be saved as a JSON file that developers can use to build custom scripts. The job won't run automatically.
          </div>
        </div>
      </div>

      <div class="divider"></div>

      <!-- Actions -->
      <div class="btn-row-3">
        <button type="button" class="clear-btn" onclick="if(confirm('Clear all fields?')) document.getElementById('payloadForm').reset()">
          <span>🗑</span> Clear
        </button>
        <button type="submit" class="save-btn">
          <span>💾</span> Save Job
        </button>
        <button type="button" id="runJobBtn" class="run-btn" disabled onclick="alert('Run job clicked')">
          <span>▶</span> Run Job
        </button>
      </div>
      <div class="btn-helpers-3">
        <div class="btn-helper"></div>
        <div id="saveHelper" class="btn-helper">Saves your job configuration to jobs/</div>
        <div id="runJobHelper" class="btn-helper warning">Missing: Job Name, Navigation Script</div>
      </div>

      @if(isset($payload))
      <div style="margin-top:12px;">
        <button type="button" id="launchJobBtn" class="launch-btn" disabled onclick="window.location.href='{{ route('payload.launch', $payload->name) }}'">
          <span>🚀</span> Launch Headful
        </button>
        <div class="btn-helpers-4">
          <div class="btn-helper"></div>
          <div id="launchJobHelper" class="btn-helper"></div>
        </div>
      </div>
      @endif

    </form>
  </div>

  <!-- Right Panel: Job Log -->
  <div class="side-panel">
    <div class="log-panel-header">
      <div class="log-title">Job Log</div>
      <div class="status-row">
        <div class="status-badge blocked">⊘ BLOCKED</div>
        <span class="env-label">Environment:</span>
        <div class="env-pills">
          <div class="env-pill dev">DEV</div>
          <div class="env-pill qa">QA</div>
          <div class="env-pill prod">PROD</div>
        </div>
      </div>
    </div>

    <div class="log-box">
      <div class="log-entry system">
        <div class="log-entry-time">14:23:01.0</div>
        <div class="log-entry-msg">Dashboard initialised — DEV</div>
      </div>
      <div class="log-entry system">
        <div class="log-entry-time">14:23:01.1</div>
        <div class="log-entry-msg">Engine ready  ·  Coordinator loaded</div>
      </div>
      <div class="log-entry warn">
        <div class="log-entry-time">14:23:01.2</div>
        <div class="log-entry-msg">WARNING  ·  No navigation script bound</div>
      </div>
      <div class="log-entry system">
        <div class="log-entry-time"></div>
        <div class="log-entry-msg">─────────────────────────────────────</div>
      </div>
      <div class="log-entry error">
        <div class="log-entry-time">14:31:44.1</div>
        <div class="log-entry-msg">Job rejected  ·  no script bound</div>
      </div>
      <div class="log-entry halt">
        <div class="log-entry-time">14:31:44.2</div>
        <div class="log-entry-msg">HALT  ·  payload valid but no class</div>
      </div>
      <div class="log-entry halt">
        <div class="log-entry-time">14:31:44.3</div>
        <div class="log-entry-msg">    of instructions for navigation</div>
      </div>
      <div class="log-entry warn">
        <div class="log-entry-time">14:31:44.4</div>
        <div class="log-entry-msg">Action  ·  assign navigation script</div>
      </div>
      <div class="log-entry system">
        <div class="log-entry-time"></div>
        <div class="log-entry-msg">─────────────────────────────────────</div>
      </div>
      <div class="log-entry info">
        <div class="log-entry-time">14:31:55.0</div>
        <div class="log-entry-msg">User bound script: availity_navigator.py</div>
      </div>
      <div class="log-entry success">
        <div class="log-entry-time">14:31:55.1</div>
        <div class="log-entry-msg">Script validation passed</div>
      </div>
      <div class="log-entry success">
        <div class="log-entry-time">14:31:55.2</div>
        <div class="log-entry-msg">Ready to execute</div>
      </div>

      <div class="log-legend">
        <div class="log-legend-item"><div class="log-legend-dot info"></div> info</div>
        <div class="log-legend-item"><div class="log-legend-dot success"></div> success</div>
        <div class="log-legend-item"><div class="log-legend-dot warn"></div> warn</div>
        <div class="log-legend-item"><div class="log-legend-dot error"></div> error</div>
        <div class="log-legend-item"><div class="log-legend-dot halt"></div> halt</div>
        <div class="log-legend-item"><div class="log-legend-dot system"></div> system</div>
      </div>
    </div>

    <!-- Script Preview Panel -->
    <div class="preview-panel">
      <div id="scriptPreviewHeader" class="preview-header" style="display:none;">
        <div class="preview-header-title" id="scriptPreviewName">Script Preview</div>
        <div class="preview-header-lang">Python</div>
      </div>
      <div id="scriptPreviewBody" class="preview-body">
        <div class="preview-empty">
          Select a navigation script to preview its code here
        </div>
      </div>
    </div>
  </div>

</div>

<script>
  var currentMode = 'existing';

  function switchMode(mode) {
    currentMode = mode;
    document.getElementById('cardExisting').classList.toggle('active', mode === 'existing');
    document.getElementById('cardPageCast').classList.toggle('active', mode === 'pagecast');
    document.getElementById('panelExisting').style.display = mode === 'existing' ? 'block' : 'none';
    document.getElementById('panelPageCast').style.display = mode === 'pagecast' ? 'block' : 'none';

    if (mode !== 'existing') {
      hideScriptCues();
      document.getElementById('tokenTableWrap').style.display = 'none';
      showEmptyPreview();
    }
    checkRunReady();
  }

  function generateId() {
    return Math.random().toString(36).substr(2,5);
  }

  function showEmptyPreview() {
    document.getElementById('scriptPreviewHeader').style.display = 'none';
    document.getElementById('scriptPreviewBody').innerHTML = '<div class="preview-empty">Select a navigation script to preview its code here</div>';
  }

  function hideScriptCues() {
    document.getElementById('scriptCues').style.display = 'none';
    document.getElementById('cueTargetUrl').style.display = 'none';
    document.getElementById('cueCreds').style.display = 'none';
  }

  function resetTokenTable() {
    document.getElementById('tokenTableWrap').style.display = 'none';
    document.getElementById('tokenTableBody').innerHTML = '';
  }

  function setTokensFromScript(tokens, usesTargetUrl, usesCreds, suggestedUrl, hasSavedCreds) {
    hideScriptCues();
    if (usesTargetUrl || usesCreds) {
      document.getElementById('scriptCues').style.display = 'block';
    }
    if (usesTargetUrl && suggestedUrl) {
      document.getElementById('cueTargetUrl').style.display = 'flex';
      document.getElementById('cueTargetUrlValue').textContent = suggestedUrl;
      document.getElementById('targetUrlInput').value = suggestedUrl;
    }
    if (usesCreds && hasSavedCreds) {
      document.getElementById('cueCreds').style.display = 'flex';
    }

    if (tokens.length > 0) {
      var tbody = document.getElementById('tokenTableBody');
      tbody.innerHTML = '';
      tokens.forEach(function(tk) {
        var tr = document.createElement('tr');
        var td1 = document.createElement('td');
        td1.textContent = tk;
        var td2 = document.createElement('td');
        var inp = document.createElement('input');
        inp.type = 'text';
        inp.name = 'tokens[' + tk + ']';
        inp.placeholder = 'Enter value';
        td2.appendChild(inp);
        tr.appendChild(td1);
        tr.appendChild(td2);
        tbody.appendChild(tr);
      });
      document.getElementById('tokenTableWrap').style.display = 'block';
    } else {
      resetTokenTable();
    }
  }

  function highlightScript(code) {
    var h = code
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/(def|class|import|from|return|if|else|elif|for|while|try|except|with|as|pass|break|continue|yield|lambda|async|await)\b/g, '<span class="kw">$1</span>')
      .replace(/(['"])([^'"]*)\1/g, '<span class="str">$1$2$1</span>')
      .replace(/#.*/g, '<span class="cmt">$&</span>')
      .replace(/\b(\w+)\(/g, '<span class="fn">$1</span>(')
      .replace(/\b(\d+)\b/g, '<span class="num">$1</span>');
    return h;
  }

  function fetchScriptContent(scriptName) {
    if (!scriptName) { showEmptyPreview(); return; }
    fetch('/script/' + encodeURIComponent(scriptName) + '/content')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.content) {
          document.getElementById('scriptPreviewHeader').style.display = 'flex';
          document.getElementById('scriptPreviewName').textContent = data.name || scriptName;
          document.getElementById('scriptPreviewBody').innerHTML =
            '<pre class="script-preview-code">' + highlightScript(data.content) + '</pre>';
        }
      })
      .catch(function() { showEmptyPreview(); });
  }

  function analyseScript(scriptName) {
    if (!scriptName) {
      hideScriptCues();
      resetTokenTable();
      return;
    }
    fetch('/script/' + encodeURIComponent(scriptName) + '/tokens')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        setTokensFromScript(
          data.tokens || [],
          data.uses_target_url || false,
          data.uses_credentials || false,
          data.suggested_url || null,
          data.has_saved_creds || false
        );
      })
      .catch(function() {});
  }

  function handleInlineFileSelect(input) {
    var name = input.files[0] ? input.files[0].name : '';
    var textEl = document.getElementById('inlineUploadText');
    var btnEl  = document.getElementById('inlineUploadBtn');
    if (name) {
      textEl.innerHTML = '<strong>' + name + '</strong> selected';
      btnEl.style.display = 'inline-block';
    } else {
      textEl.innerHTML = 'Or <strong>upload a .py script</strong> or <strong>.json config</strong>';
      btnEl.style.display = 'none';
    }
  }

  function doInlineUpload() {
    var input = document.getElementById('hiddenFileInput');
    if (!input.files[0]) return;

    var formData = new FormData();
    formData.append('payload_file', input.files[0]);
    formData.append('_token', document.querySelector('#payloadForm input[name="_token"]').value);

    var textEl = document.getElementById('inlineUploadText');
    textEl.innerHTML = '<strong>Uploading...</strong>';

    fetch('{{ route("payload.upload") }}', { method: 'POST', body: formData })
      .then(function(r) {
        if (r.redirected) {
          window.location.href = r.url;
        } else {
          window.location.reload();
        }
      })
      .catch(function() {
        textEl.innerHTML = '<strong style="color:var(--red)">Upload failed. Try again.</strong>';
      });
  }

  document.getElementById('devCheck').addEventListener('change', function() {
    document.getElementById('devMsg').style.display = this.checked ? 'flex' : 'none';
    checkRunReady();
  });

  function checkRunReady() {
    var btn    = document.getElementById('runJobBtn');
    var lbtn   = document.getElementById('launchJobBtn');
    var helper = document.getElementById('runJobHelper');
    var lhelp  = document.getElementById('launchJobHelper');
    var saveH  = document.getElementById('saveHelper');
    var form   = document.getElementById('payloadForm');
    if (!form || !btn) return;

    var nameVal    = (form.querySelector('[name="payload_name"]').value || '').trim();
    var urlVal     = (document.getElementById('targetUrlInput').value || '').trim();
    var scriptSel  = document.getElementById('scriptSelect');
    var scriptVal  = scriptSel ? scriptSel.value : '';
    var devChecked = document.getElementById('devCheck').checked;

    function disableBoth(msg, cls) {
      btn.disabled = true;
      if (lbtn) lbtn.disabled = true;
      helper.textContent = msg;
      helper.className = 'btn-helper ' + (cls || 'warning');
      if (lhelp) { lhelp.textContent = ''; lhelp.className = 'btn-helper'; }
    }

    if (devChecked) {
      disableBoth('Save your configuration, then share it with your developer');
      saveH.textContent = 'Saves configuration for developer handoff';
      saveH.className = 'btn-helper ready';
      return;
    }
    if (currentMode === 'pagecast') {
      disableBoth('Record your navigation with PageCast first');
      saveH.textContent = 'Saves your job configuration to jobs/';
      saveH.className = 'btn-helper';
      return;
    }
    var missing = [];
    if (!nameVal) missing.push('Job Name');
    if (currentMode !== 'existing') missing.push('Navigation Script');
    else if (!scriptVal) missing.push('Navigation Script');

    if (missing.length > 0) {
      disableBoth('Missing: ' + missing.join(', '));
    } else {
      btn.disabled = false;
      if (lbtn) lbtn.disabled = false;
      helper.textContent = 'Runs headless in background';
      helper.className = 'btn-helper ready';
      if (lhelp) { lhelp.textContent = 'Downloads .bat — opens browser on your screen'; lhelp.className = 'btn-helper ready'; }
    }
    saveH.textContent = 'Saves your job configuration to jobs/';
    saveH.className = 'btn-helper';
  }

  (function() {
    var form = document.getElementById('payloadForm');
    if (!form) return;
    form.querySelector('[name="payload_name"]').addEventListener('input', checkRunReady);
    document.getElementById('targetUrlInput').addEventListener('input', checkRunReady);

    var scriptSel = document.getElementById('scriptSelect');
    if (scriptSel) {
      scriptSel.addEventListener('change', function() {
        var scriptVal = this.value;
        if (scriptVal) {
          var base = scriptVal.replace(/\.py$/, '');
          var nameInput = form.querySelector('[name="payload_name"]');
          var current = nameInput.value;
          if (!current || current === 'my_job' || current.match(/^my_job_[a-z0-9]{5}$/) || current.match(/^.+_[a-z0-9]{5}$/)) {
            nameInput.value = base + '_' + generateId();
          }
        }
        checkRunReady();
        analyseScript(this.value);
        fetchScriptContent(this.value);
      });
    }
    checkRunReady();
  })();
</script>
</body>
</html>
