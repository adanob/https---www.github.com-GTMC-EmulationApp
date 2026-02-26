<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EmulationApp - Dashboard</title>
<style>
  :root {
    --bg-primary: #0E0F1A;
    --bg-secondary: #161828;
    --bg-card: #1C1E32;
    --bg-input: #232540;
    --bg-hover: #2A2D4A;
    --border: #2E3150;
    --border-focus: #6C72FF;
    --text-primary: #E8E9F0;
    --text-secondary: #8B8FAE;
    --text-muted: #5A5E7A;
    --accent: #6C72FF;
    --accent-glow: rgba(108,114,255,0.15);
    --green: #34D399;
    --green-bg: rgba(52,211,153,0.1);
    --green-border: rgba(52,211,153,0.3);
    --red: #F87171;
    --red-bg: rgba(248,113,113,0.08);
    --amber: #FBBF24;
    --amber-bg: rgba(251,191,36,0.1);
    --amber-border: rgba(251,191,36,0.3);
    --radius: 10px;
  }
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background:var(--bg-primary); color:var(--text-primary); min-height:100vh; }

  /* Top Bar */
  .topbar { display:flex; align-items:center; justify-content:space-between; padding:0 28px; height:56px; background:var(--bg-secondary); border-bottom:1px solid var(--border); }
  .topbar-left { display:flex; align-items:center; gap:12px; }
  .topbar-logo { width:32px; height:32px; border-radius:8px; background:linear-gradient(135deg,var(--accent),#A78BFA); display:flex; align-items:center; justify-content:center; font-size:16px; font-weight:700; color:#fff; }
  .topbar-title { font-size:15px; font-weight:600; }
  .topbar-title span { color:var(--text-muted); font-weight:400; }
  .topbar-right { display:flex; align-items:center; gap:8px; }
  .topbar-btn {
    background:transparent; border:1px solid var(--border); color:var(--text-secondary);
    padding:6px 12px; border-radius:8px; font-size:13px; font-family:inherit;
    cursor:pointer; display:flex; align-items:center; gap:6px; transition:all 0.15s;
  }
  .topbar-btn:hover { background:var(--bg-hover); color:var(--text-primary); border-color:var(--text-muted); }
  .topbar-btn svg { width:15px; height:15px; }
  .gear-btn { width:36px; height:36px; padding:0; display:flex; align-items:center; justify-content:center; border-radius:8px; }
  .gear-btn svg { width:18px; height:18px; }

  /* Layout */
  .layout { display:grid; grid-template-columns:1fr 360px; min-height:calc(100vh - 56px); }
  .main-panel { padding:28px 32px; overflow-y:auto; }
  .side-panel { background:var(--bg-secondary); border-left:1px solid var(--border); padding:24px; overflow-y:auto; }

  /* Section */
  .section { margin-bottom:20px; }
  .section-header { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
  .section-number { width:24px; height:24px; border-radius:50%; background:var(--accent-glow); border:1.5px solid var(--accent); display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:var(--accent); }
  .section-title { font-size:14px; font-weight:600; }
  .section-subtitle { font-size:12px; color:var(--text-muted); margin-left:auto; }

  /* Inputs */
  .field { margin-bottom:10px; }
  .field-label { display:block; font-size:12px; font-weight:500; color:var(--text-secondary); margin-bottom:5px; }
  .field-input, .field-select, .field-textarea {
    width:100%; padding:9px 13px; font-size:14px; font-family:inherit;
    background:var(--bg-input); border:1px solid var(--border); border-radius:var(--radius);
    color:var(--text-primary); outline:none; transition:border-color 0.15s;
  }
  .field-input:focus, .field-select:focus, .field-textarea:focus { border-color:var(--border-focus); box-shadow:0 0 0 3px var(--accent-glow); }
  .field-input::placeholder, .field-textarea::placeholder { color:var(--text-muted); }
  .field-select { appearance:none; cursor:pointer; }
  .field-select option { background:var(--bg-input); color:var(--text-primary); }
  .field-textarea { min-height:100px; resize:vertical; font-family:'Consolas','Courier New',monospace; font-size:13px; }

  /* Credential Row */
  .cred-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
  .password-wrap { position:relative; }
  .password-wrap .field-input { padding-right:100px; }
  .encrypt-badge {
    position:absolute; right:10px; top:50%; transform:translateY(-50%);
    background:var(--green-bg); border:1px solid var(--green-border); color:var(--green);
    font-size:10px; font-weight:600; padding:3px 8px; border-radius:20px;
  }

  /* Divider */
  .divider { height:1px; background:var(--border); margin:4px 0 16px; }

  /* Button Row */
  .btn-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
  .btn-row-3 { display:grid; grid-template-columns:auto 1fr 1fr; gap:12px; }
  .clear-btn {
    padding:13px 18px; font-size:14px; font-weight:600; font-family:inherit;
    background:transparent; border:1px solid var(--border); border-radius:var(--radius);
    color:var(--text-muted); cursor:pointer; transition:all 0.2s;
    display:flex; align-items:center; justify-content:center; gap:8px;
  }
  .clear-btn:hover { border-color:var(--red); color:var(--red); background:rgba(248,113,113,0.08); }
  .clear-btn svg { flex-shrink:0; }
  .save-btn {
    padding:13px; font-size:14px; font-weight:600; font-family:inherit;
    background:var(--bg-input); border:1px solid var(--border);
    border-radius:var(--radius);
    color:var(--text-primary); cursor:pointer; transition:all 0.2s;
    display:flex; align-items:center; justify-content:center; gap:8px;
  }
  .save-btn:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-glow); }
  .save-btn svg { flex-shrink:0; }
  .run-btn {
    padding:13px; font-size:14px; font-weight:600; font-family:inherit;
    background:linear-gradient(135deg,var(--accent),#8B5CF6); border:none;
    border-radius:var(--radius);
    color:#fff; cursor:pointer; transition:all 0.2s; box-shadow:0 4px 20px rgba(108,114,255,0.3);
    display:flex; align-items:center; justify-content:center; gap:8px;
  }
  .run-btn:hover:not(:disabled) { transform:translateY(-1px); box-shadow:0 6px 28px rgba(108,114,255,0.45); }
  .run-btn:disabled {
    background:var(--bg-hover); color:var(--text-muted); cursor:not-allowed;
    box-shadow:none; opacity:0.6;
  }
  .run-btn svg { flex-shrink:0; }
  .btn-helpers { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:6px; }
  .btn-helpers-3 { display:grid; grid-template-columns:auto 1fr 1fr; gap:12px; margin-top:6px; }
  .btn-helper { font-size:11px; color:var(--text-muted); text-align:center; }
  .btn-helper.ready { color:var(--green); }
  .btn-helper.warning { color:var(--amber); }

  /* Script Cards */
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

  /* PageCast CTA */
  .pagecast-cta {
    display:flex; align-items:center; gap:14px; padding:16px;
    background:var(--bg-input); border:1px solid var(--border);
    border-radius:var(--radius);
  }
  .pagecast-cta-icon { font-size:24px; flex-shrink:0; }
  .pagecast-cta-body { flex:1; }
  .pagecast-cta-title { font-size:14px; font-weight:600; margin-bottom:4px; }
  .pagecast-cta-desc { font-size:12px; color:var(--text-muted); line-height:1.5; }
  .pagecast-launch-btn {
    flex-shrink:0; padding:10px 18px; font-size:13px; font-weight:600; font-family:inherit;
    background:linear-gradient(135deg,var(--accent),#8B5CF6); border:none;
    border-radius:var(--radius);
    color:#fff; cursor:pointer; display:flex; align-items:center; gap:6px;
    transition:all 0.2s; box-shadow:0 2px 12px rgba(108,114,255,0.3); white-space:nowrap;
  }
  .pagecast-launch-btn:hover { transform:translateY(-1px); box-shadow:0 4px 20px rgba(108,114,255,0.45); }

  /* Developer Checkbox */
  .developer-check {
    display:flex; align-items:center; gap:10px; margin-top:14px;
    cursor:pointer; font-size:13px; color:var(--text-secondary); user-select:none;
  }
  .developer-check input[type="checkbox"] { display:none; }
  .developer-check-box {
    width:18px; height:18px; border:1.5px solid var(--border); border-radius:4px;
    background:var(--bg-input); flex-shrink:0; position:relative; transition:all 0.15s;
  }
  .developer-check input:checked ~ .developer-check-box {
    background:var(--accent); border-color:var(--accent);
  }
  .developer-check input:checked ~ .developer-check-box::after {
    content:''; position:absolute; top:2px; left:5px; width:5px; height:9px;
    border:solid #fff; border-width:0 2px 2px 0; transform:rotate(45deg);
  }
  .developer-check:hover .developer-check-box { border-color:var(--accent); }

  /* Developer Message */
  .developer-msg {
    display:flex; align-items:flex-start; gap:12px; margin-top:12px;
    padding:14px; background:var(--amber-bg); border:1px solid var(--amber-border);
    border-radius:var(--radius); font-size:13px; color:var(--text-secondary); line-height:1.5;
  }
  .developer-msg-icon { font-size:20px; flex-shrink:0; margin-top:1px; }
  .developer-msg-body strong { color:var(--text-primary); }

  /* Alerts */
  .alert { padding:12px 16px; border-radius:var(--radius); margin-bottom:16px; font-size:13px; }
  .alert-success { background:var(--green-bg); border:1px solid var(--green-border); color:var(--green); }
  .alert-error { background:var(--red-bg); border:1px solid rgba(248,113,113,0.3); color:var(--red); }

  /* Side Panel */
  .side-title { font-size:14px; font-weight:600; margin-bottom:14px; display:flex; align-items:center; gap:8px; }
  .side-divider { height:1px; background:var(--border); margin:16px 0; }

  /* Payload Items */
  .payload-item {
    display:flex; align-items:center; gap:8px; padding:10px 12px;
    background:var(--bg-card); border:1px solid var(--border);
    border-radius:var(--radius);
    margin-bottom:6px; font-size:13px; transition:border-color 0.15s;
  }
  .payload-item:hover { border-color:var(--text-muted); }
  .payload-icon { font-size:14px; }
  .payload-name { font-family:'Consolas','Courier New',monospace; font-size:11px; flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .payload-actions { display:flex; gap:4px; flex-shrink:0; }
  .payload-actions a, .payload-actions button {
    font-size:11px; padding:3px 8px; border-radius:6px; border:1px solid var(--border);
    background:transparent; color:var(--text-secondary); cursor:pointer; text-decoration:none;
    font-family:inherit; transition:all 0.15s;
  }
  .payload-actions a:hover, .payload-actions button:hover { border-color:var(--accent); color:var(--accent); }
  .payload-actions button.delete-btn:hover { border-color:var(--red); color:var(--red); }
  .payload-actions button.run-btn-sm { }
  .payload-actions button.run-btn-sm:hover { border-color:var(--green); color:var(--green); }
  .empty-state { color:var(--text-muted); font-size:13px; padding:20px 0; text-align:center; }

  /* Upload Area */
  .upload-area {
    border:1px dashed var(--border);
    border-radius:var(--radius);
    padding:16px; text-align:center; cursor:pointer; transition:all 0.15s; position:relative; display:block;
  }
  .upload-area:hover { border-color:var(--accent); background:var(--accent-glow); }
  .upload-area input[type="file"] { position:absolute; inset:0; opacity:0; cursor:pointer; }
  .upload-area-icon { font-size:24px; margin-bottom:6px; }
  .upload-area-text { font-size:12px; color:var(--text-muted); }
  .upload-area-text strong { color:var(--text-secondary); }

  /* Token Table */
  .token-table { width:100%; border-collapse:separate; border-spacing:0; }
  .token-table th {
    text-align:left; font-size:11px; font-weight:600; color:var(--text-muted);
    text-transform:uppercase; letter-spacing:0.5px; padding:0 12px 8px;
  }
  .token-table td { padding:0; }
  .token-table td .field-input {
    border-radius:0; border-right:none; margin:0;
    font-family:'Consolas','Courier New',monospace; font-size:13px;
  }
  .token-table tr:first-child td:first-child .field-input { border-top-left-radius:var(--radius); }
  .token-table tr:first-child td:last-child .field-input { border-top-right-radius:var(--radius); border-right:1px solid var(--border); }
  .token-table tr:last-child td:first-child .field-input { border-bottom-left-radius:var(--radius); }
  .token-table tr:last-child td:last-child .field-input { border-bottom-right-radius:var(--radius); border-right:1px solid var(--border); }
  .token-table tr:not(:last-child) td .field-input { border-bottom:none; }
  .token-table td:last-child .field-input { border-right:1px solid var(--border); }
  .add-token-btn {
    display:flex; align-items:center; gap:6px; margin-top:10px;
    background:transparent; border:1px dashed var(--border);
    border-radius:var(--radius);
    color:var(--text-muted); font-size:13px; font-family:inherit;
    padding:8px 14px; cursor:pointer; transition:all 0.15s; width:100%;
    justify-content:center;
  }
  .add-token-btn:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-glow); }
  .token-hint {
    padding:10px 14px; margin-bottom:10px;
    background:var(--green-bg); border:1px solid var(--green-border); border-radius:var(--radius);
    font-size:12px; color:var(--green); line-height:1.5;
  }
  .upload-submit {
    display:block; width:100%; margin-top:10px; padding:8px; font-size:13px; font-weight:600;
    font-family:inherit; background:var(--bg-input); border:1px solid var(--border);
    border-radius:var(--radius);
    color:var(--text-primary); cursor:pointer; transition:all 0.15s;
  }
  .upload-submit:hover { border-color:var(--accent); color:var(--accent); }

  /* Modal Overlay */
  .modal-overlay {
    display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6);
    z-index:100; align-items:center; justify-content:center;
  }
  .modal-overlay.active { display:flex; }
  .modal {
    background:var(--bg-card); border:1px solid var(--border); border-radius:14px;
    padding:28px; width:420px; max-width:90vw; box-shadow:0 8px 40px rgba(0,0,0,0.5);
  }
  .modal-title { font-size:16px; font-weight:700; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
  .modal-title svg { width:20px; height:20px; color:var(--text-muted); }
  .modal-close {
    margin-left:auto; background:transparent; border:none; color:var(--text-muted);
    font-size:18px; cursor:pointer; padding:4px 8px; border-radius:6px; transition:all 0.15s;
  }
  .modal-close:hover { color:var(--text-primary); background:var(--bg-hover); }
  .modal-btn {
    width:100%; padding:11px; font-size:14px; font-weight:600; font-family:inherit;
    background:var(--accent); border:none;
    border-radius:var(--radius);
    color:#fff; cursor:pointer; transition:all 0.15s; margin-top:16px;
  }
  .modal-btn:hover { background:#8186FF; }

  /* uv Install Help */
  .uv-install-help { margin-top:8px; }
  .install-steps {
    margin-top:8px; padding:12px; background:var(--bg-primary);
    border:1px solid var(--border); border-radius:var(--radius);
  }
  .install-step-label { font-size:12px; color:var(--text-secondary); margin-bottom:4px; }
  .install-cmd {
    display:block; padding:8px 10px; background:var(--bg-input); border:1px solid var(--border);
    border-radius:6px; font-family:'Consolas','Courier New',monospace; font-size:12px;
    color:var(--green); word-break:break-all; user-select:all; cursor:text;
  }

  /* Job Log */
  .log-dot { width:8px; height:8px; border-radius:50%; display:inline-block; }
  .dot-green { background:var(--green); box-shadow:0 0 6px var(--green); }
  .dot-red { background:var(--red); box-shadow:0 0 6px var(--red); }
  .dot-idle { background:var(--text-muted); }
  .log-entries { display:flex; flex-direction:column; gap:0; }
  .log-entry {
    display:grid; grid-template-columns:64px 24px 1fr; gap:8px; align-items:start;
    padding:10px 0; border-top:1px solid var(--border);
  }
  .log-entry:last-child { border-bottom:1px solid var(--border); }
  .log-time {
    font-family:'Consolas','Courier New',monospace; font-size:12px;
    color:var(--text-muted); padding-top:1px;
  }
  .log-icon { font-size:14px; text-align:center; padding-top:1px; }
  .log-text { font-size:13px; color:var(--green); line-height:1.5; }
  .log-text strong { color:#6EE7B7; font-weight:600; }
  .log-status {
    display:flex; align-items:center; gap:8px; margin-top:12px;
    padding:10px 14px; border-radius:var(--radius); font-size:13px; font-weight:500;
  }
  .log-status-ok { background:var(--green-bg); border:1px solid var(--green-border); color:var(--green); }
  .log-status-fail { background:var(--red-bg); border:1px solid rgba(248,113,113,0.3); color:var(--red); }
  .log-status-icon { font-size:14px; }

  @media (max-width:900px) {
    .layout { grid-template-columns:1fr; }
    .side-panel { border-left:none; border-top:1px solid var(--border); }
    .cred-row { grid-template-columns:1fr; }
  }
</style>
</head>
<body>

<!-- Top Bar -->
<div class="topbar">
  <div class="topbar-left">
    <div class="topbar-logo">E</div>
    <div class="topbar-title">EmulationApp <span>/ Dashboard</span></div>
  </div>
  <div class="topbar-right">
    <button class="topbar-btn gear-btn" title="Settings" onclick="document.getElementById('settingsModal').classList.add('active')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12.22 2h-.44a2 2 0 00-2 2v.18a2 2 0 01-1 1.73l-.43.25a2 2 0 01-2 0l-.15-.08a2 2 0 00-2.73.73l-.22.38a2 2 0 00.73 2.73l.15.1a2 2 0 011 1.72v.51a2 2 0 01-1 1.74l-.15.09a2 2 0 00-.73 2.73l.22.38a2 2 0 002.73.73l.15-.08a2 2 0 012 0l.43.25a2 2 0 011 1.73V20a2 2 0 002 2h.44a2 2 0 002-2v-.18a2 2 0 011-1.73l.43-.25a2 2 0 012 0l.15.08a2 2 0 002.73-.73l.22-.39a2 2 0 00-.73-2.73l-.15-.08a2 2 0 01-1-1.74v-.5a2 2 0 011-1.74l.15-.09a2 2 0 00.73-2.73l-.22-.38a2 2 0 00-2.73-.73l-.15.08a2 2 0 01-2 0l-.43-.25a2 2 0 01-1-1.73V4a2 2 0 00-2-2z"/>
        <circle cx="12" cy="12" r="3"/>
      </svg>
    </button>
  </div>
</div>

<div class="layout">
  <!-- ═══ MAIN PANEL ═══ -->
  <div class="main-panel">

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
      <div class="alert alert-error">
        @foreach($errors->all() as $error)
          {{ $error }}<br>
        @endforeach
      </div>
    @endif



    <form method="POST" action="{{ route('payload.store') }}" id="payloadForm">
      @csrf

      <!-- Step 1: Navigation Script (moved from Step 4 to first) -->
      <div class="section">
        <div class="section-header">
          <div class="section-number">1</div>
          <div class="section-title">Navigation Script</div>
          <div class="section-subtitle">What should the browser do?</div>
        </div>

        <div class="script-options">
          <div class="script-card" id="cardPagecast" onclick="selectScriptPath('pagecast')">
            <div class="script-card-icon">&#x23FA;</div>
            <div class="script-card-title">Record with PageCast</div>
            <div class="script-card-desc">Open a browser and record your actions. No coding required.</div>
          </div>
          <div class="script-card" id="cardExisting" onclick="selectScriptPath('existing')">
            <div class="script-card-icon">&#x1F4C4;</div>
            <div class="script-card-title">Use Existing Script</div>
            <div class="script-card-desc">Select a script written by a developer or from a previous job.</div>
          </div>
        </div>

        <!-- PageCast panel -->
        <div class="script-panel" id="panelPagecast" style="display:none">
          <div class="pagecast-cta">
            <div class="pagecast-cta-icon">&#x23FA;</div>
            <div class="pagecast-cta-body">
              <div class="pagecast-cta-title">Create your navigation script with PageCast</div>
              <div class="pagecast-cta-desc">PageCast will open a browser to the Target URL above. Perform the steps you want to record, then click Stop. Your script will be saved automatically.</div>
            </div>
            <button type="button" class="pagecast-launch-btn" onclick="alert('PageCast recording will launch here.')">
              <svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><polygon points="5 3 19 12 5 21 5 3"/></svg>
              Launch PageCast
            </button>
          </div>
        </div>

        <!-- Existing script panel -->
        <div class="script-panel" id="panelExisting" style="display:none">
          <div class="field" style="margin-top:0">
            <select class="field-select" name="script_path" id="scriptSelect">
              <option value="">-- Select a script --</option>
              @foreach($scripts as $script)
                <option value="{{ $script }}" {{ old('script_path') === $script ? 'selected' : '' }}>
                  {{ $script }}
                </option>
              @endforeach
            </select>
          </div>
          @if(count($scripts) === 0)
            <div class="btn-helper warning" style="text-align:left;margin-top:6px">No scripts found. Upload a .py file using the side panel.</div>
          @endif
        </div>

        <!-- Developer handoff checkbox -->
        <label class="developer-check" id="devCheckLabel">
          <input type="checkbox" name="needs_developer" id="devCheck" value="1" {{ old('needs_developer') ? 'checked' : '' }}>
          <span class="developer-check-box"></span>
          <span class="developer-check-text">I need a developer to write the navigation script for me</span>
        </label>

        <!-- Developer message (shown when checked) -->
        <div class="developer-msg" id="devMsg" style="display:none">
          <div class="developer-msg-icon">&#x1F6E0;&#xFE0F;</div>
          <div class="developer-msg-body">
            <strong>Developer assistance requested</strong><br>
            Save your configuration, then share the generated JSON file with your developer. They will write the navigation script and add it to the jobs/ folder.
          </div>
        </div>

        <input type="hidden" name="script_mode" id="scriptModeInput" value="">
      </div>

      <div class="divider"></div>

      <!-- Step 2: Job Name -->
      <div class="section">
        <div class="section-header">
          <div class="section-number">2</div>
          <div class="section-title">Job Name</div>
          <div class="section-subtitle">A name for this job configuration</div>
        </div>
        <div class="field">
          <input class="field-input" type="text" name="payload_name"
                 value="{{ old('payload_name', 'my_job') }}"
                 placeholder="my_job" required
                 pattern="[a-zA-Z0-9_\-]+">
        </div>
      </div>

      <!-- Step 3: Target URL -->
      <div class="section">
        <div class="section-header">
          <div class="section-number">3</div>
          <div class="section-title">Target URL</div>
          <div class="section-subtitle">Where should the browser navigate?</div>
        </div>
        <div class="field">
          <input class="field-input" type="url" name="target_url"
                 value="{{ old('target_url') }}"
                 placeholder="https://portal.example.com/login" required>
        </div>
      </div>

      <!-- Step 4: Credentials -->
      <div class="section">
        <div class="section-header">
          <div class="section-number">4</div>
          <div class="section-title">Credentials</div>
          <div class="section-subtitle">Auto-encrypted on save</div>
        </div>
        <div class="cred-row">
          <div class="field">
            <label class="field-label">Username</label>
            <input class="field-input" type="text" name="username"
                   value="{{ old('username') }}"
                   placeholder="user@company.com">
          </div>
          <div class="field">
            <label class="field-label">Password</label>
            <div class="password-wrap">
              <input class="field-input" type="password" name="password"
                     placeholder="Enter password">
              <div class="encrypt-badge">ENCRYPTED</div>
            </div>
          </div>
        </div>
      </div>

      <div class="divider"></div>

      <!-- Step 5: Tokens (auto-detected from script) -->
      <div class="section">
        <div class="section-header">
          <div class="section-number">5</div>
          <div class="section-title">Data Tokens</div>
          <div class="section-subtitle" id="tokenSubtitle">Key-value pairs for the navigation script</div>
        </div>
        <div class="token-hint" id="tokenHint" style="display:none">
          &#x2728; Token names were auto-detected from the selected script. Fill in the values.
        </div>
        <table class="token-table" id="tokenTable">
          <thead><tr><th style="width:40%">TOKEN NAME</th><th>VALUE</th></tr></thead>
          <tbody>
            <tr>
              <td><input class="field-input" name="token_keys[]" placeholder="key_name"></td>
              <td><input class="field-input" name="token_values[]" placeholder="value"></td>
            </tr>
          </tbody>
        </table>
        <button type="button" class="add-token-btn" onclick="addTokenRow()">+ Add Token</button>
      </div>

      <div class="divider"></div>

      <!-- Step 6: S3 Config (optional) -->
      <div class="section">
        <div class="section-header">
          <div class="section-number">6</div>
          <div class="section-title">S3 Output</div>
          <div class="section-subtitle">Optional</div>
        </div>
        <div class="cred-row">
          <div class="field">
            <label class="field-label">Bucket</label>
            <input class="field-input" type="text" name="s3_output_bucket"
                   value="{{ old('s3_output_bucket') }}"
                   placeholder="my-emulation-bucket">
          </div>
          <div class="field">
            <label class="field-label">Prefix</label>
            <input class="field-input" type="text" name="s3_output_prefix"
                   value="{{ old('s3_output_prefix') }}"
                   placeholder="results/acme/2026-01/">
          </div>
        </div>
      </div>

      <div class="divider"></div>

      <!-- Actions -->
      <div class="section">
        <div class="btn-row-3">
          <button type="button" class="clear-btn" onclick="clearForm()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
            Clear All
          </button>
          <button type="submit" name="action" value="save" class="save-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Configuration
          </button>
          <button type="submit" name="action" value="save_and_run" class="run-btn" id="runJobBtn" disabled>
            <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><polygon points="5 3 19 12 5 21 5 3"/></svg>
            Run Job
          </button>
        </div>
        <div class="btn-helpers-3">
          <span class="btn-helper">&nbsp;</span>
          <span class="btn-helper" id="saveHelper">Saves your job configuration to jobs/</span>
          <span class="btn-helper" id="runJobHelper">Select a navigation script to enable</span>
        </div>
      </div>

    </form>
  </div>

  <!-- ═══ SIDE PANEL ═══ -->
  <div class="side-panel">

    <!-- Upload Script or Configuration -->
    <div class="side-title">Upload File</div>
    <form method="POST" action="{{ route('payload.upload') }}" enctype="multipart/form-data" id="uploadForm">
      @csrf
      <label class="upload-area" for="payloadFileInput">
        <input type="file" id="payloadFileInput" name="payload_file" accept=".py,.json" onchange="
          var name = this.files[0] ? this.files[0].name : '';
          document.getElementById('uploadFileName').innerHTML = name ? '<strong>' + name + '</strong> selected' : 'Drop a <strong>.py</strong> or <strong>.json</strong> file';
        ">
        <div class="upload-area-icon">&#x1F4E4;</div>
        <div class="upload-area-text" id="uploadFileName">Drop a <strong>.py</strong> or <strong>.json</strong> file</div>
      </label>
      <button type="submit" class="upload-submit">Upload</button>
      <div class="btn-helper" style="margin-top:4px">.py scripts appear in the Step 1 dropdown &nbsp;·&nbsp; .json files are loaded as configurations</div>
    </form>

    <div class="side-divider"></div>

    <!-- Saved Jobs -->
    <div class="side-title">Saved Jobs</div>

    @forelse($payloads as $payload)
      <div class="payload-item">
        <span class="payload-icon">&#x1F4C4;</span>
        <span class="payload-name">{{ $payload }}.json</span>
        <div class="payload-actions">
          <a href="{{ route('payload.show', $payload) }}" target="_blank" title="View the raw JSON payload">View</a>
          <form method="POST" action="{{ route('payload.run', $payload) }}" style="display:inline"
                onsubmit="return confirm('Run {{ $payload }}.json now?')">
            @csrf
            <button type="submit" class="run-btn-sm" title="Execute this payload with runner.py">&#9654;</button>
          </form>
          <form method="POST" action="{{ route('payload.destroy', $payload) }}" style="display:inline"
                onsubmit="return confirm('Delete {{ $payload }}.json?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="delete-btn" title="Permanently remove this payload file">&#x2715;</button>
          </form>
        </div>
      </div>
    @empty
      <div class="empty-state">No jobs yet. Create one using the form or upload a file.</div>
    @endforelse

    <div class="side-divider"></div>

    <!-- Job Log -->
    <div class="side-title">
      @if(session('job_log'))
        <span class="log-dot {{ session('job_success') ? 'dot-green' : 'dot-red' }}"></span>
      @else
        <span class="log-dot dot-idle"></span>
      @endif
      Job Log
    </div>

    @if(session('job_log'))
      <div class="log-entries">
        @foreach(session('job_log') as $entry)
          <div class="log-entry">
            <span class="log-time">{{ $entry['time'] }}</span>
            <span class="log-icon">{!! $entry['icon'] !!}</span>
            <span class="log-text">{!! $entry['html'] !!}</span>
          </div>
        @endforeach
      </div>

      <div class="log-status {{ session('job_success') ? 'log-status-ok' : 'log-status-fail' }}">
        <span class="log-status-icon">{{ session('job_success') ? '&#x2705;' : '&#x274C;' }}</span>
        <span class="log-status-text">{{ session('job_status_text', 'Job finished') }}</span>
      </div>
    @else
      <div class="empty-state">Run a payload to see the job log here.</div>
    @endif

  </div>
</div>

<!-- ═══ SETTINGS MODAL ═══ -->
<div class="modal-overlay" id="settingsModal">
  <div class="modal">
    <div class="modal-title">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12.22 2h-.44a2 2 0 00-2 2v.18a2 2 0 01-1 1.73l-.43.25a2 2 0 01-2 0l-.15-.08a2 2 0 00-2.73.73l-.22.38a2 2 0 00.73 2.73l.15.1a2 2 0 011 1.72v.51a2 2 0 01-1 1.74l-.15.09a2 2 0 00-.73 2.73l.22.38a2 2 0 002.73.73l.15-.08a2 2 0 012 0l.43.25a2 2 0 011 1.73V20a2 2 0 002 2h.44a2 2 0 002-2v-.18a2 2 0 011-1.73l.43-.25a2 2 0 012 0l.15.08a2 2 0 002.73-.73l.22-.39a2 2 0 00-.73-2.73l-.15-.08a2 2 0 01-1-1.74v-.5a2 2 0 011-1.74l.15-.09a2 2 0 00.73-2.73l-.22-.38a2 2 0 00-2.73-.73l-.15.08a2 2 0 01-2 0l-.43-.25a2 2 0 01-1-1.73V4a2 2 0 00-2-2z"/>
        <circle cx="12" cy="12" r="3"/>
      </svg>
      Settings
      <button class="modal-close" onclick="document.getElementById('settingsModal').classList.remove('active')">&times;</button>
    </div>

    <form method="POST" action="{{ route('settings.save') }}">
      @csrf

      <div class="field">
        <label class="field-label">Path to uv</label>
        <input class="field-input" type="text" name="uv_path"
               value="{{ $settings['uv_path'] ?? '' }}"
               placeholder="Not detected"
               style="font-family:'Consolas','Courier New',monospace; font-size:13px;">
        @if(!empty($settings['uv_path']) && file_exists($settings['uv_path']))
          <div class="btn-helper ready" style="margin-top:4px;text-align:left">&#x2705; Detected at {{ $settings['uv_path'] }}</div>
        @elseif(!empty($settings['uv_path']))
          <div class="btn-helper warning" style="margin-top:4px;text-align:left">&#x26A0;&#xFE0F; File not found at this path. Reinstall or correct the path below.</div>
        @else
          <div class="uv-install-help">
            <div class="btn-helper warning" style="text-align:left">&#x26A0;&#xFE0F; uv not found. Install it, then refresh this page.</div>
            <div class="install-steps">
              <div class="install-step-label">Open PowerShell and run:</div>
              <code class="install-cmd">powershell -ExecutionPolicy ByPass -c "irm https://astral.sh/uv/install.ps1 | iex"</code>
              <div class="install-step-label" style="margin-top:8px">This installs uv to:</div>
              <code class="install-cmd">C:\Users\&lt;you&gt;\.local\bin\uv.exe</code>
            </div>
          </div>
        @endif
      </div>

      <div class="field" style="margin-top:12px">
        <label class="field-label">Browser Driver</label>
        <select class="field-select" name="driver">
          <option value="selenium" {{ ($settings['driver'] ?? 'selenium') === 'selenium' ? 'selected' : '' }}>Selenium (Chrome)</option>
          <option value="playwright" {{ ($settings['driver'] ?? '') === 'playwright' ? 'selected' : '' }}>Playwright (Chromium)</option>
        </select>
      </div>

      <div class="field" style="margin-top:12px">
        <label class="field-label">Default S3 Output Bucket</label>
        <input class="field-input" type="text" name="s3_output_bucket"
               value="{{ $settings['s3_output_bucket'] ?? '' }}"
               placeholder="my-emulation-bucket">
      </div>

      <div class="field" style="margin-top:12px">
        <label class="field-label">Default S3 Output Prefix</label>
        <input class="field-input" type="text" name="s3_output_prefix"
               value="{{ $settings['s3_output_prefix'] ?? '' }}"
               placeholder="results/">
      </div>

      <button type="submit" class="modal-btn">Save Settings</button>
      <div class="btn-helper" style="margin-top:6px">Persists to .emulation_settings.json in the project root</div>
    </form>
  </div>
</div>

<!-- Close modal on overlay click + Token table + Script branching + Validation -->
<script>
  document.getElementById('settingsModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('active');
  });

  // ── Clear form ─────────────────────────────────────
  function clearForm() {
    if (!confirm('Clear all fields?')) return;
    var form = document.getElementById('payloadForm');
    if (!form) return;

    // Reset text/url/password inputs
    form.querySelectorAll('input[type="text"], input[type="url"], input[type="password"]').forEach(function(inp) {
      if (inp.name === 'payload_name') { inp.value = 'my_job'; }
      else { inp.value = ''; }
    });

    // Reset script selection
    var scriptSel = document.getElementById('scriptSelect');
    if (scriptSel) scriptSel.value = '';

    // Reset script mode cards
    currentMode = '';
    document.getElementById('scriptModeInput').value = '';
    document.getElementById('cardPagecast').classList.remove('active');
    document.getElementById('cardExisting').classList.remove('active');
    document.getElementById('panelPagecast').style.display = 'none';
    document.getElementById('panelExisting').style.display = 'none';

    // Reset developer checkbox
    var dc = document.getElementById('devCheck');
    dc.checked = false;
    document.getElementById('devMsg').style.display = 'none';

    // Reset tokens to one empty row
    var tbody = document.querySelector('#tokenTable tbody');
    tbody.innerHTML = '<tr>'
      + '<td><input class="field-input" name="token_keys[]" placeholder="key_name"></td>'
      + '<td><input class="field-input" name="token_values[]" placeholder="value"></td>'
      + '</tr>';
    refreshTokenBorders();

    // Hide token hint
    document.getElementById('tokenHint').style.display = 'none';
    document.getElementById('tokenSubtitle').textContent = 'Key-value pairs for the navigation script';

    checkRunReady();
  }

  // ── Token table ─────────────────────────────────────
  function addTokenRow(keyName, valuePlaceholder) {
    var tbody = document.querySelector('#tokenTable tbody');
    var row = document.createElement('tr');
    var kn = keyName || '';
    var vp = valuePlaceholder || 'value';
    row.innerHTML = '<td><input class="field-input" name="token_keys[]" placeholder="key_name" value="' + kn + '"' + (kn ? ' readonly style="color:var(--green);background:var(--green-bg)"' : '') + '></td>'
                  + '<td><input class="field-input" name="token_values[]" placeholder="' + vp + '"></td>';
    tbody.appendChild(row);
    refreshTokenBorders();
    if (!kn) row.querySelector('input').focus();
  }

  function setTokensFromScript(tokenNames) {
    var tbody = document.querySelector('#tokenTable tbody');
    var hint  = document.getElementById('tokenHint');
    var sub   = document.getElementById('tokenSubtitle');

    if (!tokenNames || tokenNames.length === 0) {
      hint.style.display = 'none';
      sub.textContent = 'Key-value pairs for the navigation script';
      return;
    }

    // Clear existing rows and rebuild with detected keys
    tbody.innerHTML = '';
    tokenNames.forEach(function(name) {
      var row = document.createElement('tr');
      row.innerHTML = '<td><input class="field-input" name="token_keys[]" value="' + name + '" readonly style="color:var(--green);background:var(--green-bg)"></td>'
                    + '<td><input class="field-input" name="token_values[]" placeholder="Enter value for ' + name + '"></td>';
      tbody.appendChild(row);
    });
    refreshTokenBorders();
    hint.style.display = 'block';
    sub.textContent = tokenNames.length + ' token' + (tokenNames.length > 1 ? 's' : '') + ' detected from script';
  }

  function refreshTokenBorders() {
    var rows = document.querySelectorAll('#tokenTable tbody tr');
    rows.forEach(function(r) {
      r.querySelectorAll('.field-input').forEach(function(inp) {
        inp.style.borderRadius = '0';
        inp.style.borderRight = 'none';
        inp.style.borderBottom = 'none';
      });
    });
    if (rows.length > 0) {
      var first = rows[0];
      var last = rows[rows.length - 1];
      first.querySelector('td:first-child .field-input').style.borderTopLeftRadius = 'var(--radius)';
      first.querySelector('td:last-child .field-input').style.borderTopRightRadius = 'var(--radius)';
      first.querySelector('td:last-child .field-input').style.borderRight = '1px solid var(--border)';
      last.querySelector('td:first-child .field-input').style.borderBottomLeftRadius = 'var(--radius)';
      last.querySelector('td:last-child .field-input').style.borderBottomRightRadius = 'var(--radius)';
      last.querySelector('td:last-child .field-input').style.borderRight = '1px solid var(--border)';
      last.querySelectorAll('.field-input').forEach(function(inp) { inp.style.borderBottom = '1px solid var(--border)'; });
      rows.forEach(function(r, i) {
        if (i > 0 && i < rows.length - 1) {
          r.querySelector('td:last-child .field-input').style.borderRight = '1px solid var(--border)';
        }
      });
    }
  }

  // ── Script path branching ───────────────────────────
  var currentMode = '';

  function selectScriptPath(mode) {
    currentMode = mode;
    document.getElementById('scriptModeInput').value = mode;

    var cardPC  = document.getElementById('cardPagecast');
    var cardEx  = document.getElementById('cardExisting');
    var panelPC = document.getElementById('panelPagecast');
    var panelEx = document.getElementById('panelExisting');

    cardPC.classList.toggle('active', mode === 'pagecast');
    cardEx.classList.toggle('active', mode === 'existing');
    panelPC.style.display = mode === 'pagecast' ? 'block' : 'none';
    panelEx.style.display = mode === 'existing' ? 'block' : 'none';

    checkRunReady();
  }

  // ── Token auto-detection on script change ───────────
  function fetchScriptTokens(scriptName) {
    if (!scriptName) return;
    fetch('/script/' + encodeURIComponent(scriptName) + '/tokens')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.tokens && data.tokens.length > 0) {
          setTokensFromScript(data.tokens);
        }
      })
      .catch(function() { /* silent */ });
  }

  // ── Developer checkbox ──────────────────────────────
  var devCheck = document.getElementById('devCheck');
  var devMsg   = document.getElementById('devMsg');

  devCheck.addEventListener('change', function() {
    devMsg.style.display = this.checked ? 'flex' : 'none';
    checkRunReady();
  });

  // ── Run Job validation ──────────────────────────────
  function checkRunReady() {
    var btn        = document.getElementById('runJobBtn');
    var helper     = document.getElementById('runJobHelper');
    var saveHelper = document.getElementById('saveHelper');
    var form       = document.getElementById('payloadForm');
    if (!form || !btn) return;

    var nameVal  = (form.querySelector('[name="payload_name"]').value || '').trim();
    var urlVal   = (form.querySelector('[name="target_url"]').value || '').trim();
    var scriptSel = document.getElementById('scriptSelect');
    var scriptVal = scriptSel ? scriptSel.value : '';
    var devChecked = devCheck.checked;

    // Developer mode: can save but not run
    if (devChecked) {
      btn.disabled = true;
      helper.textContent = 'Save your configuration, then share it with your developer';
      helper.className = 'btn-helper warning';
      saveHelper.textContent = 'Saves configuration for developer handoff';
      saveHelper.className = 'btn-helper ready';
      return;
    }

    // PageCast mode: can save but not run yet
    if (currentMode === 'pagecast') {
      btn.disabled = true;
      helper.textContent = 'Record your navigation with PageCast first';
      helper.className = 'btn-helper warning';
      saveHelper.textContent = 'Saves your job configuration to jobs/';
      saveHelper.className = 'btn-helper';
      return;
    }

    // Existing script mode: check all required fields
    var missing = [];
    if (!nameVal) missing.push('Job Name');
    if (!urlVal)  missing.push('Target URL');
    if (currentMode !== 'existing') missing.push('Navigation Script (select a path in Step 1)');
    else if (!scriptVal) missing.push('Navigation Script');

    if (missing.length > 0) {
      btn.disabled = true;
      helper.textContent = 'Missing: ' + missing.join(', ');
      helper.className = 'btn-helper warning';
    } else {
      btn.disabled = false;
      helper.textContent = 'Saves configuration then executes the job';
      helper.className = 'btn-helper ready';
    }

    saveHelper.textContent = 'Saves your job configuration to jobs/';
    saveHelper.className = 'btn-helper';
  }

  // Wire up live field watchers
  (function() {
    var form = document.getElementById('payloadForm');
    if (!form) return;

    var fields = form.querySelectorAll('[name="payload_name"], [name="target_url"]');
    fields.forEach(function(f) {
      f.addEventListener('input', checkRunReady);
    });

    var scriptSel = document.getElementById('scriptSelect');
    if (scriptSel) {
      scriptSel.addEventListener('change', function() {
        checkRunReady();
        fetchScriptTokens(this.value);
      });
    }

    // Initial state
    checkRunReady();
  })();
</script>

</body>
</html>
