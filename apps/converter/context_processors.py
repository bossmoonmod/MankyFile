from datetime import date
from .models import DailyStat

def daily_usage_stats(request):
    try:
        stat, _ = DailyStat.objects.get_or_create(date=date.today())
        return {'daily_usage': stat.usage_count}
    except Exception:
        return {'daily_usage': 0}
