# Quick Fix: Migrate to MySQL Database

## The Issue
Laravel is either:
1. Still using SQLite instead of MySQL
2. Has a migrations table that thinks migrations are already run

## Quick Solution

Run this single command which will:
- Drop all tables
- Re-run all migrations on MySQL
- Seed all data

```bash
php artisan migrate:fresh --seed --force
```

## If That Doesn't Work

### Step 1: Verify .env is set to MySQL

Check your `.env` file has these lines:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=holy_shekel
DB_USERNAME=root
DB_PASSWORD=
```

### Step 2: Clear Laravel cache
```bash
php artisan config:clear
php artisan cache:clear
```

### Step 3: Drop migrations table manually

Open MySQL client or phpMyAdmin and run:
```sql
USE holy_shekel;
DROP TABLE IF EXISTS migrations;
```

### Step 4: Run migrations
```bash
php artisan migrate --force
php artisan db:seed --force
```

## Verify It Worked

Check in MySQL:
```sql
USE holy_shekel;
SHOW TABLES;
SELECT COUNT(*) FROM members;
SELECT COUNT(*) FROM groups;
SELECT COUNT(*) FROM banks;
```

Or use Laravel:
```bash
php artisan tinker
```

Then:
```php
DB::connection()->getDatabaseName()  // Should be "holy_shekel"
DB::connection()->getDriverName()    // Should be "mysql"
App\Models\Member::count()            // Should be 300
```

## Alternative: Use the Batch File

I've created `setup-mysql.bat` - just double-click it or run:
```bash
setup-mysql.bat
```

