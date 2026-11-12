@echo off
"%~dp0..\mobile\node_modules\node\bin\node.exe" "%~dp0mobile.cjs" %1
if errorlevel 1 pause
