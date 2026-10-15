$ErrorActionPreference = 'Stop'
Write-Host '=== Energiciency Laravel 12 setup ===' -ForegroundColor Cyan
if (-not (Get-Command php -ErrorAction SilentlyContinue)) { throw 'PHP tidak ditemukan. Pasang PHP 8.2+ dan tambahkan ke PATH.' }
if (-not (Get-Command composer -ErrorAction SilentlyContinue)) { throw 'Composer tidak ditemukan. Pasang Composer dan tambahkan ke PATH.' }
if (-not (Test-Path '.env')) { Copy-Item '.env.example' '.env'; Write-Host 'File .env dibuat dari .env.example.' -ForegroundColor Yellow }
composer install
php artisan key:generate
if (Get-Command npm -ErrorAction SilentlyContinue) { npm install; npm run build } else { Write-Host 'Node.js/npm belum ditemukan. Pasang Node.js LTS, lalu jalankan npm install dan npm run build.' -ForegroundColor Yellow }
Write-Host ''
Write-Host 'Setup dependensi selesai. Edit .env dan sesuaikan DB_DATABASE, DB_USERNAME, DB_PASSWORD dengan MySQL Anda.' -ForegroundColor Green
Write-Host 'Setelah database energiciency dibuat, jalankan: php artisan migrate --seed' -ForegroundColor Green
Write-Host 'Lalu jalankan: php artisan serve' -ForegroundColor Green
