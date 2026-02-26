"""
run.py - Main entry point for EmulationApp.

Usage:
    uv run python run.py jobs/sample_payload.json

This is what the Dashboard calls when the user presses "Run Job".
The encryption key is managed in .emulation_key (generated on first use).
"""

import sys
import json
from engine import EmulationEngine

def main():
    if len(sys.argv) < 2:
        print("EmulationApp")
        print("Usage:  uv run python run.py <payload.json>")
        print("        uv run python run.py jobs/sample_payload.json")
        sys.exit(0)

    filepath = sys.argv[1]

    engine = EmulationEngine(headless=False)
    result = engine.run_from_file(filepath)

    print(json.dumps(result, indent=2, default=str))

    # Exit with error code if job failed so the Dashboard detects it
    if result.get("status") != "ok":
        sys.exit(1)

if __name__ == "__main__":
    main()