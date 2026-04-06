import os
import requests

url          = os.environ["DEFECT_DOJO_URL"]
api_token    = os.environ["DEFECT_DOJO_API_TOKEN"]
scan_type    = os.environ["DEFECT_DOJO_SCAN_TYPE"]
engagement   = os.environ["DEFECT_DOJO_ENGAGEMENT"]
product_name = os.environ["DEFECT_DOJO_PRODUCT_NAME"]
file_path    = os.environ["DEFECT_DOJO_FILE"]

headers = {"Authorization": f"Token {api_token}"}

with open(file_path, "rb") as f:
    response = requests.post(
        url,
        headers=headers,
        data={
            "scan_type": scan_type,
            "engagement": engagement,
            "product_name": product_name,
            "active": True,
            "verified": False,
            "close_old_findings": False,
        },
        files={"file": f},
    )

print(f"Status: {response.status_code}")
print(response.text)

if response.status_code not in [200, 201]:
    raise Exception(f"Upload failed: {response.text}")