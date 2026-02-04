
import cloudconvert
from django.conf import settings
import os
import uuid
import time
import requests

class CloudConvertService:
    def __init__(self):
        self.api_key = settings.CLOUDCONVERT_API_KEY
        self.sandbox = getattr(settings, 'CLOUDCONVERT_SANDBOX', False)
        
        if not self.api_key:
            raise Exception("CloudConvert API Key is not configured")
            
        cloudconvert.configure(api_key=self.api_key, sandbox=self.sandbox)

    def convert(self, input_file_path, output_format, export_path=None):
        """
        Convert file using CloudConvert API
        :param input_file_path: Absolute path to the file to convert
        :param output_format: 'pdf', 'docx', 'pptx', etc.
        :param export_path: Optional path to save the output file
        :return: Path to the converted file
        """
        print(f"Starting CloudConvert job for {input_file_path} -> {output_format}")
        
        # Determine format from extension
        filename = os.path.basename(input_file_path)
        input_format = os.path.splitext(filename)[1].lstrip('.').lower()
        
        # Setup Job
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
                    "input": [
                        "import-1"
                    ]
                },
                "export-1": {
                    "operation": "export/url",
                    "input": [
                        "task-1"
                    ],
                    "inline": False,
                    "archive_multiple_files": False
                }
            },
            "tag": "mankyfile-v2"
        }

        # Create Job
        job = cloudconvert.Job.create(payload=job_payload)
        
        # Get upload URL
        upload_task = next(task for task in job['tasks'] if task['name'] == 'import-1')
        upload_task_id = upload_task['id']
        
        # Upload File
        print(f"Uploading file to CloudConvert...")
        with open(input_file_path, 'rb') as f:
            cloudconvert.Task.upload(file_name=filename, task=upload_task)  # Using built-in upload helper if possibly, else manual
            # SDK helper is Task.upload(file_name, task)
        
        # Wait for completion
        print("Waiting for conversion...")
        job = cloudconvert.Job.wait(id=job['id']) # This blocks until finished
        
        # Check tasks status
        export_task = next(task for task in job['tasks'] if task['name'] == 'export-1')
        
        if export_task['status'] != 'finished':
             raise Exception(f"CloudConvert Job Failed: {job}")

        file_url = export_task['result']['files'][0]['url']
        filename_result = export_task['result']['files'][0]['filename']
        print(f"Conversion finished. Downloading result from {file_url}")
        
        # Download Result
        if not export_path:
            # Save to same directory with new extension
            output_dir = os.path.dirname(input_file_path)
            export_path = os.path.join(output_dir, filename_result)
            
        response = requests.get(file_url)
        if response.status_code == 200:
            with open(export_path, 'wb') as f:
                f.write(response.content)
            return export_path
        else:
            raise Exception("Failed to download converted file")

