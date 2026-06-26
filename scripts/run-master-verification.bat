@echo off
REM Run master verification on all templates
REM Usage: run-master-verification.bat

set PHP_BIN=php
if exist "C:\xampp\php\php.exe" set PHP_BIN=C:\xampp\php\php.exe

echo ============================================
echo RUNNING MASTER VERIFICATION
echo ============================================
echo.

cd /d "%~dp0.."

"%PHP_BIN%" -r "require 'vendor/autoload.php'; require 'mvp/lib/master_verification_report.php'; \$report = new \WebPdfTimeSaver\Mvp\MasterVerificationReport(); \$results = \$report->generateMasterReport(); echo 'Master report generated!' . PHP_EOL; echo 'HTML: ' . \$results['html_path'] . PHP_EOL; echo 'JSON: ' . \$results['json_path'] . PHP_EOL; echo PHP_EOL; echo 'Summary:' . PHP_EOL; echo '  Total: ' . \$results['results']['summary']['total'] . PHP_EOL; echo '  Passed: ' . \$results['results']['summary']['passed'] . PHP_EOL; echo '  Failed: ' . \$results['results']['summary']['failed'] . PHP_EOL;"

echo.
echo Opening master report...
start "" "output\verification\master_report_*.html"

