# Membuat paket siap unggah untuk hosting tanpa SSH/Composer (misalnya InfinityFree).
# Jalankan dari folder proyek:  powershell -ExecutionPolicy Bypass -File deploy\buat-paket.ps1
# Hasil:
#   deploy\forecash-upload.zip  -> diekstrak ke htdocs di hosting
#   deploy\forecash-database.sql -> diimpor lewat phpMyAdmin hosting
# File .env TIDAK ikut. .env produksi dibuat sendiri di hosting.

param(
    [string]$DbName = 'forecash',
    [string]$DbUser = 'root',
    [string]$MysqlDump = 'C:\xampp\mysql\bin\mysqldump.exe',
    [switch]$TanpaDatabase   # lewati ekspor database (kalau hanya kode yang berubah)
)

$ErrorActionPreference = 'Stop'
$proyek = Split-Path -Parent $PSScriptRoot
$paket  = Join-Path $PSScriptRoot 'paket'
$zip    = Join-Path $PSScriptRoot 'forecash-upload.zip'
$sql    = Join-Path $PSScriptRoot 'forecash-database.sql'

Set-Location $proyek

Write-Host '1/5 Build CSS & JS...'
if (Test-Path 'public\hot') { throw 'npm run dev masih jalan (ada public\hot). Hentikan dulu dengan Ctrl+C.' }
npm run build
if ($LASTEXITCODE -ne 0) { throw 'npm run build gagal.' }

Write-Host '2/5 Salin file proyek ke deploy\paket...'
if (Test-Path $paket) { Remove-Item $paket -Recurse -Force }
$folderDilewati = @('.git', 'node_modules', 'vendor', 'tests', 'deploy', '.vscode', '.idea',
                    (Join-Path $proyek 'storage\logs'), (Join-Path $proyek 'storage\framework'),
                    (Join-Path $proyek 'storage\app\private'), (Join-Path $proyek 'bootstrap\cache'))
$fileDilewati = @('.env', 'env.txt', '.env.backup', '.env.production', '*.log', 'database.sqlite', 'hot')
robocopy $proyek $paket /E /NFL /NDL /NJH /NJS /NP /XD $folderDilewati /XF $fileDilewati | Out-Null
if ($LASTEXITCODE -ge 8) { throw "robocopy gagal (kode $LASTEXITCODE)." }

# folder kosong yang wajib ada & bisa ditulis Laravel
foreach ($d in 'storage\logs', 'storage\framework\cache\data', 'storage\framework\sessions',
               'storage\framework\views', 'storage\app\private', 'bootstrap\cache') {
    New-Item -ItemType Directory -Force (Join-Path $paket $d) | Out-Null
    # file penanda supaya folder kosong tidak hilang saat ZIP diekstrak di hosting
    Set-Content -Path (Join-Path $paket "$d\.gitignore") -Value "*`n!.gitignore" -Encoding ascii
}
Copy-Item (Join-Path $PSScriptRoot 'htaccess-root.txt') (Join-Path $paket '.htaccess')

Write-Host '3/5 Composer install (tanpa paket dev)...'
Push-Location $paket
composer install --no-dev --optimize-autoloader --no-interaction
$hasilComposer = $LASTEXITCODE
Pop-Location
if ($hasilComposer -ne 0) { throw 'composer install gagal.' }

Write-Host '4/5 Buat ZIP...'
if (Test-Path $zip) { Remove-Item $zip -Force }
# pakai tar bawaan Windows (bukan tar dari Git Bash yang tidak bisa membuat ZIP)
& (Join-Path $env:SystemRoot 'System32\tar.exe') -a -c -f $zip -C $paket .
if ($LASTEXITCODE -ne 0) { throw 'Gagal membuat ZIP.' }

if ($TanpaDatabase) {
    Write-Host '5/5 Ekspor database dilewati.'
} else {
    Write-Host "5/5 Ekspor database '$DbName' (tekan Enter kalau user $DbUser tidak pakai kata sandi)..."
    & $MysqlDump -u $DbUser -p --single-transaction --skip-lock-tables --no-tablespaces --result-file=$sql $DbName
    if ($LASTEXITCODE -ne 0) { throw 'Ekspor database gagal.' }
}

$ukuran = '{0:N1} MB' -f ((Get-Item $zip).Length / 1MB)
Write-Host ''
Write-Host "Selesai: $zip ($ukuran)"
Write-Host "         $sql"
