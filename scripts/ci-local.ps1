# NutriTrace — simulation locale des étapes Jenkins (Windows)
# Usage: powershell -ExecutionPolicy Bypass -File scripts/ci-local.ps1

$ErrorActionPreference = "Stop"
Set-Location (Split-Path $PSScriptRoot -Parent)

Write-Host "== [1/5] Checkout OK (repo local) ==" -ForegroundColor Cyan

Write-Host "== [2/5] Dependencies ==" -ForegroundColor Cyan
composer install --no-interaction --prefer-dist --optimize-autoloader
if (Test-Path package-lock.json) { npm ci } else { npm install }

Write-Host "== [3/5] Build assets ==" -ForegroundColor Cyan
npm run build

Write-Host "== [4/5] Tests CI ==" -ForegroundColor Cyan
php artisan test --testsuite=Unit
# Feature tests : php artisan test

Write-Host "== [5/5] Deploy CD (Docker) ==" -ForegroundColor Cyan
docker compose up -d --build
docker compose ps
Write-Host "App: http://127.0.0.1:8088" -ForegroundColor Green
Write-Host "Pipeline local terminé." -ForegroundColor Green
