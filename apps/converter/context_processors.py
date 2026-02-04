from django.utils import timezone
from .models import DailyStat

def daily_usage_stats(request):
    try:
        today = timezone.now().date()
        # Get or create stats for today. 
        # auto_now_add in model fixes the date on creation, 
        # but for get_or_create to work day-by-day correctly with strict date matching,
        # we should query mostly.
        
        stat, created = DailyStat.objects.get_or_create(date=today)
        return {'daily_usage': stat.usage_count}
    except Exception:
        # In case of DB not ready or other issues
        return {'daily_usage': 0}
