@echo off
echo ========================================
echo MySQL Database Setup for holy_shekel
echo ========================================
echo.

echo Step 1: Updating .env file...
php setup-mysql.php
echo.

echo Step 2: Clearing Laravel cache...
php artisan config:clear
php artisan cache:clear
echo.

echo Step 3: Dropping migrations table (if exists)...
php artisan tinker --execute="try { DB::statement('DROP TABLE IF EXISTS migrations'); echo 'Migrations table dropped' . PHP_EOL; } catch (Exception $e) { echo 'Note: ' . $e->getMessage() . PHP_EOL; }"
echo.

echo Step 4: Running migrations...
php artisan migrate --force
echo.

echo Step 5: Running seeders...
php artisan db:seed --force
echo.

echo Step 6: Verifying data...
php artisan tinker --execute="echo 'Members: ' . DB::table('members')->count() . PHP_EOL; echo 'Groups: ' . DB::table('groups')->count() . PHP_EOL; echo 'Banks: ' . DB::table('banks')->count() . PHP_EOL;"
echo.

echo ========================================
echo Setup Complete!
echo ========================================
pause

