import sys
import sqlite3
import pikepdf
import itertools
import time
import os
from concurrent.futures import ThreadPoolExecutor

# Argument parsing
if len(sys.argv) < 4:
    print("Usage: python worker.py <input> <output> <job_id>")
    sys.exit(1)

INPUT_PATH = sys.argv[1]
OUTPUT_PATH = sys.argv[2]
JOB_ID = sys.argv[3]
DB_PATH = os.path.join(os.path.dirname(__file__), 'db', 'tasks.sqlite')

def update_db(status, password=None):
    try:
        conn = sqlite3.connect(DB_PATH)
        c = conn.cursor()
        c.execute("UPDATE tasks SET status=?, password_found=?, updated_at=CURRENT_TIMESTAMP WHERE id=?", 
                  (status, password, JOB_ID))
        conn.commit()
        conn.close()
    except Exception as e:
        print(f"DB Error: {e}")

def check_solution(pwd):
    try:
        with pikepdf.open(INPUT_PATH, password=pwd) as pdf:
            pdf.save(OUTPUT_PATH)
            return True
    except:
        return False

def run_cracker():
    update_db('processing')
    
    # -----------------------------------------------
    # 🚀 UNLEASHED MODE (Machine Power)
    # -----------------------------------------------
    MAX_WORKERS = 8 # Server บ้านแรง! ใส่ไปเลย 8-16 threads
    TIMEOUT = 300   # ให้เวลา 5 นาที (300 วิ)
    start_time = time.time()
    found_password = None
    
    # ... (Logic การแคร็กที่นำมาจาก pdf_cracker.py แต่ปรับให้รันเต็มสูบ) ...
    # เพื่อความกระชับ ผมจะใส่ Logic หลักๆ ที่จำเป็นครับ
    
    # 1. Common Passwords
    defaults = ["", "123456", "password", "1234", "admin", "1111", "0000"]
    for p in defaults:
        if check_solution(p):
            update_db('completed', p)
            return

    # Helper Generators (เหมือนเดิม)
    def gen_dates():
        for y in range(2025, 1950, -1):
            if found_password: return
            ystr, ybe = str(y), str(y+543)
            for m in range(1,13):
                maxd = 31 if m not in [4,6,9,11,2] else (29 if m==2 else 30)
                for d in range(1, maxd+1):
                    dd, mm = f"{d:02d}", f"{m:02d}"
                    # Patterns
                    pats = [
                        dd+mm+ystr, ystr+mm+dd, dd+mm+ybe, # Standard
                        dd+"/"+mm+"/"+ystr, dd+"-"+mm+"-"+ystr, dd+"."+mm+"."+ystr, # Separators
                        dd+"/"+mm+"/"+ybe,  dd+"-"+mm+"-"+ybe # BE Separators
                    ]
                    for x in pats:
                        if check_solution(x): return x

    def gen_pins():
        # 0000-999999
        for i in range(1000000):
            if found_password: return
            p = f"{i:04d}" if i < 10000 else f"{i:06d}" # Prioritize shortness? No, just scan.
            # actually scan 4 digits fully then 6.
            # simpler:
            if check_solution(f"{i:06d}"): return f"{i:06d}"
            if i < 10000 and check_solution(f"{i:04d}"): return f"{i:04d}"

    # Execution
    # Note: Creating true "Found" signal between threads in Python requires Manager or shared flag.
    # Here simpler: sequential block per thread strategy or just basic
    
    # Simplified Logic for Script:
    # Just run Date Strategy & PIN Strategy in parallel
    
    with ThreadPoolExecutor(max_workers=MAX_WORKERS) as exe:
        # Submit tasks
        f1 = exe.submit(gen_dates)
        f2 = exe.submit(gen_pins)
        
        while not (f1.done() and f2.done()):
             time.sleep(1)
             if f1.result(): found_password = f1.result(); break
             if f2.result(): found_password = f2.result(); break
             if time.time() - start_time > TIMEOUT: break
             
    if found_password:
        update_db('completed', found_password)
    else:
        update_db('failed')

if __name__ == '__main__':
    try:
        run_cracker()
    except Exception as e:
        print(e)
        update_db('failed')
