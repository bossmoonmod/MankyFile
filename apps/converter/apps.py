from django.apps import AppConfig
import threading
import time
import os
import sys
from django.conf import settings

class ConverterConfig(AppConfig):
    default_auto_field = 'django.db.models.BigAutoField'
    name = 'apps.converter'

    def ready(self):
        # Ensure we only run one scheduler instance
        # 'RUN_MAIN' is set by Django's auto-reloader. 
        # We run enabling logic only in the main worker process.
        if os.environ.get('RUN_MAIN') == 'true' or not settings.DEBUG:
            
            # Simple lock mechanism to avoid duplicate threads in some WSGI containers
            if not os.environ.get('DJANGO_SCHEDULER_STARTED'):
                os.environ['DJANGO_SCHEDULER_STARTED'] = 'true'
                
                # Start the background thread
                cleanup_thread = threading.Thread(target=self.background_cleanup_task, daemon=True)
                cleanup_thread.start()
                print("🚀 Background Cleanup Scheduler Started (Auto-running every 30 mins)")

    def background_cleanup_task(self):
        """
        This runs in the background to clean files and expired links
        """
        # Initial delay to let server start up fully
        time.sleep(60) 
        
        from utils.file_cleanup import cleanup_old_files, cleanup_expired_links_db
        
        while True:
            try:
                # 1. Clean Files (Older than 1 hour)
                cleanup_old_files(hours=1)
                
                # 2. Clean Expired Short Links (24h / 7d)
                cleanup_expired_links_db()
                
            except Exception as e:
                print(f"⚠️ Background Cleanup Error: {e}")
            
            # Wait for 30 minutes before next run
            time.sleep(1800)
