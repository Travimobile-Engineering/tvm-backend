import os
import requests

url = os.environ["DEFECT_DOJO_URL"]
api_token = os.environ["DEFECT_DOJO_API_TOKEN"]
scan_type = os.environ["DEFECT_DOJO_SCAN_TYPE"]
engagement = os.environ["DEFECT_DOJO_ENGAGEMENT"]
product_name = os.environ["DEFECT_DOJO_PRODUCT_NAME"]
file_path = os.environ["DEFECT_DOJO_FILE"]

headers = {
    "Authorization": f"Token {api_token}",
}

with open(file_path, "rb") as f:
    response = requests.post(
        url,
        headers=headers,
        data={
            "engagement": engagement,
            "scan_type": scan_type,
            "product_name": product_name,
            "active": True,
            "verified": False,
            "close_old_findings": False,
        },
        files={"file": f},
    )

print(f"Status: {response.status_code}")
print(f"Response: {response.text}")

if response.status_code not in (200, 201):
    raise SystemExit(f"Upload failed with status {response.status_code}")