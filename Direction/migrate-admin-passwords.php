<?php
/**
 * ADMIN USER MIGRATION SCRIPT
 * Run this once to migrate existing admin users to secure password hashing
 * 
 * Usage: 
 * 1. Copy this file to your project root temporarily
 * 2. Access via browser: http://localhost:8080/seee/migrate-admin-passwords.php
 * 3. Delete this file after running
 */

require_once 'config/database.php';
require_once 'api/includes/auth.php';

// Simple security check - only run once
$migrationFile = __DIR__ . '/.migration_complete';
if (file_exists($migrationFile)) {
    die('Migration already completed. Delete the .migration_complete file to run again.');
}

echo "<h1>Admin User Password Migration</h1>";
echo "<p>Converting admin passwords to bcrypt hashing...</p>";

try {
    // Get all admin users
    $query = "SELECT id, username, password_hash FROM admin_users";
    $admins = getRows($query);
    
    if (empty($admins)) {
        echo "<p style='color:red;'>No admin users found!</p>";
        die();
    }
    
    $migrated = 0;
    $alreadySecure = 0;
    $errors = [];
    
    foreach ($admins as $admin) {
        $currentHash = $admin['password_hash'];
        
        // Check if already hashed with bcrypt
        if (substr($currentHash, 0, 4) === '$2y$' || substr($currentHash, 0, 4) === '$2a$' || substr($currentHash, 0, 4) === '$2b$') {
            echo "<p>✓ Admin '{$admin['username']}' already using bcrypt hash</p>";
            $alreadySecure++;
            continue;
        }
        
        // This is the old plain password hash or plain password
        // You need to manually reset these
        echo "<p style='color:orange;'>⚠ Admin '{$admin['username']}' needs manual password reset</p>";
        $errors[] = $admin['username'];
    }
    
    echo "<h2>Migration Summary</h2>";
    echo "<p>✓ Already Secure: {$alreadySecure}</p>";
    echo "<p>⚠ Need Manual Reset: " . count($errors) . "</p>";
    
    if (!empty($errors)) {
        echo "<h3>Manual Password Reset Required For:</h3>";
        echo "<ul>";
        foreach ($errors as $username) {
            echo "<li>{$username}</li>";
        }
        echo "</ul>";
        
        echo "<h3>To Reset Passwords:</h3>";
        echo "<pre>";
        echo "1. For testing only, use this script:\n\n";
        echo "&lt;?php\n";
        echo "require_once 'config/database.php';\n";
        echo "require_once 'api/includes/auth.php';\n\n";
        echo "\$username = 'admin';\n";
        echo "\$newPassword = 'NewSecurePassword123!';\n";
        echo "\$hash = password_hash(\$newPassword, PASSWORD_BCRYPT, ['cost' => 12]);\n\n";
        echo "\$query = \"UPDATE admin_users SET password_hash = ? WHERE username = ?\";\n";
        echo "executeQuery(\$query, [\$hash, \$username]);\n\n";
        echo "echo 'Password updated for ' . \$username;\n";
        echo "?&gt;\n";
        echo "</pre>";
    }
    
    // Mark migration as complete
    file_put_contents($migrationFile, date('Y-m-d H:i:s') . ' - Migration completed');
    
    echo "<p style='color:green;'><strong>Migration Complete!</strong></p>";
    echo "<p>This file should now be deleted from the server for security.</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 800px;
        margin: 50px auto;
        padding: 20px;
    }
    h1 { color: #333; }
    h2 { color: #666; border-top: 1px solid #ccc; padding-top: 20px; }
    p { line-height: 1.6; }
    pre {
        background: #f4f4f4;
        padding: 15px;
        border-radius: 5px;
        overflow-x: auto;
    }
    ul { margin: 15px 0; }
    li { margin: 8px 0; }
</style>
