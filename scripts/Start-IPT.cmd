@echo off
"%~dp0..\mobile\node_modules\node\bin\node.exe" "%~dp0start.cjs"
if errorlevel 1 pause
