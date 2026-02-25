"""
EmulationApp - Script-driven web emulation engine.

Usage:
    from engine import EmulationEngine, Payload

    engine = EmulationEngine(secret_key="your-secret-key")
    result = engine.run_from_file("payloads/my_job.json")
"""

__version__ = "1.0.0"

from .browser_helper import BrowserHelper
# Lazy imports: CredentialManager is lightweight (no heavy deps).
# Other modules require boto3, selenium, playwright etc. and are
# imported on demand so the encryption helper works even when those
# packages are not installed.

from .credentials import CredentialManager  # always available
from .driver_factory import DriverFactory
from .logger import JobLogger
from .payload import Payload
from .runner import EmulationEngine
from .s3_storage import S3Storage


def __getattr__(name):
    """Lazy-load heavy modules only when accessed."""
    _lazy = {
        "EmulationEngine": ".runner",
        "Payload":         ".payload",
        "DriverFactory":   ".driver_factory",
        "BrowserHelper":   ".browser_helper",
        "S3Storage":       ".s3_storage",
        "JobLogger":       ".logger",
    }
    if name in _lazy:
        import importlib
        module = importlib.import_module(_lazy[name], __package__)
        obj = getattr(module, name)
        globals()[name] = obj
        return obj
    raise AttributeError(f"module {__name__!r} has no attribute {name!r}")

__all__ = [
    "EmulationEngine",
    "Payload",
    "CredentialManager",
    "DriverFactory",
    "BrowserHelper",
    "S3Storage",
    "JobLogger",
]