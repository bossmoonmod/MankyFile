import sys

file_path = r'f:\Web\MankyFile\pdf_master_project\apps\converter\views.py'

with open(file_path, 'r', encoding='utf-8') as f:
    lines = f.readlines()

new_lines = []
skip = False

for i, line in enumerate(lines):
    line_num = i + 1
    if line_num == 1724:
        # Start of ShortenURLView
        new_lines.append("class ShortenURLView(View):\n")
        new_lines.append("    def get(self, request):\n")
        new_lines.append("        return render(request, 'converter/shorten_link.html')\n")
        new_lines.append("    \n")
        new_lines.append("    def post(self, request):\n")
        new_lines.append("        import requests\n")
        new_lines.append("        original_url = request.POST.get('url')\n")
        new_lines.append("        expiry_option = request.POST.get('expiry', '24h') \n")
        new_lines.append("        \n")
        new_lines.append("        if not original_url:\n")
        new_lines.append("            if request.headers.get('x-requested-with') == 'XMLHttpRequest' or request.GET.get('ajax') == 'true':\n")
        new_lines.append("                return JsonResponse({'success': False, 'error': 'กรุณาระบุ URL'})\n")
        new_lines.append("            return render(request, 'converter/shorten_link.html', {'error': 'กรุณาระบุ URL'})\n")
        new_lines.append("        \n")
        new_lines.append("        try:\n")
        new_lines.append("            WORKER_URL = 'https://blilnkdex.biz.id/shorten_api.php'\n")
        new_lines.append("            API_KEY = 'MANKY_SECRET_KEY_12345'\n")
        new_lines.append("            data = {'long_url': original_url, 'duration': expiry_option}\n")
        new_lines.append("            headers = {'X-API-KEY': API_KEY}\n")
        new_lines.append("            target_url = f'{WORKER_URL}?action=create&key={API_KEY}'\n")
        new_lines.append("            response = requests.post(target_url, data=data, headers=headers, timeout=10, verify=False)\n")
        new_lines.append("            \n")
        new_lines.append("            if response.status_code == 200:\n")
        new_lines.append("                res_data = response.json()\n")
        new_lines.append("                if res_data.get('success'):\n")
        new_lines.append("                    short_code = res_data.get('short_code')\n")
        new_lines.append("                    short_url = request.build_absolute_uri(f'/s/{short_code}/')\n")
        new_lines.append("                    expires_at_str = res_data.get('expires_at')\n")
        new_lines.append("                    expires_at_display = expires_at_str if expires_at_str else 'ไม่มีวันหมดอายุ (Forever)'\n")
        new_lines.append("                    \n")
        new_lines.append("                    if request.headers.get('x-requested-with') == 'XMLHttpRequest' or request.GET.get('ajax') == 'true':\n")
        new_lines.append("                        return JsonResponse({'success': True, 'short_url': short_url, 'expires_at': expires_at_display})\n")
        new_lines.append("\n")
        new_lines.append("                    return render(request, 'converter/shorten_link.html', {\n")
        new_lines.append("                        'short_url': short_url, 'original_url': original_url,\n")
        new_lines.append("                        'expires_at': expires_at_display, 'short_code': short_code\n")
        new_lines.append("                    })\n")
        new_lines.append("                else:\n")
        new_lines.append("                    raise Exception(res_data.get('error', 'Unknown Error'))\n")
        new_lines.append("            else:\n")
        new_lines.append("                raise Exception(f'Worker Error: {response.status_code}')\n")
        new_lines.append("        except Exception as e:\n")
        new_lines.append("            if request.headers.get('x-requested-with') == 'XMLHttpRequest' or request.GET.get('ajax') == 'true':\n")
        new_lines.append("                return JsonResponse({'success': False, 'error': str(e)})\n")
        new_lines.append("            return render(request, 'converter/shorten_link.html', {'error': str(e)})\n")
        new_lines.append("\n")
        new_lines.append("class RedirectShortLinkView(View):\n")
        new_lines.append("    def get(self, request, short_code):\n")
        new_lines.append("        return redirect(f'https://blilnkdex.biz.id/shorten_api.php?code={short_code}')\n")
        
        skip = True
    elif line_num == 1800:
        skip = False
        new_lines.append(line)
    elif not skip:
        new_lines.append(line)

with open(file_path, 'w', encoding='utf-8') as f:
    f.writelines(new_lines)

print("Success")
