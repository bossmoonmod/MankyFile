from django.urls import path
from .views import (
    IndexView, ResultView, MergePDFView, SplitPDFView, PDFToWordView, 
    WordToPDFView, MergeWordView, ArrangeWordView, ArrangePDFView, 
    DownloadFileView, CompressPDFView, PDFToPowerPointView, 
    PDFToExcelView, PowerPointToPDFView, TermsView, PrivacyView,
    download_file, QRCodeGeneratorView, DeleteInstantView
)

app_name = 'converter'

urlpatterns = [
    path('', IndexView.as_view(), name='index'),
    path('merge-pdf/', MergePDFView.as_view(), name='merge_pdf'),
    path('split-pdf/', SplitPDFView.as_view(), name='split_pdf'),
    path('pdf-to-word/', PDFToWordView.as_view(), name='pdf_to_word'),
    path('compress-pdf/', CompressPDFView.as_view(), name='compress_pdf'),
    path('word-to-pdf/', WordToPDFView.as_view(), name='word_to_pdf'),
    path('merge-word/', MergeWordView.as_view(), name='merge_word'),
    path('arrange-word/', ArrangeWordView.as_view(), name='arrange_word'),
    path('arrange-pdf/', ArrangePDFView.as_view(), name='arrange_pdf'),
    path('result/', ResultView.as_view(), name='result'),
    # New direct download path (ID only - safer for Thai filenames)
    path('download-direct/<uuid:job_id>/', download_file, name='download_file'),
    # Old path kept for compatibility only if needed (commented out or kept as fallback)
    # path('download/<uuid:file_id>/', DownloadFileView.as_view(), name='download_file_old'), 
    path('pdf-to-powerpoint/', PDFToPowerPointView.as_view(), name='pdf_to_powerpoint'),
    path('pdf-to-excel/', PDFToExcelView.as_view(), name='pdf_to_excel'),
    path('powerpoint-to-pdf/', PowerPointToPDFView.as_view(), name='powerpoint_to_pdf'),
    path('terms/', TermsView.as_view(), name='terms'),
    path('privacy/', PrivacyView.as_view(), name='privacy'),
    path('qrcode-generator/', QRCodeGeneratorView.as_view(), name='qrcode_generator'),
    path('delete-instant/', DeleteInstantView.as_view(), name='delete_instant'),
]
