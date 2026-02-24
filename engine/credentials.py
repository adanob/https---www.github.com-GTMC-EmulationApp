"""
CredentialManager - Encrypt and decrypt portal credentials.

Credentials are resolved by target URL pattern, not by processor or
gateway ID. The engine never stores plaintext passwords at rest.

Encryption: AES-CTR via a locally managed secret key.
Key management: The secret key is generated on first use and stored
in a local config file (.emulation_key). The user never sees or
handles it. In the Dashboard, the key is managed under Settings
(the gear icon).

Optional: AWS KMS integration for production-grade key management.
"""

import base64
import hashlib
import os
import secrets
from typing import Dict, Optional

# Default key file location (project root)
_DEFAULT_KEY_FILE = os.path.join(
    os.path.dirname(os.path.dirname(os.path.abspath(__file__))),
    ".emulation_key"
)


class CredentialManager:
    """Handles credential encryption, decryption, and resolution."""

    def __init__(self, secret_key: Optional[str] = None, key_file: Optional[str] = None):
        """
        Resolve the secret key in this order:
          1. Explicit secret_key argument (used by tests and the Dashboard)
          2. EMULATION_SECRET_KEY environment variable (used by CI/CD)
          3. Local key file - generated on first use (default for end users)

        :param secret_key: Optional explicit key. If provided, the key file is not used.
        :param key_file:   Path to the local key file. Defaults to .emulation_key
                           in the project root.
        """
        self._key_file = key_file or _DEFAULT_KEY_FILE

        if secret_key:
            self._key = secret_key.encode("utf-8")
        elif os.getenv("EMULATION_SECRET_KEY"):
            self._key = os.getenv("EMULATION_SECRET_KEY").encode("utf-8")
        else:
            self._key = self._load_or_generate_key()

    # -- Public API -----------------------------------------------

    def encrypt(self, plaintext: str) -> str:
        """Encrypt a plaintext string and return a base64 token."""
        try:
            from pyaes import AESModeOfOperationCTR
        except ImportError:
            raise ImportError("Install pyaes: run uv sync")

        aes = AESModeOfOperationCTR(self._padded_key())
        cipher = aes.encrypt(plaintext.encode("utf-8"))
        return base64.b64encode(cipher).decode("utf-8")

    def decrypt(self, token: str) -> str:
        """Decrypt a base64 token back to plaintext."""
        try:
            from pyaes import AESModeOfOperationCTR
        except ImportError:
            raise ImportError("Install pyaes: run uv sync")

        raw = base64.b64decode(token)
        aes = AESModeOfOperationCTR(self._padded_key())
        return aes.decrypt(raw).decode("utf-8")

    def encrypt_credentials(self, username: str, password: str) -> Dict[str, str]:
        """Return an encrypted credential pair ready for payload injection."""
        return {
            "username": self.encrypt(username),
            "password": self.encrypt(password),
        }

    def decrypt_credentials(self, creds: Dict[str, str]) -> Dict[str, str]:
        """Decrypt a credential pair from a payload."""
        return {
            "username": self.decrypt(creds.get("username", "")),
            "password": self.decrypt(creds.get("password", "")),
        }

    # -- KMS (optional, AWS environments) -------------------------

    @staticmethod
    def kms_encrypt(kms_key_id: str, plaintext: str) -> str:
        """Encrypt using AWS KMS. Returns base64-encoded ciphertext."""
        import boto3
        client = boto3.client("kms", region_name="us-east-1")
        blob = client.encrypt(KeyId=kms_key_id, Plaintext=plaintext)["CiphertextBlob"]
        return base64.b64encode(blob).decode("utf-8")

    @staticmethod
    def kms_decrypt(ciphertext_b64: str) -> str:
        """Decrypt a KMS-encrypted base64 string."""
        import boto3
        client = boto3.client("kms", region_name="us-east-1")
        raw = base64.b64decode(ciphertext_b64)
        return client.decrypt(CiphertextBlob=raw)["Plaintext"].decode("utf-8")

    # -- Key Management -------------------------------------------

    def _load_or_generate_key(self) -> bytes:
        """
        Load the key from the local key file. If the file does not exist,
        generate a new 32-character key, write it to the file, and return it.

        The key file is created with restrictive permissions (owner-only on
        Unix systems). On Windows, standard file permissions apply.
        """
        if os.path.exists(self._key_file):
            with open(self._key_file, "r") as f:
                key_text = f.read().strip()
            if key_text:
                return key_text.encode("utf-8")

        # Generate a new key
        key_text = secrets.token_urlsafe(32)

        # Write with restrictive permissions
        try:
            fd = os.open(self._key_file, os.O_WRONLY | os.O_CREAT | os.O_TRUNC, 0o600)
            with os.fdopen(fd, "w") as f:
                f.write(key_text)
        except OSError:
            # Fallback for environments where os.open with mode fails
            with open(self._key_file, "w") as f:
                f.write(key_text)

        return key_text.encode("utf-8")

    @staticmethod
    def rotate_key(key_file: Optional[str] = None) -> str:
        """
        Generate a new key and overwrite the key file. Returns the new key.

        WARNING: Existing encrypted credentials will not decrypt with the
        new key. Re-encrypt all credentials after rotating.

        This method is called from the Dashboard Settings (gear icon)
        when the user clicks "Rotate Encryption Key".
        """
        path = key_file or _DEFAULT_KEY_FILE
        new_key = secrets.token_urlsafe(32)
        try:
            fd = os.open(path, os.O_WRONLY | os.O_CREAT | os.O_TRUNC, 0o600)
            with os.fdopen(fd, "w") as f:
                f.write(new_key)
        except OSError:
            with open(path, "w") as f:
                f.write(new_key)
        return new_key

    # -- Internal -------------------------------------------------

    def _padded_key(self) -> bytes:
        """Return a 32-byte key derived from the raw key."""
        return hashlib.sha256(self._key).digest()