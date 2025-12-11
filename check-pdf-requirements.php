<?php

/**
 * Check PDF Requirements for mPDF
 * Run: php check-pdf-requirements.php
 */

echo "=== PDF Requirements Check ===\n\n";

// Check PHP extensions
$requiredExtensions = ['mbstring', 'gd', 'zip', 'xml', 'iconv'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
        echo "❌ Missing extension: {$ext}\n";
    } else {
        echo "✅ Extension loaded: {$ext}\n";
    }
}

echo "\n";

// Check if packages are installed
echo "=== Package Check ===\n";
$composerLock = json_decode(file_get_contents('composer.lock'), true);
$packages = [];

if (isset($composerLock['packages'])) {
    foreach ($composerLock['packages'] as $package) {
        $packages[$package['name']] = $package['version'];
    }
}

$requiredPackages = [
    'maatwebsite/excel' => 'Laravel Excel',
    'mpdf/mpdf' => 'mPDF Library'
];

foreach ($requiredPackages as $packageName => $packageLabel) {
    if (isset($packages[$packageName])) {
        echo "✅ {$packageLabel}: {$packages[$packageName]}\n";
    } else {
        echo "❌ Missing: {$packageLabel}\n";
    }
}

echo "\n";

// Check storage permissions
echo "=== Storage Permissions ===\n";
$storagePaths = [
    'storage/framework/cache' => 'Cache directory',
    'storage/app' => 'App storage'
];

foreach ($storagePaths as $path => $label) {
    if (is_dir($path)) {
        if (is_writable($path)) {
            echo "✅ {$label}: Writable\n";
        } else {
            echo "❌ {$label}: Not writable (chmod 775 recommended)\n";
        }
    } else {
        echo "❌ {$label}: Directory does not exist\n";
    }
}

echo "\n";

// Check font directory
echo "=== Font Directory ===\n";
$fontDir = 'storage/fonts';
if (is_dir($fontDir)) {
    echo "✅ Font directory exists: {$fontDir}\n";
} else {
    echo "⚠️  Font directory does not exist (will be created automatically if needed)\n";
}

echo "\n";

// Summary
if (empty($missingExtensions)) {
    echo "✅ All required PHP extensions are loaded!\n";
} else {
    echo "❌ Missing extensions. Install them:\n";
    foreach ($missingExtensions as $ext) {
        echo "   - {$ext}\n";
    }
}

echo "\n=== Check Complete ===\n";

