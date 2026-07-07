@echo off
echo ================================================================
echo   Application Java - Gestion des Utilisateurs (JavaFX + MySQL)
echo ================================================================
echo.
echo PREREQUIS :
echo   1. MySQL Server doit etre demarre
echo   2. Verifiez les parametres dans DatabaseConfig.java :
echo      - DB_HOST     = localhost
echo      - DB_PORT     = 3306
echo      - DB_NAME     = gestion_users
echo      - DB_USER     = root
echo      - DB_PASSWORD = (votre mot de passe)
echo.
echo COMPILATION VIA INTELLIJ IDEA :
echo   - Ouvrir le projet dans IntelliJ IDEA
echo   - Selectionner : Build > Build Project (Ctrl+F9)
echo   - Lancer : Run > Run 'MainApp'
echo.
echo COMPILATION VIA MAVEN (si maven est installe) :
echo   mvn clean compile
echo   mvn javafx:run
echo.
echo ================================================================
pause
