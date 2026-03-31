from django.contrib import admin
from .models import DailyStat, UploadedFile, ProcessedFile, ShortLink

# Register your models here.
admin.site.register(DailyStat)
admin.site.register(UploadedFile)
admin.site.register(ProcessedFile)
admin.site.register(ShortLink)
