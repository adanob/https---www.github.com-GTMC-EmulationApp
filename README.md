# EmulationApp

Script-driven web emulation engine.

## Project Structure

```
EmulationApp/
├── run.py                  Entry point (uv run python run.py payload.json)
├── test_emulation.py       Verify your setup works
├── pyproject.toml          Project config and dependencies
├── .python-version         Pinned Python version
├── .emulation_key          Encryption key (generated on first run, do not share)
├── engine/                 Core engine (do not modify)
│   ├── __init__.py
│   ├── runner.py
│   ├── payload.py
│   ├── credentials.py
│   ├── driver_factory.py
│   ├── browser_helper.py
│   ├── logger.py
│   └── s3_storage.py
├── scripts/                Navigation scripts go here
│   └── sample_navigation_script.py
├── payloads/               Job payloads go here (also created by the UI)
│   └── sample_payload.json
└── ui/                     Laravel Dashboard (optional)
    ├── app/Http/Controllers/EmulationController.php
    ├── config/emulation.php
    ├── resources/views/dashboard.blade.php
    ├── routes/web.php
    └── .env
```

## Quick Start (Engine)

1. Open this folder as a project in PyCharm
2. Open the Terminal tab and run: `uv sync`
3. Run the test: `uv run python test_emulation.py`
4. If you see "TEST PASSED" the engine is ready

## Dashboard UI Setup (Laravel)

The Dashboard is a Laravel app that lives in the `ui/` folder. It provides
a browser-based form for creating payloads with encrypted credentials.
Payloads generated from the UI will auto encrypt the password in the payload.

### Prerequisites

- PHP 8.1+
- Composer (getcomposer.org)
- The engine must be installed first (`uv sync` completed)

### Installation

1. Create the Laravel project inside EmulationApp:

```
cd C:\wamp64\www\EmulationApp
composer create-project laravel/laravel ui
```

2. Copy the skeleton files into the Laravel project:

| File                                           | Copy To                            |
|------------------------------------------------|------------------------------------|
| `ui/app/Http/Controllers/EmulationController.php` | (already in place)              |
| `ui/config/emulation.php`                      | (already in place)                 |
| `ui/routes/web.php`                            | Replace the default file contents  |
| `ui/resources/views/dashboard.blade.php`       | (already in place)                 |

3. Add these three lines to `ui/.env`:

```
EMULATION_APP_ROOT=C:\wamp64\www\EmulationApp
EMULATION_PAYLOADS_DIR=C:\wamp64\www\EmulationApp\payloads
EMULATION_SCRIPTS_DIR=C:\wamp64\www\EmulationApp\scripts
```

4. Start the Laravel server:

```
cd ui
php artisan serve
```

5. Open http://localhost:8000 in your browser.

### What the Dashboard Does

- Displays a form matching the numbered steps: URL, credentials, tokens, script, S3 config
- Encrypts the username and password when you click Save Payload
  (calls the Python CredentialManager using the same `.emulation_key`)
- Writes the payload JSON directly to `EmulationApp/payloads/`
- Lists all saved payloads in a side panel with View and Delete actions
- Populates the script dropdown from the `scripts/` folder

## Encrypting Credentials for a Payload

Credentials in payload files must be encrypted. The encryption key is
generated on first run and stored in `.emulation_key` in the project root.

To generate encrypted credentials, run this in the Terminal:

```
uv run python -c "from engine import CredentialManager; cm = CredentialManager(); c = cm.encrypt_credentials('your_username', 'your_password'); print(c)"
```

This prints something like:

```
{'username': 'abc123...', 'password': 'xyz789...'}
```

Copy those two values into your payload JSON, replacing the `<encrypted>` placeholders:

```json
{
  "credentials": {
    "username": "abc123...",
    "password": "xyz789..."
  }
}
```

The key is unique to your machine. Do not share `.emulation_key` or the
encrypted values with others unless they have the same key file.

## Running a Job

From the Terminal:

```
uv run python run.py payloads/sample_payload.json
```

Or from Python:

```python
from engine import EmulationEngine

engine = EmulationEngine()
result = engine.run_from_file("payloads/my_job.json")
```

## Logs

Each job writes a timestamped `log.txt` to a temp directory. The path is
printed at the end of every run. If an S3 output bucket is configured in
the payload, the log is also uploaded there.
