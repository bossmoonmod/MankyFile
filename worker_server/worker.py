import sys
import sqlite3
import os
import time
import io
import string
import itertools
from concurrent.futures import ThreadPoolExecutor

# --- Dependencies Check ---
try:
    import pikepdf
except ImportError:
    print("Error: pikepdf not installed")
    sys.exit(1)

# Argument parsing
if len(sys.argv) < 5:
    print("Usage: python worker.py <input> <output> <job_id> <task_type>")
    sys.exit(1)

INPUT_PATH = sys.argv[1]
OUTPUT_PATH = sys.argv[2]
JOB_ID = sys.argv[3]
TASK_TYPE = sys.argv[4]
DB_PATH = os.path.join(os.path.dirname(__file__), 'db', 'tasks.sqlite')

# --- DB Helper ---
def update_db(status, password=None, error_msg=None):
    try:
        conn = sqlite3.connect(DB_PATH, timeout=10)
        c = conn.cursor()
        # If storing password/error logic needs extension, modify here. 
        # Currently leveraging password_found field for error msg if failed? Or just logging.
        # Let's keep password_found strictly for passwords.
        c.execute("UPDATE tasks SET status=?, password_found=?, updated_at=CURRENT_TIMESTAMP WHERE id=?", 
                  (status, password, JOB_ID))
        conn.commit()
        conn.close()
    except Exception as e:
        print(f"DB Error: {e}")

# ==========================================
# 🔐 MODE: UNLOCK (Psychology Strategy)
# ==========================================
def run_unlock():
    print(f"🚀 Starting Unlock Task: {JOB_ID}")
    
    # Read file to RAM
    try:
        with open(INPUT_PATH, 'rb') as f:
            FILE_BYTES = f.read()
    except Exception as e:
        print(f"Read Error: {e}")
        update_db('failed')
        return

    def check_solution(pwd):
        try:
            mem_file = io.BytesIO(FILE_BYTES)
            with pikepdf.open(mem_file, password=pwd) as pdf:
                pdf.save(OUTPUT_PATH)
                return True
        except:
            return False

    COMMON_WORDS = ["admin", "password", "welcome", "server", "system", "master", "love", "happy", "money", "secret", "manchester", "liverpool"]
    found_password = None
    stop_event = False

    def solver_wrapper(candidate_gen):
        nonlocal found_password, stop_event
        for pwd in candidate_gen:
            if stop_event: return None
            if check_solution(pwd):
                stop_event = True
                return pwd
        return None

    # Generators
    def gen_repeats_and_seq():
        for i in range(10):
            c = str(i)
            for length in range(4, 10): yield c * length
        seq_asc = "01234567890123456789"
        seq_desc = "98765432109876543210"
        for length in range(4, 10):
            for i in range(10):
                yield seq_asc[i:i+length]
                yield seq_desc[i:i+length]
        chars = string.ascii_letters
        for c in chars:
            for length in range(4, 8): yield c * length

    def gen_hybrid():
        suffixes = ["123", "1234", "12345", "00", "01", "888", "999", "2023", "2024", "2025", "2026"]
        prefixes = ["123", "1234"]
        for w in COMMON_WORDS:
            variations = [w, w.capitalize(), w.upper()]
            for var in variations:
                for s in suffixes: yield f"{var}{s}"
                for p in prefixes: yield f"{p}{var}"

    def gen_dates_pins():
        for i in range(1000000):
            yield f"{i:06d}"
            if i < 10000: yield f"{i:04d}"
        current_year = 2026
        for y in range(current_year, 1980, -1):
            ystr, ybe = str(y), str(y+543)
            for m in range(1, 13):
                maxd = 31
                for d in range(1, maxd+1):
                    dd, mm = f"{d:02d}", f"{m:02d}"
                    dn, mn = str(d), str(m)
                    yield dd+mm+ybe
                    yield dd+mm+ystr
                    yield dn+mn+ybe

    MAX_WORKERS = (os.cpu_count() or 4) + 2
    with ThreadPoolExecutor(max_workers=MAX_WORKERS) as exe:
        futures = []
        futures.append(exe.submit(solver_wrapper, gen_repeats_and_seq()))
        futures.append(exe.submit(solver_wrapper, gen_hybrid()))
        futures.append(exe.submit(solver_wrapper, gen_dates_pins()))
        
        while any(not f.done() for f in futures):
            if stop_event or found_password: break
            time.sleep(0.5)

        for f in futures:
            if f.done() and f.result():
                found_password = f.result()
                break

    if found_password:
        update_db('completed', found_password)
    else:
        update_db('failed')

# ==========================================
# 📊 MODE: PDF TO PPT (Image Based)
# ==========================================
def run_pdf_to_ppt():
    print(f"📊 Starting PDF->PPT Task: {JOB_ID}")
    try:
        from pdf2image import convert_from_path
        from pptx import Presentation
        from pptx.util import Inches
    except ImportError:
        print("Missing libraries: pdf2image or python-pptx")
        update_db('failed')
        return

    try:
        # Convert PDF to Images
        # Note: 'poppler' must be installed on the system (apt install poppler-utils)
        images = convert_from_path(INPUT_PATH)
        
        prs = Presentation()
        # Clean default slides
        
        for i, image in enumerate(images):
            # Save image temporarily
            img_path = f"{OUTPUT_PATH}_temp_{i}.jpg"
            image.save(img_path, 'JPEG')
            
            # Create blank slide
            blank_slide_layout = prs.slide_layouts[6] 
            slide = prs.slides.add_slide(blank_slide_layout)
            
            # Add image to slide (Fit to page logic could be added)
            # Default: specific size or fit
            left = top = Inches(0)
            # A4 aspect ratio approx
            slide.shapes.add_picture(img_path, left, top, height=Inches(7.5)) 
            
            os.remove(img_path) # Cleanup temp image

        prs.save(OUTPUT_PATH)
        update_db('completed')
        
    except Exception as e:
        print(f"PPT Conversion Error: {e}")
        update_db('failed')

# ==========================================
# 📈 MODE: PDF TO EXCEL
# ==========================================
def run_pdf_to_excel():
    print(f"📈 Starting PDF->Excel Task: {JOB_ID}")
    try:
        import pdfplumber
        import pandas as pd
    except ImportError:
        print("Missing libraries: pdfplumber or pandas")
        update_db('failed')
        return

    try:
        all_tables = []
        with pdfplumber.open(INPUT_PATH) as pdf:
            for page in pdf.pages:
                tables = page.extract_tables()
                for table in tables:
                    df = pd.DataFrame(table)
                    all_tables.append(df)
        
        if all_tables:
            # Concatenate all tables or create multiple sheets?
            # Simple approach: Concat all
            final_df = pd.concat(all_tables)
            final_df.to_excel(OUTPUT_PATH, index=False, header=False)
            update_db('completed')
        else:
            print("No tables found")
            # Create empty excel
            pd.DataFrame(["No tables found"]).to_excel(OUTPUT_PATH)
            update_db('completed') # Treated as success but empty
            
    except Exception as e:
        print(f"Excel Conversion Error: {e}")
        update_db('failed')


# ==========================================
# 📄 MODE: PPT TO PDF (LibreOffice)
# ==========================================
def run_ppt_to_pdf():
    print(f"📄 Starting PPT->PDF Task: {JOB_ID}")
    import subprocess
    
    try:
        # Check if LibreOffice is installed
        # Common names: libreoffice, soffice
        cmd = ['libreoffice', '--headless', '--convert-to', 'pdf', '--outdir', os.path.dirname(OUTPUT_PATH), INPUT_PATH]
        
        print(f"Executing: {' '.join(cmd)}")
        result = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        
        if result.returncode == 0:
            # LibreOffice output name logic: same name as input but .pdf
            # We need to ensure it matches OUTPUT_PATH expected by system
            
            # Theoretical output
            base_name = os.path.splitext(os.path.basename(INPUT_PATH))[0]
            generated_pdf = os.path.join(os.path.dirname(OUTPUT_PATH), base_name + '.pdf')
            
            if os.path.exists(generated_pdf):
                # Rename to expected OUTPUT_PATH if needed
                if generated_pdf != OUTPUT_PATH:
                     if os.path.exists(OUTPUT_PATH): os.remove(OUTPUT_PATH)
                     os.rename(generated_pdf, OUTPUT_PATH)
                
                update_db('completed')
            else:
                print("LibreOffice finished but output file missing.")
                print(f"Expected: {generated_pdf}")
                update_db('failed')
        else:
            print(f"LibreOffice Error: {result.stderr.decode()}")
            update_db('failed')

    except Exception as e:
        print(f"PPT->PDF Error: {e}")
# ==========================================
# 📝 MODE: PDF TO WORD
# ==========================================
def run_pdf_to_word():
    print(f"📝 Starting PDF->Word Task: {JOB_ID}")
    try:
        from pdf2docx import Converter
    except ImportError:
        print("Missing library: pdf2docx")
        update_db('failed')
        return

    try:
        cv = Converter(INPUT_PATH)
        cv.convert(OUTPUT_PATH, start=0, end=None)
        cv.close()
        update_db('completed')
    except Exception as e:
        print(f"PDF->Word Error: {e}")
        update_db('failed')

# ==========================================
# 📄 MODE: WORD TO PDF (LibreOffice)
# ==========================================
def run_word_to_pdf():
    print(f"📄 Starting Word->PDF Task: {JOB_ID}")
    import subprocess
    
    try:
        # LibreOffice Handles .doc and .docx
        cmd = ['libreoffice', '--headless', '--convert-to', 'pdf', '--outdir', os.path.dirname(OUTPUT_PATH), INPUT_PATH]
        
        print(f"Executing: {' '.join(cmd)}")
        result = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        
        if result.returncode == 0:
            # Logic same as PPT->PDF
            base_name = os.path.splitext(os.path.basename(INPUT_PATH))[0]
            generated_pdf = os.path.join(os.path.dirname(OUTPUT_PATH), base_name + '.pdf')
            
            if os.path.exists(generated_pdf):
                if generated_pdf != OUTPUT_PATH:
                     if os.path.exists(OUTPUT_PATH): os.remove(OUTPUT_PATH)
                     os.rename(generated_pdf, OUTPUT_PATH)
                update_db('completed')
            else:
                update_db('failed')
        else:
            print(f"LibreOffice Error: {result.stderr.decode()}")
            update_db('failed')

    except Exception as e:
        print(f"Word->PDF Error: {e}")
        update_db('failed')

# ==========================================
# 🎮 MAIN DISPATCHER
# ==========================================
if __name__ == '__main__':
    update_db('processing')
    
    mapping = {
        'unlock': run_unlock,
        'pdf-to-ppt': run_pdf_to_ppt,
        'pdf-to-excel': run_pdf_to_excel,
        'ppt-to-pdf': run_ppt_to_pdf,
        'pdf-to-word': run_pdf_to_word,
        'word-to-pdf': run_word_to_pdf
    }

    if TASK_TYPE in mapping:
        mapping[TASK_TYPE]()
    else:
        print(f"Unknown Task Type: {TASK_TYPE}")
        update_db('failed')
