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
                # MODE B: Unknown Password (Brute Force)
                from utils.pdf_cracker import brute_force_pdf
                print(f"🕵️ Starting Crack for {input_path}")
                final_password = brute_force_pdf(input_path, output_full_path)
                
            
            if final_password is not None:
                # Success!
                original_size = os.path.getsize(input_path)
                new_size = os.path.getsize(output_full_path)
                
                # Save result to DB
                processed_rel_path = f"processed/{job_id}/{output_filename}"
                processed_file = ProcessedFile(file=processed_rel_path)
                processed_file.save()
                
                # Usage Stat
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
                # Failed to crack
                print("❌ Failed to crack password within limits.")
                return render(request, 'converter/unlock_pdf.html', {
                    'error': "ไม่สามารถปลดล็อคไฟล์นี้ได้โดยอัตโนมัติ (รหัสผ่านซับซ้อนเกินไป หรือเราใช้เวลาเดานานเกินกำหนด)"
                })

                
        except Exception as e:
            print(f"Unlock Error: {e}")
            return render(request, 'converter/unlock_pdf.html', {
                'error': f"เกิดข้อผิดพลาด: {str(e)}"
            })
