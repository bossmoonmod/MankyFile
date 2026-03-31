from django.core.management.base import BaseCommand
from utils.file_cleanup import cleanup_old_files, cleanup_expired_links_db

class Command(BaseCommand):
    help = 'Clean up EVERYTHING: Old files and expired links (Best for Render/Heroku)'

    def handle(self, *args, **options):
        self.stdout.write('🚀 Starting System Cleanup...')
        
        # 1. Clean Files (Default 1 hour)
        try:
            files_deleted = cleanup_old_files(hours=1)
            self.stdout.write(self.style.SUCCESS(f'Files deleted: {files_deleted}'))
        except Exception as e:
            self.stdout.write(self.style.ERROR(f'Error cleaning files: {e}'))
            
        # 2. Clean Database Links
        try:
            links_deleted = cleanup_expired_links_db()
            self.stdout.write(self.style.SUCCESS(f'Expired links deleted: {links_deleted}'))
        except Exception as e:
            self.stdout.write(self.style.ERROR(f'Error cleaning links: {e}'))
            
        self.stdout.write(self.style.SUCCESS('✨ System Cleanup Finished!'))
