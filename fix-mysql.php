<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting MySQL setup...\n";

// Step 1: Update .env
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    if (file_exists(__DIR__ . '/.env.example')) {
        copy(__DIR__ . '/.env.example', $envFile);
    }
}

$content = file_get_contents($envFile);
$lines = explode("\n", $content);
$newLines = [];
foreach ($lines as $line) {
    if (!preg_match("/^DB_/", trim($line))) {
        $newLines[] = $line;
    }
}
$newLines[] = "";
$newLines[] = "DB_CONNECTION=mysql";
$newLines[] = "DB_HOST=127.0.0.1";
$newLines[] = "DB_PORT=3306";
$newLines[] = "DB_DATABASE=holy_shekel";
$newLines[] = "DB_USERNAME=root";
$newLines[] = "DB_PASSWORD=";
file_put_contents($envFile, implode("\n", $newLines));
echo "✓ .env configured for MySQL\n";

// Step 2: Bootstrap Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "✓ Laravel bootstrapped\n";

// Step 3: Connect and verify
try {
    $pdo = DB::connection()->getPdo();
    $dbName = DB::connection()->getDatabaseName();
    $driver = DB::connection()->getDriverName();
    
    echo "✓ Connected to: {$dbName} (Driver: {$driver})\n";
    
    if ($driver !== 'mysql') {
        die("ERROR: Not using MySQL!\n");
    }
    
    // Step 4: Drop migrations table
    echo "\nDropping migrations table...\n";
    DB::statement("DROP TABLE IF EXISTS migrations");
    echo "✓ Migrations table dropped\n";
    
    // Step 5: Run migrations
    echo "\nRunning migrations...\n";
    $exitCode = Artisan::call('migrate', ['--force' => true]);
    $output = Artisan::output();
    echo $output;
    echo "✓ Migrations completed (exit code: {$exitCode})\n";
    
    // Step 6: Run seeders
    echo "\nRunning seeders...\n";
    $exitCode = Artisan::call('db:seed', ['--force' => true]);
    $output = Artisan::output();
    echo $output;
    echo "✓ Seeders completed (exit code: {$exitCode})\n";
    
    // Step 7: Verify
    echo "\n=== Verification ===\n";
    $members = DB::table('members')->count();
    $groups = DB::table('groups')->count();
    $banks = DB::table('banks')->count();
    
    echo "Members: {$members}\n";
    echo "Groups: {$groups}\n";
    echo "Banks: {$banks}\n";
    
    if ($members > 0) {
        echo "\n✓ SUCCESS! Database is populated!\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

