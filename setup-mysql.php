<?php

$envFile = __DIR__ . '/.env';

// Read .env file
if (!file_exists($envFile)) {
    die(".env file not found!\n");
}

$content = file_get_contents($envFile);
$lines = explode("\n", $content);
$newLines = [];
$dbConfigAdded = false;

// Process each line
foreach ($lines as $line) {
    $trimmed = trim($line);
    
    // Skip existing DB_ configuration lines
    if (preg_match("/^DB_(CONNECTION|HOST|PORT|DATABASE|USERNAME|PASSWORD)=/", $trimmed)) {
        continue;
    }
    
    $newLines[] = $line;
    
    // Add DB config after APP_URL or at end of file
    if (!$dbConfigAdded && (preg_match("/^APP_URL=/", $trimmed) || empty($trimmed))) {
        $newLines[] = "";
        $newLines[] = "# Database Configuration";
        $newLines[] = "DB_CONNECTION=mysql";
        $newLines[] = "DB_HOST=127.0.0.1";
        $newLines[] = "DB_PORT=3306";
        $newLines[] = "DB_DATABASE=holy_shekel";
        $newLines[] = "DB_USERNAME=root";
        $newLines[] = "DB_PASSWORD=";
        $dbConfigAdded = true;
    }
}

// If we didn't add it yet, add at the end
if (!$dbConfigAdded) {
    $newLines[] = "";
    $newLines[] = "# Database Configuration";
    $newLines[] = "DB_CONNECTION=mysql";
    $newLines[] = "DB_HOST=127.0.0.1";
    $newLines[] = "DB_PORT=3306";
    $newLines[] = "DB_DATABASE=holy_shekel";
    $newLines[] = "DB_USERNAME=root";
    $newLines[] = "DB_PASSWORD=";
}

file_put_contents($envFile, implode("\n", $newLines));

echo "✓ .env file updated with MySQL configuration\n";
echo "DB_CONNECTION=mysql\n";
echo "DB_DATABASE=holy_shekel\n";
echo "DB_HOST=127.0.0.1\n";
echo "DB_PORT=3306\n";
echo "DB_USERNAME=root\n";
echo "DB_PASSWORD=(empty)\n\n";
echo "Note: If your MySQL has a password, update DB_PASSWORD in .env\n";

