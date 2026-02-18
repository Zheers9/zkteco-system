@echo off
echo Starting Laravel Server on Network (0.0.0.0:8000)...
echo IMPORTANT: Make sure Windows Firewall allows PHP to accept public connections.
echo Your device should point to your PC IP Address: 8000
php artisan serve --host 0.0.0.0 --port 8000
pause
