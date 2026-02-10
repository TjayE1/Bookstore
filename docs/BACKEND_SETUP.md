# Backend Server & Database Setup Guide

## 📊 Database Schema Overview

### Tables Created:

1. **products** - Book catalog
   - id, name, description, price, category, emoji, in_stock
   
2. **customers** - Customer information
   - id, name, email, phone, address, city, country

3. **orders** - Customer orders
   - id, order_number, customer_id, total_amount, status, payment_status

4. **order_items** - Items in each order
   - id, order_id, product_id, quantity, unit_price

5. **bookings** - Counselling appointments
   - id, booking_number, customer_name, email, booking_date, booking_time, status

6. **unavailable_dates** - Blocked dates for counselling
   - id, unavailable_date, reason

7. **admin_users** - Admin accounts
   - id, username, password_hash, email, role

8. **inventory** - Stock management
   - id, product_id, quantity_in_stock, quantity_reserved

9. **audit_logs** - Activity tracking
   - id, admin_id, action, entity_type, entity_id, created_at

## 🚀 Setup Instructions

### Step 1: Create the Database

1. **Login to Hostinger cPanel**
2. **Go to MySQL Databases**
3. **Create new database:**
   - Database name: `readers_haven`
   - Add a database user with a strong password
4. **Import the schema:**
   - Go to phpMyAdmin
   - Select your database
   - Click "Import"
   - Upload `database/database_schema.sql`
   - Click "Go"

### Step 2: Update Database Configuration

Edit `config/database.php`:

```php
define('DB_HOST', 'localhost'); // Your Hostinger host
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'readers_haven');
```

### Step 3: API Endpoints Created

**Products:**
- `GET /api/get-products.php` - Get all products

**Orders:**
- `POST /api/create-order.php` - Create new order
- `GET /api/admin/get-orders.php` - Admin: Get all orders

**Bookings:**
- `POST /api/create-booking.php` - Create new booking
- `GET /api/admin/get-bookings.php` - Admin: Get all bookings
- `GET /api/get-available-slots.php?date=YYYY-MM-DD` - Get available time slots

**Unavailable Dates:**
- `GET /api/get-unavailable-dates.php` - Get blocked dates

## 📝 API Usage Examples

### Get Products
```bash
curl http://localhost/seee/api/get-products.php
```

### Create Order
```bash
curl -X POST http://localhost/seee/api/create-order.php \
  -H "Content-Type: application/json" \
  -d '{
    "customerName": "John Doe",
    "customerEmail": "john@example.com",
    "items": [
      {"id": 1, "name": "Gratitude Journal", "quantity": 1, "price": 89990}
    ],
    "total": 89990
  }'
```

### Create Booking
```bash
curl -X POST http://localhost/seee/api/create-booking.php \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Doe",
    "email": "jane@example.com",
    "date": "2026-01-25",
    "time": "14:00",
    "message": "Looking forward to the session"
  }'
```

### Get Available Slots
```bash
curl http://localhost/seee/api/get-available-slots.php?date=2026-01-25
```

## 🔐 Default Admin Account

- **Username:** admin
- **Password:** admin123
- **Email:** admin@readers-haven.com

⚠️ **CHANGE PASSWORD IMMEDIATELY AFTER FIRST LOGIN**

## 📂 File Structure

```
seee/
├── config/
│   └── database.php          # Database connection
├── database/
│   └── database_schema.sql   # SQL schema
├── api/
│   ├── get-products.php
│   ├── create-order.php
│   ├── create-booking.php
│   ├── get-available-slots.php
│   ├── get-unavailable-dates.php
│   ├── admin/
│   │   ├── get-orders.php
│   │   └── get-bookings.php
│   └── includes/
│       └── auth.php          # Authentication functions
└── [other files]
```

## 🔄 Database Migration from localStorage

The JavaScript still uses localStorage. To fully migrate:

1. **Update shopping-cart.html** to call backend APIs
2. **Update admin-bookings.html** to use database
3. **Update admin-orders.html** to use database

## 💡 Key Features

✅ **Automatic Order Numbers** - ORD-[timestamp]-[random]
✅ **Automatic Booking Numbers** - BOOK-[timestamp]-[random]
✅ **Inventory Management** - Tracks stock and reserved items
✅ **Audit Logging** - Track admin actions
✅ **Transaction Support** - Ensures data consistency
✅ **CORS Enabled** - Works with frontend apps
✅ **Error Handling** - Comprehensive error messages

## 🧪 Test the Database

1. Create a test file: `test-db.php`
2. Add:
```php
<?php
require_once 'config/database.php';

// Test connection
$products = getRows("SELECT * FROM products LIMIT 1");
echo "<pre>";
print_r($products);
echo "</pre>";
?>
```
3. Open in browser to verify database connection

## 🔒 Security Notes

- ✅ Prepared statements prevent SQL injection
- ✅ Input validation and sanitization
- ✅ Password hashing (bcrypt)
- ✅ Session-based authentication
- ⚠️ Add HTTPS requirement in .htaccess
- ⚠️ Change default admin password
- ⚠️ Use environment variables for sensitive data

## 📞 Hostinger Database Setup

For Hostinger specifically:

1. **Database Host:** Usually `localhost`
2. **Create database user:** Via cPanel
3. **Remote access:** May need to be enabled
4. **phpMyAdmin:** Available in cPanel
5. **Backup:** Automatic backups available

## Next Steps

1. ✅ Import database schema
2. ✅ Update config/database.php
3. ✅ Test API endpoints
4. ✅ Update JavaScript to use APIs
5. ✅ Test complete flow end-to-end
6. ✅ Set up admin authentication
7. ✅ Deploy to Hostinger

---

**Status:** Backend infrastructure ready for integration!
