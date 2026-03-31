
import os
import sys

# Ensure we use the project's venv if available
venv_python = r"i:\Web\MankyFile\.venv\Scripts\python.exe"

test_script = """
import qrcode
from qrcode.image.styled.pil import StyledImage
from qrcode.image.styles.moduledrawers import CircleModuleDrawer
from qrcode.image.styles.colormasks import SolidFillColorMask
import os

try:
    qr = qrcode.QRCode(error_correction=qrcode.constants.ERROR_CORRECT_H)
    qr.add_data('https://mankyfile.onrender.com/qrcode-generator/')
    qr.make(fit=True)
    
    img = qr.make_image(
        image_factory=StyledImage,
        module_drawer=CircleModuleDrawer(),
        color_mask=SolidFillColorMask(back_color=(255, 255, 255), front_color=(0, 0, 0))
    )
    img.save('test_circle_qr.png')
    print('SUCCESS: QR generated')
except Exception as e:
    print(f'ERROR: {e}')
    import traceback
    traceback.print_exc()
"""

with open('debug_qr.py', 'w', encoding='utf-8') as f:
    f.write(test_script)

print("Debug script created. Running...")
