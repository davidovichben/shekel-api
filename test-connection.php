<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing MySQL Connection ===\n\n";

// Show configuration
echo "DB_CONNECTION: " . config('database.default') . "\n";
echo "DB_HOST: " . config('database.connections.mysql.host') . "\n";
echo "DB_PORT: " . config('database.connections.mysql.port') . "\n";
echo "DB_DATABASE: " . config('database.connections.mysql.database') . "\n";
echo "DB_USERNAME: " . config('database.connections.mysql.username') . "\n";
echo "DB_PASSWORD: " . (config('database.connections.mysql.password') ? '***' : '(empty)') . "\n\n";

// Test connection
try {
    $pdo = DB::connection()->getPdo();
    echo "✓ Connection successful!\n";
    echo "✓ Connected to: " . DB::connection()->getDatabaseName() . "\n\n";
    
    // Check tables
    $tables = DB::select("SHOW TABLES");
    echo "=== Tables in database ===\n";
    if (empty($tables)) {
        echo "No tables found!\n";
    } else {
        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];
            $count = DB::table($tableName)->count();
            echo "- {$tableName}: {$count} rows\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Connection FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nPlease check:\n";
    echo "1. MySQL server is running\n";
    echo "2. Database 'holy_shekel' exists\n";
    echo "3. Username/password in .env is correct\n";
}

echo "\n";

