"""
Progress Tracker Utility for File Conversion
Uses Django Cache Framework to store progress state
"""
from django.core.cache import cache
import time


class ProgressTracker:
    """Track progress of file conversion tasks"""
    
    def __init__(self, task_id):
        self.task_id = task_id
        self.cache_key = f"progress_{task_id}"
        
    def set_progress(self, percent, status="Processing", message=""):
        """
        Update progress state
        
        Args:
            percent (int): Progress percentage (0-100)
            status (str): Status text (Uploading, Processing, Complete, Error)
            message (str): Additional message
        """
        data = {
            'percent': min(100, max(0, percent)),
            'status': status,
            'message': message,
            'timestamp': time.time()
        }
        cache.set(self.cache_key, data, timeout=300)  # 5 minutes
        
    def get_progress(self):
        """Get current progress state"""
        data = cache.get(self.cache_key)
        if data is None:
            return {
                'percent': 0,
                'status': 'Not Found',
                'message': 'Task not found or expired',
                'timestamp': time.time()
            }
        return data
    
    def complete(self, message="Conversion completed successfully"):
        """Mark task as complete"""
        self.set_progress(100, "Complete", message)
        
    def error(self, message="An error occurred"):
        """Mark task as error"""
        self.set_progress(0, "Error", message)
        
    def clear(self):
        """Clear progress data"""
        cache.delete(self.cache_key)


def simulate_progress(tracker, steps=10, delay=0.5):
    """
    Simulate progress for testing
    
    Args:
        tracker (ProgressTracker): Progress tracker instance
        steps (int): Number of steps
        delay (float): Delay between steps in seconds
    """
    for i in range(steps):
        percent = int((i + 1) / steps * 100)
        tracker.set_progress(percent, "Processing", f"Step {i+1}/{steps}")
        time.sleep(delay)
    tracker.complete()
