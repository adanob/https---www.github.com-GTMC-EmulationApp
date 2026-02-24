"""
EmulationApp - Script-driven web emulation engine.

Usage:
    from engine import EmulationEngine, Payload

    engine = EmulationEngine(secret_key="your-secret-key")
    result = engine.run_from_file("payloads/my_job.json")
"""

__version__ = "1.0.0"

from .logger import JobLogger
from .runner import EmulationEngine
from .payload import Payload
from .credentials import CredentialManager
from .driver_factory import DriverFactory
from .browser_helper import BrowserHelper
from .s3_storage import S3Storage

__all__ = [
    "EmulationEngine",
    "Payload",
    "CredentialManager",
    "DriverFactory",
    "BrowserHelper",
    "S3Storage",
    "JobLogger",
]