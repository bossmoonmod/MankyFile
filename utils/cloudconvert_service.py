
import json
import requests
import os
import time
from django.conf import settings

class CloudConvertService:
    print("DEBUG: Loading CloudConvertService (Direct V.2)")
    def __init__(self):
        self.api_key = settings.CLOUDCONVERT_API_KEY
        self.api_url = "https://api.cloudconvert.com/v2"
        self.headers = {
            "Authorization": f"Bearer {self.api_key}",
            "Content-Type": "application/json"
        }
        
        if not self.api_key:
            raise Exception("CloudConvert API Key is not configured")

    def convert(self, input_file_path, output_format, export_path=None):
        """
        Convert file using CloudConvert REST API (No SDK required)
        """
        print(f"Starting CloudConvert Direct API for {input_file_path} -> {output_format}")
        
        filename = os.path.basename(input_file_path)
        input_format = os.path.splitext(filename)[1].lstrip('.').lower()
        
        # 1. Create Job with Tasks
        # We need: Import loop -> Convert -> Export
        job_payload = {
            "tasks": {
                "import-1": {
                    "operation": "import/upload"
                },
                "task-1": {
                    "operation": "convert",
                    "input_format": input_format,
                    "output_format": output_format,
                    "engine": "office",
                    "input": ["import-1"]
                },
                "export-1": {
                    "operation": "export/url",
                    "input": ["task-1"],
                    "inline": False,
                    "archive_multiple_files": False
                }
            },
            "tag": "mankyfile-v2-direct"
        }

        # Create Job
        create_res = requests.post(f"{self.api_url}/jobs", headers=self.headers, json=job_payload)
        if create_res.status_code != 201:
            raise Exception(f"Failed to create job: {create_res.text}")
            
        job_data = create_res.json()['data']
        job_id = job_data['id']
        
        # 2. Get Upload URL
        upload_task = next(task for task in job_data['tasks'] if task['name'] == 'import-1')
        upload_task_id = upload_task['id']
        
        # Use the form parameters provided by CloudConvert for upload
        upload_form = upload_task['result']['form']
        upload_url = upload_form['url']
        upload_params = upload_form['parameters']
        
        # 3. Upload File
        print(f"Uploading file to CloudConvert...")
        with open(input_file_path, 'rb') as f:
            files = {'file': f}
            # The upload requires multipart/form-data with specific fields
            upload_res = requests.post(upload_url, data=upload_params, files=files)
            
        if upload_res.status_code not in [200, 201, 204]:
             raise Exception(f"Upload failed: {upload_res.text}")

        # 4. Wait for Job Completion
        print("Waiting for conversion...")
        final_status = None
        export_task = None
        
        # Polling loop
        for _ in range(60): # Timeout 60 attempts (approx 2 mins)
            time.sleep(2)
            check_res = requests.get(f"{self.api_url}/jobs/{job_id}", headers=self.headers)
            job_data = check_res.json()['data']
            final_status = job_data['status']
            
            if final_status in ['finished', 'error']:
                break
        
        if final_status != 'finished':
            raise Exception(f"Job not finished. Status: {final_status}")

        # 5. Download Result
        export_task = next(task for task in job_data['tasks'] if task['name'] == 'export-1')
        file_info = export_task['result']['files'][0]
        download_url = file_info['url']
        result_filename = file_info['filename']
        
        print(f"Downloading result from {download_url}")
        download_res = requests.get(download_url)
        
        if not export_path:
            output_dir = os.path.dirname(input_file_path)
            export_path = os.path.join(output_dir, result_filename)
            
        with open(export_path, 'wb') as f:
            f.write(download_res.content)
            
        return export_path
