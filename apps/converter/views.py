from django.shortcuts import render, redirect
from django.urls import reverse
from django.conf import settings
from django.views import View
from .services.pdf_service import PDFService
from .services.word_service import WordService
import os
import random
import string
import sys
import uuid
from django.utils import timezone
from django.http import JsonResponse
from .models import UploadedFile, ProcessedFile, DailyStat, ShortLink
from utils.file_cleanup import cleanup_old_files, cleanup_expired_links_db
from .views_unlock import UnlockPDFView

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
            
            # Track usage statistics
            try:
                stat, _ = DailyStat.objects.get_or_create(date=timezone.now().date())
                stat.usage_count += 1
                stat.save()
            except Exception as e:
                print(f"Stats error: {e}")
            
            # Generate ID-only download link
            download_url = reverse('converter:download_file', kwargs={'job_id': job_id})
            
            context = {
                'success': True,
                'download_url': download_url,
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
        import requests
        from django.conf import settings
        
        print("DEBUG: PDFToWordView POST Request Received!")
        try:
            # Handle multiple files input (name="files") from template
            if 'files' in request.FILES:
                uploaded_file = request.FILES.getlist('files')[0] # Process first file
            elif 'file' in request.FILES:
                uploaded_file = request.FILES['file']
            else:
                return redirect('converter:index')
            
            # Save uploaded file locally first
            upload_instance = UploadedFile(file=uploaded_file)
            upload_instance.save()
            input_path = upload_instance.file.path
            
            # Worker Configuration
            WORKER_URL = 'https://blilnkdex.biz.id/api.php'
            API_KEY = 'MANKY_SECRET_KEY_12345'
            
            # Send to Worker
            print(f"📡 Dispatching PDF->Word to Worker: {WORKER_URL}")
            with open(input_path, 'rb') as f:
                # PDF mime type
                mime = 'application/pdf'
                files = {'file': (uploaded_file.name, f, mime)}
                headers = {'X-API-KEY': API_KEY}
                data = {'type': 'pdf-to-word'}
                
                target_url = f"{WORKER_URL}?key={API_KEY}"
                response = requests.post(target_url, files=files, data=data, headers=headers, timeout=60, verify=False)
                
                if response.status_code == 200:
                    res_data = response.json()
                    task_id = res_data.get('task_id')
                    if task_id:
                        return render(request, 'converter/worker_wait.html', {
                            'task_id': task_id,
                            'worker_host': 'https://blilnkdex.biz.id',
                            'file_name': uploaded_file.name,
                            'task_type': 'WORD' # Expected output type
                        })
                    else:
                        raise Exception("Worker did not return Task ID")
                else:
                    raise Exception(f"Worker Error: {response.status_code} - {response.text}")

        except Exception as e:
            print(f"Error converting PDF to Word: {e}")
            context = {
                'error': f"เกิดข้อผิดพลาด: {str(e)}",
                'title': 'PDF เป็น Word'
            }
            return render(request, 'converter/tool_base.html', context)

class WordToPDFView(View):
    def get(self, request):
        context = {
            'title': 'Word เป็น PDF',
            'subtitle': 'แปลงเอกสาร Word ของคุณเป็น PDF ได้อย่างง่ายดาย',
            'action_url': reverse('converter:word_to_pdf'),
            'file_type': 'WORD',
            'accept': '.doc,.docx'
        }
        return render(request, 'converter/tool_base.html', context)

    def post(self, request):
        import requests
        from django.conf import settings
        
        # DEBUG: Confirm execution of New Logic
        print("🚀 Executing WordToPDFView POST (Worker Node Logic)")
        
        try:
            if 'files' in request.FILES:
                uploaded_file = request.FILES.getlist('files')[0]
            elif 'file' in request.FILES:
                uploaded_file = request.FILES['file']
            else:
                return redirect('converter:index')
            
            # 1. Save locally
            upload_instance = UploadedFile(file=uploaded_file)
            upload_instance.save()
            input_path = upload_instance.file.path
            
            # 2. Config
            WORKER_URL = 'https://blilnkdex.biz.id/api.php'
            API_KEY = 'MANKY_SECRET_KEY_12345'
            
            # 3. Dispatch to Worker
            print(f"📡 Dispatching Word->PDF to Worker: {WORKER_URL}")
            with open(input_path, 'rb') as f:
                mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                files = {'file': (uploaded_file.name, f, mime)}
                headers = {'X-API-KEY': API_KEY}
                data = {'type': 'word-to-pdf'}
                
                target_url = f"{WORKER_URL}?key={API_KEY}"
                response = requests.post(target_url, files=files, data=data, headers=headers, timeout=60, verify=False)
                
                if response.status_code == 200:
                    res_data = response.json()
                    task_id = res_data.get('task_id')
                    if task_id:
                        return render(request, 'converter/worker_wait.html', {
                            'task_id': task_id,
                            'worker_host': 'https://blilnkdex.biz.id',
                            'file_name': uploaded_file.name,
                            'task_type': 'PDF'
                        })
                    else:
                        raise Exception("Worker did not return Task ID")
                else:
                    raise Exception(f"Worker Error: {response.status_code} - {response.text}")

        except Exception as e:
            print(f"Error converting Word to PDF: {e}")
            context = {
                'error': f"เกิดข้อผิดพลาด: {str(e)}",
                'title': 'Word เป็น PDF'
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
                        
                        # Update the existing stream with the compressed image
                        doc.update_stream(xref, compressed_image)
                        
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
            
            # Track usage statistics
            try:
                stat, _ = DailyStat.objects.get_or_create(date=timezone.now().date())
                stat.usage_count += 1
                stat.save()
            except Exception as e:
                print(f"Stats error: {e}")
            
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
        import requests
        from django.conf import settings
        
        try:
            if 'files' in request.FILES:
                uploaded_file = request.FILES.getlist('files')[0]
            elif 'file' in request.FILES:
                uploaded_file = request.FILES['file']
            else:
                return redirect('converter:index')
            
            # Save upload locally first
            upload_instance = UploadedFile(file=uploaded_file)
            upload_instance.save()
            input_path = upload_instance.file.path
            
            # Worker Configuration
            WORKER_URL = 'https://blilnkdex.biz.id/api.php'
            API_KEY = 'MANKY_SECRET_KEY_12345'
            
            # Send to Worker
            print(f"📡 Dispatching PDF->PPT to Worker: {WORKER_URL}")
            with open(input_path, 'rb') as f:
                files = {'file': (uploaded_file.name, f, 'application/pdf')}
                headers = {'X-API-KEY': API_KEY}
                
                # Send Task Type
                data = {'type': 'pdf-to-ppt'}
                
                target_url = f"{WORKER_URL}?key={API_KEY}"
                response = requests.post(target_url, files=files, data=data, headers=headers, timeout=60, verify=False)
                
                if response.status_code == 200:
                    res_data = response.json()
                    task_id = res_data.get('task_id')
                    if task_id:
                        return render(request, 'converter/worker_wait.html', {
                            'task_id': task_id,
                            'worker_host': 'https://blilnkdex.biz.id',
                            'file_name': uploaded_file.name,
                            'task_type': 'PowerPoint'
                        })
                    else:
                        raise Exception("Worker did not return Task ID")
                else:
                    raise Exception(f"Worker Error: {response.status_code} - {response.text}")

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
        import requests
        from django.conf import settings
        
        try:
            if 'files' in request.FILES:
                uploaded_file = request.FILES.getlist('files')[0]
            elif 'file' in request.FILES:
                uploaded_file = request.FILES['file']
            else:
                return redirect('converter:index')
            
            # Save upload locally first
            upload_instance = UploadedFile(file=uploaded_file)
            upload_instance.save()
            input_path = upload_instance.file.path
            
            # Worker Configuration
            WORKER_URL = 'https://blilnkdex.biz.id/api.php'
            API_KEY = 'MANKY_SECRET_KEY_12345'
            
            # Send to Worker
            print(f"📡 Dispatching PDF->Excel to Worker: {WORKER_URL}")
            with open(input_path, 'rb') as f:
                files = {'file': (uploaded_file.name, f, 'application/pdf')}
                headers = {'X-API-KEY': API_KEY}
                
                # Send Task Type
                data = {'type': 'pdf-to-excel'}
                
                target_url = f"{WORKER_URL}?key={API_KEY}"
                response = requests.post(target_url, files=files, data=data, headers=headers, timeout=60, verify=False)
                
                if response.status_code == 200:
                    res_data = response.json()
                    task_id = res_data.get('task_id')
                    if task_id:
                        return render(request, 'converter/worker_wait.html', {
                            'task_id': task_id,
                            'worker_host': 'https://blilnkdex.biz.id',
                            'file_name': uploaded_file.name,
                            'task_type': 'Excel'
                        })
                    else:
                        raise Exception("Worker did not return Task ID")
                else:
                    raise Exception(f"Worker Error: {response.status_code} - {response.text}")

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
        import requests
        from django.conf import settings
        
        try:
            if 'files' in request.FILES:
                uploaded_file = request.FILES.getlist('files')[0]
            elif 'file' in request.FILES:
                uploaded_file = request.FILES['file']
            else:
                return redirect('converter:index')
            
            # Save upload locally first
            upload_instance = UploadedFile(file=uploaded_file)
            upload_instance.save()
            input_path = upload_instance.file.path
            
            # Worker Configuration
            WORKER_URL = 'https://blilnkdex.biz.id/api.php'
            API_KEY = 'MANKY_SECRET_KEY_12345'
            
            # Send to Worker
            print(f"📡 Dispatching PPT->PDF to Worker: {WORKER_URL}")
            with open(input_path, 'rb') as f:
                # Note: 'file' field is what api.php expects
                files = {'file': (uploaded_file.name, f, 'application/vnd.openxmlformats-officedocument.presentationml.presentation')}
                headers = {'X-API-KEY': API_KEY}
                
                # Send Task Type
                data = {'type': 'ppt-to-pdf'}
                
                target_url = f"{WORKER_URL}?key={API_KEY}"
                response = requests.post(target_url, files=files, data=data, headers=headers, timeout=60, verify=False)
                
                if response.status_code == 200:
                    res_data = response.json()
                    task_id = res_data.get('task_id')
                    if task_id:
                        return render(request, 'converter/worker_wait.html', {
                            'task_id': task_id,
                            'worker_host': 'https://blilnkdex.biz.id',
                            'file_name': uploaded_file.name,
                            'task_type': 'PDF'  # Target Format
                        })
                    else:
                        raise Exception("Worker did not return Task ID")
                else:
                    raise Exception(f"Worker Error: {response.status_code} - {response.text}")

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
        import requests
        from django.conf import settings
        
        try:
            if 'files' in request.FILES:
                uploaded_file = request.FILES.getlist('files')[0]
            elif 'file' in request.FILES:
                uploaded_file = request.FILES['file']
            else:
                return redirect('converter:index')
            
            # Save upload locally first
            upload_instance = UploadedFile(file=uploaded_file)
            upload_instance.save()
            input_path = upload_instance.file.path
            
            # Worker Configuration
            WORKER_URL = 'https://blilnkdex.biz.id/api.php'
            API_KEY = 'MANKY_SECRET_KEY_12345'
            
            # Send to Worker
            print(f"📡 Dispatching Word->PDF to Worker: {WORKER_URL}")
            with open(input_path, 'rb') as f:
                # Word files (doc/docx) mime type
                mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                files = {'file': (uploaded_file.name, f, mime)}
                headers = {'X-API-KEY': API_KEY}
                data = {'type': 'word-to-pdf'}
                
                target_url = f"{WORKER_URL}?key={API_KEY}"
                response = requests.post(target_url, files=files, data=data, headers=headers, timeout=60, verify=False)
                
                if response.status_code == 200:
                    res_data = response.json()
                    task_id = res_data.get('task_id')
                    if task_id:
                        return render(request, 'converter/worker_wait.html', {
                            'task_id': task_id,
                            'worker_host': 'https://blilnkdex.biz.id',
                            'file_name': uploaded_file.name,
                            'task_type': 'PDF'
                        })
                    else:
                        raise Exception("Worker did not return Task ID")
                else:
                    raise Exception(f"Worker Error: {response.status_code} - {response.text}")

        except Exception as e:
            print(f"Error converting Word to PDF: {e}")
            context = {
                'error': f"เกิดข้อผิดพลาด: {str(e)}",
                'title': 'Word เป็น PDF'
            }
            return render(request, 'converter/tool_base.html', context)

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
            
            # Track usage statistics
            try:
                stat, _ = DailyStat.objects.get_or_create(date=timezone.now().date())
                stat.usage_count += 1
                stat.save()
            except Exception as e:
                print(f"Stats error: {e}")
            
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
            
            # Track usage statistics
            try:
                from django.utils import timezone
                stat, _ = DailyStat.objects.get_or_create(date=timezone.now().date())
                stat.usage_count += 1
                stat.save()
            except Exception as e:
                print(f"Stats error: {e}")
            
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
        job_id = request.GET.get('job_id')
        file_type = request.GET.get('file_type', 'WORD')
        
        if job_id:
            # Construct download URL for the result page button
            download_url = reverse('converter:download_file_direct', kwargs={'job_id': job_id})
            return render(request, 'converter/result.html', {
                'download_url': download_url,
                'file_type': file_type,
                'job_id': job_id
            })
            
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
            pattern_style = request.POST.get('pattern_style', 'square') # square, gapped, circle, rounded, vertical, horizontal
            
            # 3. Generate QR Code
            import qrcode
            from django.http import JsonResponse
            
            # Helper: Convert Hex to RGB Tuple for StyledImage reliability
            def hex_to_rgb(hex_color):
                hex_color = hex_color.lstrip('#')
                return tuple(int(hex_color[i:i+2], 16) for i in (0, 2, 4))

            # Default to standard generation first
            use_styled = False
            drawer = None
            
            if pattern_style and pattern_style != 'square':
                try:
                    from qrcode.image.styled.pil import StyledImage
                    from qrcode.image.styles.moduledrawers import (
                        SquareModuleDrawer,
                        GappedSquareModuleDrawer,
                        CircleModuleDrawer,
                        RoundedModuleDrawer,
                        VerticalBarsDrawer,
                        HorizontalBarsDrawer
                    )
                    use_styled = True
                    
                    print(f"[QR DEBUG] Requested pattern: {pattern_style}")
                    
                    if pattern_style == 'gapped':
                        drawer = GappedSquareModuleDrawer()
                    elif pattern_style == 'circle':
                        drawer = CircleModuleDrawer()
                    elif pattern_style == 'rounded':
                        drawer = RoundedModuleDrawer()
                    elif pattern_style == 'vertical':
                        drawer = VerticalBarsDrawer()
                    elif pattern_style == 'horizontal':
                        drawer = HorizontalBarsDrawer()
                    else:
                        drawer = SquareModuleDrawer()
                    
                    print(f"[QR DEBUG] Using drawer: {drawer.__class__.__name__}")
                except ImportError as e:
                    print(f"[QR ERROR] Styled QR modules import failed: {e}")
                    import traceback
                    traceback.print_exc()
                    use_styled = False
                except Exception as e:
                    print(f"[QR ERROR] Unexpected error setting up styled QR: {e}")
                    import traceback
                    traceback.print_exc()
                    use_styled = False

            qr = qrcode.QRCode(
                version=None, # Auto version
                error_correction=qrcode.constants.ERROR_CORRECT_H,
                box_size=20, # Higher quality
                border=2,
            )
            qr.add_data(data)
            qr.make(fit=True)
            
            # Create Image with colors
            try:
                # Debug info
                print(f"DEBUG: Pattern Style requested: '{pattern_style}'")
                
                # Check imports explicitly
                try:
                    from qrcode.image.styled.pil import StyledImage
                    from PIL import ImageOps
                except ImportError as e:
                    print(f"CRITICAL ERROR: Libraries missing: {e}")
                    raise e

                if use_styled and drawer:
                    print(f"DEBUG: Generating Styled QR using drawer: {drawer}")
                    
                    # 1. Generate Grayscale Mask (L Mode)
                    try:
                        img_mask = qr.make_image(
                            image_factory=StyledImage,
                            module_drawer=drawer,
                        ).convert('L') 
                        print("DEBUG: Styled Mask generated successfully")
                    except Exception as e:
                        print(f"ERROR generating styled mask: {e}")
                        raise e
                    
                    # 2. Manual Colorization using ImageOps
                    try:
                        fill_rgb = hex_to_rgb(fill_color)
                        back_rgb = hex_to_rgb(back_color)
                        img = ImageOps.colorize(img_mask, black=fill_rgb, white=back_rgb)
                        print("DEBUG: Colorization successful")
                    except Exception as e:
                         print(f"ERROR coloring image: {e}")
                         raise e
                    
                else:
                    print("DEBUG: Using Standard Generation")
                    img = qr.make_image(fill_color=fill_color, back_color=back_color).convert('RGB')
            except Exception as e:
                print(f"FATAL QR GENERATION ERROR: {e}")
                import traceback
                traceback.print_exc()
                # Return standard black/white as absolute last resort, but LOG IT LOUDLY
                img = qr.make_image(fill_color="black", back_color="white").convert('RGB')

            # --- LOGO PROCESSING START ---
            logo_file = request.FILES.get('logo')
            if logo_file:
                try:
                    logo = Image.open(logo_file)
                    
                    basewidth = int(img.size[0] * 0.22)
                    wpercent = (basewidth / float(logo.size[0]))
                    hsize = int((float(logo.size[1]) * float(wpercent)))
                    
                    logo = logo.resize((basewidth, hsize), Image.Resampling.LANCZOS)
                    pos = ((img.size[0] - logo.size[0]) // 2, (img.size[1] - logo.size[1]) // 2)
                    
                    if logo.mode == 'RGBA':
                        img.paste(logo, pos, logo)
                    else:
                        img.paste(logo, pos)
                except Exception as e:
                    print(f"Logo processing error: {e}")
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
            
            # 5. Prepare Context/Response
            qr_image_url = f"{settings.MEDIA_URL}{processed_rel_path}"
            download_url = reverse('converter:download_file', kwargs={'job_id': job_id})
            
            # Check for AJAX Request
            if request.headers.get('x-requested-with') == 'XMLHttpRequest':
                return JsonResponse({
                    'success': True,
                    'qr_image_url': qr_image_url,
                    'download_url': download_url,
                    'message': 'QR Code generated successfully',
                    'debug_style': pattern_style,
                    'debug_used_styled': use_styled
                })

            # Normal Request
            context = {
                'title': 'สร้าง QR Code',
                'subtitle': 'สร้าง QR Code ฟรีจากลิงก์ ข้อความ หรือเบอร์โทรศัพท์ พร้อมปรับแต่งสีได้ตามใจชอบ',
                'action_url': reverse('converter:qrcode_generator'),
                'generated': True,
                'qr_image_url': qr_image_url,
                'download_url': download_url,
                'fill_color': fill_color,
                'back_color': back_color,
                'pattern_style': pattern_style,
            }

            # Track usage statistics
            try:
                stat, _ = DailyStat.objects.get_or_create(date=timezone.now().date())
                stat.usage_count += 1
                stat.save()
            except Exception as e:
                print(f"Stats error: {e}")

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

class DeleteInstantView(View):
    def get(self, request):
        """
        Handle 'Delete Immediately' request.
        For now, this simply redirects to the index page, essentially 'discarding' the result.
        Actual file cleanup is handled by the background scheduler/cron job.
        """
        return redirect('converter:index')

# --- Shorten URL Views ---
class ShortenURLView(View):
    def get(self, request):
        return render(request, 'converter/shorten_link.html')
    
    def post(self, request):
        original_url = request.POST.get('url')
        expiry_option = request.POST.get('expiry') # 24h, 7d
        
        if not original_url:
            if request.headers.get('x-requested-with') == 'XMLHttpRequest' or request.GET.get('ajax') == 'true':
                return JsonResponse({'success': False, 'error': 'กรุณาระบุ URL'})
            return render(request, 'converter/shorten_link.html', {'error': 'กรุณาระบุ URL'})
        
        # Generate Short Code
        def generate_code(length=6):
            return ''.join(random.choices(string.ascii_letters + string.digits, k=length))
        
        short_code = generate_code()
        while ShortLink.objects.filter(short_code=short_code).exists():
            short_code = generate_code()
        
        # Calculate Expiry
        from datetime import timedelta
        now = timezone.now()
        if expiry_option == '7d':
            expires_at = now + timedelta(days=7)
        else: # Default 24h
            expires_at = now + timedelta(hours=24)
            
        link = ShortLink.objects.create(
            original_url=original_url,
            short_code=short_code,
            expires_at=expires_at
        )
        
        # Build absolute URL
        short_url = request.build_absolute_uri(f'/s/{short_code}/')
        
        # Check if AJAX request
        if request.headers.get('x-requested-with') == 'XMLHttpRequest' or request.GET.get('ajax') == 'true':
            return JsonResponse({
                'success': True,
                'short_url': short_url,
                'expires_at': expires_at.strftime("%d %b %Y %H:%M")
            })

        return render(request, 'converter/shorten_link.html', {
            'short_url': short_url,
            'original_url': original_url,
            'expires_at': expires_at,
            'short_code': short_code
        })

class RedirectShortLinkView(View):
    def get(self, request, short_code):
        try:
            link = ShortLink.objects.get(short_code=short_code)
            
            # Check Expiry
            if timezone.now() > link.expires_at:
                return render(request, 'converter/redirect_page.html', {'error': 'ลิงก์นี้หมดอายุแล้ว (Expired link)'})
            
            # Update clicks
            link.clicks += 1
            link.save()
            
            # Render interstitial page
            return render(request, 'converter/redirect_page.html', {
                'original_url': link.original_url,
                'short_code': short_code
            })
            
        except ShortLink.DoesNotExist:
            return render(request, 'converter/redirect_page.html', {'error': 'ไม่พบลิงก์ที่คุณต้องการ (Link not found)'})

class SystemCleanupView(View):
    def get(self, request):
        # Security check (Simple key)
        key = request.GET.get('key')
        if key != settings.SECRET_KEY: # Use Django secret key or a specific one
            return JsonResponse({'status': 'error', 'message': 'Invalid Key'}, status=403)
            
        # Run Cleanup
        try:
            files_deleted = cleanup_old_files(hours=1)
            links_deleted = cleanup_expired_links_db()
            
            return JsonResponse({
                'status': 'success', 
                'files_deleted': files_deleted,
                'links_deleted': links_deleted,
                'message': 'System cleanup completed successfully'
            })
        except Exception as e:
            return JsonResponse({'status': 'error', 'message': str(e)}, status=500)
