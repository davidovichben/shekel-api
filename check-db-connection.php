<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Database Connection Check ===\n\n";

// Check .env file
echo "Reading .env file:\n";
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    preg_match('/^DB_CONNECTION=(.+)$/m', $envContent, $matches);
    echo "DB_CONNECTION from .env: " . ($matches[1] ?? 'NOT FOUND') . "\n";
} else {
    echo ".env file NOT FOUND!\n";
}

echo "\nLaravel Configuration:\n";
echo "Default connection: " . config('database.default') . "\n";
echo "MySQL database: " . config('database.connections.mysql.database') . "\n";
echo "MySQL host: " . config('database.connections.mysql.host') . "\n";

echo "\nTesting connection:\n";
try {
    $pdo = DB::connection()->getPdo();
    $dbName = DB::connection()->getDatabaseName();
    echo "✓ Connected to: {$dbName}\n";
    echo "✓ Driver: " . DB::connection()->getDriverName() . "\n";
    
    // Check migrations table
    echo "\nChecking migrations table:\n";
    try {
        $migrations = DB::table('migrations')->get();
        echo "Migrations table exists with " . count($migrations) . " records\n";
        if (count($migrations) > 0) {
            echo "First few migrations:\n";
            foreach ($migrations->take(5) as $migration) {
                echo "  - {$migration->migration}\n";
            }
        }
    } catch (Exception $e) {
        echo "Migrations table does NOT exist: " . $e->getMessage() . "\n";
    }
    
    // List all tables
    echo "\nAll tables in database:\n";
    $tables = DB::select("SHOW TABLES");
    foreach ($tables as $table) {
        $tableName = array_values((array)$table)[0];
        echo "  - {$tableName}\n";
    }
    
} catch (Exception $e) {
    echo "✗ Connection failed: " . $e->getMessage() . "\n";
}

echo "\n";

