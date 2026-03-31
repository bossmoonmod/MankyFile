#!/usr/bin/env bash
# exit on error
set -o errexit

echo "Starting build process..."

pip install --upgrade pip
pip install -r requirements.txt

python manage.py collectstatic --no-input

# Re-create fresh database structure because migration files were corrupted
mkdir -p apps/converter/migrations
rm -f apps/converter/migrations/00*.py
touch apps/converter/migrations/__init__.py

python manage.py makemigrations converter
python manage.py migrate

echo "Build complete."
