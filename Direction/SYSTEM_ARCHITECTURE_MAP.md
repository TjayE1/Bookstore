# 🗺️ System Architecture & File Map

## 📊 Complete System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                      CUSTOMER EXPERIENCE                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  shopping-cart.html                                             │
│  ┌──────────────────────────┐                                  │
│  │ Add Items to Cart        │                                  │
│  │ Click "Checkout"         │                                  │
│  │   ↓                      │                                  │
│  │ Fetch Delivery Options   │ ──→ /api/get-delivery-options   │
│  │   ↓                      │                                  │
│  │ Select Delivery Method   │                                  │
│  │ (Cost updates in realtime)                                 │
│  │   ↓                      │                                  │
│  │ Enter: Name, Email, Address                               │
│  │   ↓                      │                                  │
│  │ Review Total (Items + Delivery)                           │
│  │   ↓                      │                                  │
│  │ Submit Order             │ ──→ /api/create-order.php       │
│  │   ↓                      │                                  │
│  │ Success Message          │                                  │
│  └──────────────────────────┘                                  │
│           ↓                                                      │
│      Email Confirmation                                          │
│           ↓                                                      │
└─────────────────────────────────────────────────────────────────┘
                         ↓↓↓
┌─────────────────────────────────────────────────────────────────┐
│                     DATABASE LAYER                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  delivery_options table              orders table                │
│  ┌──────────────────────┐           ┌──────────────────────┐   │
│  │ id (1-4)             │           │ order_id             │   │
│  │ name                 │◀──────────│ order_number         │   │
│  │ cost                 │ FK        │ customer_name        │   │
│  │ delivery_time_min    │           │ delivery_method_id   │   │
│  │ delivery_time_max    │           │ delivery_cost        │   │
│  │ is_active            │           │ shipping_address     │   │
│  └──────────────────────┘           │ dispatch_slip_number │   │
│       ↑                 │           │ status               │   │
│       │                 │           └──────────────────────┘   │
│       └─────────────────┘                  ↑                    │
│                                            │                    │
└─────────────────────────────────────────────────────────────────┘
                         ↑↑↑
┌─────────────────────────────────────────────────────────────────┐
│                      API LAYER                                   │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  GET /api/get-delivery-options.php                              │
│  ├─ No Auth Required                                            │
│  ├─ Queries: delivery_options (WHERE is_active = 1)            │
│  └─ Returns: JSON array of 4 methods with costs                │
│                                                                   │
│  POST /api/create-order.php                                     │
│  ├─ No Auth Required                                            │
│  ├─ Validates: deliveryMethodId, shippingAddress               │
│  ├─ Queries: delivery_options, INSERT orders                   │
│  ├─ Calculates: Total = items + delivery_cost                 │
│  └─ Returns: orderId, orderNumber                             │
│                                                                   │
│  GET /api/generate-dispatch-slip.php                            │
│  ├─ Auth Required (Admin)                                       │
│  ├─ Queries: orders, order_items, delivery_options             │
│  ├─ Generates: Unique slip number, HTML, PDF                   │
│  ├─ Calculates: Estimated delivery date                        │
│  └─ Returns: HTML + metadata                                    │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
                         ↑↑↑
┌─────────────────────────────────────────────────────────────────┐
│                      ADMIN LAYER                                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  admin-orders.html                                              │
│  ┌──────────────────────────┐                                  │
│  │ View Orders              │ ←── /api/get-orders.php          │
│  │ (with delivery info)      │                                  │
│  │   ↓                      │                                  │
│  │ Update Order Status      │ ──→ /api/update-order-status    │
│  │   ↓                      │                                  │
│  │ Generate Dispatch Slip   │ ──→ /api/generate-dispatch-slip  │
│  │   ↓                      │                                  │
│  │ Print Shipping Label     │     (HTML/PDF)                   │
│  │   ↓                      │                                  │
│  │ Track Order Fulfillment  │                                  │
│  └──────────────────────────┘                                  │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📁 File Structure Map

```
/seee (Project Root)
│
├── 📄 README_DELIVERY_SYSTEM.md ⭐ START HERE
├── 📄 QUICK_START_CARD.md ⭐ PRINT THIS
├── 📄 IMPLEMENTATION_COMPLETE.md
├── 📄 DELIVERY_SYSTEM_FINAL_SUMMARY.md
│
├── 📁 database/
│   ├── database_schema.sql (Original)
│   └── migration_delivery_options.sql ⭐ RUN THIS
│
├── 📁 api/
│   ├── get-delivery-options.php ✅ NEW
│   ├── generate-dispatch-slip.php ✅ NEW
│   ├── create-order.php (ENHANCED)
│   ├── get-orders.php
│   ├── update-order-status.php
│   ├── send-order-email.php
│   └── ... (other endpoints)
│
├── 🛒 shopping-cart.html ✅ UPDATED
│   ├── fetchDeliveryOptions() ✅ NEW
│   ├── updateDeliveryPrice() ✅ NEW
│   ├── submitOrderToAPI() ✅ NEW
│   ├── checkout() (REWRITTEN)
│   └── ... (other functions)
│
├── 👥 admin-bookings.html (Already using API)
├── 📊 admin-orders.html (For viewing orders)
│
└── 📚 Documentation/
    ├── README_DELIVERY_SYSTEM.md
    ├── QUICK_START_CARD.md
    ├── IMPLEMENTATION_COMPLETE.md
    ├── MIGRATION_QUICK_START.md
    ├── DELIVERY_DISPATCH_IMPLEMENTATION.md
    ├── DISPATCH_SLIP_ADMIN_GUIDE.md
    ├── TESTING_GUIDE.md
    ├── DOCUMENTATION_INDEX.md
    └── DELIVERY_SYSTEM_FINAL_SUMMARY.md
```

---

## 🔄 Data Flow Diagram

### Customer Order Flow
```
Customer ──add items──> shopping-cart.html
              │
              ▼
         Click Checkout
              │
              ├─→ /api/get-delivery-options.php ──→ Fetch Methods
              │   
              ├─→ Show Form (name, email, address, delivery method)
              │
              ├─→ Select Delivery ──→ updateDeliveryPrice()
              │                           │
              │                           └─→ Real-time cost calc
              │
              ├─→ Submit Order
              │   │
              │   ├─→ submitOrderToAPI()
              │   │   │
              │   │   ├─→ Validate inputs
              │   │   │
              │   │   └─→ POST /api/create-order.php
              │   │       │
              │   │       ├─→ Backend Validation
              │   │       ├─→ Query delivery_options
              │   │       ├─→ Calculate total
              │   │       ├─→ INSERT into orders
              │   │       │
              │   │       └─→ Response: orderId, orderNumber
              │   │
              │   └─→ sendOrderConfirmationEmail()
              │
              └─→ Success: Order saved, cart cleared
```

### Admin Dispatch Flow
```
Admin Panel ──view orders──> /api/get-orders.php
              │
              ├─→ Display orders with delivery info
              │
              ├─→ Click "Generate Dispatch Slip"
              │   │
              │   ├─→ /api/generate-dispatch-slip.php
              │   │   │
              │   │   ├─→ Query order from database
              │   │   ├─→ Query order items
              │   │   ├─→ Generate slip number (DS-...)
              │   │   ├─→ Calculate est. delivery date
              │   │   ├─→ Generate HTML
              │   │   │
              │   │   └─→ Response: html, slip_number
              │   │
              │   └─→ Open in window, print, or save as PDF
              │
              └─→ Update order status: "Shipped"
```

---

## 🔌 API Dependency Map

```
Shopping Cart (Client-Side)
    │
    ├─→ /api/get-delivery-options.php
    │   └─ Database: SELECT from delivery_options
    │
    └─→ /api/create-order.php
        ├─ Database: SELECT from delivery_options (validate)
        ├─ Database: INSERT into orders
        ├─ Database: INSERT into order_items
        └─ External: Send email confirmation

Admin Panel (Client-Side)
    │
    ├─→ /api/get-orders.php
    │   └─ Database: SELECT from orders + items
    │
    ├─→ /api/update-order-status.php
    │   └─ Database: UPDATE orders
    │
    └─→ /api/generate-dispatch-slip.php
        ├─ Database: SELECT from orders
        ├─ Database: SELECT from order_items
        ├─ Database: SELECT from delivery_options
        ├─ Database: UPDATE dispatch_slip_number
        └─ File System: Generate/return HTML
```

---

## 📊 Database Relationship Diagram

```
delivery_options table
┌─────────────────────────────┐
│ id (PRIMARY KEY)            │
│ name                        │
│ description                 │
│ delivery_time_min           │
│ delivery_time_max           │
│ cost                        │
│ is_active                   │
│ created_at                  │
│ updated_at                  │
└─────────────────────────────┘
         ▲
         │ One-to-Many (FK)
         │
    [delivery_method_id]
         │
         ▼
orders table
┌─────────────────────────────┐
│ id (PRIMARY KEY)            │
│ order_number                │
│ customer_name               │
│ customer_email              │
│ shipping_address ✅ NEW     │
│ delivery_method_id ✅ NEW   │
│ delivery_cost ✅ NEW        │
│ delivery_date ✅ NEW        │
│ dispatch_slip_number ✅ NEW │
│ total                       │
│ status                      │
│ created_at                  │
│ updated_at                  │
└─────────────────────────────┘
         ▲
         │ One-to-Many
         │
    [order_id]
         │
         ▼
order_items table
┌─────────────────────────────┐
│ id (PRIMARY KEY)            │
│ order_id (FK)               │
│ product_id                  │
│ quantity                    │
│ price                       │
│ created_at                  │
│ updated_at                  │
└─────────────────────────────┘
```

---

## 🔐 Security Layer

```
All API Endpoints
    │
    ├─→ Input Validation (Validator class)
    │   ├─ Type checking
    │   ├─ Length limits
    │   ├─ Format validation
    │   └─ Sanitization
    │
    ├─→ Database Protection
    │   ├─ Prepared statements
    │   ├─ Parameterized queries
    │   └─ No string concatenation
    │
    ├─→ Rate Limiting
    │   ├─ Per-IP throttling
    │   └─ Request counting
    │
    ├─→ Authentication (Admin endpoints)
    │   ├─ Token validation
    │   ├─ User verification
    │   └─ Permission check
    │
    └─→ Error Handling
        ├─ No sensitive data leaks
        ├─ Generic error messages
        ├─ Logging for debugging
        └─ Response validation
```

---

## 📈 Feature Hierarchy

```
Level 1: Core System
├─ Delivery Options Table (4 methods)
└─ Orders Table Enhancement (4 new columns)

Level 2: API Endpoints
├─ GET /api/get-delivery-options.php (Public)
├─ POST /api/create-order.php (Enhanced)
└─ GET /api/generate-dispatch-slip.php (Admin)

Level 3: Frontend Integration
├─ Fetch delivery options
├─ Select delivery method
├─ Real-time price calculation
├─ Form validation
└─ API submission

Level 4: Admin Features
├─ View orders with delivery
├─ Generate dispatch slips
├─ Print shipping labels
└─ Track fulfillment

Level 5: Advanced Features (Optional)
├─ SMS notifications
├─ Customer tracking portal
├─ Regional pricing
└─ Shipping provider integration
```

---

## 🎯 Implementation Timeline

```
Time    Component               Status      File
─────────────────────────────────────────────────
0 min   Start                   ✅ Done
5 min   Run migration           ⏳ Action    migration_delivery_options.sql
10 min  Verify database         ⏳ Action    Check delivery_options table
15 min  Test checkout           ⏳ Action    shopping-cart.html
20 min  Verify order saved      ⏳ Action    Check orders table
25 min  Test dispatch slip      ⏳ Action    /api/generate-dispatch-slip.php
30 min  Print label             ⏳ Action    HTML output
35 min  Email confirmation      ⏳ Action    Check inbox
40 min  Admin testing           ⏳ Action    admin-orders.html
45 min  Full test suite         ⏳ Action    See TESTING_GUIDE.md
60 min  Ready for production    ✅ Goal
```

---

## 📚 Documentation Hierarchy

```
Level 1: Quick Start
└─ QUICK_START_CARD.md (Print this!)

Level 2: Overview
├─ README_DELIVERY_SYSTEM.md
└─ IMPLEMENTATION_COMPLETE.md

Level 3: Setup
├─ MIGRATION_QUICK_START.md
└─ DELIVERY_DISPATCH_IMPLEMENTATION.md

Level 4: Usage
├─ DISPATCH_SLIP_ADMIN_GUIDE.md
└─ admin-orders.html code

Level 5: Testing
├─ TESTING_GUIDE.md
└─ Test scenarios

Level 6: Reference
├─ DOCUMENTATION_INDEX.md
└─ DELIVERY_SYSTEM_FINAL_SUMMARY.md
```

---

## 🚀 Deployment Checklist

```
Pre-Deployment
├─ □ Read: README_DELIVERY_SYSTEM.md
├─ □ Run: migration_delivery_options.sql
├─ □ Verify: delivery_options table exists
├─ □ Test: Get delivery options API
├─ □ Test: Create order API
├─ □ Test: Generate dispatch slip

Deployment
├─ □ Backup: Current database
├─ □ Execute: Migration script
├─ □ Verify: All tables created
├─ □ Check: Foreign key relationships
├─ □ Test: Shopping cart checkout
├─ □ Test: Order appears in database
├─ □ Test: Admin panel shows orders
├─ □ Test: Dispatch slip generation
├─ □ Test: Email confirmations send

Post-Deployment
├─ □ Monitor: First few orders
├─ □ Check: Email confirmations
├─ □ Check: Order storage in database
├─ □ Train: Admin staff on dispatch slips
├─ □ Verify: No error logs
├─ □ Document: Any customizations made
```

---

**This architecture diagram provides a complete visual reference of how all components interact. Use it for:**

1. Understanding system flow
2. Troubleshooting issues
3. Training new team members
4. Planning enhancements
5. Documentation reference

🎉 Everything is documented and ready to deploy!
