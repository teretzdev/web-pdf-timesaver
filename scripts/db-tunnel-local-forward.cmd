@echo off
REM Local forward: YOUR PC 127.0.0.1:3307 -> SSH server 127.0.0.1:3306 (MySQL on desktopmasters.com).
REM Then set app env: DB_HOST=127.0.0.1  DB_PORT=3307  DB_NAME=LawDocumentManager.com
REM Key: OpenSSH .key (try this first). If plink errors, use the .ppk in plink -i instead.

set "ROOT=%~dp0.."
set "DS=%ROOT%\DESIGN SPECS"
set "PLINK=%DS%\plink.exe"
set "KEY=%DS%\jjames at DesktopMasters.com.ed25519.key"

if not exist "%PLINK%" (
  echo plink.exe not found at "%PLINK%"
  exit /b 1
)
if not exist "%KEY%" (
  set "KEY=%DS%\jjames at DesktopMasters.com.ed25519.ppk"
)
if not exist "%KEY%" (
  echo No key found in DESIGN SPECS ^(.key or .ppk^)
  exit /b 1
)

echo Starting local forward: 127.0.0.1:3307 -^> remote 127.0.0.1:3306  (SSH desktopmasters.com:2211^)
echo Use in PHP: DB_HOST=127.0.0.1  DB_PORT=3307
"%PLINK%" -batch -N -i "%KEY%" jjames@desktopmasters.com -P 2211 -L 127.0.0.1:3307:127.0.0.1:3306
echo Tunnel exited with code %ERRORLEVEL%
pause
