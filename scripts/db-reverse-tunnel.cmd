@echo off
REM Optional REVERSE tunnel (server listens on :3306, forwards to your PC). Most dev uses
REM   scripts\db-tunnel-local-forward.cmd  (local :3307 -> server MySQL) instead.
REM This script: remote 0.0.0.0:3306 -> this PC 127.0.0.1:3306. Run local MySQL on 3306 first.

set "ROOT=%~dp0.."
set "DS=%ROOT%\DESIGN SPECS"
set "PLINK=%DS%\plink.exe"
set "KEY=%DS%\jjames at DesktopMasters.com.ed25519.ppk"

if not exist "%PLINK%" (
  echo plink.exe not found at "%PLINK%"
  exit /b 1
)
if not exist "%KEY%" (
  echo Key not found at "%KEY%"
  exit /b 1
)

echo Starting reverse tunnel: remote :3306 -^> local 127.0.0.1:3306 ...
"%PLINK%" -batch -N -i "%KEY%" jjames@desktopmasters.com -P 2211 -R 0.0.0.0:3306:127.0.0.1:3306
echo Tunnel exited with code %ERRORLEVEL%
pause
