<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "=== Database Verification ===\n\n";

try {
    $dbName = DB::connection()->getDatabaseName();
    echo "Database: {$dbName}\n";
    echo "Connection: " . config('database.default') . "\n\n";
    
    // Check if tables exist
    $tables = DB::select("SHOW TABLES");
    echo "Tables found: " . count($tables) . "\n";
    
    if (empty($tables)) {
        echo "\n⚠️  NO TABLES FOUND!\n";
        echo "Run: php artisan migrate\n";
    } else {
        echo "\n=== Table Counts ===\n";
        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];
            try {
                $count = DB::table($tableName)->count();
                echo "{$tableName}: {$count} rows\n";
            } catch (Exception $e) {
                echo "{$tableName}: Error - {$e->getMessage()}\n";
            }
        }
        
        // Check specific tables
        echo "\n=== Key Tables ===\n";
        $keyTables = ['members', 'groups', 'banks', 'users'];
        foreach ($keyTables as $table) {
            try {
                $exists = DB::select("SHOW TABLES LIKE '{$table}'");
                if (!empty($exists)) {
                    $count = DB::table($table)->count();
                    echo "✓ {$table}: {$count} rows\n";
                } else {
                    echo "✗ {$table}: Table does not exist\n";
                }
            } catch (Exception $e) {
                echo "✗ {$table}: Error\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Check MySQL is running\n";
    echo "2. Check .env file has correct DB settings\n";
    echo "3. Check database 'holy_shekel' exists\n";
    echo "4. Check MySQL username/password\n";
}

echo "\n";

