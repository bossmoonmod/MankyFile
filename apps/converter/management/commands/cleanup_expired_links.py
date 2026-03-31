from django.core.management.base import BaseCommand
from django.utils import timezone
from apps.converter.models import ShortLink

class Command(BaseCommand):
    help = 'Clean up expired short links from the database'

    def handle(self, *args, **options):
        self.stdout.write('Checking for expired links...')
        
        now = timezone.now()
        # Find links where expiration time is less than current time
        expired_links = ShortLink.objects.filter(expires_at__lt=now)
        count = expired_links.count()
        
        if count > 0:
            expired_links.delete()
            self.stdout.write(self.style.SUCCESS(f'Successfully deleted {count} expired short link(s)'))
        else:
            self.stdout.write(self.style.SUCCESS('No expired links found. Everything is clean!'))
