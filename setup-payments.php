<?php
/**
 * PAYMENT SETUP ASSISTANT
 * 
 * Run this in browser: http://localhost/seee/setup-payments.php
 * This helps you configure all payment methods
 */

session_start();

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        // Create or update .env file
        $config = [
            'BANK_NAME' => $_POST['bank_name'] ?? '',
            'ACCOUNT_NAME' => $_POST['account_name'] ?? '',
            'ACCOUNT_NUMBER' => $_POST['account_number'] ?? '',
            'BANK_CURRENCY' => $_POST['bank_currency'] ?? 'UGX',
            'MTN_NUMBER' => $_POST['mtn_number'] ?? '',
            'AIRTEL_NUMBER' => $_POST['airtel_number'] ?? '',
            'PAYPAL_ENABLED' => isset($_POST['paypal_enabled']) ? 'true' : 'false',
            'PAYPAL_CLIENT_ID' => $_POST['paypal_client_id'] ?? '',
            'PAYPAL_SECRET' => $_POST['paypal_secret'] ?? '',
            'PAYPAL_MODE' => $_POST['paypal_mode'] ?? 'sandbox',
            'STRIPE_ENABLED' => isset($_POST['stripe_enabled']) ? 'true' : 'false',
            'STRIPE_PUBLIC_KEY' => $_POST['stripe_public_key'] ?? '',
            'STRIPE_SECRET_KEY' => $_POST['stripe_secret_key'] ?? '',
        ];
        
        $envContent = generateEnvFile($config);
        $envPath = dirname(__DIR__) . '/.env';
        
        if (file_put_contents($envPath, $envContent)) {
            chmod($envPath, 0600);
            $_SESSION['success'] = 'Configuration saved successfully!';
        } else {
            $_SESSION['error'] = 'Failed to save configuration. Check file permissions.';
        }
    }
    
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Read current .env if exists
$currentConfig = [];
$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && $line[0] !== '#') {
            list($key, $value) = explode('=', $line, 2);
            $currentConfig[trim($key)] = trim($value, '"');
        }
    }
}

function generateEnvFile($config) {
    $env = "# === DATABASE CONFIGURATION ===\n";
    $env .= "DB_HOST=localhost\n";
    $env .= "DB_USER=root\n";
    $env .= "DB_PASS=\n";
    $env .= "DB_NAME=readers_haven\n\n";
    
    $env .= "# === BANK TRANSFER CONFIGURATION ===\n";
    $env .= "BANK_NAME=\"{$config['BANK_NAME']}\"\n";
    $env .= "ACCOUNT_NAME=\"{$config['ACCOUNT_NAME']}\"\n";
    $env .= "ACCOUNT_NUMBER=\"{$config['ACCOUNT_NUMBER']}\"\n";
    $env .= "BANK_CURRENCY={$config['BANK_CURRENCY']}\n\n";
    
    $env .= "# === MOBILE MONEY CONFIGURATION ===\n";
    $env .= "MTN_NUMBER=\"{$config['MTN_NUMBER']}\"\n";
    $env .= "AIRTEL_NUMBER=\"{$config['AIRTEL_NUMBER']}\"\n\n";
    
    $env .= "# === PAYPAL CONFIGURATION ===\n";
    $env .= "PAYPAL_ENABLED={$config['PAYPAL_ENABLED']}\n";
    $env .= "PAYPAL_CLIENT_ID=\"{$config['PAYPAL_CLIENT_ID']}\"\n";
    $env .= "PAYPAL_SECRET=\"{$config['PAYPAL_SECRET']}\"\n";
    $env .= "PAYPAL_MODE={$config['PAYPAL_MODE']}\n\n";
    
    $env .= "# === STRIPE CONFIGURATION ===\n";
    $env .= "STRIPE_ENABLED={$config['STRIPE_ENABLED']}\n";
    $env .= "STRIPE_PUBLIC_KEY=\"{$config['STRIPE_PUBLIC_KEY']}\"\n";
    $env .= "STRIPE_SECRET_KEY=\"{$config['STRIPE_SECRET_KEY']}\"\n";
    
    return $env;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Configuration Setup</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            padding: 40px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .section-icon {
            font-size: 24px;
            margin-right: 10px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="url"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="url"]:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
        }
        
        input[type="checkbox"] {
            margin-right: 10px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            justify-content: center;
        }
        
        button {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #e9ecef;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #dee2e6;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #0c5aa0;
        }
        
        .link {
            color: #667eea;
            text-decoration: none;
        }
        
        .link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>💳 Payment Configuration Setup</h1>
        <p class="subtitle">Configure your payment methods to start accepting payments</p>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                ✓ <?php echo htmlspecialchars($_SESSION['success']); ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                ✗ <?php echo htmlspecialchars($_SESSION['error']); ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="action" value="save">
            
            <!-- BANK TRANSFER SECTION -->
            <div class="section">
                <div class="section-title">
                    <span class="section-icon">🏦</span>
                    Bank Transfer
                </div>
                <div class="info-box">
                    Customers will see these details during checkout and transfer money to your account.
                </div>
                
                <div class="form-group">
                    <label for="bank_name">Bank Name *</label>
                    <input type="text" id="bank_name" name="bank_name" value="<?php echo htmlspecialchars($currentConfig['BANK_NAME'] ?? 'Stanbic Bank Uganda'); ?>" placeholder="e.g., Stanbic Bank Uganda">
                    <div class="help-text">The name of your bank</div>
                </div>
                
                <div class="form-group">
                    <label for="account_name">Account Name *</label>
                    <input type="text" id="account_name" name="account_name" value="<?php echo htmlspecialchars($currentConfig['ACCOUNT_NAME'] ?? ''); ?>" placeholder="Your business name">
                    <div class="help-text">Account holder name as shown in the bank</div>
                </div>
                
                <div class="form-group">
                    <label for="account_number">Account Number *</label>
                    <input type="text" id="account_number" name="account_number" value="<?php echo htmlspecialchars($currentConfig['ACCOUNT_NUMBER'] ?? ''); ?>" placeholder="e.g., 9100123456789">
                    <div class="help-text">Your bank account number</div>
                </div>
            </div>
            
            <!-- MOBILE MONEY SECTION -->
            <div class="section">
                <div class="section-title">
                    <span class="section-icon">📱</span>
                    Mobile Money
                </div>
                <div class="info-box">
                    Add your MTN and Airtel money numbers for customers to send payments
                </div>
                
                <div class="form-group">
                    <label for="mtn_number">MTN Mobile Money Number</label>
                    <input type="text" id="mtn_number" name="mtn_number" value="<?php echo htmlspecialchars($currentConfig['MTN_NUMBER'] ?? ''); ?>" placeholder="+256700000000">
                    <div class="help-text">Leave empty to disable MTN Money option</div>
                </div>
                
                <div class="form-group">
                    <label for="airtel_number">Airtel Money Number</label>
                    <input type="text" id="airtel_number" name="airtel_number" value="<?php echo htmlspecialchars($currentConfig['AIRTEL_NUMBER'] ?? ''); ?>" placeholder="+256700000001">
                    <div class="help-text">Leave empty to disable Airtel Money option</div>
                </div>
            </div>
            
            <!-- PAYPAL SECTION -->
            <div class="section">
                <div class="section-title">
                    <span class="section-icon">🅿️</span>
                    PayPal
                </div>
                <div class="info-box">
                    <strong>✓ Works with Personal Account - No Business Registration Required!</strong><br>
                    <a href="https://developer.paypal.com" class="link" target="_blank">Get credentials from PayPal Developer Dashboard →</a>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="paypal_enabled" name="paypal_enabled" <?php echo ($currentConfig['PAYPAL_ENABLED'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                        <label for="paypal_enabled" style="margin-bottom: 0; font-weight: 600;">Enable PayPal Payments</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="paypal_client_id">PayPal Client ID</label>
                    <input type="text" id="paypal_client_id" name="paypal_client_id" value="<?php echo htmlspecialchars($currentConfig['PAYPAL_CLIENT_ID'] ?? ''); ?>" placeholder="Ad...">
                    <div class="help-text">Get from Apps & Credentials → Sandbox/Live</div>
                </div>
                
                <div class="form-group">
                    <label for="paypal_secret">PayPal Secret</label>
                    <input type="text" id="paypal_secret" name="paypal_secret" value="<?php echo htmlspecialchars($currentConfig['PAYPAL_SECRET'] ?? ''); ?>" placeholder="EE...">
                    <div class="help-text">Get from Apps & Credentials → Sandbox/Live</div>
                </div>
                
                <div class="form-group">
                    <label for="paypal_mode">PayPal Mode</label>
                    <select id="paypal_mode" name="paypal_mode">
                        <option value="sandbox" <?php echo ($currentConfig['PAYPAL_MODE'] ?? 'sandbox') === 'sandbox' ? 'selected' : ''; ?>>🧪 Sandbox (Test Mode)</option>
                        <option value="live" <?php echo ($currentConfig['PAYPAL_MODE'] ?? 'sandbox') === 'live' ? 'selected' : ''; ?>>✓ Live (Accept Real Payments)</option>
                    </select>
                    <div class="help-text">Use Sandbox to test first, switch to Live when ready</div>
                </div>
            </div>
            
            <!-- STRIPE SECTION -->
            <div class="section">
                <div class="section-title">
                    <span class="section-icon">💳</span>
                    Stripe
                </div>
                <div class="info-box">
                    <strong>✓ No Business Registration - Instant Setup!</strong><br>
                    <a href="https://stripe.com/register" class="link" target="_blank">Sign up for Stripe →</a>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="stripe_enabled" name="stripe_enabled" <?php echo ($currentConfig['STRIPE_ENABLED'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                        <label for="stripe_enabled" style="margin-bottom: 0; font-weight: 600;">Enable Stripe Card Payments</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="stripe_public_key">Stripe Publishable Key</label>
                    <input type="text" id="stripe_public_key" name="stripe_public_key" value="<?php echo htmlspecialchars($currentConfig['STRIPE_PUBLIC_KEY'] ?? ''); ?>" placeholder="pk_test_...">
                    <div class="help-text">Get from Developers → API Keys</div>
                </div>
                
                <div class="form-group">
                    <label for="stripe_secret_key">Stripe Secret Key</label>
                    <input type="text" id="stripe_secret_key" name="stripe_secret_key" value="<?php echo htmlspecialchars($currentConfig['STRIPE_SECRET_KEY'] ?? ''); ?>" placeholder="sk_test_...">
                    <div class="help-text">⚠️ Keep this secret! Never share it!</div>
                </div>
            </div>
            
            <!-- BUTTONS -->
            <div class="button-group">
                <button type="button" class="btn-secondary" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn-primary">💾 Save Configuration</button>
            </div>
        </form>
    </div>
</body>
</html>
