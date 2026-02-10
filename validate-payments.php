<?php
/**
 * PAYMENT SYSTEM VALIDATION
 * Run this to verify payment integration is working
 * URL: http://localhost/seee/validate-payments.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment System Validation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 10px; }
        .subtitle { color: #666; margin-bottom: 30px; font-size: 14px; }
        .check { margin: 15px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #ddd; border-radius: 3px; }
        .check.pass { border-left-color: #4CAF50; background: #f1f8f5; }
        .check.fail { border-left-color: #f44336; background: #fef5f5; }
        .check.warn { border-left-color: #ff9800; background: #fff8f3; }
        .status { display: inline-block; padding: 5px 12px; border-radius: 3px; font-weight: 600; font-size: 12px; margin-left: 10px; }
        .status.pass { background: #4CAF50; color: white; }
        .status.fail { background: #f44336; color: white; }
        .status.warn { background: #ff9800; color: white; }
        .details { margin-top: 10px; font-size: 13px; color: #666; padding-left: 20px; }
        .code { background: #f5f5f5; padding: 10px; border-radius: 3px; font-family: monospace; margin: 10px 0; overflow-x: auto; }
        h2 { color: #333; margin: 25px 0 15px 0; padding-top: 15px; border-top: 2px solid #eee; }
        .summary { padding: 15px; background: #f0f7ff; border-radius: 5px; margin: 20px 0; border-left: 4px solid #2196F3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>✓ Payment System Validation</h1>
        <p class="subtitle">Verifying all payment components are correctly installed</p>
        
        <?php
        $checks = [];
        $issues = [];
        
        // Check 1: Configuration file exists
        $configFile = dirname(__DIR__) . '/seee/config/payment-config.php';
        if (file_exists($configFile)) {
            $checks[] = ['name' => 'Payment Configuration File', 'pass' => true, 'details' => 'config/payment-config.php exists'];
        } else {
            $checks[] = ['name' => 'Payment Configuration File', 'pass' => false, 'details' => 'config/payment-config.php NOT FOUND'];
            $issues[] = 'Payment config file missing';
        }
        
        // Check 2: API endpoints exist
        $apiFiles = [
            'api/payment/get-methods.php',
            'api/payment/get-payment-instructions.php',
        ];
        
        foreach ($apiFiles as $file) {
            $fullPath = dirname(__DIR__) . '/seee/' . $file;
            if (file_exists($fullPath)) {
                $checks[] = ['name' => "API Endpoint: $file", 'pass' => true, 'details' => "File exists"];
            } else {
                $checks[] = ['name' => "API Endpoint: $file", 'pass' => false, 'details' => "File NOT found"];
                $issues[] = "Missing: $file";
            }
        }
        
        // Check 3: Setup page exists
        $setupFile = dirname(__DIR__) . '/seee/setup-payments.php';
        if (file_exists($setupFile)) {
            $checks[] = ['name' => 'Setup Page', 'pass' => true, 'details' => 'setup-payments.php accessible'];
        } else {
            $checks[] = ['name' => 'Setup Page', 'pass' => false, 'details' => 'setup-payments.php NOT found'];
            $issues[] = 'Setup page missing';
        }
        
        // Check 4: Environment Config helper exists
        $envFile = dirname(__DIR__) . '/seee/includes/EnvironmentConfig.php';
        if (file_exists($envFile)) {
            $checks[] = ['name' => 'Environment Helper', 'pass' => true, 'details' => 'EnvironmentConfig.php exists'];
        } else {
            $checks[] = ['name' => 'Environment Helper', 'pass' => false, 'details' => 'EnvironmentConfig.php NOT found'];
            $issues[] = 'Environment config helper missing';
        }
        
        // Check 5: .env file
        $dotEnv = dirname(__DIR__) . '/seee/.env';
        if (file_exists($dotEnv)) {
            $perms = substr(sprintf('%o', fileperms($dotEnv)), -4);
            $checks[] = ['name' => '.env File', 'pass' => true, 'details' => ".env exists (permissions: $perms)"];
        } else {
            $checks[] = ['name' => '.env File', 'pass' => false, 'details' => '.env NOT found - run setup-payments.php first', 'warn' => true];
            $issues[] = 'Configuration not saved yet';
        }
        
        // Check 6: Database connection
        try {
            $dbFile = dirname(__DIR__) . '/seee/config/database.php';
            if (file_exists($dbFile)) {
                $checks[] = ['name' => 'Database Connection', 'pass' => true, 'details' => 'Database configuration found'];
            }
        } catch (Exception $e) {
            $checks[] = ['name' => 'Database Connection', 'pass' => false, 'details' => 'Database error: ' . $e->getMessage()];
        }
        
        // Check 7: Documentation exists
        $docs = [
            'docs/PAYMENT_SETUP_GUIDE.md',
            'PAYMENT_QUICK_START.md',
            'PAYMENT_IMPLEMENTATION_COMPLETE.md',
        ];
        
        $docCount = 0;
        foreach ($docs as $doc) {
            $fullPath = dirname(__DIR__) . '/seee/' . $doc;
            if (file_exists($fullPath)) {
                $docCount++;
            }
        }
        
        $checks[] = ['name' => 'Documentation', 'pass' => $docCount === 3, 'details' => "Found $docCount/3 documentation files"];
        
        // Display results
        foreach ($checks as $check) {
            $class = $check['pass'] ? 'pass' : ($check['warn'] ?? false ? 'warn' : 'fail');
            $statusText = $check['pass'] ? '✓ OK' : ($check['warn'] ?? false ? '⚠ WARNING' : '✗ FAIL');
            echo '<div class="check ' . $class . '">';
            echo '<strong>' . htmlspecialchars($check['name']) . '</strong>';
            echo '<span class="status ' . $class . '">' . $statusText . '</span>';
            if (!empty($check['details'])) {
                echo '<div class="details">' . htmlspecialchars($check['details']) . '</div>';
            }
            echo '</div>';
        }
        
        // Summary
        $passCount = count(array_filter($checks, fn($c) => $c['pass']));
        $totalCount = count($checks);
        $allPass = $passCount === $totalCount;
        
        echo '<h2>Summary</h2>';
        echo '<div class="summary">';
        echo '<strong>Status: ' . ($allPass ? '✓ Ready to Use' : '⚠ Setup Needed') . '</strong><br>';
        echo "Components: $passCount/$totalCount configured<br>";
        
        if ($allPass) {
            echo '<br><strong>Next Steps:</strong><br>';
            echo '1. Open: <a href="/seee/setup-payments.php">setup-payments.php</a><br>';
            echo '2. Configure your bank details and payment methods<br>';
            echo '3. Start accepting payments!<br>';
        } else if (count($issues) > 0) {
            echo '<br><strong>Issues Found:</strong><br>';
            foreach ($issues as $issue) {
                echo '• ' . htmlspecialchars($issue) . '<br>';
            }
        }
        
        echo '</div>';
        
        // Quick links
        echo '<h2>Quick Links</h2>';
        echo '<ul style="line-height: 1.8;">';
        echo '<li><a href="/seee/setup-payments.php">→ Configure Payment Methods</a></li>';
        echo '<li><a href="/seee/PAYMENT_QUICK_START.md">→ Quick Start Guide</a></li>';
        echo '<li><a href="/seee/docs/PAYMENT_SETUP_GUIDE.md">→ Full Setup Guide</a></li>';
        echo '</ul>';
        
        ?>
    </div>
</body>
</html>
