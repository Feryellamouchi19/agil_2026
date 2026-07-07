@echo off
echo Starting JavaFX User Management Application...
call mvn javafx:run
if %errorlevel% neq 0 (
    echo.
    echo Failed to start the application. Make sure Maven is installed and added to your system PATH.
    echo If you are using an IDE, you can run com.userapp.MainApp directly.
    pause
)
