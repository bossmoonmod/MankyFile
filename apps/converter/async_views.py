"""
Async Views with Progress Tracking
"""
from django.shortcuts import render
from django.http import JsonResponse
from django.views import View
from django.urls import reverse
from .models import UploadedFile, ProcessedFile, DailyStat
from utils.progress_tracker import ProgressTracker
import uuid
import threading
import os
from django.conf import settings
from django.utils import timezone


class PDFToWordAsyncView(View):
    """
    PDF to Word conversion with real-time progress tracking
    """
    
    def get(self, request):
        context = {
            'title': 'PDF เป็น Word',
            'subtitle': 'แปลงไฟล์ PDF ของคุณเป็นเอกสาร DOC และ DOCX ที่แก้ไขได้ง่ายอย่างง่ายดาย',
            'file_type': 'PDF',
            'accept': '.pdf'
        }
        return render(request, 'converter/tool_base_v2.html', context)
    
    def post(self, request):
        # Check if AJAX request
        is_ajax = request.GET.get('ajax') == 'true'
        
        try:
            # Get uploaded file
            if 'files' in request.FILES:
                uploaded_file = request.FILES.getlist('files')[0]
            elif 'file' in request.FILES:
                uploaded_file = request.FILES['file']
            else:
                if is_ajax:
                    return JsonResponse({'success': False, 'message': 'ไม่พบไฟล์'}, status=400)
                return render(request, 'converter/tool_base_v2.html', {
                    'error': 'ไม่พบไฟล์',
                    'title': 'PDF เป็น Word'
                })
            
            # Save uploaded file
            upload_instance = UploadedFile(file=uploaded_file)
            upload_instance.save()
            
            # Generate task ID and job ID
            task_id = str(uuid.uuid4())
            job_id = str(uuid.uuid4())
            
            # Start background conversion
            thread = threading.Thread(
                target=self.convert_pdf_to_word,
                args=(task_id, job_id, upload_instance.file.path, uploaded_file.name)
            )
            thread.daemon = True
            thread.start()
            
            if is_ajax:
                return JsonResponse({
                    'success': True,
                    'task_id': task_id,
                    'job_id': job_id,
                    'message': 'เริ่มการแปลงไฟล์แล้ว'
                })
            else:
                # Fallback for non-AJAX
                return render(request, 'converter/result.html', {
                    'success': True,
                    'message': 'กำลังแปลงไฟล์...'
                })
                
        except Exception as e:
            print(f"Error in PDFToWordAsyncView: {e}")
            if is_ajax:
                return JsonResponse({'success': False, 'message': str(e)}, status=500)
            return render(request, 'converter/tool_base_v2.html', {
                'error': str(e),
                'title': 'PDF เป็น Word'
            })
    
    def convert_pdf_to_word(self, task_id, job_id, input_path, original_filename):
        """Background task for PDF to Word conversion"""
        tracker = ProgressTracker(task_id)
        
        try:
            # Step 1: Upload (0-20%)
            tracker.set_progress(0, "Uploading", "กำลังอัปโหลดไฟล์...")
            
            filename_only = os.path.splitext(os.path.basename(input_path))[0]
            output_filename = f"{filename_only}.docx"
            
            # Define output path
            output_dir = os.path.join(settings.MEDIA_ROOT, 'processed', job_id)
            os.makedirs(output_dir, exist_ok=True)
            output_full_path = os.path.join(output_dir, output_filename)
            
            tracker.set_progress(20, "Processing", "กำลังเตรียมการแปลง...")
            
            # Step 2: Convert (20-90%)
            from utils.cc_v2_api import CloudConvertService
            
            tracker.set_progress(30, "Converting", "กำลังแปลงไฟล์...")
            
            converter = CloudConvertService()
            converter.convert(
                input_file_path=input_path,
                output_format='docx',
                export_path=output_full_path
            )
            
            tracker.set_progress(90, "Finalizing", "กำลังเตรียมไฟล์...")
            
            # Step 3: Save to database
            processed_rel_path = f"processed/{job_id}/{output_filename}"
            processed_file = ProcessedFile(file=processed_rel_path)
            processed_file.save()
            
            # Track usage
            try:
                stat, _ = DailyStat.objects.get_or_create(date=timezone.now().date())
                stat.usage_count += 1
                stat.save()
            except Exception as e:
                print(f"Stats error: {e}")
            
            # Generate download URL
            download_url = reverse('converter:download_file', kwargs={'job_id': job_id})
            
            # Complete
            tracker.set_progress(100, "Complete", f"แปลงไฟล์สำเร็จ! ขนาด: {self.format_file_size(output_full_path)}")
            
            # Store result in tracker for retrieval
            result_data = tracker.get_progress()
            result_data['download_url'] = download_url
            result_data['filename'] = output_filename
            tracker.set_progress(100, "Complete", result_data)
            
        except Exception as e:
            print(f"Conversion error: {e}")
            tracker.error(f"เกิดข้อผิดพลาด: {str(e)}")
    
    def format_file_size(self, file_path):
        """Format file size in human readable format"""
        if not os.path.exists(file_path):
            return "0 B"
        
        size = os.path.getsize(file_path)
        for unit in ['B', 'KB', 'MB', 'GB']:
            if size < 1024.0:
                return f"{size:.1f} {unit}"
            size /= 1024.0
        return f"{size:.1f} TB"
