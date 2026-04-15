import os
import sys
import requests

url = os.environ["DEFECT_DOJO_URL"]
api_token = os.environ["DEFECT_DOJO_API_TOKEN"]
scan_type = os.environ["DEFECT_DOJO_SCAN_TYPE"]
engagement = os.environ["DEFECT_DOJO_ENGAGEMENT"]
file_path = os.environ["DEFECT_DOJO_FILE"]

headers = {
    "Authorization": f"Token {api_token}",
}

data = {
    "engagement": engagement,
    "scan_type": scan_type,
    "minimum_severity": "Info",
    "active": "true",
    "verified": "true",
    "close_old_findings": "false",
}

print("Uploading to:", url)
print("Scan type:", scan_type)
print("Engagement:", engagement)
print("File:", file_path)

if not os.path.exists(file_path):
    raise SystemExit(f"Report file not found: {file_path}")

print("File size:", os.path.getsize(file_path))

content_type = "application/json" if file_path.endswith(".json") else "application/xml"

with open(file_path, "rb") as f:
    response = requests.post(
        url,
        headers=headers,
        data=data,
        files={
            "file": (os.path.basename(file_path), f, content_type)
        },
        timeout=120,
    )

print(f"Status: {response.status_code}")
print(f"Response: {response.text}")

if response.status_code not in (200, 201):
    sys.exit(f"Upload failed with status {response.status_code}")