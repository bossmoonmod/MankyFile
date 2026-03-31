from django.core.management.base import BaseCommand
from utils.file_cleanup import cleanup_old_files


class Command(BaseCommand):
    help = 'Clean up old uploaded and processed files'

    def add_arguments(self, parser):
        parser.add_argument(
            '--hours',
            type=int,
            default=1,
            help='Delete files older than this many hours (default: 1)'
        )

    def handle(self, *args, **options):
        hours = options['hours']
        self.stdout.write(f'Starting cleanup of files older than {hours} hour(s)...')
        
        deleted_count = cleanup_old_files(hours=hours)
        
        self.stdout.write(
            self.style.SUCCESS(f'Successfully deleted {deleted_count} file(s)')
        )
