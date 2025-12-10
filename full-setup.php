<?php

echo "=== Full Database Setup ===\n\n";

// Step 1: Update .env
echo "Step 1: Updating .env file...\n";
$envFile = __DIR__ . '/.env';

if (!file_exists($envFile)) {
    if (file_exists(__DIR__ . '/.env.example')) {
        copy(__DIR__ . '/.env.example', $envFile);
        echo "Created .env from .env.example\n";
    } else {
        die(".env.example not found!\n");
    }
}

$content = file_get_contents($envFile);
$lines = explode("\n", $content);
$newLines = [];
$dbConfig = [];

foreach ($lines as $line) {
    $trimmed = trim($line);
    if (preg_match("/^DB_(CONNECTION|HOST|PORT|DATABASE|USERNAME|PASSWORD)=(.+)$/", $trimmed, $matches)) {
        $dbConfig[$matches[1]] = $matches[2];
        continue; // Remove old DB config
    }
    $newLines[] = $line;
}

// Add DB config
$newLines[] = "";
$newLines[] = "# Database Configuration";
$newLines[] = "DB_CONNECTION=mysql";
$newLines[] = "DB_HOST=127.0.0.1";
$newLines[] = "DB_PORT=3306";
$newLines[] = "DB_DATABASE=holy_shekel";
$newLines[] = "DB_USERNAME=root";
$newLines[] = "DB_PASSWORD=";

file_put_contents($envFile, implode("\n", $newLines));
echo "✓ .env updated\n\n";

// Step 2: Bootstrap Laravel
echo "Step 2: Loading Laravel...\n";
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();
echo "✓ Laravel loaded\n\n";

// Step 3: Test connection
echo "Step 3: Testing MySQL connection...\n";
try {
    $pdo = DB::connection()->getPdo();
    $dbName = DB::connection()->getDatabaseName();
    echo "✓ Connected to database: {$dbName}\n";
    
    // Check if database exists
    $databases = DB::select("SHOW DATABASES");
    $dbExists = false;
    foreach ($databases as $db) {
        if ($db->Database === 'holy_shekel') {
            $dbExists = true;
            break;
        }
    }
    
    if (!$dbExists) {
        echo "⚠️  Database 'holy_shekel' does not exist!\n";
        echo "Creating database...\n";
        DB::statement("CREATE DATABASE IF NOT EXISTS holy_shekel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✓ Database created\n";
    }
    
} catch (Exception $e) {
    echo "✗ Connection failed: " . $e->getMessage() . "\n";
    echo "\nPlease check:\n";
    echo "1. MySQL server is running on localhost:3306\n";
    echo "2. MySQL username/password is correct\n";
    echo "3. Database 'holy_shekel' exists or can be created\n";
    exit(1);
}

echo "\n";

// Step 4: Run migrations
echo "Step 4: Running migrations...\n";
try {
    $exitCode = Artisan::call('migrate', ['--force' => true]);
    $output = Artisan::output();
    echo $output;
    if ($exitCode === 0) {
        echo "✓ Migrations completed\n";
    } else {
        echo "⚠️  Migrations may have issues (exit code: {$exitCode})\n";
    }
} catch (Exception $e) {
    echo "✗ Migration error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Step 5: Check tables
echo "Step 5: Checking tables...\n";
try {
    $tables = DB::select("SHOW TABLES");
    echo "Tables found: " . count($tables) . "\n";
    foreach ($tables as $table) {
        $tableName = array_values((array)$table)[0];
        echo "  - {$tableName}\n";
    }
} catch (Exception $e) {
    echo "✗ Error checking tables: " . $e->getMessage() . "\n";
}

echo "\n";

// Step 6: Run seeders
echo "Step 6: Running seeders...\n";
try {
    $exitCode = Artisan::call('db:seed', ['--force' => true]);
    $output = Artisan::output();
    echo $output;
    if ($exitCode === 0) {
        echo "✓ Seeders completed\n";
    } else {
        echo "⚠️  Seeders may have issues (exit code: {$exitCode})\n";
    }
} catch (Exception $e) {
    echo "✗ Seeder error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";

// Step 7: Verify data
echo "Step 7: Verifying data...\n";
try {
    $members = DB::table('members')->count();
    $groups = DB::table('groups')->count();
    $banks = DB::table('banks')->count();
    $users = DB::table('users')->count();
    
    echo "Members: {$members}\n";
    echo "Groups: {$groups}\n";
    echo "Banks: {$banks}\n";
    echo "Users: {$users}\n";
    
    if ($members > 0 && $groups > 0 && $banks > 0) {
        echo "\n✓ Database successfully populated!\n";
    } else {
        echo "\n⚠️  Some tables are empty\n";
    }
} catch (Exception $e) {
    echo "✗ Error verifying data: " . $e->getMessage() . "\n";
}

echo "\n=== Setup Complete ===\n";

