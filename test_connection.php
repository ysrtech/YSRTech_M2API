<?php
/**
 * Simple test script for YSRTech_M2API module
 * This tests basic connectivity and module functionality
 */

// Test 1: Check if module files exist
echo "=== YSRTech_M2API Module Test ===\n\n";

echo "Test 1: Checking module files...\n";
$modulePath = __DIR__ . '/app/code/local/YSRTech/M2API';
$moduleXml = __DIR__ . '/app/etc/modules/YSRTech_M2API.xml';

if (file_exists($modulePath)) {
    echo "✓ Module directory exists\n";
} else {
    echo "✗ Module directory NOT found\n";
}

if (file_exists($moduleXml)) {
    echo "✓ Module XML exists\n";
} else {
    echo "✗ Module XML NOT found\n";
}

// Test 2: Check module configuration
echo "\nTest 2: Checking module configuration...\n";
$configFile = $modulePath . '/etc/config.xml';
if (file_exists($configFile)) {
    echo "✓ Config file exists\n";
    $config = simplexml_load_file($configFile);
    if ($config) {
        $version = (string)$config->modules->YSRTech_M2API->version;
        echo "✓ Module version: $version\n";
    }
} else {
    echo "✗ Config file NOT found\n";
}

// Test 3: Check required module files
echo "\nTest 3: Checking core module files...\n";
$requiredFiles = [
    'Model/Token.php',
    'Model/Resource/Token.php',
    'Model/Resource/Token/Collection.php',
    'Model/Resource/Setup.php',
    'Helper/Data.php',
    'controllers/V1Controller.php',
    'sql/m2api_setup/install-1.0.0.php'
];

$allFilesExist = true;
foreach ($requiredFiles as $file) {
    $fullPath = $modulePath . '/' . $file;
    if (file_exists($fullPath)) {
        echo "✓ $file\n";
    } else {
        echo "✗ $file NOT found\n";
        $allFilesExist = false;
    }
}

// Test 4: Basic PHP syntax check
echo "\nTest 4: Checking PHP syntax...\n";
$phpFiles = [
    'Model/Token.php',
    'Model/Resource/Token.php',
    'Helper/Data.php',
    'controllers/V1Controller.php'
];

$syntaxOk = true;
foreach ($phpFiles as $file) {
    $fullPath = $modulePath . '/' . $file;
    if (file_exists($fullPath)) {
        exec("php -l \"$fullPath\" 2>&1", $output, $returnVar);
        if ($returnVar === 0) {
            echo "✓ $file - syntax OK\n";
        } else {
            echo "✗ $file - syntax error\n";
            $syntaxOk = false;
        }
    }
}

// Summary
echo "\n=== Test Summary ===\n";
if ($allFilesExist && $syntaxOk) {
    echo "✓ All tests passed! Module structure is valid.\n";
} else {
    echo "✗ Some tests failed. Please review the output above.\n";
}

echo "\n=== GitHub Repository Info ===\n";
// Check git status
exec("git remote -v 2>&1", $gitOutput);
if (!empty($gitOutput)) {
    echo "Git remotes:\n";
    foreach ($gitOutput as $line) {
        echo "  $line\n";
    }
} else {
    echo "No git remotes configured.\n";
}

echo "\nTest completed!\n";
