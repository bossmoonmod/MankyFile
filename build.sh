#!/usr/bin/env bash
# exit on error
set -o errexit

pip install --upgrade pip
pip install requests
# Force Rebuild Trigger 1
pip install -r requirements.txt

python manage.py collectstatic --noinput
python manage.py migrate
