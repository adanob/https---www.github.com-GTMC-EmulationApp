"""
JobLogger - Real-time logging for emulation jobs.

Captures timestamped events during execution and:
  1. Streams them to a callback (for the Dashboard UI)
  2. Writes them to a log.txt file in the job's output directory

Usage inside runner.py:
    logger = JobLogger(log_dir="/tmp/emulation/job_123")
    logger.info("Browser opened", "Navigating to portal.example.com")
    logger.success("Login successful")
    logger.error("Element not found", "//button[@id='submit']")
"""

import os
import time
from datetime import datetime
from typing import Callable, Optional


class JobLogger:
    """Timestamped event logger with file output and UI callback."""

    def __init__(self, log_dir: str, callback: Optional[Callable] = None):
        """
        :param log_dir:   Directory where log.txt will be written.
        :param callback:  Optional function called with each log entry dict.
                          The Dashboard UI hooks into this to display live events.
        """
        self._log_dir = log_dir
        self._callback = callback
        self._entries = []
        self._log_file = None

        os.makedirs(log_dir, exist_ok=True)
        self._log_path = os.path.join(log_dir, "log.txt")
        self._log_file = open(self._log_path, "w", encoding="utf-8")

    # -- Public API -----------------------------------------------

    def info(self, message: str, detail: str = ""):
        """Log an informational event."""
        self._emit("INFO", message, detail)

    def success(self, message: str, detail: str = ""):
        """Log a success event."""
        self._emit("OK", message, detail)

    def warning(self, message: str, detail: str = ""):
        """Log a warning."""
        self._emit("WARN", message, detail)

    def error(self, message: str, detail: str = ""):
        """Log an error."""
        self._emit("ERROR", message, detail)

    def close(self):
        """Flush and close the log file."""
        if self._log_file and not self._log_file.closed:
            self._log_file.flush()
            self._log_file.close()

    @property
    def entries(self):
        """Return all log entries as a list of dicts."""
        return list(self._entries)

    @property
    def log_path(self):
        """Return the path to the log.txt file."""
        return self._log_path

    # -- Internal -------------------------------------------------

    def _emit(self, level: str, message: str, detail: str):
        """Create a log entry, write to file, and notify callback."""
        timestamp = datetime.now().strftime("%H:%M:%S.%f")[:-3]

        # Flatten multiline details (e.g. Selenium exceptions) to single line
        if detail:
            detail = " ".join(detail.replace("\r", "").split("\n")).strip()
            # Truncate very long details (Selenium errors can be enormous)
            if len(detail) > 300:
                detail = detail[:300] + "..."

        entry = {
            "time": timestamp,
            "level": level,
            "message": message,
            "detail": detail,
        }
        self._entries.append(entry)

        # Write to log.txt
        line = f"[{timestamp}] [{level:5s}] {message}"
        if detail:
            line += f"  |  {detail}"
        line += "\n"

        if self._log_file and not self._log_file.closed:
            self._log_file.write(line)
            self._log_file.flush()

        # Print to console
        print(f"    [{timestamp}] {message}" + (f"  |  {detail}" if detail else ""))

        # Notify the UI callback
        if self._callback:
            try:
                self._callback(entry)
            except Exception:
                pass  # UI callback failures should never crash the engine

    def __del__(self):
        self.close()