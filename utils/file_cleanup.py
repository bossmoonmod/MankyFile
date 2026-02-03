import os
import time
from django.conf import settings
from pathlib import Path

def cleanup_old_files(hours=1):
    """
    Delete files in media directory older than 'hours'.
    Default: 1 hour (files are deleted after 1 hour)
    """
    media_root = Path(settings.MEDIA_ROOT)
    now = time.time()
    cutoff = now - (hours * 3600)
    
    deleted_count = 0
    print(f"🧹 Cleaning up files older than {hours} hours...")
    
    # Clean uploads folder
    uploads_dir = media_root / 'uploads'
    if uploads_dir.exists():
        for path in uploads_dir.rglob('*'):
            if path.is_file():
                try:
                    mtime = path.stat().st_mtime
                    if mtime < cutoff:
                        print(f"  ❌ Deleting upload: {path.name}")
                        path.unlink()
                        deleted_count += 1
                except Exception as e:
                    print(f"  ⚠️ Error deleting {path}: {e}")
    
    # Clean processed folder
    processed_dir = media_root / 'processed'
    if processed_dir.exists():
        for path in processed_dir.rglob('*'):
            if path.is_file():
                try:
                    mtime = path.stat().st_mtime
                    if mtime < cutoff:
                        print(f"  ❌ Deleting processed: {path.name}")
                        path.unlink()
                        deleted_count += 1
                except Exception as e:
                    print(f"  ⚠️ Error deleting {path}: {e}")
    
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
