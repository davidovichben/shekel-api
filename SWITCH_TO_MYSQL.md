# How to Switch from SQLite to MySQL

## Why SQLite is Currently Used

Your Laravel app defaults to SQLite because:
- **Line 19 in `config/database.php`**: `'default' => env('DB_CONNECTION', 'sqlite')`
- This means if no `.env` file exists or `DB_CONNECTION` isn't set, it uses SQLite
- SQLite is easier for development (no database server needed)

## Your Current Setup

- **Database Type**: SQLite
- **Database File**: `database/database.sqlite` (a single file)
- **Data Location**: All data is stored in that single SQLite file

## Switching to MySQL

### Step 1: Make sure MySQL is installed and running
- Install MySQL/MariaDB if you haven't already
- Or use XAMPP/WAMP which includes MySQL
- Make sure MySQL service is running

### Step 2: Create the MySQL database
```sql
CREATE DATABASE shekel_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Step 3: Update your `.env` file

Open `.env` file and change these lines:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shekel_api
DB_USERNAME=root
DB_PASSWORD=your_mysql_password
```

**Remove or comment out** the SQLite line if it exists:
```env
# DB_DATABASE=database/database.sqlite
```

### Step 4: Migrate your data (if you have existing data in SQLite)

If you have data in SQLite that you want to keep:

1. **Export from SQLite**:
   ```bash
   sqlite3 database/database.sqlite .dump > sqlite_dump.sql
   ```

2. **Convert and import to MySQL** (requires manual conversion of SQL syntax)

OR

3. **Re-run seeders** (if you're using seeders):
   ```bash
   php artisan migrate:fresh --seed
   ```

### Step 5: Run migrations
```bash
php artisan migrate
```

### Step 6: Verify it's working
```bash
php artisan tinker
# Then in tinker:
DB::connection()->getDatabaseName()
App\Models\Member::count()
```

## Differences: SQLite vs MySQL

| Feature | SQLite | MySQL |
|---------|--------|-------|
| **Storage** | Single file (`database.sqlite`) | Server-based database |
| **Setup** | No setup needed | Requires MySQL server |
| **Performance** | Good for small-medium apps | Better for large-scale apps |
| **Concurrent writes** | Limited | Better support |
| **Production** | Usually not recommended | Industry standard |
| **Viewing data** | SQLite browser tools | MySQL Workbench, phpMyAdmin, etc. |

## Recommendation

- **Development**: SQLite is fine and easier
- **Production**: Use MySQL (or PostgreSQL)
- **If you need MySQL features**: Switch to MySQL

## Need Help?

If you want me to help you switch, let me know:
1. Do you have MySQL installed?
2. Do you want to keep your existing SQLite data?
3. What's your MySQL username/password?

