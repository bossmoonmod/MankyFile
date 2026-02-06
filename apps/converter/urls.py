from django.urls import path
from .views import (
    IndexView, ResultView, MergePDFView, SplitPDFView,
    MergeWordView, ArrangeWordView, ArrangePDFView, 
    DownloadFileView, CompressPDFView, PDFToPowerPointView, 
    PDFToExcelView, PowerPointToPDFView, PDFToWordView, WordToPDFView,
    TermsView, PrivacyView, QRCodeGeneratorView, DeleteInstantView,
    ShortenURLView, RedirectShortLinkView, SystemCleanupView,
    download_file
)
from . import views_unlock
from .api_views import ProgressAPIView, ProgressTestAPIView
from django.views.generic import TemplateView

app_name = 'converter'

urlpatterns = [
    path('', IndexView.as_view(), name='index'),
    path('merge-pdf/', MergePDFView.as_view(), name='merge_pdf'),
    path('split-pdf/', SplitPDFView.as_view(), name='split_pdf'),
    
    # Use standard version with Worker Node
    path('pdf-to-word/', PDFToWordView.as_view(), name='pdf_to_word'),
    
    path('compress-pdf/', CompressPDFView.as_view(), name='compress_pdf'),
    path('word-to-pdf/', WordToPDFView.as_view(), name='word_to_pdf'),
    path('merge-word/', MergeWordView.as_view(), name='merge_word'),
    path('arrange-word/', ArrangeWordView.as_view(), name='arrange_word'),
    path('arrange-pdf/', ArrangePDFView.as_view(), name='arrange_pdf'),
    path('result/', ResultView.as_view(), name='result'),
    
    # Download routes (both old and new for compatibility)
    path('download/<uuid:file_id>/', DownloadFileView.as_view(), name='download_file'),
    path('download-direct/<uuid:job_id>/', download_file, name='download_file_direct'),
    
    path('pdf-to-powerpoint/', PDFToPowerPointView.as_view(), name='pdf_to_powerpoint'),
    path('pdf-to-excel/', PDFToExcelView.as_view(), name='pdf_to_excel'),
    path('powerpoint-to-pdf/', PowerPointToPDFView.as_view(), name='powerpoint_to_pdf'),
    path('terms/', TermsView.as_view(), name='terms'),
    path('privacy/', PrivacyView.as_view(), name='privacy'),
    path('qrcode-generator/', QRCodeGeneratorView.as_view(), name='qrcode_generator'),
    path('delete-instant/', DeleteInstantView.as_view(), name='delete_instant'),
    path('unlock-pdf/', views_unlock.UnlockPDFView.as_view(), name='unlock_pdf'),
    path('check-worker-status/', views_unlock.check_worker_status, name='check_worker_status'),

    # URL Shortener
    path('shorten-url/', ShortenURLView.as_view(), name='shorten_url'),
    path('s/<str:short_code>/', RedirectShortLinkView.as_view(), name='redirect_short_link'),

    # System Cleanup (For Cron/Render)
    path('cleanup-system/', SystemCleanupView.as_view(), name='cleanup_system'),
    
    # Progress Demo
    path('progress-demo/', TemplateView.as_view(template_name='converter/progress_demo.html'), name='progress_demo'),
    
    # API Endpoints
    path('api/progress/<str:task_id>/', ProgressAPIView.as_view(), name='api_progress'),
    path('api/progress/test/', ProgressTestAPIView.as_view(), name='api_progress_test'),
]
