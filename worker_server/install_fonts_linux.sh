#!/bin/bash

# ตรวจสอบสิทธิ์ Root
if [ "$EUID" -ne 0 ]; then
  echo "กรุณารันด้วย sudo (Ex: sudo ./install_fonts_linux.sh)"
  exit
fi

echo "=== เริ่มต้นติดตั้งฟอนต์ 4 ภาษา (ไทย, อังกฤษ, ญี่ปุ่น, จีน) บน Ubuntu ==="

# Update package list
echo "[1/5] Updating package lists..."
apt-get update

# 1. ติดตั้งฟอนต์ภาษาไทย (TLWG Fonts)
echo "[2/5] Installing Thai Fonts..."
apt-get install -y fonts-thai-tlwg fonts-loma

# 2. ติดตั้งฟอนต์ภาษาญี่ปุ่น (IPA & Takao)
echo "[3/5] Installing Japanese Fonts..."
apt-get install -y fonts-ipafont-gothic fonts-ipafont-mincho fonts-takao

# 3. ติดตั้งฟอนต์ภาษาจีน (WenQuanYi Zen Hei - มาตรฐาน Linux)
echo "[4/5] Installing Chinese Fonts..."
apt-get install -y fonts-wqy-zenhei

# 4. ติดตั้งฟอนต์ Google Fonts (Noto Sans - รองรับทุกภาษาและสวยงาม)
# เป็นฟอนต์ Backup ที่ดีมาก
echo "[5/5] Installing Google Noto Fonts (Global Support)..."
apt-get install -y fonts-noto fonts-noto-cjk

# (Optional) ฟอนต์ Microsoft (Arial, etc.) - มักต้องกดยอมรับ License
# echo "Installing Microsoft Core Fonts..."
# apt-get install -y ttf-mscorefonts-installer

# Refresh Font Cache
echo "Refreshing font cache..."
fc-cache -fv

echo "=== ติดตั้งเสร็จสิ้น! (Installation Complete) ==="
echo "ตอนนี้ Server ของคุณรองรับภาษา ไทย/อังกฤษ/ญี่ปุ่น/จีน แล้วครับ"
