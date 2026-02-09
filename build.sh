#!/usr/bin/env bash
# exit on error
set -o errexit

python -m pip install --upgrade pip
python -m pip install setuptools
python -m pip install requests
python -m pip install -r requirements.txt

python manage.py collectstatic --noinput
python manage.py migrate
