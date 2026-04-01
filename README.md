# 📄 MankyFile - Premium PDF & Document Tools

<a href="https://blilnkdex.biz.id/image_api.php?id=img_69cd2eb305543.png"><img src="https://blilnkdex.biz.id/image_api.php?id=img_69cd2eb305543.png" alt="ภาพ 2026 02 05 161716777" border="0"></a>
<!-- Note: Layout & Design Updated V.3 -->

**MankyFile** คือสุดยอดเครื่องมือจัดการเอกสารออนไลน์แบบ All-in-One ที่รวมฟีเจอร์แปลงไฟล์ PDF, Word, PowerPoint และ QR Code ไว้ในที่เดียว ออกแบบด้วยแนวคิด **"Mobile-First"** เพื่อการใช้งานที่ลื่นไหลบนมือถือ พร้อมระบบความปลอดภัยระดับสูง

---

## 📦 ประวัติเวอร์ชัน (Version History)

### **V.3 (Premium & Stability Update - 2026)** ✨ **Latest!**
*   🌙 **Full Dark Mode Support:** เพิ่มระบบ Dark Mode สมบูรณ์แบบสำหรับทุกหน้าเครื่องมือ รองรับการสลับ Theme อัตโนมัติและจดจำค่าผ่าน LocalStorage
*   🎨 **Premium Arrange Workspace:** ปรับโฉมหน้าจัดเรียงไฟล์ (Arrange PDF/Word/Image) ใหม่ทั้งหมด ด้วยดีไซน์ระดับ High-End, ระบบ Smart Auto-Numbering และ UI ที่ลื่นไหล
*   🔏 **Neural Unlock Node:** เชื่อมต่อระบบประมวลผลระยะไกล (Worker Node) เพื่อการปลดล็อครหัสผ่าน PDF ด้วยความเร็วสูง พร้อมระบบคิวและ Heartbeat 
*   📱 **Mobile Pixel-Perfect:** แก้ไขปุ่มเลือกไฟล์และ Layout ทั้งระบบให้กึ่งกลางเป๊ะ 100% บนมือถือทุกรุ่น ด้วยเทคนิค Border-Box Reset
*   🖥️ **Desktop Grid 4.0:** ปรับปรุงหน้าแรกบน PC ให้แสดงผล 4 คอลัมน์อย่างสมดุลบนทุกความละเอียดหน้าจอ

### **V.2 (Mobile & Security)** 🔥
*   📱 **Advanced Mobile UX:** ปรับโฉมใหม่รองรับการใช้งานบนมือถือ สำหรับหน้า **QR Code Generator** และ **Compress PDF**
*   🛡️ **Security Hardening:** อัปเกรดการดาวน์โหลดไฟล์ป้องกัน Directory Traversal ด้วย UUID
*   ⚡ **Smart Compression Engine:** อัปเกรดอัลกอริทึมบีบอัดรูปภาพให้คมชัดแม้ไฟล์เล็กลง

### **V.1 (Foundation)**
*   ✅ ระบบแปลงไฟล์หลัก: Word -> PDF, PDF -> Word
*   ✅ เครื่องมือพื้นฐาน: Merge, Split PDF
*   ✅ ระบบจัดการไฟล์อัตโนมัติ (Auto-Cleanup)

---

## ✨ ความสามารถหลัก (Key Features)

### 🚀 1. การจัดการ PDF ระดับมืออาชีพ
*   **รวมไฟล์ PDF (Merge PDF):** ลากวางเพื่อจัดเรียงลำดับไฟล์ได้ตามใจชอบ
*   **บีบอัด PDF (Compress PDF):** **อัลกอริทึมใหม่!** เลือกบีบอัดได้ 4 ระดับ (72 - 300+ DPI) ลดขนาดไฟล์ได้สูงสุด 90%
*   **แยกไฟล์ PDF (Split PDF):** ดึงเฉพาะหน้าที่ต้องการหรือแยกเป็นไฟล์ย่อย
*   **PDF to Word/Excel/PPT:** แปลงกลับเป็นไฟล์ Office เพื่อแก้ไขต่อได้ทันที

### 📱 2. QR Code Generator (V.2 New)
*   สร้าง QR Code ได้ทั้งแบบ URL, Text, WiFi, Crypto, VCard
*   **Custom Design:** ปรับสี QR, สีพื้นหลัง, รูปทรงตา (Marker)
*   **Logo Embedding:** อัปโหลดโลโก้แบรนด์ใส่ตรงกลาง QR Code ได้สวยงาม
*   **Mobile-Friendly:** ใช้งานบนมือถือได้สะดวก ไม่ต้องซูมเข้าซูมออก

### 🔒 3. ความปลอดภัยและความเป็นส่วนตัวสูงสุด
*   **UUID Based Access:** ลิงก์ดาวน์โหลดไฟล์ใช้รหัสลับ 36 หลัก ป้องกันการสุ่มเดา
*   **Ephemeral Processing:** ไฟล์จะถูกลบอัตโนมัติทันทีเมื่อหมดอายุ (1-24 ชม.) หรือเมื่อกด "ลบไฟล์ทันที"
*   **No File Snooping:** ทีมงานไม่มีสิทธิ์เปิดดูไฟล์ของผู้ใช้งาน

---

## 🛠️ เทคโนโลยีที่ใช้ (Tech Stack)

*   **Backend:** [Django 5.x](https://www.djangoproject.com/) (Python Efficiency)
*   **Engines:** 
    *   `LibreOffice` (Headless Conversion)
    *   `PyMuPDF` (Advanced PDF Manipulation)
    *   `Pillow` (Image Optimize)
*   **Frontend:** HTML5, CSS3, JavaScript (Vanilla - Lightweight)
*   **Infrastructure:** รองรับ Docker, CloudPanel, Nginx

---

## 🚀 การติดตั้งและใช้งาน (Getting Started)

### การติดตั้งในเครื่องคอมพิวเตอร์ (Local Development)

1.  **Clone Repository:**
    ```bash
    git clone https://github.com/bossmoonmod/MankyFile.git
    cd MankyFile
    ```

2.  **สร้าง Virtual Environment:**
    ```bash
    python -m venv venv
    # Windows:
    venv\Scripts\activate
    # Mac/Linux:
    source venv/bin/activate 
    ```

3.  **ติดตั้ง Dependencies:**
    ```bash
    pip install -r requirements.txt
    ```

4.  **ติดตั้งโปรแกรมเสริม (จำเป็น):**
    *   [LibreOffice](https://www.libreoffice.org/download/download/) (สำหรับแปลง Word<>PDF)
    *   [Poppler](https://github.com/oschwartz10612/poppler-windows/releases/) (สำหรับ PDF Preview ถ้าจำเป็น)

5.  **เริ่มระบบ:**
    ```bash
    python manage.py migrate
    python manage.py runserver
    ```
    เข้าใช้งานได้ที่: `http://127.0.0.1:8000/`

---

## 🌐 การติดตั้งบน Server (Production)

ระบบรองรับ **WhiteNoise** สำหรับจัดการ Static Files สามารถ Deploy บน:
*   Ubuntu / Debian VPS
*   CloudPanel (Recommended)
*   Render / Heroku

ดูคู่มือฉบับเต็ม: [**DEPLOYMENT.md**](./DEPLOYMENT.md)

---

## 📜 ระบบลบไฟล์อัตโนมัติ (Auto-Cleanup)

ตั้งค่า Cron Job เพื่อลบไฟล์ขยะทุกชั่วโมง:
```bash
# รันทุกชั่วโมง
python manage.py cleanup_files --hours 1
```

---

## 👨‍💻 ผู้พัฒนา
พัฒนาและดูแลโดย **@bossmoonmod**
*   Project Repository: [GitHub](https://github.com/bossmoonmod/MankyFile)

---
© 2026 MankyFile Project. All Rights Reserved.
