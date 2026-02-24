"""
S3Storage  - Minimal S3 interface for payload and file storage.

No hardcoded bucket names. No bucket ID registry. No cb911 coupling.
The caller (or the payload) specifies which bucket to use.
"""

import json
import os
from typing import Optional

import boto3


class S3Storage:
    """Thin wrapper around boto3 S3 client."""

    def __init__(self):
        self._client = None

    @property
    def client(self):
        if self._client is None:
            self._client = boto3.client("s3")
        return self._client

    # ── Read ───────────────────────────────────────────────────

    def get(self, bucket: str, key: str) -> str:
        """Fetch an object as a UTF-8 string."""
        obj = self.client.get_object(Bucket=bucket, Key=key)
        return obj["Body"].read().decode("utf-8")

    def get_bytes(self, bucket: str, key: str) -> bytes:
        """Fetch an object as raw bytes."""
        obj = self.client.get_object(Bucket=bucket, Key=key)
        return obj["Body"].read()

    def list_keys(self, bucket: str, prefix: str):
        """Yield all object keys under a prefix."""
        paginator = self.client.get_paginator("list_objects_v2")
        for page in paginator.paginate(Bucket=bucket, Prefix=prefix):
            for item in page.get("Contents", []):
                yield item["Key"]

    def exists(self, bucket: str, key: str) -> bool:
        """Check whether an object exists."""
        resp = self.client.list_objects_v2(Bucket=bucket, Prefix=key, MaxKeys=1)
        return resp.get("KeyCount", 0) > 0

    # ── Write ──────────────────────────────────────────────────

    def put(self, bucket: str, key: str, data, metadata: Optional[dict] = None):
        """Upload data (str or bytes) to S3."""
        if isinstance(data, str):
            data = data.encode("utf-8")
        kwargs = {"Bucket": bucket, "Key": key, "Body": data}
        if metadata and len(json.dumps(metadata)) < 2000:
            kwargs["Metadata"] = metadata
        self.client.put_object(**kwargs)

    def upload_file(self, bucket: str, key: str, local_path: str):
        """Upload a local file to S3."""
        self.client.upload_file(local_path, Bucket=bucket, Key=key)

    def download_file(self, bucket: str, key: str, local_path: str):
        """Download an S3 object to a local file."""
        directory = os.path.dirname(local_path)
        if directory:
            os.makedirs(directory, exist_ok=True)
        self.client.download_file(Bucket=bucket, Key=key, Filename=local_path)

    # ── Delete ─────────────────────────────────────────────────

    def delete(self, bucket: str, key: str):
        """Delete an object from S3."""
        self.client.delete_object(Bucket=bucket, Key=key)

    # ── Move ───────────────────────────────────────────────────

    def move(self, bucket: str, source_key: str, dest_key: str):
        """Copy an object to a new key, then delete the original."""
        self.client.copy_object(
            Bucket=bucket,
            CopySource={"Bucket": bucket, "Key": source_key},
            Key=dest_key,
        )
        self.delete(bucket, source_key)
