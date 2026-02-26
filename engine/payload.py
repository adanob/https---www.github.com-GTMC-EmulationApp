"""
Payload  - Domain-agnostic job definition.

The payload is a plain JSON object with four concerns:
  1. target_url    - where the browser navigates
  2. tokens        - freeform key-value map consumed by the navigation script
  3. credentials   - optional encrypted username / password
  4. driver        - which browser engine to use (selenium | playwright)
  5. script_path   - the navigation script that controls all actions

The engine does not decide what to do.  The script decides.
No action field.  No processor_id.  No parser.  No mid.  No gateway.
"""

import json
from dataclasses import dataclass, field
from typing import Dict, Optional


@dataclass
class Payload:
    target_url: str
    tokens: Dict[str, str] = field(default_factory=dict)
    credentials: Optional[Dict[str, str]] = None   # {"username": "...", "password": "..."}
    driver: str = "selenium"                        # "selenium" | "playwright"
    script_path: Optional[str] = None               # navigation script (None → PageCast instructions)
    s3_output_bucket: Optional[str] = None
    s3_output_prefix: Optional[str] = None

    # ── Factories ──────────────────────────────────────────────

    @classmethod
    def from_json(cls, raw: str) -> "Payload":
        """Parse a JSON string into a Payload, ignoring unknown fields."""
        data = json.loads(raw)
        return cls._from_filtered(data)

    @classmethod
    def from_dict(cls, data: dict) -> "Payload":
        """Build a Payload from a plain dictionary, ignoring unknown fields."""
        return cls._from_filtered(data)

    @classmethod
    def _from_filtered(cls, data: dict) -> "Payload":
        """Build a Payload keeping only fields the dataclass accepts."""
        known = {f.name for f in cls.__dataclass_fields__.values()}
        return cls(**{k: v for k, v in data.items() if k in known})

    @classmethod
    def from_s3(cls, s3_storage, bucket: str, key: str) -> "Payload":
        """Fetch a payload JSON from S3 and parse it."""
        raw = s3_storage.get(bucket, key)
        return cls.from_json(raw)

    # ── Serialisation ──────────────────────────────────────────

    def to_dict(self) -> dict:
        return {
            "target_url": self.target_url,
            "tokens": self.tokens,
            "credentials": self.credentials,
            "driver": self.driver,
            "script_path": self.script_path,
            "s3_output_bucket": self.s3_output_bucket,
            "s3_output_prefix": self.s3_output_prefix,
        }

    def to_json(self) -> str:
        return json.dumps(self.to_dict(), indent=2)

    # ── Validation ─────────────────────────────────────────────

    def validate(self):
        """Raise ValueError if the payload is incomplete."""
        if not self.target_url:
            raise ValueError("target_url is required.")
        if self.driver not in ("selenium", "playwright"):
            raise ValueError(f"Invalid driver '{self.driver}'. Must be selenium | playwright.")
        if not self.script_path:
            raise ValueError("script_path is required. The navigation script controls all actions.")