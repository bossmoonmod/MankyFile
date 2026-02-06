from django.shortcuts import render, redirect
from django.views import View
from django.utils import timezone
from .models import UploadedFile, ProcessedFile, DailyStat

class UnlockPDFView(View):
    def get(self, request):
        context = {
            'title': 'ปลดล็อค PDF',
            'subtitle': 'ลบรหัสผ่านและการป้องกันออกจากไฟล์ PDF ของคุณ',
            'action_url': '',
            'file_type': 'PDF',
            'accept': '.pdf',
            'error': None
        }
        return render(request, 'converter/unlock_pdf.html', context)

    def post(self, request):
        try:
            # 1. Get File
            if 'file' in request.FILES:
                uploaded_file = request.FILES['file']
            else:
                return redirect('converter:unlock_pdf')
            
            # 2. Save Upload
            upload_instance = UploadedFile(file=uploaded_file)
            upload_instance.save()
            input_path = upload_instance.file.path
            
            import pikepdf
            import os
            from django.conf import settings
            import uuid
            
            # 3. Mode Handling
            mode = request.POST.get('mode', 'unknown')
            password_input = request.POST.get('password', '').strip()
            
            output_full_path = "" # To be set
            final_password = None
            
            # Setup Output Path
            job_id = str(uuid.uuid4())
            output_dir = os.path.join(settings.MEDIA_ROOT, 'processed', job_id)
            os.makedirs(output_dir, exist_ok=True)
            output_filename = f"unlocked_{uuid.uuid4()}.pdf"
            output_full_path = os.path.join(output_dir, output_filename)

            if mode == 'known':
                if not password_input:
                    return render(request, 'converter/unlock_pdf.html', {
                        'error': "กรุณากรอกรหัสผ่าน (หากคุณเลือกโหมด 'รู้รหัสผ่าน')"
                    })
                
                # MODE A: Known Password
                try:
                    pdf = pikepdf.open(input_path, password=password_input)
                    pdf.save(output_full_path)
                    final_password = password_input
                except pikepdf.PasswordError:
                    return render(request, 'converter/unlock_pdf.html', {
                        'error': "รหัสผ่านที่คุณระบุไม่ถูกต้อง กรุณาลองใหม่"
                    })
            else:
                # MODE B: Unknown Password (Brute Force) -> Offload to Worker Node
                # Worker Configuration
                WORKER_URL = 'https://blilnk.shop/api.php' 
                API_KEY = 'MANKY_SECRET_KEY_12345'
                
                import requests
                
                try:
                    # Prepare file for upload
                    with open(input_path, 'rb') as f:
                        files = {'file': (uploaded_file.name, f, 'application/pdf')}
                        headers = {'X-API-KEY': API_KEY}
                        print(f"📡 Sending task to Worker Node: {WORKER_URL}")
                        
                        # Disable SSL Verify strictly for worker node temporary fix
                        response = requests.post(WORKER_URL, files=files, headers=headers, timeout=60, verify=False)
                        
                        if response.status_code == 200:
                            data = response.json()
                            task_id = data.get('task_id')
                            if task_id:
                                # Render Waiting Page
                                return render(request, 'converter/worker_wait.html', {
                                    'task_id': task_id,
                                    'worker_host': 'https://blilnk.shop',
                                    'file_name': uploaded_file.name
                                })
                            else:
                                raise Exception("Worker did not return a Task ID")
                        else:
                            raise Exception(f"Worker Error: {response.status_code} - {response.text}")

                except Exception as worker_error:
                    print(f"❌ Worker Connection Failed: {worker_error}")
                    # STOP: Do not fallback to local cracking on Render!
                    return render(request, 'converter/unlock_pdf.html', {
                        'error': f"ไม่สามารถเชื่อมต่อกับ Server ประมวลผลได้ (Worker Node Error): {worker_error}"
                    })
            
            if final_password is not None:
                # This block only runs if verify_password (local mode) succeeded
                # ... (rest of success logic)
                # Same Success Logic as before (for Local/Known mode)
                original_size = os.path.getsize(input_path)
                new_size = os.path.getsize(output_full_path)
                
                processed_rel_path = f"processed/{job_id}/{output_filename}"
                processed_file = ProcessedFile(file=processed_rel_path)
                processed_file.save()
                
                try:
                    stat, _ = DailyStat.objects.get_or_create(date=timezone.now().date())
                    stat.usage_count += 1
                    stat.save()
                except:
                    pass

                from django.urls import reverse
                download_url = reverse('converter:download_file', kwargs={'file_id': processed_file.id})
                
                return render(request, 'converter/result.html', {
                    'success': True,
                    'download_url': download_url,
                    'file_name': output_filename,
                    'file_type': 'PDF',
                    'extra_message': f'รหัสผ่านที่ปลดล็อคได้คือ: {final_password}' if final_password else None,
                    'original_size': original_size,
                    'file_size': new_size,
                    'reduction_percent': '0'
                })
            
            else:
                # Failed (Local)
                return render(request, 'converter/unlock_pdf.html', {
                    'error': "ไม่สามารถปลดล็อคไฟล์นี้ได้โดยอัตโนมัติ (Worker Unreachable & Local Timeout)"
                })

        except Exception as e:
            print(f"Unlock Error: {e}")
            return render(request, 'converter/unlock_pdf.html', {
                'error': f"เกิดข้อผิดพลาด: {str(e)}"
            })
