# Reset Migrations for MySQL

## The Problem
Laravel thinks migrations are already run, so `php artisan migrate` says "Nothing to migrate". This happens when:
1. Migrations were run on SQLite
2. Migrations table exists in MySQL with old records

## Solution: Reset Migrations Table

### Option 1: Drop and Re-run (Recommended)

Run this command to drop the migrations table and re-run everything:

```bash
php artisan migrate:fresh --seed
```

This will:
- Drop all tables
- Re-run all migrations
- Run all seeders

### Option 2: Manually Reset Migrations Table

If you want to keep existing data but reset migrations:

```bash
php artisan tinker
```

Then in tinker:
```php
// Drop migrations table
DB::statement("DROP TABLE IF EXISTS migrations");

// Exit tinker
exit
```

Then run:
```bash
php artisan migrate
php artisan db:seed
```

### Option 3: Use the Fix Script

I've created `fix-mysql.php` - run it:

```bash
php fix-mysql.php
```

## Verify It's Using MySQL

Check your connection:

```bash
php artisan tinker
```

Then:
```php
DB::connection()->getDatabaseName()  // Should show "holy_shekel"
DB::connection()->getDriverName()    // Should show "mysql"
```

## Check .env File

Make sure your `.env` has:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=holy_shekel
DB_USERNAME=root
DB_PASSWORD=
```

Then clear config cache:
```bash
php artisan config:clear
```

