from django.shortcuts import render, redirect
from django.urls import reverse
from django.conf import settings
from django.views import View
from .services.pdf_service import PDFService
from .services.word_service import WordService
from .models import UploadedFile, ProcessedFile, DailyStat
import uuid
from django.utils import timezone
import sys
import os

class IndexView(View):
    def get(self, request):
        return render(request, 'converter/index.html')

class MergePDFView(View):
    def get(self, request):
        context = {
            'title': 'รวมไฟล์ PDF',
            'subtitle': 'รวมไฟล์ PDF ตามลำดับที่คุณต้องการด้วยตัวรวม PDF ที่ง่ายที่สุด',
            'action_url': '',  # To be implemented
            'file_type': 'PDF',
            'accept': '.pdf'
        }
        return render(request, 'converter/tool_base.html', context)

    def post(self, request):
        files = request.FILES.getlist('files')
        if files:
            uploaded_ids = []
            for f in files:
                uploaded_file = UploadedFile.objects.create(
                    file=f,
                    original_filename=f.name
                )
                uploaded_ids.append(str(uploaded_file.id))
            
            # Save IDs to session
            if request.GET.get('append') == 'true':
                 existing_ids = request.session.get('merge_pdf_file_ids', [])
                 existing_ids.extend(uploaded_ids)
                 request.session['merge_pdf_file_ids'] = existing_ids
                 request.session.modified = True
            else:
                 request.session['merge_pdf_file_ids'] = uploaded_ids
                 request.session.modified = True
            
            return redirect('converter:arrange_pdf')
            
        return redirect('converter:merge_pdf')

class SplitPDFView(View):
    def get(self, request):
        context = {
            'title': 'แยกไฟล์ PDF',
            'subtitle': 'แยกหน้าหนึ่งหน้าหรือทั้งชุดเพื่อแปลงเป็นไฟล์ PDF อิสระได้อย่างง่ายดาย',
            'action_url': reverse('converter:split_pdf'),
            'file_type': 'PDF',
            'accept': '.pdf'
        }
        return render(request, 'converter/tool_base.html', context)

    def post(self, request):
        try:
             # Handle multiple files input (name="files") from template
            if 'files' in request.FILES:
                uploaded_file = request.FILES.getlist('files')[0]
            elif 'file' in request.FILES:
                uploaded_file = request.FILES['file']
            else:
                return redirect('converter:index')
            
            # Save uploaded file
            upload_instance = UploadedFile(file=uploaded_file)
            upload_instance.save()
            input_path = upload_instance.file.path
            
            # Prepare output directory
            job_id = str(uuid.uuid4())
            output_dir = os.path.join(settings.MEDIA_ROOT, 'processed', job_id)
            os.makedirs(output_dir, exist_ok=True)
            
            # Split PDF using PyPDF2
            import PyPDF2
            import zipfile
            
            extracted_files = []
            filename_base = os.path.splitext(os.path.basename(input_path))[0]
            
            with open(input_path, 'rb') as f:
                reader = PyPDF2.PdfReader(f)
                total_pages = len(reader.pages)
                
                for i in range(total_pages):
                    writer = PyPDF2.PdfWriter()
                    writer.add_page(reader.pages[i])
                    
                    page_filename = f"{filename_base}_page_{i+1}.pdf"
                    page_path = os.path.join(output_dir, page_filename)
                    
                    with open(page_path, 'wb') as out_f:
                        writer.write(out_f)
                    extracted_files.append(page_filename)
            
            # Zip all extracted files
            zip_filename = f"{filename_base}_split.zip"
            zip_path = os.path.join(output_dir, zip_filename)
            
            with zipfile.ZipFile(zip_path, 'w') as zipf:
                for file in extracted_files:
                    file_abs_path = os.path.join(output_dir, file)
                    zipf.write(file_abs_path, arcname=file)
                    
            # Cleanup: Delete individual PDF pages after zipping
            # ensures only the ZIP file remains for the download view to find
            for file in extracted_files:
                try:
                    os.remove(os.path.join(output_dir, file))
                except Exception as cleanup_error:
                    print(f"Cleanup error: {cleanup_error}")
            
            # Create ProcessedFile record for the ZIP
            processed_rel_path = f"processed/{job_id}/{zip_filename}"
            processed_file = ProcessedFile(
                file=processed_rel_path
            )
            processed_file.save()
            
            # Generate ID-only download link
            download_url = reverse('converter:download_file', kwargs={'job_id': job_id})
            
            context = {
                'success': True,
                'file_url': download_url,
                'file_name': zip_filename, # User will download the ZIP
                'file_type': 'ZIP', # For result template
                'file_size': os.path.getsize(zip_path)
            }
            return render(request, 'converter/result.html', context)

        except Exception as e:
            print(f"Error splitting PDF: {e}")
            context = {
                'error': f"เกิดข้อผิดพลาดในการแยกไฟล์: {str(e)}",
                'title': 'แยกไฟล์ PDF'
            }
            return render(request, 'converter/tool_base.html', context)

class PDFToWordView(View):
    def get(self, request):
        context = {
            'title': 'PDF เป็น Word',
            'subtitle': 'แปลงไฟล์ PDF ของคุณเป็นเอกสาร DOC และ DOCX ที่แก้ไขได้ง่ายอย่างง่ายดาย',
            'action_url': reverse('converter:pdf_to_word'),
            'file_type': 'PDF',
            'accept': '.pdf'
        }
        return render(request, 'converter/tool_base.html', context)

    def post(self, request):
        import os  # Force local import
        from django.conf import settings # Force local import of settings
        
        print("DEBUG: PDFToWordView POST Request Received!")
        try:
            # Handle multiple files input (name="files") from template
            if 'files' in request.FILES:
                uploaded_file = request.FILES.getlist('files')[0] # Process first file
            elif 'file' in request.FILES:
                uploaded_file = request.FILES['file']
            else:
                return redirect('converter:index')
            
            # Save uploaded file
            upload_instance = UploadedFile(file=uploaded_file)
            upload_instance.save()
            
            input_path = upload_instance.file.path
            input_filename = os.path.basename(input_path)
            filename_only = os.path.splitext(input_filename)[0]
            output_filename = f"{filename_only}.docx"
            
            # Define output path
            job_id = str(uuid.uuid4())
            output_dir = os.path.join(settings.MEDIA_ROOT, 'processed', job_id)
            os.makedirs(output_dir, exist_ok=True)
            output_full_path = os.path.join(output_dir, output_filename)
            
            # Import V2 API Service
            from utils.cc_v2_api import CloudConvertService
            print(f"Starting PDF -> Word (API V2) for {input_filename}")
            
            converter = CloudConvertService()
            converter.convert(
                input_file_path=input_path, 
                output_format='docx',
                export_path=output_full_path
            )
            
            # Create ProcessedFile record (Model only has 'file' field)
            processed_rel_path = f"processed/{job_id}/{output_filename}"
            processed_file = ProcessedFile(
                file=processed_rel_path
            )
            processed_file.save()
            
            # Track Usage
            try:
                stat, _ = DailyStat.objects.get_or_create(date=timezone.now().date())
                stat.usage_count += 1
                stat.save()
            except Exception as e:
                print(f"Stats error: {e}")
            
            # Ensure URL points to our custom download view (Bypass Media URL issues)
            # Use only job_id to avoid URL encoding issues with Thai filenames
            from django.urls import reverse
            download_url = reverse('converter:download_file', kwargs={'job_id': job_id})
            
            context = {
                'success': True,
                'file_url': download_url,
                'file_name': output_filename,
                'file_size': os.path.getsize(output_full_path) if os.path.exists(output_full_path) else 0
            }
            return render(request, 'converter/result.html', context)

        except Exception as e:
            print(f"Error converting PDF to Word: {e}")
            context = {
                'error': f"เกิดข้อผิดพลาด (V.Fixed): {str(e)}",
                'title': 'PDF เป็น Word'
            }
            return render(request, 'converter/tool_base.html', context)

# Direct File Download View (ID Only - Logic resolves filename)
from django.http import FileResponse, Http404
def download_file(request, job_id):
    import os
    from django.conf import settings
    
    # Path to the job directory
    job_dir = os.path.join(settings.MEDIA_ROOT, 'processed', job_id)
    
    print(f"DEBUG: Attempting download for Job ID: {job_id}")
    
    if os.path.exists(job_dir) and os.path.isdir(job_dir):
        # Find the first file in this directory
        files = os.listdir(job_dir)
        if files:
            filename = files[0]
            file_path = os.path.join(job_dir, filename)
            print(f"DEBUG: Found file: {file_path}")
            
            try:
                response = FileResponse(open(file_path, 'rb'), as_attachment=True)
                # Manually set filename header to handle UTF-8 correctly
                from urllib.parse import quote
                response['Content-Disposition'] = f"attachment; filename*=UTF-8''{quote(filename)}"
                return response
            except Exception as e:
                print(f"DEBUG: Error opening file: {e}")
                raise Http404(f"Error reading file: {e}")
                
    print("DEBUG: File or directory not found")
    raise Http404("File not found on server")

class CompressPDFView(View):
    def get(self, request):
        context = {
            'title': 'บีบอัด PDF',
            'subtitle': 'ลดขนาดไฟล์ PDF ของคุณโดยไม่สูญเสียคุณภาพมากเกินไป',
            'action_url': '',
            'file_type': 'PDF',
            'accept': '.pdf',
            'show_quality_options': True  # Flag to show compression quality options
        }
        return render(request, 'converter/compress_pdf.html', context)

    def post(self, request):
        files = request.FILES.getlist('files')
        if not files:
            return redirect('converter:compress_pdf')
        
        # Get compression quality from form
        quality = request.POST.get('quality', 'medium')
        
        uploaded_file = UploadedFile.objects.create(
            file=files[0],
            original_filename=files[0].name
        )
        
        try:
            import fitz  # PyMuPDF
            import os
            from django.conf import settings
            
            input_path = uploaded_file.file.path
            output_filename = f"compressed_{uuid.uuid4()}.pdf"
            output_rel_path = f"processed/{output_filename}"
            output_full_path = os.path.join(settings.MEDIA_ROOT, 'processed', output_filename)
            
            os.makedirs(os.path.dirname(output_full_path), exist_ok=True)
            
            # Quality settings for PyMuPDF
            quality_settings = {
                'low': {'deflate': 9, 'garbage': 4, 'image_quality': 50},
                'medium': {'deflate': 5, 'garbage': 3, 'image_quality': 75},
                'high': {'deflate': 3, 'garbage': 2, 'image_quality': 90},
                'maximum': {'deflate': 1, 'garbage': 1, 'image_quality': 95}
            }
            
            settings_dict = quality_settings.get(quality, quality_settings['medium'])
            
            # Open PDF
            doc = fitz.open(input_path)
            
            # Compress images in PDF
            for page_num in range(len(doc)):
                page = doc[page_num]
                
                # Get all images on the page
                image_list = page.get_images()
                
                for img_index, img in enumerate(image_list):
                    xref = img[0]
                    
                    # Extract image
                    try:
                        base_image = doc.extract_image(xref)
                        image_bytes = base_image["image"]
                        
                        # Compress image using PIL
                        from PIL import Image
                        import io
                        
                        # Open image
                        pil_image = Image.open(io.BytesIO(image_bytes))
                        
                        # Convert to RGB if necessary
                        if pil_image.mode in ('RGBA', 'LA', 'P'):
                            pil_image = pil_image.convert('RGB')
                        
                        # Compress
                        output_buffer = io.BytesIO()
                        pil_image.save(
                            output_buffer,
                            format='JPEG',
                            quality=settings_dict['image_quality'],
                            optimize=True
                        )
                        
                        # Replace image in PDF
                        compressed_image = output_buffer.getvalue()
                        
                        # Update image in PDF
                        doc._deleteObject(xref)
                        
                    except Exception as e:
                        print(f"Error compressing image {img_index} on page {page_num}: {e}")
                        continue
            
            # Save with compression
            doc.save(
                output_full_path,
                garbage=settings_dict['garbage'],
                deflate=True,
                clean=True
            )
            doc.close()
            
            # Get file sizes for comparison
            original_size = os.path.getsize(input_path)
            compressed_size = os.path.getsize(output_full_path)
            
            if compressed_size >= original_size:
                # If compressed file is larger, use original
                import shutil
                shutil.copy(input_path, output_full_path)
                compressed_size = original_size
                reduction_percent = 0
            else:
                reduction_percent = ((original_size - compressed_size) / original_size) * 100
            
            print(f"Original size: {original_size} bytes")
            print(f"Compressed size: {compressed_size} bytes")
            print(f"Reduction: {reduction_percent:.1f}%")
            
            # Save processed file
            processed_file = ProcessedFile(file=output_rel_path)
            processed_file.save()
            
            return render(request, 'converter/result.html', {
                'download_url': processed_file.file.url,
                'file_id': processed_file.id,
                'file_type': 'PDF',
                'original_size': original_size,
                'compressed_size': compressed_size,
                'reduction_percent': f"{reduction_percent:.1f}"
            })
            
        except Exception as e:
            print(f"PDF compression error: {e}")
            import traceback
            traceback.print_exc()
            from django.contrib import messages
            messages.error(request, f'เกิดข้อผิดพลาดในการบีบอัดไฟล์: {str(e)}')
            return redirect('converter:compress_pdf')


class PDFToPowerPointView(View):
    def get(self, request):
        from django.urls import reverse
        context = {
            'title': 'PDF เป็น PowerPoint',
            'subtitle': 'เปลี่ยนไฟล์ PDF ของคุณเป็นสไลด์โชว์ PPT และ PPTX ที่แก้ไขได้ง่าย',
            'action_url': reverse('converter:pdf_to_powerpoint'),
            'file_type': 'PDF',
            'accept': '.pdf'
        }
        return render(request, 'converter/tool_base.html', context)

    def post(self, request):
        import os
        from django.conf import settings
        import uuid
        from utils.cc_v2_api import CloudConvertService
        from django.urls import reverse
        
        try:
            if 'files' in request.FILES:
                uploaded_file = request.FILES.getlist('files')[0]
            elif 'file' in request.FILES:
                uploaded_file = request.FILES['file']
            else:
                return redirect('converter:index')
            
            upload_instance = UploadedFile(file=uploaded_file)
            upload_instance.save()
            input_path = upload_instance.file.path
            
            job_id = str(uuid.uuid4())
            output_dir = os.path.join(settings.MEDIA_ROOT, 'processed', job_id)
            os.makedirs(output_dir, exist_ok=True)
            
            input_filename = os.path.basename(input_path)
            filename_only = os.path.splitext(input_filename)[0]
            output_filename = f"{filename_only}.pptx"
            output_full_path = os.path.join(output_dir, output_filename)
            
            # Use CloudConvert
            converter = CloudConvertService()
            converter.convert(
                input_file_path=input_path, 
                output_format='pptx',
                export_path=output_full_path
            )
            
            processed_rel_path = f"processed/{job_id}/{output_filename}"
            processed_file = ProcessedFile(file=processed_rel_path)
            processed_file.save()
            
            # Track Usage
            try:
                stat, _ = DailyStat.objects.get_or_create(date=timezone.now().date())
                stat.usage_count += 1
                stat.save()
            except Exception as e:
                print(f"Stats error: {e}")
            
            download_url = reverse('converter:download_file', kwargs={'job_id': job_id})
            
            context = {
                'success': True,
                'file_url': download_url,
                'file_name': output_filename,
                'file_type': 'PPTX',
                'file_size': os.path.getsize(output_full_path) if os.path.exists(output_full_path) else 0
            }
            return render(request, 'converter/result.html', context)

        except Exception as e:
            print(f"Error converting PDF to PPTX: {e}")
            context = {
                'error': f"เกิดข้อผิดพลาด: {str(e)}",
                'title': 'PDF เป็น PowerPoint'
            }
            return render(request, 'converter/tool_base.html', context)


class PDFToExcelView(View):
    def get(self, request):
        from django.urls import reverse
        context = {
            'title': 'PDF เป็น Excel',
            'subtitle': 'ดึงข้อมูลโดยตรงจาก PDF ลงในสเปรดชีต Excel ในเวลาไม่กี่วินาที',
            'action_url': reverse('converter:pdf_to_excel'),
            'file_type': 'PDF',
            'accept': '.pdf'
        }
        return render(request, 'converter/tool_base.html', context)

    def post(self, request):
        import os
        from django.conf import settings
        import uuid
        from utils.cc_v2_api import CloudConvertService
        from django.urls import reverse
        
        try:
            if 'files' in request.FILES:
                uploaded_file = request.FILES.getlist('files')[0]
            elif 'file' in request.FILES:
                uploaded_file = request.FILES['file']
            else:
                return redirect('converter:index')
            
            upload_instance = UploadedFile(file=uploaded_file)
            upload_instance.save()
            input_path = upload_instance.file.path
            
            job_id = str(uuid.uuid4())
            output_dir = os.path.join(settings.MEDIA_ROOT, 'processed', job_id)
            os.makedirs(output_dir, exist_ok=True)
            
            input_filename = os.path.basename(input_path)
            filename_only = os.path.splitext(input_filename)[0]
            output_filename = f"{filename_only}.xlsx"
            output_full_path = os.path.join(output_dir, output_filename)
            
            # Use CloudConvert
            converter = CloudConvertService()
            converter.convert(
                input_file_path=input_path, 
                output_format='xlsx',
                export_path=output_full_path
            )
            
            processed_rel_path = f"processed/{job_id}/{output_filename}"
            processed_file = ProcessedFile(file=processed_rel_path)
            processed_file.save()
            
            # Track Usage
            try:
                stat, _ = DailyStat.objects.get_or_create(date=timezone.now().date())
                stat.usage_count += 1
                stat.save()
            except Exception as e:
                print(f"Stats error: {e}")
            
            download_url = reverse('converter:download_file', kwargs={'job_id': job_id})
            
            context = {
                'success': True,
                'file_url': download_url,
                'file_name': output_filename,
                'file_type': 'XLSX',
                'file_size': os.path.getsize(output_full_path) if os.path.exists(output_full_path) else 0
            }
            return render(request, 'converter/result.html', context)

        except Exception as e:
            print(f"Error converting PDF to Excel: {e}")
            context = {
                'error': f"เกิดข้อผิดพลาด: {str(e)}",
                'title': 'PDF เป็น Excel'
            }
            return render(request, 'converter/tool_base.html', context)


class PowerPointToPDFView(View):
    def get(self, request):
        from django.urls import reverse
        context = {
            'title': 'PowerPoint เป็น PDF',
            'subtitle': 'ทำให้สไลด์โชว์ PPT และ PPTX ดูง่ายโดยการแปลงเป็น PDF',
            'action_url': reverse('converter:powerpoint_to_pdf'),
            'file_type': 'POWERPOINT',
            'accept': '.ppt,.pptx'
        }
        return render(request, 'converter/tool_base.html', context)

    def post(self, request):
        import os
        from django.conf import settings
        import uuid
        from utils.cc_v2_api import CloudConvertService
        from django.urls import reverse
        
        try:
            if 'files' in request.FILES:
                uploaded_file = request.FILES.getlist('files')[0]
            elif 'file' in request.FILES:
                uploaded_file = request.FILES['file']
            else:
                return redirect('converter:index')
            
            upload_instance = UploadedFile(file=uploaded_file)
            upload_instance.save()
            input_path = upload_instance.file.path
            
            job_id = str(uuid.uuid4())
            output_dir = os.path.join(settings.MEDIA_ROOT, 'processed', job_id)
            os.makedirs(output_dir, exist_ok=True)
            
            input_filename = os.path.basename(input_path)
            filename_only = os.path.splitext(input_filename)[0]
            output_filename = f"{filename_only}.pdf"
            output_full_path = os.path.join(output_dir, output_filename)
            
            # Use CloudConvert
            converter = CloudConvertService()
            converter.convert(
                input_file_path=input_path, 
                output_format='pdf',
                export_path=output_full_path
            )
            
            processed_rel_path = f"processed/{job_id}/{output_filename}"
            processed_file = ProcessedFile(file=processed_rel_path)
            processed_file.save()
            
            # Track Usage
            try:
                stat, _ = DailyStat.objects.get_or_create(date=timezone.now().date())
                stat.usage_count += 1
                stat.save()
            except Exception as e:
                print(f"Stats error: {e}")
            
            download_url = reverse('converter:download_file', kwargs={'job_id': job_id})
            
            context = {
                'success': True,
                'file_url': download_url,
                'file_name': output_filename,
                'file_type': 'PDF',
                'file_size': os.path.getsize(output_full_path) if os.path.exists(output_full_path) else 0
            }
            return render(request, 'converter/result.html', context)

        except Exception as e:
            print(f"Error converting PPTX to PDF: {e}")
            context = {
                'error': f"เกิดข้อผิดพลาด: {str(e)}",
                'title': 'PowerPoint เป็น PDF'
            }
            return render(request, 'converter/tool_base.html', context)


class WordToPDFView(View):
    def get(self, request):
        context = {
            'title': 'Word เป็น PDF',
            'subtitle': 'ทำให้ไฟล์ DOC และ DOCX อ่านง่ายโดยการแปลงเป็น PDF',
            'action_url': '',
            'file_type': 'WORD',
            'accept': '.doc,.docx'
        }
        return render(request, 'converter/tool_base.html', context)

    def post(self, request):
        files = request.FILES.getlist('files')
        if not files:
            return redirect('converter:word_to_pdf')
        
        # For now, convert only the first file
        uploaded_file = UploadedFile.objects.create(
            file=files[0],
            original_filename=files[0].name
        )
        
        try:
            import subprocess
            import os
            from django.conf import settings
            
            input_path = uploaded_file.file.path
            output_filename = f"converted_{uuid.uuid4()}.pdf"
            output_rel_path = f"processed/{output_filename}"
            output_full_path = os.path.join(settings.MEDIA_ROOT, 'processed', output_filename)
            
            os.makedirs(os.path.dirname(output_full_path), exist_ok=True)
            
            # V.2 Strategy: Try CloudConvert API first (Direct REST Mode)
            try:
                # The service now uses 'requests' internally, so no external SDK import error.
                print("DEBUG: Importing CloudConvertService (New)...")
                from utils.cc_v2_api import CloudConvertService
                print(f"DEBUG: Service Imported successfully: {CloudConvertService}")
                print("Attempting CloudConvert V2 API (Direct REST)...")
                
                converter = CloudConvertService()
                converter.convert(
                    input_file_path=input_path, 
                    output_format='pdf',
                    export_path=output_full_path
                )
                print("CloudConvert V2 API Success!")
                
            except Exception as api_error:
                print(f"CloudConvert API Failed: {api_error}")
                
                # Check if we are on a Linux server (Production) where LibreOffice is likely missing
                # If so, show the API error directly instead of misleading "LibreOffice not found"
                is_linux = sys.platform != 'win32'
                if is_linux:
                     raise Exception(f"CloudConvert Error: {str(api_error)}")

                print("Falling back to local LibreOffice Engine...")
                
                # ... Fallback to original LibreOffice logic (Only for Local/Windows) ...
                
                # Try to find LibreOffice installation (Windows and Linux)
                libreoffice_paths = [
                    # Windows paths
                    r"C:\Program Files\LibreOffice\program\soffice.exe",
                    r"C:\Program Files (x86)\LibreOffice\program\soffice.exe",
                    r"C:\Program Files\LibreOffice 7\program\soffice.exe",
                    r"C:\Program Files\LibreOffice 24\program\soffice.exe",
                    r"C:\Program Files\LibreOffice 25\program\soffice.exe",
                    # Linux paths (for production server)
                    "/usr/bin/libreoffice",
                    "/usr/bin/soffice",
                    "/snap/bin/libreoffice",
                    "/opt/libreoffice/program/soffice",
                ]
                
                soffice_path = None
                for path in libreoffice_paths:
                    if os.path.exists(path):
                        soffice_path = path
                        print(f"Found LibreOffice at: {path}")
                        break
                
                if not soffice_path:
                    # Try using 'which' command on Linux
                    try:
                        result = subprocess.run(['which', 'libreoffice'], capture_output=True, text=True)
                        if result.returncode == 0 and result.stdout.strip():
                            soffice_path = result.stdout.strip()
                            print(f"Found LibreOffice via 'which': {soffice_path}")
                    except:
                        pass
                
                if not soffice_path:
                    # Reraise the original API error if local engine also fails
                    raise Exception(f"CloudConvert failed ({str(api_error)}) and LibreOffice not found.")
                
                # Convert using LibreOffice
                cmd = [
                    soffice_path,
                    '--headless',
                    '--convert-to',
                    'pdf',
                    '--outdir',
                    os.path.dirname(output_full_path),
                    input_path
                ]
                
                print(f"Running LibreOffice command: {' '.join(cmd)}")
                result = subprocess.run(cmd, capture_output=True, text=True, timeout=120)
                
                if result.returncode != 0:
                    raise Exception(f"LibreOffice conversion failed: {result.stderr}")
                
                # LibreOffice creates file with same name but .pdf extension
                temp_output = os.path.join(
                    os.path.dirname(output_full_path),
                    os.path.splitext(os.path.basename(input_path))[0] + '.pdf'
                )
                
                # Rename to our desired filename
                if os.path.exists(temp_output):
                    # Remove placeholder if created by touch or previous run
                    if os.path.exists(output_full_path):
                        os.remove(output_full_path) 
                    os.rename(temp_output, output_full_path)
                else:
                    raise Exception("Converted PDF file not found")
            
            # Save processed file
            processed_file = ProcessedFile(file=output_rel_path)
            processed_file.save()
            
            return render(request, 'converter/result.html', {
                'download_url': processed_file.file.url,
                'file_id': processed_file.id,
                'file_type': 'PDF'
            })
            
        except subprocess.TimeoutExpired:
            print("LibreOffice conversion timeout")
            from django.contrib import messages
            messages.error(request, 'การแปลงไฟล์ใช้เวลานานเกินไป กรุณาลองใหม่อีกครั้ง')
            return redirect('converter:word_to_pdf')
        except Exception as e:
            print(f"Word to PDF conversion error: {e}")
            import traceback
            traceback.print_exc()
            from django.contrib import messages
            messages.error(request, f'เกิดข้อผิดพลาดในการแปลงไฟล์: {str(e)}')
            return redirect('converter:word_to_pdf')

class MergeWordView(View):
    def get(self, request):
        context = {
            'title': 'รวมไฟล์ Word',
            'subtitle': 'รวมไฟล์ Docs และ Docx หลายไฟล์ให้เป็นไฟล์ Word เดียว',
            'action_url': '',
            'file_type': 'WORD',
            'accept': '.doc,.docx'
        }
        return render(request, 'converter/tool_base.html', context)

    def post(self, request):
        files = request.FILES.getlist('files')
        if files:
            uploaded_ids = []
            for f in files:
                uploaded_file = UploadedFile.objects.create(
                    file=f,
                    original_filename=f.name
                )
                uploaded_ids.append(str(uploaded_file.id))
            
            # Save IDs to session to use in Arrange Page
            if request.GET.get('append') == 'true':
                 existing_ids = request.session.get('merge_word_file_ids', [])
                 existing_ids.extend(uploaded_ids)
                 request.session['merge_word_file_ids'] = existing_ids
                 request.session.modified = True
            else:
                 request.session['merge_word_file_ids'] = uploaded_ids
                 request.session.modified = True
            
            return redirect('converter:arrange_word')
            
        return redirect('converter:merge_word')

class ArrangeWordView(View):
    def get(self, request):
        file_ids = request.session.get('merge_word_file_ids', [])
        if not file_ids:
            return redirect('converter:merge_word')
        
        # Preserve order? No, user will order them here.
        # But we need to fetch objects.
        files = UploadedFile.objects.filter(id__in=file_ids)
        
        # Sort files by the order they were in the list if possible, 
        # but filter() querysets are not ordered by input list.
        # We can re-sort them in python to match input order if desired, 
        # or just list them.
        files_dict = {str(f.id): f for f in files}
        ordered_files = [files_dict[fid] for fid in file_ids if fid in files_dict]

        # Extract preview text for each file
        from docx import Document
        for f in ordered_files:
            try:
                doc = Document(f.file.path)
                full_text = []
                for para in doc.paragraphs:
                    if para.text.strip():
                        full_text.append(para.text.strip())
                    if len(full_text) >= 5: # Get first 5 paragraphs
                        break
                f.preview_text = full_text
            except Exception as e:
                print(f"Error reading doc {f.id} path {f.file.path}: {e}")
                f.preview_text = []

        print(f"DEBUG: Processed {len(ordered_files)} files.")
        
        # Define alignment mapping
        # 0=LEFT, 1=CENTER, 2=RIGHT, 3=JUSTIFY
        # Note: python-docx alignment might be None if inherited.
        
        for f in ordered_files:
             # Pre-calculate display name
             f.display_name = f.original_filename if f.original_filename else f.filename
             
             # Enhanced Preview Extraction
             try:
                 doc = Document(f.file.path)
                 preview_data = []
                 for para in doc.paragraphs:
                     text = para.text.strip()
                     if text:
                         # Determine Alignment
                         align = 'left'
                         try:
                             # WD_ALIGN_PARAGRAPH.CENTER is 1
                             if para.alignment == 1:
                                 align = 'center'
                             elif para.alignment == 2:
                                 align = 'right'
                         except:
                             pass
                         
                         # Determine Style (Basic)
                         is_bold = False
                         style_name = para.style.name.lower()
                         if 'title' in style_name or 'heading' in style_name:
                             is_bold = True
                             
                         preview_data.append({
                             'text': text,
                             'align': align,
                             'bold': is_bold
                         })
                         
                     if len(preview_data) >= 6:
                         break
                 f.preview_data = preview_data
             except Exception as e:
                 print(f"Preview error for {f.id}: {e}")
                 f.preview_data = []
                 
             print(f"File {f.id} preview lines: {len(getattr(f, 'preview_data', []))}")

        context = {
            'files': ordered_files
        }
        return render(request, 'converter/arrange_word.html', context)

    def post(self, request):
        ordered_ids = request.POST.getlist('file_order[]')
        
        if not ordered_ids:
            return redirect('converter:arrange_word')

        files = []
        try:
            for fid in ordered_ids:
                files.append(UploadedFile.objects.get(id=fid))
        except UploadedFile.DoesNotExist:
            # Handle error
            return redirect('converter:merge_word')

        # Perform Merge
        service = WordService()
        
        # Prepare file paths
        import os
        from django.conf import settings
        
        file_paths = [f.file.path for f in files]
        
        # Output filename
        output_filename = f"merged_{uuid.uuid4()}.docx"
        output_rel_path = f"processed/{output_filename}"
        output_full_path = os.path.join(settings.MEDIA_ROOT, 'processed', output_filename)
        
        # Ensure processed dir exists
        os.makedirs(os.path.dirname(output_full_path), exist_ok=True)
        
        try:
            service.merge_word_files(file_paths, output_full_path)
            
            # Create ProcessedFile entry
            # Django FileField expects a path relative to MEDIA_ROOT or a File object.
            # Since we wrote directly to filesystem, we can just save the relative path string
            # IF upload_to handles it, but FileField usually needs a File wrapper to "save".
            # However, for an existing file, we can manually set the name attribute.
            
            processed_file = ProcessedFile(file=output_rel_path)
            processed_file.save()
            
            # Cleanup session
            if 'merge_word_file_ids' in request.session:
                del request.session['merge_word_file_ids']
                
            # Redirect to result with success
            # You might want to pass the download link or ID
            return render(request, 'converter/result.html', {
                'download_url': processed_file.file.url,
                'file_id': processed_file.id,
                'file_type': 'WORD'
            })
            
        except Exception as e:
            print(f"Merge Error: {e}")
            # In production show a user friendly error
            return redirect('converter:merge_word')

class ArrangePDFView(View):
    def get(self, request):
        file_ids = request.session.get('merge_pdf_file_ids', [])
        if not file_ids:
            return redirect('converter:merge_pdf')
        
        files = UploadedFile.objects.filter(id__in=file_ids)
        files_dict = {str(f.id): f for f in files}
        ordered_files = [files_dict[fid] for fid in file_ids if fid in files_dict]

        # Extract preview text for PDF
        import PyPDF2
        for f in ordered_files:
            f.display_name = f.original_filename if f.original_filename else f.filename
            try:
                reader = PyPDF2.PdfReader(f.file.path)
                if len(reader.pages) > 0:
                    page = reader.pages[0]
                    text = page.extract_text()
                    lines = [l.strip() for l in text.split('\n') if l.strip()]
                    f.preview_data = [{'text': line, 'align': 'left', 'bold': False} for line in lines[:6]]
                else:
                    f.preview_data = []
            except Exception as e:
                print(f"PDF Preview error {f.id}: {e}")
                f.preview_data = []

        context = {
            'files': ordered_files
        }
        return render(request, 'converter/arrange_pdf.html', context)

    def post(self, request):
        ordered_ids = request.POST.getlist('file_order[]')
        
        if not ordered_ids:
            return redirect('converter:arrange_pdf')

        files = []
        try:
            for fid in ordered_ids:
                files.append(UploadedFile.objects.get(id=fid))
        except UploadedFile.DoesNotExist:
            return redirect('converter:merge_pdf')

        # Perform PDF Merge
        from PyPDF2 import PdfMerger
        import os
        from django.conf import settings
        
        file_paths = [f.file.path for f in files]
        
        output_filename = f"merged_{uuid.uuid4()}.pdf"
        output_rel_path = f"processed/{output_filename}"
        output_full_path = os.path.join(settings.MEDIA_ROOT, 'processed', output_filename)
        os.makedirs(os.path.dirname(output_full_path), exist_ok=True)
        
        try:
            merger = PdfMerger()
            for path in file_paths:
                merger.append(path)
            merger.write(output_full_path)
            merger.close()
            
            processed_file = ProcessedFile(file=output_rel_path)
            processed_file.save()
            
            if 'merge_pdf_file_ids' in request.session:
                del request.session['merge_pdf_file_ids']
                
            return render(request, 'converter/result.html', {
                'download_url': processed_file.file.url,
                'file_id': processed_file.id,
                'file_type': 'PDF'
            })
            
        except Exception as e:
            print(f"PDF Merge Error: {e}")
            return redirect('converter:merge_pdf')

class ResultView(View):
    def get(self, request):
        return render(request, 'converter/result.html')

from django.http import FileResponse, Http404
class DownloadFileView(View):
    def get(self, request, file_id):
        try:
            processed_file = ProcessedFile.objects.get(id=file_id)
            file_path = processed_file.file.path
            
            # Get custom filename from query params
            filename = request.GET.get('filename', '')
            if not filename:
                filename = processed_file.file.name.split('/')[-1]
            
            # Detect extension from original file
            original_ext = '.' + processed_file.file.name.split('.')[-1].lower()
            if not filename.lower().endswith(original_ext):
                # If user didn't type extension, append it. 
                # If user typed WRONG extension, maybe we should fix it? 
                # For now, just ensure it ends with the real extension to allow opening.
                # But wait, logic above was forcing .docx.
                # Let's align with the actual file type.
                if original_ext in ['.doc', '.docx']:
                     if not filename.lower().endswith('.docx') and not filename.lower().endswith('.doc'):
                          filename += '.docx'
                elif original_ext == '.pdf':
                     if not filename.lower().endswith('.pdf'):
                          filename += '.pdf'
                
            response = FileResponse(open(file_path, 'rb'), as_attachment=True, filename=filename)
            return response
        except ProcessedFile.DoesNotExist:
            raise Http404("File not found")
        except Exception as e:
            print(f"Download error: {e}")
            raise Http404("File processing error")


class TermsView(View):
    def get(self, request):
        return render(request, 'converter/terms.html', {
            'title': 'เงื่อนไขการใช้งาน',
            'subtitle': 'ข้อกำหนดและเงื่อนไขการใช้บริการของ MankyFile'
        })


class PrivacyView(View):
    def get(self, request):
        return render(request, 'converter/privacy.html', {
            'title': 'นโยบายความเป็นส่วนตัว',
            'subtitle': 'การคุ้มครองข้อมูลส่วนบุคคลและความเป็นส่วนตัวของผู้ใช้'
        })


class QRCodeGeneratorView(View):
    def get(self, request):
        from django.urls import reverse
        context = {
            'title': 'สร้าง QR Code',
            'subtitle': 'สร้าง QR Code ฟรีจากลิงก์ ข้อความ หรือเบอร์โทรศัพท์ พร้อมปรับแต่งสีได้ตามใจชอบ',
            'action_url': reverse('converter:qrcode_generator'),
        }
        return render(request, 'converter/qrcode_tool.html', context)

    def post(self, request):
        import qrcode
        from PIL import Image
        import os
        from django.conf import settings
        import uuid
        from django.urls import reverse
        from .models import UploadedFile, ProcessedFile, DailyStat
        from django.utils import timezone
        
        try:
            # 1. Get QR Type and Construct Data
            qr_type = request.POST.get('qr_type', 'url')
            data = ""
            
            if qr_type == 'url':
                data = request.POST.get('content_url', '').strip()
                if not data.startswith('http'): data = 'https://' + data
            
            elif qr_type == 'text':
                data = request.POST.get('content_text', '').strip()
                
            elif qr_type == 'wifi':
                ssid = request.POST.get('wifi_ssid', '').strip()
                password = request.POST.get('wifi_password', '').strip()
                encryption = request.POST.get('wifi_encryption', 'WPA')
                hidden = request.POST.get('wifi_hidden', 'false')
                # Format: WIFI:S:MySSID;T:WPA;P:MyPass;H:false;;
                data = f"WIFI:S:{ssid};T:{encryption};P:{password};H:{hidden};;"
            
            elif qr_type == 'email':
                email = request.POST.get('email_address', '').strip()
                subject = request.POST.get('email_subject', '').strip()
                body = request.POST.get('email_body', '').strip()
                data = f"mailto:{email}?subject={subject}&body={body}"
                
            elif qr_type == 'tel':
                phone = request.POST.get('tel_number', '').strip()
                data = f"tel:{phone}"
                
            elif qr_type == 'sms':
                phone = request.POST.get('sms_number', '').strip()
                message = request.POST.get('sms_message', '').strip()
                data = f"SMSTO:{phone}:{message}"
                
            elif qr_type == 'whatsapp':
                phone = request.POST.get('whatsapp_number', '').strip()
                message = request.POST.get('whatsapp_message', '').strip()
                # Clean phone number
                phone = ''.join(filter(str.isdigit, phone))
                data = f"https://wa.me/{phone}?text={message}"

            # Validate Data
            if not data:
                # ถ้าข้อมูลไม่ครบ ให้เรนเดอร์กลับไปพร้อม Error
                context = {
                    'title': 'สร้าง QR Code',
                    'error': 'กรุณากรอกข้อมูลให้ครบถ้วน',
                    'action_url': reverse('converter:qrcode_generator'),
                }
                return render(request, 'converter/qrcode_tool.html', context)
                
            # 2. Get Design Options
            fill_color = request.POST.get('fill_color', '#000000')
            back_color = request.POST.get('back_color', '#ffffff')
            
            # 3. Generate QR Code
            qr = qrcode.QRCode(
                version=1,
                error_correction=qrcode.constants.ERROR_CORRECT_H,
                box_size=20, # Higher quality
                border=2,
            )
            qr.add_data(data)
            qr.make(fit=True)
            
            # Create Image with colors
            try:
                img = qr.make_image(fill_color=fill_color, back_color=back_color).convert('RGB')
            except ValueError:
                # Fallback if color is invalid
                img = qr.make_image(fill_color="black", back_color="white").convert('RGB')

            # --- LOGO PROCESSING START ---
            logo_file = request.FILES.get('logo')
            if logo_file:
                try:
                    logo = Image.open(logo_file)
                    
                    # Calculate dimensions
                    # Logo width should be about 20% of QR code width for readability
                    basewidth = int(img.size[0] * 0.25)
                    wpercent = (basewidth / float(logo.size[0]))
                    hsize = int((float(logo.size[1]) * float(wpercent)))
                    
                    # Resize logo
                    logo = logo.resize((basewidth, hsize), Image.Resampling.LANCZOS)
                    
                    # Calculate center position
                    pos = ((img.size[0] - logo.size[0]) // 2, (img.size[1] - logo.size[1]) // 2)
                    
                    # Paste logo
                    # If logo has transparency, use it as mask
                    if logo.mode == 'RGBA':
                        img.paste(logo, pos, logo)
                    else:
                        img.paste(logo, pos)
                        
                except Exception as e:
                    print(f"Logo processing error: {e}")
                    # Continue without logo if error
            # --- LOGO PROCESSING END ---
            
            # 4. Save file
            job_id = str(uuid.uuid4())
            output_dir = os.path.join(settings.MEDIA_ROOT, 'processed', job_id)
            os.makedirs(output_dir, exist_ok=True)
            
            output_filename = f"qr_{qr_type}_{job_id[:8]}.png"
            output_full_path = os.path.join(output_dir, output_filename)
            
            img.save(output_full_path, format="PNG")
            
            # Save Record
            processed_rel_path = f"processed/{job_id}/{output_filename}"
            processed_file = ProcessedFile(file=processed_rel_path)
            processed_file.save()
            
            # Track Usage
            try:
                stat, _ = DailyStat.objects.get_or_create(date=timezone.now().date())
                stat.usage_count += 1
                stat.save()
            except Exception as e:
                print(f"Stats error: {e}")
            
            # 5. Prepare Context for User
            # สร้าง URL สำหรับแสดงผล (Media URL) และ Download URL
            qr_image_url = f"{settings.MEDIA_URL}{processed_rel_path}"
            download_url = reverse('converter:download_file', kwargs={'job_id': job_id})
            
            context = {
                'title': 'สร้าง QR Code',
                'subtitle': 'สร้าง QR Code ฟรีจากลิงก์ ข้อความ หรือเบอร์โทรศัพท์ พร้อมปรับแต่งสีได้ตามใจชอบ',
                'action_url': reverse('converter:qrcode_generator'),
                'generated': True,        # Flag to tell template to show image
                'qr_image_url': qr_image_url,
                'download_url': download_url,
                # ส่งค่าเดิมกลับไปด้วย เพื่อให้ Form ยังคงค่าเดิมไว้ (Optional)
                'fill_color': fill_color,
                'back_color': back_color,
            }
            return render(request, 'converter/qrcode_tool.html', context)
            
        except Exception as e:
            print(f"QR Error: {e}")
            import traceback
            traceback.print_exc()
            context = {
                'title': 'สร้าง QR Code',
                'error': f'เกิดข้อผิดพลาด: {str(e)}',
                'action_url': reverse('converter:qrcode_generator'),
            }
            return render(request, 'converter/qrcode_tool.html', context)
            
            context = {
                'success': True,
                'file_url': download_url,
                'file_name': output_filename,
                'file_type': 'PNG', 
                'file_size': os.path.getsize(output_full_path)
            }
            return render(request, 'converter/result.html', context)
            
        except Exception as e:
            print(f"QR Gen Error: {e}")
            # Re-render with error (need to pass previous inputs back ideally, but for now simple error)
            return render(request, 'converter/qrcode_tool.html', {
                'title': 'สร้าง QR Code', 
                'subtitle': 'สร้าง QR Code ฟรี ครบทุกรูปแบบ', 
                'error': f"เกิดข้อผิดพลาด: {str(e)}"
            })
