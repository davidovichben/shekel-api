<?php

// Force MySQL connection and run migrations

require __DIR__ . '/vendor/autoload.php';

// Ensure .env is set to MySQL
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $content = file_get_contents($envFile);
    
    // Remove all DB_ lines
    $lines = explode("\n", $content);
    $newLines = [];
    foreach ($lines as $line) {
        if (!preg_match("/^DB_/", trim($line))) {
            $newLines[] = $line;
        }
    }
    
    // Add MySQL config
    $newLines[] = "";
    $newLines[] = "DB_CONNECTION=mysql";
    $newLines[] = "DB_HOST=127.0.0.1";
    $newLines[] = "DB_PORT=3306";
    $newLines[] = "DB_DATABASE=holy_shekel";
    $newLines[] = "DB_USERNAME=root";
    $newLines[] = "DB_PASSWORD=";
    
    file_put_contents($envFile, implode("\n", $newLines));
    echo "✓ .env updated to use MySQL\n";
}

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "\n=== Database Setup ===\n\n";

// Verify connection
try {
    $dbName = DB::connection()->getDatabaseName();
    $driver = DB::connection()->getDriverName();
    echo "Connected to: {$dbName} (Driver: {$driver})\n";
    
    if ($driver !== 'mysql') {
        die("ERROR: Not using MySQL! Current driver: {$driver}\n");
    }
    
    // Drop migrations table if it exists
    echo "\nResetting migrations table...\n";
    try {
        DB::statement("DROP TABLE IF EXISTS migrations");
        echo "✓ Dropped migrations table\n";
    } catch (Exception $e) {
        echo "Note: " . $e->getMessage() . "\n";
    }
    
    // Run migrations
    echo "\nRunning migrations...\n";
    Artisan::call('migrate', ['--force' => true]);
    echo Artisan::output();
    
    // Run seeders
    echo "\nRunning seeders...\n";
    Artisan::call('db:seed', ['--force' => true]);
    echo Artisan::output();
    
    // Verify
    echo "\n=== Verification ===\n";
    $members = DB::table('members')->count();
    $groups = DB::table('groups')->count();
    $banks = DB::table('banks')->count();
    
    echo "Members: {$members}\n";
    echo "Groups: {$groups}\n";
    echo "Banks: {$banks}\n";
    
    if ($members > 0) {
        echo "\n✓ SUCCESS! Database populated!\n";
    } else {
        echo "\n⚠️  Database tables created but no data seeded\n";
    }
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

