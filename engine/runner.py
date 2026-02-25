"""
EmulationEngine - Main entry point.

Workflow:
  1. Load payload from S3 (or local JSON)
  2. Resolve the navigation script (fetch from S3 if needed)
  3. Decrypt credentials (if present)
  4. Open the browser (visible to the user)
  5. Execute the navigation script - the script decides all actions
  6. Collect downloaded files and upload to the S3 output bucket
  7. Write log.txt and upload to S3
  8. Clean up

The engine does NOT decide what to do.
The navigation script is the sole authority.

Usage:
    from engine import EmulationEngine
    engine = EmulationEngine()
    result = engine.run_from_file("jobs/my_job.json")
"""

import glob
import importlib.util
import os
import tempfile
import time
import traceback
from typing import Callable, Optional

from .payload import Payload
from .credentials import CredentialManager
from .driver_factory import DriverFactory
from .browser_helper import BrowserHelper
from .s3_storage import S3Storage
from .logger import JobLogger


class EmulationEngine:
    """Standalone, domain-agnostic emulation runner."""

    def __init__(
        self,
        secret_key: Optional[str] = None,
        headless: bool = False,
        log_callback: Optional[Callable] = None,
    ):
        """
        :param secret_key:    Optional AES key override. If not provided, the
                              CredentialManager generates and stores one in
                              .emulation_key on first use.
        :param headless:      Run the browser without a visible window
                              (default: False - browser is visible).
        :param log_callback:  Optional function called with each log entry dict.
                              The Dashboard UI hooks into this to display live
                              events in the Job Log panel.
        """
        self.headless = headless
        self.s3 = S3Storage()
        self.creds = CredentialManager(secret_key=secret_key)
        self._log_callback = log_callback

    # -- Public API -----------------------------------------------

    def run(self, payload: Payload) -> dict:
        """Execute an emulation job from a Payload object."""
        payload.validate()
        return self._execute(payload)

    def run_from_json(self, json_str: str) -> dict:
        """Execute from a raw JSON string."""
        return self.run(Payload.from_json(json_str))

    def run_from_s3(self, bucket: str, key: str) -> dict:
        """Fetch a payload from S3 and execute it."""
        return self.run(Payload.from_s3(self.s3, bucket, key))

    def run_from_file(self, filepath: str) -> dict:
        """Load a local JSON file and execute it."""
        with open(filepath) as f:
            payload = Payload.from_json(f.read())
        # Pass the payload's directory so co-located scripts can be found
        payload._source_dir = os.path.dirname(os.path.abspath(filepath))
        return self.run(payload)

    # -- Internal: main execution loop ----------------------------

    def _execute(self, payload: Payload) -> dict:
        """Core execution loop. The script decides everything."""
        start = time.time()
        job_id = str(int(time.time()))

        # Create working directories
        job_dir = os.path.join(tempfile.gettempdir(), "emulation", job_id)
        download_dir = os.path.join(job_dir, "downloads")
        os.makedirs(download_dir, exist_ok=True)

        # Start logging
        logger = JobLogger(log_dir=job_dir, callback=self._log_callback)
        logger.info("Job started", f"ID: {job_id}")

        result = {"status": "error", "message": "", "elapsed": 0, "job_id": job_id}
        helper = None

        try:
            # -- Step 1: Resolve navigation script --------------------
            script_path = self._resolve_script(payload, logger, job_dir)

            # -- Step 2: Decrypt credentials --------------------------
            plain_creds = {}
            if payload.credentials:
                plain_creds = self.creds.decrypt_credentials(payload.credentials)
                logger.info("Credentials decrypted")
            else:
                logger.info("No credentials provided", "Skipping login")

            # -- Step 3: Open browser ---------------------------------
            logger.info("Opening browser", f"Driver: {payload.driver}")
            driver = DriverFactory.create(
                driver_type=payload.driver,
                headless=self.headless,
                download_dir=download_dir,
            )
            helper = BrowserHelper(driver, payload.driver, download_dir)
            logger.success("Browser opened", f"Navigating to {payload.target_url}")

            # -- Step 4: Build context for the script -----------------
            context = {
                "helper": helper,
                "tokens": payload.tokens,
                "credentials": plain_creds,
                "target_url": payload.target_url,
                "download_dir": download_dir,
                "s3": self.s3,
                "s3_output_bucket": payload.s3_output_bucket,
                "s3_output_prefix": payload.s3_output_prefix,
                "logger": logger,
            }

            # -- Step 5: Execute the navigation script ----------------
            logger.info("Executing navigation script", os.path.basename(script_path))
            script_result = self._run_script(script_path, context)
            logger.success("Navigation script completed")

            # -- Step 6: Upload downloads to S3 -----------------------
            uploaded_files = self._upload_downloads(
                payload, download_dir, logger
            )

            # -- Step 7: Upload log to S3 -----------------------------
            logger.info("Job complete")
            logger.close()
            self._upload_log(payload, logger.log_path)

            result["status"] = "ok"
            result["message"] = "Emulation completed successfully."
            result["script_result"] = script_result
            result["uploaded_files"] = uploaded_files
            result["log_path"] = logger.log_path

        except Exception as e:
            logger.error("Job failed", str(e))
            result["status"] = "error"
            result["message"] = str(e)
            result["traceback"] = traceback.format_exc()
            result["log_path"] = logger.log_path

            # Upload log even on failure
            logger.close()
            self._upload_log(payload, logger.log_path)

        finally:
            if helper:
                helper.quit()
                logger.info("Browser closed") if not logger._log_file.closed else None
            result["elapsed"] = round(time.time() - start, 2)

        return result

    # -- Internal: script resolution ------------------------------

    def _resolve_script(self, payload: Payload, logger: JobLogger, job_dir: str) -> str:
        """
        Resolve the navigation script to a local file path.

        script_path can be:
          - A filename:         "my_script.py"       (co-located in jobs/)
          - A relative path:    "jobs/my_script.py"  (from project root)
          - An S3 reference:    "s3://bucket-name/path/to/script.py"

        Resolution order for local paths:
          1. Exact path as given (absolute or relative to cwd)
          2. Co-located with the payload file (same directory)
        """
        script_path = payload.script_path

        if script_path.startswith("s3://"):
            # Parse s3://bucket/key
            parts = script_path[5:].split("/", 1)
            if len(parts) < 2:
                raise ValueError(f"Invalid S3 script path: {script_path}")

            bucket = parts[0]
            key = parts[1]
            filename = os.path.basename(key)
            local_path = os.path.join(job_dir, filename)

            logger.info("Fetching script from S3", f"{bucket}/{key}")
            self.s3.download_file(bucket, key, local_path)
            logger.success("Script downloaded", filename)

            return local_path

        else:
            # Try exact path first
            if os.path.exists(script_path):
                logger.info("Script loaded", os.path.basename(script_path))
                return os.path.abspath(script_path)

            # Try co-located with the payload file (jobs/ directory)
            source_dir = getattr(payload, '_source_dir', None)
            if source_dir:
                co_located = os.path.join(source_dir, os.path.basename(script_path))
                if os.path.exists(co_located):
                    logger.info("Script loaded (co-located)", os.path.basename(co_located))
                    return co_located

            raise FileNotFoundError(
                f"Navigation script not found: {script_path}"
                + (f" (also checked {source_dir})" if source_dir else "")
            )

    # -- Internal: script execution -------------------------------

    def _run_script(self, script_path: str, context: dict):
        """
        Load and execute a user-provided navigation script.

        The script must define a function:
            def navigate(context: dict) -> dict
        """
        spec = importlib.util.spec_from_file_location("nav_script", script_path)
        module = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(module)

        if not hasattr(module, "navigate"):
            raise AttributeError(
                f"Navigation script '{script_path}' must define a 'navigate(context)' function."
            )

        return module.navigate(context)

    # -- Internal: upload downloads to S3 -------------------------

    def _upload_downloads(
        self, payload: Payload, download_dir: str, logger: JobLogger
    ) -> list:
        """
        Scan the download directory for files and upload each one
        to the S3 output bucket.

        Returns a list of uploaded S3 keys.
        """
        if not payload.s3_output_bucket:
            logger.info("No S3 output bucket configured", "Skipping upload")
            return []

        files = glob.glob(os.path.join(download_dir, "**", "*"), recursive=True)
        files = [f for f in files if os.path.isfile(f)]

        if not files:
            logger.info("No downloaded files to upload")
            return []

        uploaded = []
        prefix = payload.s3_output_prefix or ""

        for filepath in files:
            filename = os.path.basename(filepath)
            s3_key = f"{prefix}{filename}" if prefix else filename

            logger.info("Uploading to S3", f"{payload.s3_output_bucket}/{s3_key}")
            self.s3.upload_file(payload.s3_output_bucket, s3_key, filepath)
            logger.success("Uploaded", filename)
            uploaded.append(s3_key)

        logger.info(
            f"{len(uploaded)} file(s) uploaded to S3",
            f"Bucket: {payload.s3_output_bucket}",
        )
        return uploaded

    # -- Internal: upload log to S3 -------------------------------

    def _upload_log(self, payload: Payload, log_path: str):
        """Upload log.txt to the S3 output bucket if configured."""
        if not payload.s3_output_bucket:
            return
        if not os.path.exists(log_path):
            return

        prefix = payload.s3_output_prefix or ""
        s3_key = f"{prefix}log.txt"

        try:
            self.s3.upload_file(payload.s3_output_bucket, s3_key, log_path)
        except Exception:
            pass  # Log upload failure should never crash the job