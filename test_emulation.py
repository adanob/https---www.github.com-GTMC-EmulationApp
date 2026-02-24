"""
test_emulation.py - Simulates the end-to-end user experience.

This test mirrors what happens when a user presses "Run" in the Dashboard:
  1. A local HTML test page stands in for a real website
  2. Credentials are encrypted (the key is generated on first run)
  3. A payload is assembled behind the scenes
  4. The engine runs the navigation script (with full logging)
  5. The result and log are printed

Run:
    uv run python test_emulation.py
"""

import os
import tempfile

from engine import Payload, CredentialManager, EmulationEngine


# ==============================================================
#  Simulate a real website (local HTML test page)
# ==============================================================

TEST_HTML = """
<!DOCTYPE html>
<html>
<head><title>Test Portal</title></head>
<body>
  <h1 id="title">Welcome to the My Portal</h1>
  <form>
    <input id="username" type="text" placeholder="Username" />
    <input id="password" type="password" placeholder="Password" />
    <button id="login" type="button" onclick="
      document.getElementById('status').innerText = 'Logged in as ' + document.getElementById('username').value;
      document.getElementById('dashboard').style.display = 'block';
    ">Login</button>
  </form>
  <p id="status"></p>
  <div id="dashboard" style="display:none;">
    <h2>Dashboard</h2>
    <p id="welcome">You are now logged in.</p>
    <p id="token-display"></p>
    <button id="show-tokens" type="button" onclick="
      document.getElementById('token-display').innerText = 'Tokens received.';
    ">Load Data</button>
  </div>
</body>
</html>
"""

test_page = os.path.join(tempfile.gettempdir(), "test_portal.html")
with open(test_page, "w") as f:
    f.write(TEST_HTML)
test_page_url = f"file://{test_page}"


# ==============================================================
#  Simulate a navigation script (would be recorded via PageCast
#  or uploaded by the user through the Dashboard)
# ==============================================================

SCRIPT = '''
def navigate(context):
    helper = context["helper"]
    creds  = context["credentials"]
    logger = context["logger"]

    helper.go(context["target_url"])
    helper.wait_for_page_load()

    title = helper.get_text('//h1[@id="title"]')
    logger.info("Page loaded", title)

    helper.type_text('//input[@id="username"]', creds.get("username", ""))
    helper.type_text('//input[@id="password"]', creds.get("password", ""))
    helper.click('//button[@id="login"]')
    helper.wait(1)

    status = helper.get_text('//p[@id="status"]')
    logger.success("Login successful", status)

    helper.click('//button[@id="show-tokens"]')
    helper.wait(1)

    result_text = helper.get_text('//p[@id="token-display"]')
    logger.info("Data loaded", result_text)

    screenshot = helper.screenshot()
    logger.info("Screenshot captured", screenshot)

    return {"title": title, "login_status": status, "result": result_text}
'''

script_path = os.path.join(tempfile.gettempdir(), "test_nav_script.py")
with open(script_path, "w") as f:
    f.write(SCRIPT)


# ==============================================================
#  Simulate what the Dashboard does when the user clicks "Run"
# ==============================================================

print("=" * 60)
print("  EMULATION ENGINE - TEST RUN")
print("=" * 60)

cred_manager = CredentialManager()
encrypted = cred_manager.encrypt_credentials(
    username="test_user@example.com",
    password="s3cureP@ssw0rd"
)

payload = Payload(
    target_url=test_page_url,
    driver="selenium",
    script_path=script_path,
    tokens={
        "account_name": "Acme Corp",
        "report_type": "monthly_summary",
        "date_from": "2026-01-01",
        "date_to": "2026-01-31",
    },
    credentials=encrypted,
)

print(f"\n  Target URL:  {payload.target_url}")
print(f"  Driver:      {payload.driver}")
print(f"  Tokens:      {len(payload.tokens)} keys")
print(f"  Credentials: encrypted")
print(f"\n  Running (with logging)...")
print(f"  {'_' * 54}")

engine = EmulationEngine(headless=False)
result = engine.run(payload)

print(f"  {'_' * 54}")
print(f"\n  Result:  {result['status']}")
print(f"  Elapsed: {result['elapsed']}s")
print(f"  Log:     {result.get('log_path', 'N/A')}")

# Show the log file contents
log_path = result.get("log_path")
if log_path and os.path.exists(log_path):
    print(f"\n  --- log.txt ---")
    with open(log_path, "r") as f:
        for line in f:
            print(f"  {line.rstrip()}")
    print(f"  --- end ---")

if result["status"] == "ok":
    print(f"\n{'=' * 60}")
    print(f"  TEST PASSED")
    print(f"{'=' * 60}")
else:
    print(f"\n{'=' * 60}")
    print(f"  TEST FAILED - {result['message']}")
    print(f"{'=' * 60}")