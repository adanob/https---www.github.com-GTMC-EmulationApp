# EmulationApp

Script-driven web emulation engine.

## Project Structure

```
EmulationApp/
├── run.py                  Entry point (uv run python run.py payload.json)
├── test_emulation.py       Verify your setup/repo works
├── pyproject.toml          Project config and dependencies
├── .python-version         Pinned Python version
├── engine/                 Core engine (do not modify)
│   ├── __init__.py
│   ├── runner.py
│   ├── payload.py
│   ├── credentials.py
│   ├── driver_factory.py
│   ├── browser_helper.py
│   └── s3_storage.py
├── scripts/                Navigation scripts go here
│   └── sample_navigation_script.py
└── payloads/               Job payloads go here
    └── sample_payload.json
```

## Quick Start

1. Open this folder as a project in PyCharm
2. Open the Terminal tab and run: uv sync
3. Right-click `test_emulation.py` and select Run
4. If you see "TEST PASSED" the app is ready

## Usage

```python
from engine import EmulationEngine

engine = EmulationEngine(secret_key="your-key")
result = engine.run_from_file("payloads/my_job.json")
```

Or from terminal:

```
uv run python run.py payloads/sample_payload.json
```
