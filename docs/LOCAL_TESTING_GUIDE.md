# 🧪 Local Testing with XAMPP & MySQL Workbench

## Quick Setup (5 Minutes)

### Step 1: Start XAMPP
1. Open **XAMPP Control Panel**
2. Click **Start** for:
   - ✅ Apache (web server)
   - ✅ MySQL (database)
3. Verify both show **green** status

### Step 2: Import Database

**Option A: Using MySQL Workbench (Recommended)**
1. Open **MySQL Workbench**
2. Click **Local instance MySQL80** (or your connection)
3. Default credentials: `root` / (no password) or `root` / `root`
4. Click **Server** → **Data Import**
5. Select **Import from Self-Contained File**
6. Browse to: `D:\using\hp\IDEA_TO_TRY\AS_developer\the_7_day_plan\seee\database\database_schema.sql`
7. Under **Default Target Schema**, click **New...** and create: `readers_haven`
8. Click **Start Import**
9. Wait for success message ✅
10. Refresh schemas - you should see `readers_haven` with 9 tables

**Option B: Using phpMyAdmin**
1. Go to http://localhost/phpmyadmin
2. Click **New** in left sidebar
3. Create database: `readers_haven`
4. Click on `readers_haven` database
5. Click **Import** tab
6. Choose file: `database_schema.sql`
7. Click **Go**
8. Verify 9 tables created ✅

### Step 3: Update Database Configuration

Edit: `seee/config/database.php`

```php
<?php
// Local XAMPP Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');           // XAMPP default
define('DB_PASS', '');               // XAMPP default (empty)
define('DB_NAME', 'readers_haven');
define('DB_CHARSET', 'utf8mb4');

// ENABLE debug mode for local testing
define('DEBUG_MODE', true);  // ← Set to TRUE

// Rest of the file stays the same...
```

**Save the file!**

### Step 4: Move Files to XAMPP

**Copy your entire `seee/` folder to XAMPP's web directory:**

```powershell
# PowerShell command:
xcopy "D:\using\hp\IDEA_TO_TRY\AS_developer\the_7_day_plan\seee" "C:\xampp\htdocs\seee\" /E /I /Y
```

Or **manually**:
1. Open folder: `C:\xampp\htdocs\`
2. Create new folder: `seee`
3. Copy ALL files from your project's `seee/` folder into `C:\xampp\htdocs\seee\`

### Step 5: Test Database Connection

Open browser: http://localhost/seee/api/get-products.php

**Expected Result:**
```json
{
  "success": true,
  "data": [
    {
      "id": "1",
      "name": "Gratitude Journal",
      "description": "...",
      "price": "45000",
      "emoji": "📗",
      "quantity_available": "50"
    },
    // ... 2 more products
  ]
}
```

**If you see this ✅** → Database is working!

**If error** → Check:
- XAMPP MySQL is running (green)
- Database `readers_haven` exists
- `config/database.php` credentials match
- Files are in `C:\xampp\htdocs\seee\`

---

## Testing All Features

### 1️⃣ Test Product Listing
```
URL: http://localhost/seee/api/get-products.php
Method: GET
Expected: 200 OK with 3 products
```

### 2️⃣ Test Order Creation
**Using PowerShell:**
```powershell
$orderData = @{
    customerName = "Test Customer"
    customerEmail = "test@example.com"
    customerPhone = "0700123456"
    items = @(
        @{
            product_id = 1
            quantity = 2
            unit_price = 45000
        }
    )
    total = 90000
    address = "Test Address, Kampala"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost/seee/api/create-order.php" -Method POST -Body $orderData -ContentType "application/json"
```

**Expected Result:**
```json
{
  "success": true,
  "message": "Order created successfully",
  "orderId": 1,
  "orderNumber": "ORD-1737564123-ABC123"
}
```

**Verify in MySQL Workbench:**
```sql
SELECT * FROM orders;
SELECT * FROM order_items;
SELECT * FROM inventory;  -- quantity_reserved should increase
```

### 3️⃣ Test Booking Creation
**Using PowerShell:**
```powershell
$bookingData = @{
    name = "Test Counselee"
    email = "counselee@example.com"
    phone = "0700654321"
    date = "2026-01-25"
    time = "10:00"
    notes = "Test booking"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost/seee/api/create-booking.php" -Method POST -Body $bookingData -ContentType "application/json"
```

**Expected Result:**
```json
{
  "success": true,
  "message": "Booking created successfully",
  "bookingId": 1,
  "bookingNumber": "BOOK-1737564456-DEF456"
}
```

### 4️⃣ Test Available Slots
```
URL: http://localhost/seee/api/get-available-slots.php?date=2026-01-25
Method: GET
Expected: Array of available time slots
```

### 5️⃣ Test Admin Endpoints

**First, verify admin user exists:**
```sql
-- In MySQL Workbench:
SELECT * FROM admin_users;
```

**Test admin stats (should work without auth for now):**
```
URL: http://localhost/seee/api/admin/get-stats.php
Expected: Dashboard statistics
```

---

## Testing with Frontend

### 1️⃣ Update Frontend to Use Local API

**Edit `shopping-cart.html` to use local URLs:**

Find this section (around line 800):
```javascript
// API Configuration
const API_BASE_URL = 'http://localhost/seee/api';  // ← Use this for local testing
// const API_BASE_URL = 'https://yourdomain.com/seee/api';  // ← Use this for production
```

### 2️⃣ Test Shopping Cart
```
URL: http://localhost/seee/shopping-cart.html
```

**Test Flow:**
1. Add items to cart
2. Click checkout
3. Fill in customer details
4. Submit order
5. Check browser console for API responses
6. Verify order in database:
   ```sql
   SELECT * FROM orders ORDER BY id DESC LIMIT 1;
   ```

### 3️⃣ Test Booking System
```
URL: http://localhost/seee/counselling.html
```

**Test Flow:**
1. Select a date
2. Choose available time slot
3. Fill in contact details
4. Submit booking
5. Verify booking in database:
   ```sql
   SELECT * FROM bookings ORDER BY id DESC LIMIT 1;
   ```

### 4️⃣ Test Admin Panels
```
URL: http://localhost/seee/admin-orders.html
URL: http://localhost/seee/admin-bookings.html
```

---

## Testing Email System (Optional)

### Skip Email Testing Locally
For local testing, you can **temporarily disable emails** to avoid SMTP errors:

**Edit API files and comment out email calls:**

In `api/create-order.php` (around line 80):
```php
// Temporarily disable for local testing
// sendOrderConfirmationEmail($orderId, $customerEmail);
```

In `api/create-booking.php` (around line 70):
```php
// Temporarily disable for local testing
// sendBookingConfirmationEmail($bookingId, $email);
```

**OR Test with Gmail SMTP (if you want):**

Edit `config/email-config.php`:
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-gmail@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');  // Generate at https://myaccount.google.com/apppasswords
define('SMTP_ENCRYPTION', 'tls');
```

---

## Troubleshooting

### ❌ "Connection refused" Error
**Solution:**
1. Check XAMPP MySQL is running (green light)
2. Verify credentials in `config/database.php`
3. Test MySQL connection in MySQL Workbench

### ❌ "Database not found" Error
**Solution:**
1. Open MySQL Workbench
2. Run: `SHOW DATABASES;`
3. If `readers_haven` missing, import schema again

### ❌ "404 Not Found" on API
**Solution:**
1. Verify files are in `C:\xampp\htdocs\seee\`
2. Check URL is: `http://localhost/seee/api/...` (not `http://localhost:5500/...`)
3. Restart Apache in XAMPP

### ❌ Blank page / No output
**Solution:**
1. Check PHP errors:
   - Go to: `C:\xampp\apache\logs\error.log`
   - Look for recent errors
2. Set `DEBUG_MODE = true` in `config/database.php`
3. Add to top of API file:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

### ❌ Email not sending
**Solution:**
- **For local testing**: Comment out email functions (see above)
- **For production**: Use real SMTP credentials

---

## Verification Checklist

Before deploying to Hostinger, verify:

- [ ] ✅ XAMPP Apache running
- [ ] ✅ XAMPP MySQL running
- [ ] ✅ Database `readers_haven` created with 9 tables
- [ ] ✅ Default data exists (3 products, 1 admin)
- [ ] ✅ GET /api/get-products.php returns 3 products
- [ ] ✅ POST /api/create-order.php creates order successfully
- [ ] ✅ Order appears in `orders` table
- [ ] ✅ Inventory `quantity_reserved` updates correctly
- [ ] ✅ POST /api/create-booking.php creates booking
- [ ] ✅ Booking appears in `bookings` table
- [ ] ✅ GET /api/get-available-slots.php returns time slots
- [ ] ✅ Shopping cart frontend works with local API
- [ ] ✅ Counselling form works with local API
- [ ] ✅ Admin panels display data correctly
- [ ] ✅ No JavaScript errors in browser console
- [ ] ✅ No PHP errors in XAMPP error log

---

## Quick Commands Reference

**Copy files to XAMPP:**
```powershell
xcopy "D:\using\hp\IDEA_TO_TRY\AS_developer\the_7_day_plan\seee" "C:\xampp\htdocs\seee\" /E /I /Y
```

**Test API with PowerShell:**
```powershell
# Get products
Invoke-RestMethod -Uri "http://localhost/seee/api/get-products.php"

# Get available slots
Invoke-RestMethod -Uri "http://localhost/seee/api/get-available-slots.php?date=2026-01-25"

# Get admin stats
Invoke-RestMethod -Uri "http://localhost/seee/api/admin/get-stats.php"
```

**Check database in MySQL:**
```sql
-- Show all tables
USE readers_haven;
SHOW TABLES;

-- Check products
SELECT * FROM products;

-- Check recent orders
SELECT * FROM orders ORDER BY created_at DESC LIMIT 5;

-- Check inventory
SELECT p.name, i.quantity_in_stock, i.quantity_reserved, i.quantity_available
FROM products p
JOIN inventory i ON p.id = i.product_id;
```

---

## Once Local Testing is Complete

When everything works locally:

1. **Set DEBUG_MODE = false** in `config/database.php`
2. **Re-enable email functions** if you disabled them
3. **Follow DEPLOYMENT_CHECKLIST.md** to deploy to Hostinger
4. **Update API_BASE_URL** in frontend files to production domain

---

**Happy Testing! 🚀**

Questions? Check:
- API_DOCUMENTATION.md - Full API reference
- BACKEND_SETUP.md - Database details
- DEPLOYMENT_CHECKLIST.md - Production deployment
