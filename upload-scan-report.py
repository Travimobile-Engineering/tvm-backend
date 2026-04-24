import json
import os
import sys
from pathlib import Path

import requests


def main() -> int:
    url = os.environ["DEFECT_DOJO_URL"]
    api_token = os.environ["DEFECT_DOJO_API_TOKEN"]
    scan_type = os.environ["DEFECT_DOJO_SCAN_TYPE"]
    engagement = os.environ["DEFECT_DOJO_ENGAGEMENT"]
    file_path = Path(os.environ["DEFECT_DOJO_FILE"])

    headers = {
        "Authorization": f"Token {api_token}",
    }

    print(f"Uploading to: {url}")
    print(f"Scan type: {scan_type}")
    print(f"Engagement: {engagement}")
    print(f"File: {file_path}")

    if not file_path.exists():
        print(f"Report file not found: {file_path}")
        return 1

    file_size = file_path.stat().st_size
    print(f"File size: {file_size}")

    if file_size == 0:
        print("Report file is empty")
        return 1

    # Validate JSON if this is a JSON file
    if file_path.suffix.lower() == ".json":
        try:
            with file_path.open("r", encoding="utf-8") as jf:
                json.load(jf)
            print("JSON validation: OK")
        except Exception as exc:
            print(f"Invalid JSON file: {exc}")
            return 1

    data = {
        "engagement": engagement,
        "scan_type": scan_type,
        "minimum_severity": "Info",
        "active": "true",
        "verified": "true",
        "close_old_findings": "false",
    }

    content_type = "application/json"
    if file_path.suffix.lower() == ".xml":
        content_type = "application/xml"
    elif file_path.suffix.lower() == ".sarif":
        content_type = "application/sarif+json"

    try:
        with file_path.open("rb") as f:
            files = {
                "file": (file_path.name, f, content_type)
            }

            response = requests.post(
                url,
                headers=headers,
                data=data,
                files=files,
                timeout=180,
            )

        print(f"Status: {response.status_code}")
        print(f"Response: {response.text}")

        if response.status_code not in (200, 201):
            print(f"Upload failed with status {response.status_code}")
            return 1

        print("Upload successful")
        return 0

    except requests.RequestException as exc:
        print(f"Request failed: {exc}")
        return 1


if __name__ == "__main__":
    sys.exit(main())