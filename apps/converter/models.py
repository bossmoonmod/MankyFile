from django.db import models
import uuid

class UploadedFile(models.Model):
    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    file = models.FileField(upload_to='uploads/%Y/%m/%d/')
    original_filename = models.CharField(max_length=255, blank=True, null=True)
    uploaded_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return self.original_filename or self.file.name

    @property
    def filename(self):
        import os
        return os.path.basename(self.file.name)

class ProcessedFile(models.Model):
    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    file = models.FileField(upload_to='processed/')
    created_at = models.DateTimeField(auto_now_add=True)
    
    def __str__(self):
        return self.file.name
