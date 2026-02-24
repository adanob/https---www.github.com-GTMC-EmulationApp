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

  /* Layout */
  .layout { display:grid; grid-template-columns:1fr 340px; min-height:calc(100vh - 56px); }
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

  /* Buttons */
  .run-btn {
    width:100%; padding:13px; font-size:15px; font-weight:600; font-family:inherit;
    background:linear-gradient(135deg,var(--accent),#8B5CF6); border:none; border-radius:var(--radius);
    color:#fff; cursor:pointer; transition:all 0.2s; box-shadow:0 4px 20px rgba(108,114,255,0.3);
  }
  .run-btn:hover { transform:translateY(-1px); box-shadow:0 6px 28px rgba(108,114,255,0.45); }

  /* Alerts */
  .alert { padding:12px 16px; border-radius:var(--radius); margin-bottom:16px; font-size:13px; }
  .alert-success { background:var(--green-bg); border:1px solid var(--green-border); color:var(--green); }
  .alert-error { background:var(--red-bg); border:1px solid rgba(248,113,113,0.3); color:var(--red); }

  /* Side Panel - Payloads */
  .side-title { font-size:14px; font-weight:600; margin-bottom:14px; }
  .payload-item {
    display:flex; align-items:center; gap:10px; padding:10px 12px;
    background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius);
    margin-bottom:6px; font-size:13px; transition:border-color 0.15s;
  }
  .payload-item:hover { border-color:var(--text-muted); }
  .payload-icon { font-size:16px; }
  .payload-name { font-family:'Consolas','Courier New',monospace; font-size:12px; flex:1; }
  .payload-actions { display:flex; gap:6px; }
  .payload-actions a, .payload-actions button {
    font-size:11px; padding:3px 8px; border-radius:6px; border:1px solid var(--border);
    background:transparent; color:var(--text-secondary); cursor:pointer; text-decoration:none;
    font-family:inherit; transition:all 0.15s;
  }
  .payload-actions a:hover, .payload-actions button:hover { border-color:var(--accent); color:var(--accent); }
  .payload-actions button.delete-btn:hover { border-color:var(--red); color:var(--red); }
  .empty-state { color:var(--text-muted); font-size:13px; padding:20px 0; text-align:center; }

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
    <div class="topbar-title">EmulationApp <span>/ New Payload</span></div>
  </div>
</div>

<div class="layout">
  <!-- Main Panel -->
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

    <form method="POST" action="{{ route('payload.store') }}">
      @csrf

      <!-- Payload Name -->
      <div class="section">
        <div class="section-header">
          <div class="section-number">0</div>
          <div class="section-title">Payload Name</div>
          <div class="section-subtitle">Filename saved to payloads/</div>
        </div>
        <div class="field">
          <input class="field-input" type="text" name="payload_name"
                 value="{{ old('payload_name', 'my_job') }}"
                 placeholder="my_job" required
                 pattern="[a-zA-Z0-9_\-]+">
        </div>
      </div>

      <!-- Step 1: Target URL -->
      <div class="section">
        <div class="section-header">
          <div class="section-number">1</div>
          <div class="section-title">Target URL</div>
          <div class="section-subtitle">Where should the browser navigate?</div>
        </div>
        <div class="field">
          <input class="field-input" type="url" name="target_url"
                 value="{{ old('target_url') }}"
                 placeholder="https://portal.example.com/login" required>
        </div>
      </div>

      <!-- Step 2: Credentials -->
      <div class="section">
        <div class="section-header">
          <div class="section-number">2</div>
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

      <!-- Step 3: Tokens -->
      <div class="section">
        <div class="section-header">
          <div class="section-number">3</div>
          <div class="section-title">Data Tokens</div>
          <div class="section-subtitle">One per line, key:value</div>
        </div>
        <div class="field">
          <textarea class="field-textarea" name="tokens"
                    placeholder="account_name:Acme Corp&#10;report_type:monthly_summary&#10;date_from:2026-01-01&#10;date_to:2026-01-31">{{ old('tokens') }}</textarea>
        </div>
      </div>

      <div class="divider"></div>

      <!-- Step 4: Script -->
      <div class="section">
        <div class="section-header">
          <div class="section-number">4</div>
          <div class="section-title">Navigation Script</div>
          <div class="section-subtitle">Select from scripts/ folder</div>
        </div>
        <div class="field">
          <select class="field-select" name="script_path" required>
            @forelse($scripts as $script)
              <option value="{{ $script }}" {{ old('script_path') === $script ? 'selected' : '' }}>
                {{ $script }}
              </option>
            @empty
              <option value="" disabled>No scripts found in scripts/</option>
            @endforelse
          </select>
        </div>
      </div>

      <div class="divider"></div>

      <!-- S3 Config (optional) -->
      <div class="section">
        <div class="section-header">
          <div class="section-number">5</div>
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

      <!-- Save -->
      <div class="section">
        <button type="submit" class="run-btn">Save Payload</button>
      </div>

    </form>
  </div>

  <!-- Side Panel - Saved Payloads -->
  <div class="side-panel">
    <div class="side-title">Saved Payloads</div>

    @forelse($payloads as $payload)
      <div class="payload-item">
        <span class="payload-icon">&#x1F4C4;</span>
        <span class="payload-name">{{ $payload }}.json</span>
        <div class="payload-actions">
          <a href="{{ route('payload.show', $payload) }}" target="_blank">View</a>
          <form method="POST" action="{{ route('payload.destroy', $payload) }}" style="display:inline"
                onsubmit="return confirm('Delete {{ $payload }}.json?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="delete-btn">Del</button>
          </form>
        </div>
      </div>
    @empty
      <div class="empty-state">No payloads yet. Create one using the form.</div>
    @endforelse
  </div>
</div>

</body>
</html>
