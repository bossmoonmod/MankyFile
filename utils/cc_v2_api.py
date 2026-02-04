
import json
import urllib.request
import urllib.parse
import urllib.error
import os
import time
import uuid
import mimetypes
from django.conf import settings

# RENAME FILE TO FORCE CACHE INVALIDATION
class CloudConvertService:
    print("DEBUG: Loading cc_v2_api (New File - Pure urllib)")
    def __init__(self):
        self.api_key = settings.CLOUDCONVERT_API_KEY
        self.api_url = "https://api.cloudconvert.com/v2"
        self.headers = {
            "Authorization": f"Bearer {self.api_key}",
            "Content-Type": "application/json"
        }
        
        if not self.api_key:
            raise Exception("CloudConvert API Key is not configured")

    def _request(self, method, url, data=None, headers=None, is_json=True):
        if headers is None:
            headers = self.headers.copy()
            
        if data and is_json:
            data = json.dumps(data).encode('utf-8')
        
        req = urllib.request.Request(url, data=data, headers=headers, method=method)
        try:
            with urllib.request.urlopen(req) as response:
                if response.status in [200, 201, 204]:
                    resp_data = response.read()
                    if resp_data:
                        return json.loads(resp_data)
                    return {}
                else:
                    raise Exception(f"API Error {response.status}")
        except urllib.error.HTTPError as e:
            error_body = e.read().decode()
            raise Exception(f"HTTP Error {e.code}: {error_body}")

    def convert(self, input_file_path, output_format, export_path=None):
        print(f"Starting convert via urllib for: {input_file_path}")
        
        filename = os.path.basename(input_file_path)
        input_format = os.path.splitext(filename)[1].lstrip('.').lower()
        
        # 1. Create Job with Tasks
        job_payload = {
            "tasks": {
                "import-1": {
                    "operation": "import/upload"
                },
                "task-1": {
                    "operation": "convert",
                    "input_format": input_format,
                    "output_format": output_format,
                    "engine": "office" if input_format in ['doc', 'docx'] else "solid", # Use 'solid' for PDF->Word
                    "input": ["import-1"]
                },
                "export-1": {
                    "operation": "export/url",
                    "input": ["task-1"],
                    "inline": False,
                    "archive_multiple_files": False
                }
            },
            "tag": "mankyfile-v2-native-renamed"
        }
        
        print("Creating Job...")
        job_res = self._request("POST", f"{self.api_url}/jobs", data=job_payload)
        job_data = job_res['data']
        job_id = job_data['id']
        
        # 2. Get Upload URL
        upload_task = next(task for task in job_data['tasks'] if task['name'] == 'import-1')
        
        upload_form = upload_task['result']['form']
        upload_url = upload_form['url']
        upload_params = upload_form['parameters']
        
        # 3. Upload File (Multipart Manual Construction)
        print("Uploading file...")
        boundary = uuid.uuid4().hex
        boundary_bytes = boundary.encode('utf-8')
        
        parts = []
        
        # Add form parameters
        for key, value in upload_params.items():
            parts.append(f'--{boundary}'.encode('utf-8'))
            parts.append(f'Content-Disposition: form-data; name="{key}"'.encode('utf-8'))
            parts.append(b'')
            parts.append(str(value).encode('utf-8'))
            
        # Add file
        parts.append(f'--{boundary}'.encode('utf-8'))
        parts.append(f'Content-Disposition: form-data; name="file"; filename="{filename}"'.encode('utf-8'))
        parts.append(b'Content-Type: application/octet-stream')
        parts.append(b'')
        
        with open(input_file_path, 'rb') as f:
            file_content = f.read()
            parts.append(file_content)
            
        parts.append(f'--{boundary}--'.encode('utf-8'))
        parts.append(b'')
        
        body = b'\r\n'.join(parts)
        
        upload_headers = {
            "Content-Type": f"multipart/form-data; boundary={boundary}",
            "Content-Length": str(len(body))
        }
        
        # Post multipart
        upload_req = urllib.request.Request(upload_url, data=body, headers=upload_headers, method="POST")
        try:
            with urllib.request.urlopen(upload_req) as response:
                pass # Success
        except urllib.error.HTTPError as e:
            raise Exception(f"Upload Failed: {e.read().decode()}")

        # 4. Wait for completion
        print("Waiting for completion...")
        for _ in range(60):
            time.sleep(2)
            check_res = self._request("GET", f"{self.api_url}/jobs/{job_id}")
            status = check_res['data']['status']
            if status in ['finished', 'error']:
                if status == 'error':
                    raise Exception(f"Job failed: {check_res['data']}")
                break
        
        # 5. Download
        print("Downloading result...")
        job_res = self._request("GET", f"{self.api_url}/jobs/{job_id}")
        export_task = next(task for task in job_res['data']['tasks'] if task['name'] == 'export-1')
        file_info = export_task['result']['files'][0]
        download_url = file_info['url']
        result_filename = file_info['filename']
        
        if not export_path:
            export_path = os.path.join(os.path.dirname(input_file_path), result_filename)
            
        with urllib.request.urlopen(download_url) as d, open(export_path, 'wb') as f:
            f.write(d.read())
            
        return export_path
