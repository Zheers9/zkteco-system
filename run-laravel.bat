@echo off
cd /d C:\Users\zheer\Herd\zkteco

start cmd /k php artisan serve

timeout /t 3 > nul
start http://127.0.0.1:8000
