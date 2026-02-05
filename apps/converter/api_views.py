"""
API Views for Progress Tracking
"""
from django.http import JsonResponse
from django.views import View
from django.views.decorators.csrf import csrf_exempt
from django.utils.decorators import method_decorator
from utils.progress_tracker import ProgressTracker
import json
import threading
import time
import uuid


class ProgressAPIView(View):
    """
    API endpoint to get progress of a conversion task
    GET /api/progress/<task_id>/
    """
    
    def get(self, request, task_id):
        tracker = ProgressTracker(task_id)
        progress_data = tracker.get_progress()
        
        return JsonResponse({
            'success': True,
            'task_id': task_id,
            'progress': progress_data
        })


@method_decorator(csrf_exempt, name='dispatch')
class ProgressTestAPIView(View):
    """
    Test endpoint to simulate progress
    POST /api/progress/test/
    Body: { "duration": 10 }
    """
    
    def post(self, request):
        try:
            data = json.loads(request.body)
            duration = int(data.get('duration', 10))
            
            # Generate unique task ID
            task_id = str(uuid.uuid4())
            
            # Start background task
            thread = threading.Thread(
                target=self.simulate_task,
                args=(task_id, duration)
            )
            thread.daemon = True
            thread.start()
            
            return JsonResponse({
                'success': True,
                'task_id': task_id,
                'message': f'Task started with {duration}s duration'
            })
            
        except Exception as e:
            return JsonResponse({
                'success': False,
                'message': str(e)
            }, status=400)
    
    def simulate_task(self, task_id, duration):
        """Simulate a long-running task with progress updates"""
        tracker = ProgressTracker(task_id)
        
        try:
            steps = 20
            step_duration = duration / steps
            
            tracker.set_progress(0, "Uploading", "กำลังอัปโหลดไฟล์...")
            time.sleep(step_duration * 2)
            
            for i in range(1, steps - 2):
                percent = int((i / steps) * 100)
                
                if percent < 30:
                    status = "Processing"
                    message = "กำลังวิเคราะห์ไฟล์..."
                elif percent < 70:
                    status = "Converting"
                    message = "กำลังแปลงไฟล์..."
                else:
                    status = "Finalizing"
                    message = "กำลังเตรียมไฟล์..."
                
                tracker.set_progress(percent, status, message)
                time.sleep(step_duration)
            
            tracker.complete("แปลงไฟล์สำเร็จ! ไฟล์พร้อมดาวน์โหลด")
            
        except Exception as e:
            tracker.error(f"เกิดข้อผิดพลาด: {str(e)}")
