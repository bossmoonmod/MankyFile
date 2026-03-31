import os
import time
from django.conf import settings
from pathlib import Path
from django.utils import timezone
# We need to import models inside function or securely to avoid circular imports if models import utils
# But usually utils don't import models at top level if not needed.

def cleanup_old_files(hours=1):
    """
    Delete files in media directory older than 'hours' AND their associated DB records.
    Default: 1 hour. This ensures 100% data privacy and compliance.
    """
    media_root = Path(settings.MEDIA_ROOT)
    now = time.time()
    cutoff = now - (hours * 3600)
    
    deleted_count = 0
    print(f"🧹 Cleaning up files older than {hours} hours...")
    
    # 1. Clean physical files
    for folder in ['uploads', 'processed', 'previews']:
        folder_dir = media_root / folder
        if folder_dir.exists():
            for path in folder_dir.rglob('*'):
                if path.is_file():
                    try:
                        mtime = path.stat().st_mtime
                        if mtime < cutoff:
                            # print(f"  ❌ Deleting {folder}: {path.name}")
                            path.unlink()
                            deleted_count += 1
                    except Exception as e: pass
            
            # Clean up empty directories safely
            for path in sorted(folder_dir.rglob('*'), reverse=True):
                if path.is_dir() and not any(path.iterdir()):
                    try: path.rmdir()
                    except: pass
                    
    # 2. Clean database records
    try:
        from apps.converter.models import UploadedFile, ProcessedFile
        import datetime
        db_cutoff = timezone.now() - datetime.timedelta(hours=hours)
        
        old_uploads = UploadedFile.objects.filter(uploaded_at__lt=db_cutoff)
        if old_uploads.exists():
            old_uploads.delete()
            
        old_processed = ProcessedFile.objects.filter(created_at__lt=db_cutoff)
        if old_processed.exists():
            old_processed.delete()
            
    except Exception as e:
        print(f"  ⚠️ DB Cleanup Error: {e}")
    
    print(f"✅ Cleanup complete! Deleted {deleted_count} files.")
    return deleted_count


def cleanup_all_files():
    """
    Delete ALL files in media directory (use with caution!)
    """
    media_root = Path(settings.MEDIA_ROOT)
    deleted_count = 0
    
    print("🗑️ Deleting ALL files in media directory...")
    
    for folder in ['uploads', 'processed']:
        folder_path = media_root / folder
        if folder_path.exists():
            for path in folder_path.rglob('*'):
                if path.is_file():
                    try:
                        path.unlink()
                        deleted_count += 1
                    except Exception as e:
                        print(f"  ⚠️ Error deleting {path}: {e}")
    
    print(f"✅ Deleted {deleted_count} files.")
    return deleted_count

def cleanup_expired_links_db():
    """
    Delete expired ShortLink records from database
    """
    from apps.converter.models import ShortLink
    now = timezone.now()
    expired_links = ShortLink.objects.filter(expires_at__lt=now)
    count = expired_links.count()
    if count > 0:
        expired_links.delete()
        print(f"✅ Deleted {count} expired short links.")
    return count
