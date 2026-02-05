import pikepdf
import itertools
import string
import time
import datetime
from concurrent.futures import ThreadPoolExecutor

def brute_force_pdf(input_path, output_path):
    """
    ULTIMATE PDF CRACKER V5 (INTELLIGENT ORDER + PARALLEL)
    Optimized:
    1. Checks Recent Years & Common Birth Years FIRST (Highest probability).
    2. Checks User's provided '14072004' (DDMMYYYY) efficiently.
    3. Runs multiple threads.
    """
    
    start_time = time.time()
    TIMEOUT = 55
    
    class CrackerState:
        found = None
        
    state = CrackerState()

    def check_solution(pwd):
        if state.found: return True
        try:
            with pikepdf.open(input_path, password=pwd) as pdf:
                pdf.save(output_path)
                state.found = pwd
                return True
        except:
            return False

    print(f"🔓 Starting Ultimate Crack V5 for {input_path}...")

    # 1. INSTANT COMMON (Main Thread)
    # Check these first instantly
    fast_list = ["", "123456", "password", "admin", "1234", "1111", "0000", "12345678", "000000", "111111"]
    for p in fast_list:
        if check_solution(p): return p

    # ----------------------------------------------------
    # TACTICAL GENERATORS (Ordered by Probability)
    # ----------------------------------------------------
    
    def generate_dates_smart():
        # Generator for high-probability dates
        # Range 1: 1980 - 2005 (Likely Birthdays of Users) -> Includes 2004
        years_prio_1 = list(range(1980, 2010)) 
        
        # Range 2: 2020 - 2026 (Likely Document Dates)
        years_prio_2 = list(range(2020, 2027))
        
        # Range 3: 1950 - 1979 (Older Users)
        years_prio_3 = list(range(1950, 1980))
        
        # Combine priorities (Newer/Common first)
        year_list = sorted(list(set(years_prio_2 + years_prio_1 + years_prio_3)), reverse=True)
        
        for y in year_list:
             if state.found: return
             y_str = str(y)
             y_be = str(y + 543) # Thai Year
             
             # Optimization: only check 1980+ for BE too? No, keep logic simple but fast.
             
             for m in range(1, 13):
                 max_d = 31
                 if m in [4,6,9,11]: max_d=30
                 elif m==2: max_d=29
                 
                 for d in range(1, max_d+1):
                     dd = f"{d:02d}"
                     mm = f"{m:02d}"
                     
                     # 1. DDMMYYYY (AD) -> 14072004
                     if check_solution(f"{dd}{mm}{y_str}"): return
                     # 2. DDMM (Short)
                     # if check_solution(f"{dd}{mm}"): return # Too ambiguous?
                     # 3. YYYYMMDD
                     if check_solution(f"{y_str}{mm}{dd}"): return
                     # 4. DDMMYYYY (BE)
                     if check_solution(f"{dd}{mm}{y_be}"): return
                     # 5. DDMMYY (Short year)
                     if check_solution(f"{dd}{mm}{y_str[2:]}"): return
                     if check_solution(f"{dd}{mm}{y_be[2:]}"): return

    def generate_dates_with_sep():
        # DD/MM/YYYY, etc.
        # Scan same optimized range? Or just scan full?
        # Let's scan full fast.
        for y in range(2026, 1950, -1):
            if state.found: return
            y_str = str(y)
            y_be = str(y+543)
            
            for m in range(1, 13):
                max_d = 31
                if m==2: max_d=29
                elif m in [4,6,9,11]: max_d=30
                
                for d in range(1, max_d+1):
                    dd = f"{d:02d}"
                    mm = f"{m:02d}"
                    
                    # Common Separators
                    for sep in ['/', '-', '.']:
                        if check_solution(f"{dd}{sep}{mm}{sep}{y_str}"): return
                        if check_solution(f"{dd}{sep}{mm}{sep}{y_be}"): return

    def generate_pins_numeric():
        # Pure numeric 0-9
        # Priority: 4 digits, 6 digits, then others
        
        # 4 Digits
        for i in range(10000):
            if state.found: return
            if check_solution(f"{i:04d}"): return
        
        if time.time() - start_time > TIMEOUT: return
        
        # 6 Digits
        for i in range(1000000):
            if state.found: return
            if i % 2000 == 0 and (time.time() - start_time > TIMEOUT): return # Check timeout periodically
            if check_solution(f"{i:06d}"): return

    # EXECUTE THREADS
    with ThreadPoolExecutor(max_workers=5) as executor:
        futures = [
            executor.submit(generate_dates_smart),     # Prio 1: Birthdays (AD/BE)
            executor.submit(generate_dates_with_sep),  # Prio 2: Separators
            executor.submit(generate_pins_numeric),    # Prio 3: PINs 4/6
        ]
        
        # Wait until done or timeout
        while (time.time() - start_time < TIMEOUT) and not state.found:
            time.sleep(0.5)
            if all(f.done() for f in futures):
                break

    return state.found
