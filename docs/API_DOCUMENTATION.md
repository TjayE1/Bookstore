# Reader's Haven - Complete Backend API Documentation

## 📊 Database Architecture

### Core Tables

```
products (50 books) → inventory (stock management)
                  ↓
customers → orders → order_items (many-to-many)
                
customers → bookings ← unavailable_dates
                
admin_users → audit_logs
```

## 🔌 API Endpoints

### **1. PRODUCTS API**

#### Get All Products
```
GET /api/get-products.php
```

**Response:**
```json
{
  "success": true,
  "products": [
    {
      "id": 1,
      "name": "Gratitude Journal",
      "description": "Transform your life with gratitude...",
      "price": 89990,
      "category": "book",
      "emoji": "📓",
      "in_stock": true,
      "available_quantity": 50
    }
  ]
}
```

---

### **2. ORDERS API**

#### Create New Order
```
POST /api/create-order.php
Content-Type: application/json
```

**Request Body:**
```json
{
  "customerName": "John Doe",
  "customerEmail": "john@example.com",
  "items": [
    {
      "id": 1,
      "name": "Gratitude Journal",
      "quantity": 2,
      "price": 89990
    }
  ],
  "total": 179980
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Order created successfully",
  "orderId": 1,
  "orderNumber": "ORD-20260122120530-abc123"
}
```

**Error Responses:**
- 400: Missing or invalid fields
- 500: Database error

---

#### Get All Orders (Admin)
```
GET /api/admin/get-orders.php
GET /api/admin/get-orders.php?status=pending
Authorization: Admin Session
```

**Response:**
```json
{
  "success": true,
  "orders": [
    {
      "id": 1,
      "order_number": "ORD-20260122120530-abc123",
      "customer_name": "John Doe",
      "customer_email": "john@example.com",
      "total_amount": 179980,
      "status": "pending",
      "item_count": 2,
      "created_at": "2026-01-22 12:05:30"
    }
  ]
}
```

---

#### Get Order Details
```
GET /api/admin/get-order-details.php?orderId=1
Authorization: Admin Session
```

**Response:**
```json
{
  "success": true,
  "order": {
    "id": 1,
    "order_number": "ORD-...",
    "customer_id": 1,
    "customer_name": "John Doe",
    "customer_email": "john@example.com",
    "total_amount": 179980,
    "status": "pending",
    "payment_status": "pending",
    "created_at": "2026-01-22 12:05:30"
  },
  "items": [
    {
      "id": 1,
      "order_id": 1,
      "product_id": 1,
      "product_name": "Gratitude Journal",
      "quantity": 2,
      "unit_price": 89990,
      "total_price": 179980
    }
  ]
}
```

---

#### Update Order Status
```
POST /api/admin/update-order-status.php
Authorization: Admin Session
```

**Request Body:**
```json
{
  "orderId": 1,
  "status": "shipped"
}
```

**Valid Statuses:** pending, processing, shipped, delivered, cancelled

**Response:**
```json
{
  "success": true,
  "message": "Order status updated successfully"
}
```

---

### **3. BOOKINGS API**

#### Create Booking
```
POST /api/create-booking.php
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "phone": "+256701234567",
  "date": "2026-01-25",
  "time": "14:00",
  "message": "I want to discuss stress management"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Booking created successfully",
  "bookingId": 1,
  "bookingNumber": "BOOK-20260122120530-abc123"
}
```

**Error Responses:**
- 400: Date is a weekend, date is unavailable, slot is booked
- 500: Database error

---

#### Get Available Time Slots
```
GET /api/get-available-slots.php?date=2026-01-25
```

**Response:**
```json
{
  "success": true,
  "date": "2026-01-25",
  "available_slots": [
    "08:00",
    "08:30",
    "09:00",
    "10:00",
    "14:00",
    "15:00",
    "16:00",
    "17:00"
  ]
}
```

**Time Slots Available:** 8:00 AM - 5:30 PM (excluding 12:00 - 2:00 PM lunch)

---

#### Get All Bookings (Admin)
```
GET /api/admin/get-bookings.php
GET /api/admin/get-bookings.php?status=pending
GET /api/admin/get-bookings.php?start_date=2026-01-20&end_date=2026-01-31
Authorization: Admin Session
```

**Response:**
```json
{
  "success": true,
  "bookings": [
    {
      "id": 1,
      "booking_number": "BOOK-...",
      "customer_name": "Jane Doe",
      "customer_email": "jane@example.com",
      "booking_date": "2026-01-25",
      "booking_time": "14:00",
      "status": "pending",
      "created_at": "2026-01-22 12:05:30"
    }
  ]
}
```

---

#### Update Booking Status
```
POST /api/admin/update-booking-status.php
Authorization: Admin Session
```

**Request Body:**
```json
{
  "bookingId": 1,
  "status": "confirmed"
}
```

**Valid Statuses:** pending, confirmed, completed, cancelled

---

#### Delete Booking
```
POST /api/admin/delete-booking.php
Authorization: Admin Session
```

**Request Body:**
```json
{
  "bookingId": 1
}
```

---

### **4. UNAVAILABLE DATES API**

#### Get All Unavailable Dates
```
GET /api/get-unavailable-dates.php
```

**Response:**
```json
{
  "success": true,
  "unavailable_dates": [
    {
      "date": "2026-01-20",
      "reason": "Staff meeting"
    },
    {
      "date": "2026-02-05",
      "reason": "Training session"
    }
  ]
}
```

---

#### Add Unavailable Date (Admin)
```
POST /api/admin/add-unavailable-date.php
Authorization: Admin Session
```

**Request Body:**
```json
{
  "date": "2026-02-10",
  "reason": "Holiday"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Unavailable date added successfully"
}
```

---

### **5. ADMIN DASHBOARD API**

#### Get Dashboard Statistics
```
GET /api/admin/get-stats.php
Authorization: Admin Session
```

**Response:**
```json
{
  "success": true,
  "stats": {
    "totalOrders": 25,
    "pendingOrders": 3,
    "totalRevenue": 4500000,
    "totalBookings": 18,
    "pendingBookings": 2,
    "totalCustomers": 20
  },
  "recentOrders": [
    {
      "id": 25,
      "order_number": "ORD-...",
      "customer_name": "Recent Customer",
      "total_amount": 89990,
      "status": "processing",
      "created_at": "2026-01-22 14:30:00"
    }
  ],
  "upcomingBookings": [
    {
      "id": 18,
      "customer_name": "Jane Doe",
      "booking_date": "2026-01-23",
      "booking_time": "14:00",
      "status": "confirmed"
    }
  ]
}
```

---

## 🔐 Authentication

### Admin Login (Currently Session-based)

Authentication happens through:
1. Check if `admin_users` table has username
2. Verify password (bcrypt hashing)
3. Create PHP session
4. Check session in protected endpoints

**Default Credentials:**
```
Username: admin
Password: admin123
```

⚠️ **Change immediately after deployment!**

---

## 📨 Email Integration

When an order is created or booking is confirmed, these APIs are called:

```
POST /api/send-order-email.php
POST /api/send-booking-email.php
```

(See EMAIL_SYSTEM_README.md for details)

---

## 💾 Database Schema Summary

### Products Table
```sql
id, name, description, price, category, emoji, in_stock
```

### Customers Table
```sql
id, name, email, phone, address, city, country, created_at
```

### Orders Table
```sql
id, order_number, customer_id, total_amount, 
status, payment_status, created_at, updated_at
```

### Order Items Table
```sql
id, order_id, product_id, quantity, unit_price, total_price
```

### Bookings Table
```sql
id, booking_number, customer_name, email, 
booking_date, booking_time, notes, status, created_at
```

### Unavailable Dates Table
```sql
id, unavailable_date, reason, created_at
```

### Inventory Table
```sql
id, product_id, quantity_in_stock, quantity_reserved, 
quantity_available (calculated), last_restocked
```

---

## 🧪 Testing API Endpoints

### Test with cURL:

```bash
# Get products
curl http://localhost/seee/api/get-products.php

# Create order
curl -X POST http://localhost/seee/api/create-order.php \
  -H "Content-Type: application/json" \
  -d '{
    "customerName":"John",
    "customerEmail":"john@example.com",
    "items":[{"id":1,"name":"Journal","quantity":1,"price":89990}],
    "total":89990
  }'

# Get available slots
curl "http://localhost/seee/api/get-available-slots.php?date=2026-01-25"

# Create booking
curl -X POST http://localhost/seee/api/create-booking.php \
  -H "Content-Type: application/json" \
  -d '{
    "name":"Jane",
    "email":"jane@example.com",
    "date":"2026-01-25",
    "time":"14:00",
    "message":"Need counselling"
  }'
```

---

## 🔒 Error Handling

### Standard Error Response:
```json
{
  "success": false,
  "message": "Human-readable error message",
  "error": "Technical error details (in DEBUG mode only)"
}
```

### HTTP Status Codes:
- `200 OK` - Successful GET
- `201 Created` - Successful POST (resource created)
- `400 Bad Request` - Invalid parameters
- `401 Unauthorized` - Missing/invalid authentication
- `404 Not Found` - Resource not found
- `405 Method Not Allowed` - Wrong HTTP method
- `500 Internal Server Error` - Database/server error

---

## 🚀 Deployment Checklist

- [ ] Database schema imported on Hostinger
- [ ] `config/database.php` updated with production credentials
- [ ] Email configuration set up and tested
- [ ] Admin password changed
- [ ] HTTPS enabled
- [ ] `.htaccess` security rules enabled
- [ ] DEBUG_MODE set to false
- [ ] All API endpoints tested
- [ ] Email delivery verified
- [ ] Admin dashboard working
- [ ] Order and booking creation working

---

## 📞 Support

**Hostinger Help:**
- Knowledge Base: https://support.hostinger.com
- Chat Support: Available 24/7

**API Issues:**
1. Check database connection: `config/database.php`
2. Verify database is imported: `database/database_schema.sql`
3. Check error logs in cPanel
4. Test endpoints with cURL
5. Enable DEBUG_MODE temporarily for detailed errors

---

**Last Updated:** January 22, 2026
**Version:** 1.0
**Status:** Production Ready ✅
