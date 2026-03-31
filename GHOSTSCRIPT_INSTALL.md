# การติดตั้ง Ghostscript สำหรับ Compress PDF

## Windows

### วิธีที่ 1: ดาวน์โหลดจากเว็บไซต์

1. ไปที่ https://www.ghostscript.com/releases/gsdnld.html
2. ดาวน์โหลด "Ghostscript 10.04.0 for Windows (64 bit)"
3. รันไฟล์ติดตั้ง (gs10040w64.exe)
4. ติดตั้งตามค่า default
5. Ghostscript จะติดตั้งที่: `C:\Program Files\gs\gs10.04.0\bin\gswin64c.exe`

### วิธีที่ 2: ใช้ Chocolatey

```powershell
choco install ghostscript
```

## Linux (สำหรับ Production Server)

```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install -y ghostscript

# CentOS/RHEL
sudo yum install -y ghostscript

# ตรวจสอบการติดตั้ง
which gs
gs --version
```

## ทดสอบว่าติดตั้งสำเร็จ

### Windows
```powershell
& "C:\Program Files\gs\gs10.04.0\bin\gswin64c.exe" --version
```

### Linux
```bash
gs --version
```

## หลังติดตั้งเสร็จ

1. รีสตาร์ท Django server
2. ไปที่ http://127.0.0.1:8000/compress-pdf/
3. อัปโหลดไฟล์ PDF
4. เลือกระดับการบีบอัด
5. กดบีบอัด

## Troubleshooting

### ถ้าระบบหา Ghostscript ไม่เจอ

แก้ไขใน `views.py` เพิ่ม path ที่ติดตั้งจริง:

```python
gs_paths = [
    r"C:\Program Files\gs\gs10.04.0\bin\gswin64c.exe",  # เปลี่ยนเป็น version ที่ติดตั้ง
    # ... paths อื่นๆ
]
```

### ถ้าได้ Permission Denied (Linux)

```bash
sudo chmod +x /usr/bin/gs
```
