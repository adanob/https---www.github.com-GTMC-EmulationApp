<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>PageCast Recorder - Navigation Script Generator</title>

    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        
        :root {
            --primary: #0077B6;
            --primary-dark: #005F8C;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
            --bg: #F9FAFB;
            --bg-secondary: #FFFFFF;
            --border: #E5E7EB;
            --text-primary: #111827;
            --text-secondary: #6B7280;
            --text-muted: #9CA3AF;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text-primary);
        }

        .topbar {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .topbar-left { display: flex; align-items: center; gap: 16px; }
        
        .logo {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-badge.idle {
            background: #F3F4F6;
            color: var(--text-secondary);
        }

        .status-badge.recording {
            background: #FEE2E2;
            color: var(--danger);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .rec-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--danger);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
        }

        .card-header {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 6px;
            color: var(--text-primary);
        }

        .form-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0,119,182,0.1);
        }

        .actions-list {
            max-height: 400px;
            overflow-y: auto;
            background: #F9FAFB;
            border-radius: 8px;
            padding: 12px;
        }

        .action-item {
            background: white;
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .action-type {
            font-weight: 600;
            color: var(--primary);
            text-transform: uppercase;
            font-size: 11px;
            margin-bottom: 4px;
        }

        .action-details {
            color: var(--text-secondary);
            font-size: 12px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .token-row {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 12px;
            margin-bottom: 12px;
            align-items: end;
        }

        .btn-icon {
            padding: 10px;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-icon:hover {
            background: var(--bg);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .iframe-container {
            border: 2px solid var(--primary);
            border-radius: 12px;
            overflow: hidden;
            height: 600px;
        }

        .iframe-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .recording-indicator {
            position: fixed;
            top: 80px;
            right: 24px;
            background: var(--danger);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(239,68,68,0.3);
            display: none;
            align-items: center;
            gap: 8px;
            z-index: 1000;
        }

        .recording-indicator.active {
            display: flex;
        }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-left">
        <div class="logo">
            🎬 PageCast Recorder
        </div>
        <div class="status-badge" id="statusBadge">
            <span id="statusText">Idle</span>
        </div>
    </div>
    <div>
        <button class="btn btn-primary" onclick="window.location.href='/emulation'">
            ← Back to Dashboard
        </button>
    </div>
</div>

<div class="recording-indicator" id="recordingIndicator">
    <div class="rec-dot"></div>
    <span>Recording in progress...</span>
</div>

<div class="container">
    <div class="grid-2">
        <!-- Left Column: Controls & Setup -->
        <div>
            <!-- Session Setup -->
            <div class="card">
                <div class="card-header">📋 Session Setup</div>
                
                <div class="form-group">
                    <label class="form-label">Session Name</label>
                    <input type="text" class="form-input" id="sessionName" placeholder="e.g., amp_portal_login">
                </div>

                <div class="form-group">
                    <label class="form-label">Target URL</label>
                    <input type="url" class="form-input" id="targetUrl" placeholder="https://portal.example.com">
                </div>

                <div class="form-group">
                    <label class="form-label">Description (Optional)</label>
                    <input type="text" class="form-input" id="description" placeholder="Brief description of what this script does">
                </div>

                <button class="btn btn-success" id="btnStartRecording" onclick="startRecording()" style="width:100%;">
                    🎬 Start Recording
                </button>
            </div>

            <!-- Tokens Management -->
            <div class="card" style="margin-top:24px;">
                <div class="card-header">🔑 Tokens</div>
                <p style="font-size:13px; color:var(--text-secondary); margin-bottom:16px;">
                    Define tokens that will be parameterized in the script
                </p>

                <div id="tokensContainer">
                    <!-- Tokens added dynamically -->
                </div>

                <button class="btn btn-primary" onclick="addToken()" style="width:100%; margin-top:12px;">
                    + Add Token
                </button>
            </div>

            <!-- Recording Controls -->
            <div class="card" style="margin-top:24px; display:none;" id="recordingControls">
                <div class="card-header">🎮 Recording Controls</div>
                
                <button class="btn btn-danger" onclick="stopRecording()" style="width:100%; margin-bottom:12px;">
                    ⏹ Stop Recording
                </button>

                <button class="btn btn-primary" onclick="pauseRecording()" id="btnPause" style="width:100%; margin-bottom:12px;">
                    ⏸ Pause
                </button>

                <button class="btn btn-success" onclick="generateScript()" style="width:100%;">
                    💾 Generate Script
                </button>

                <div style="margin-top:16px; padding:12px; background:#F3F4F6; border-radius:8px;">
                    <div style="font-size:12px; color:var(--text-secondary);">
                        <div>Actions Recorded: <strong id="actionCount">0</strong></div>
                        <div>Duration: <strong id="recordingDuration">00:00</strong></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Preview & Actions -->
        <div>
            <!-- Browser Preview -->
            <div class="card">
                <div class="card-header">🌐 Browser Preview</div>
                
                <div id="previewContainer">
                    <div class="empty-state">
                        <div class="empty-icon">🎬</div>
                        <p>Click "Start Recording" to begin</p>
                    </div>
                </div>
            </div>

            <!-- Recorded Actions -->
            <div class="card" style="margin-top:24px;">
                <div class="card-header">📝 Recorded Actions</div>
                
                <div class="actions-list" id="actionsList">
                    <div class="empty-state">
                        <div class="empty-icon">📋</div>
                        <p>No actions recorded yet</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Global state
let currentSession = null;
let recordingActive = false;
let recordingPaused = false;
let recordedActions = [];
let recordingStartTime = null;
let durationInterval = null;
let tokenCounter = 0;

// CSRF token
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// Start recording session
async function startRecording() {
    const sessionName = document.getElementById('sessionName').value;
    const targetUrl = document.getElementById('targetUrl').value;
    const description = document.getElementById('description').value;

    if (!sessionName || !targetUrl) {
        alert('Please fill in session name and target URL');
        return;
    }

    try {
        const response = await fetch('/recorder/start-session', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                session_name: sessionName,
                target_url: targetUrl,
                description: description
            })
        });

        const data = await response.json();

        if (data.success) {
            currentSession = data.session_id;
            recordingActive = true;
            recordingStartTime = Date.now();

            // Update UI
            document.getElementById('statusBadge').className = 'status-badge recording';
            document.getElementById('statusBadge').innerHTML = '<div class="rec-dot"></div> <span>Recording</span>';
            document.getElementById('recordingIndicator').classList.add('active');
            document.getElementById('recordingControls').style.display = 'block';
            document.getElementById('btnStartRecording').disabled = true;

            // Load target URL in iframe
            loadPreview(targetUrl);

            // Start duration counter
            startDurationCounter();

            console.log('Recording started:', data);
        }
    } catch (error) {
        console.error('Error starting recording:', error);
        alert('Failed to start recording session');
    }
}

// Load preview iframe
function loadPreview(url) {
    const container = document.getElementById('previewContainer');
    container.innerHTML = `
        <div class="iframe-container">
            <iframe id="recordingFrame" src="${url}"></iframe>
        </div>
    `;

    // Inject recorder script into iframe
    const iframe = document.getElementById('recordingFrame');
    iframe.addEventListener('load', () => {
        injectRecorderScript(iframe);
    });
}

// Inject recorder script into iframe
function injectRecorderScript(iframe) {
    try {
        const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
        
        // Create recorder script
        const script = iframeDoc.createElement('script');
        script.textContent = `
            ${getRecorderScript()}
            
            // Start recorder
            $Recorder.start();
            
            // Send actions to parent window
            window.addEventListener('action-recorded', function(e) {
                window.parent.postMessage({
                    type: 'action-recorded',
                    action: e.detail
                }, '*');
            });
        `;
        
        iframeDoc.body.appendChild(script);
        console.log('Recorder script injected');
    } catch (error) {
        console.error('Could not inject recorder script (cross-origin):', error);
        alert('Cannot record cross-origin pages. Please use a same-origin target or proxy.');
    }
}

// Listen for actions from iframe
window.addEventListener('message', function(event) {
    if (event.data.type === 'action-recorded' && recordingActive && !recordingPaused) {
        const action = event.data.action;
        recordedActions.push(action);
        updateActionsList();
        updateActionCount();
        
        // Auto-save actions every 5 actions
        if (recordedActions.length % 5 === 0) {
            saveActions();
        }
    }
});

// Stop recording
async function stopRecording() {
    if (!currentSession) return;

    recordingActive = false;
    
    // Save final actions
    await saveActions();

    // Update UI
    document.getElementById('statusBadge').className = 'status-badge idle';
    document.getElementById('statusBadge').innerHTML = '<span>Stopped</span>';
    document.getElementById('recordingIndicator').classList.remove('active');
    clearInterval(durationInterval);

    console.log('Recording stopped');
}

// Pause/Resume recording
function pauseRecording() {
    recordingPaused = !recordingPaused;
    const btn = document.getElementById('btnPause');
    
    if (recordingPaused) {
        btn.textContent = '▶ Resume';
        btn.className = 'btn btn-success';
        document.getElementById('statusBadge').innerHTML = '<span>Paused</span>';
    } else {
        btn.textContent = '⏸ Pause';
        btn.className = 'btn btn-primary';
        document.getElementById('statusBadge').innerHTML = '<div class="rec-dot"></div> <span>Recording</span>';
    }
}

// Save actions to session
async function saveActions() {
    if (!currentSession || recordedActions.length === 0) return;

    try {
        await fetch('/recorder/save-actions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                session_id: currentSession,
                actions: recordedActions
            })
        });
        
        console.log('Actions saved');
    } catch (error) {
        console.error('Error saving actions:', error);
    }
}

// Generate Python script
async function generateScript() {
    if (!currentSession) {
        alert('No active session');
        return;
    }

    const scriptName = document.getElementById('sessionName').value;

    // Save tokens first
    await saveTokens();

    try {
        const response = await fetch('/recorder/generate-script', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                session_id: currentSession,
                script_name: scriptName
            })
        });

        const data = await response.json();

        if (data.success) {
            // Notify parent window (dashboard) if opened from there
            if (window.opener && !window.opener.closed) {
                window.opener.postMessage({
                    type: 'recorder-complete',
                    scriptName: data.script_name,
                    scriptPath: data.script_path
                }, window.location.origin);
            }
            
            // Show success message
            alert(`Script generated successfully: ${data.script_name}\n\nYou can now close this window or continue recording.`);
            
            // Optionally redirect to dashboard
            if (confirm('Script generated! Go to dashboard now?')) {
                window.location.href = '/emulation';
            }
        }
    } catch (error) {
        console.error('Error generating script:', error);
        alert('Failed to generate script');
    }
}

// Update actions list UI
function updateActionsList() {
    const list = document.getElementById('actionsList');
    
    if (recordedActions.length === 0) {
        list.innerHTML = '<div class="empty-state"><div class="empty-icon">📋</div><p>No actions recorded yet</p></div>';
        return;
    }

    list.innerHTML = recordedActions.slice(-20).reverse().map((action, index) => `
        <div class="action-item">
            <div class="action-type">${action.ai_action || 'unknown'}</div>
            <div class="action-details">${escapeHtml(action.blob || '')}</div>
        </div>
    `).join('');
}

// Update action count
function updateActionCount() {
    document.getElementById('actionCount').textContent = recordedActions.length;
}

// Start duration counter
function startDurationCounter() {
    durationInterval = setInterval(() => {
        const elapsed = Math.floor((Date.now() - recordingStartTime) / 1000);
        const minutes = Math.floor(elapsed / 60).toString().padStart(2, '0');
        const seconds = (elapsed % 60).toString().padStart(2, '0');
        document.getElementById('recordingDuration').textContent = `${minutes}:${seconds}`;
    }, 1000);
}

// Add token
function addToken() {
    tokenCounter++;
    const container = document.getElementById('tokensContainer');
    const tokenId = 'token_' + tokenCounter;
    
    const tokenRow = document.createElement('div');
    tokenRow.className = 'token-row';
    tokenRow.id = tokenId;
    tokenRow.innerHTML = `
        <div>
            <input type="text" class="form-input" placeholder="Token name (e.g., date_from)" data-token-key="${tokenId}">
        </div>
        <div>
            <input type="text" class="form-input" placeholder="Value (e.g., 01/01/2025)" data-token-value="${tokenId}">
        </div>
        <button class="btn-icon" onclick="removeToken('${tokenId}')">
            🗑️
        </button>
    `;
    
    container.appendChild(tokenRow);
}

// Remove token
function removeToken(tokenId) {
    document.getElementById(tokenId).remove();
}

// Save tokens
async function saveTokens() {
    if (!currentSession) return;

    const tokens = {};
    document.querySelectorAll('[data-token-key]').forEach(input => {
        const key = input.value.trim();
        const tokenId = input.dataset.tokenKey;
        const valueInput = document.querySelector(`[data-token-value="${tokenId}"]`);
        const value = valueInput ? valueInput.value.trim() : '';
        
        if (key && value) {
            tokens[key] = value;
        }
    });

    try {
        await fetch('/recorder/save-tokens', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                session_id: currentSession,
                tokens: tokens
            })
        });
    } catch (error) {
        console.error('Error saving tokens:', error);
    }
}

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Get recorder script content
function getRecorderScript() {
    return `
        var $Recorder = new function() {
            this.events = ['click', 'keyup'];
            
            this.start = function() {
                this.events.forEach(event => {
                    document.addEventListener(event, this.handleEvent.bind(this));
                });
            };
            
            this.handleEvent = function(event) {
                const action = {
                    ai_action: event.type,
                    blob: this.getElementHTML(event.target),
                    ai_value: this.getValue(event),
                    timestamp: new Date().toISOString()
                };
                
                const customEvent = new CustomEvent('action-recorded', { detail: action });
                window.dispatchEvent(customEvent);
            };
            
            this.getElementHTML = function(element) {
                const attrs = Array.from(element.attributes)
                    .filter(attr => attr.name !== 'style')
                    .map(attr => attr.name + '="' + attr.value + '"')
                    .join(' ');
                return '<' + element.tagName.toLowerCase() + ' ' + attrs + '>';
            };
            
            this.getValue = function(event) {
                if (event.type === 'keyup') {
                    return event.target.value || '';
                }
                return '';
            };
        };
    `;
}
</script>

</body>
</html>