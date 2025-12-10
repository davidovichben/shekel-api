<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Resetting Migrations ===\n\n";

try {
    $dbName = DB::connection()->getDatabaseName();
    $driver = DB::connection()->getDriverName();
    
    echo "Connected to: {$dbName}\n";
    echo "Driver: {$driver}\n\n";
    
    if ($driver !== 'mysql') {
        echo "⚠️  WARNING: Not connected to MySQL! Current driver: {$driver}\n";
        echo "Please check your .env file - DB_CONNECTION should be 'mysql'\n";
        exit(1);
    }
    
    // Check if migrations table exists
    echo "Checking migrations table...\n";
    try {
        $migrations = DB::table('migrations')->get();
        $count = count($migrations);
        echo "Found {$count} migration records\n";
        
        if ($count > 0) {
            echo "\nDeleting migration records...\n";
            DB::table('migrations')->truncate();
            echo "✓ All migration records deleted\n";
        } else {
            echo "Migrations table is empty\n";
        }
    } catch (Exception $e) {
        echo "Migrations table does not exist (this is OK for fresh setup)\n";
    }
    
    echo "\n✓ Migrations table is now empty\n";
    echo "You can now run: php artisan migrate\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

