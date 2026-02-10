# 🧪 Complete Testing Guide: Delivery Options & Shopping Cart API Integration

## Pre-Testing Checklist

- [ ] Database migration executed successfully
- [ ] All API files created: get-delivery-options.php, generate-dispatch-slip.php, create-order.php
- [ ] shopping-cart.html updated with new checkout flow
- [ ] Web server running and accessible
- [ ] Database credentials configured
- [ ] Email system working (for order confirmation)

## Test 1: Database Verification

### 1.1 Verify Delivery Options Table

**SQL Query:**
```sql
SELECT * FROM delivery_options WHERE is_active = 1;
```

**Expected Result (4 rows):**
```
id | name                | description                      | cost  | delivery_time_min | delivery_time_max
1  | Standard Delivery   | Delivered in 5-7 business days  | 5000  | 5                 | 7
2  | Express Delivery    | Delivered in 2-3 business days  | 15000 | 2                 | 3
3  | Next Day Delivery   | Delivered next business day     | 25000 | 1                 | 1
4  | Pickup              | Pick up from store              | 0     | 0                 | 0
```

**If Failed:**
- Run SQL migration: `migration_delivery_options.sql`
- Check error messages
- Verify all rows inserted

### 1.2 Verify Orders Table Changes

**SQL Query:**
```sql
SHOW COLUMNS FROM orders;
```

**Should Include (New Columns):**
```
delivery_method_id      INT(11) NULL
delivery_cost          DECIMAL(10,2) NULL
delivery_date          TIMESTAMP NULL
dispatch_slip_number   VARCHAR(50) NULL
```

**If Failed:**
- Run migration again
- Check for duplicate column errors
- Verify ALTER TABLE commands executed

### 1.3 Check Foreign Key Constraint

**SQL Query:**
```sql
SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE TABLE_NAME = 'orders' AND COLUMN_NAME = 'delivery_method_id';
```

**Expected:** Foreign key points to delivery_options.id

## Test 2: API Endpoint Verification

### 2.1 Test GET Delivery Options Endpoint

**Request:**
```bash
# Option 1: Using curl
curl http://localhost/api/get-delivery-options.php

# Option 2: Using browser
Visit: http://localhost/api/get-delivery-options.php

# Option 3: Using JavaScript console
fetch('/api/get-delivery-options.php').then(r => r.json()).then(d => console.log(d));
```

**Expected Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": "1",
            "name": "Standard Delivery",
            "description": "Delivered in 5-7 business days",
            "delivery_time_min": "5",
            "delivery_time_max": "7",
            "cost": "5000"
        },
        {
            "id": "2",
            "name": "Express Delivery",
            ...
        },
        ...
    ]
}
```

**If Failed:**
- Check if file exists: `/api/get-delivery-options.php`
- Check error log for PHP errors
- Verify database connection in api/includes/config.php
- Check if delivery_options table exists

### 2.2 Test POST Create Order Endpoint

**Request (JavaScript):**
```javascript
const orderData = {
    customerName: "Test User",
    customerEmail: "test@example.com",
    shippingAddress: "123 Test St, Kampala",
    deliveryMethodId: 1,  // Standard Delivery
    items: [
        {
            id: 1,
            name: "Test Book",
            quantity: 2,
            price: 50000
        }
    ],
    total: 105000  // 100,000 (items) + 5,000 (delivery)
};

const response = await fetch('/api/create-order.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(orderData)
});

const result = await response.json();
console.log(result);
```

**Expected Response:**
```json
{
    "success": true,
    "message": "Order created successfully",
    "orderId": 123,
    "orderNumber": "ORD-20260124123456-abc123"
}
```

**Verify in Database:**
```sql
SELECT * FROM orders WHERE order_id = 123;
```

Should show:
- delivery_method_id = 1
- delivery_cost = 5000
- shipping_address = "123 Test St, Kampala"

**If Failed:**
- Check if total calculation is correct (items + delivery)
- Verify deliveryMethodId is valid integer
- Check error message in response
- Review server error logs

### 2.3 Test Generate Dispatch Slip Endpoint

**Authentication First:**
```javascript
// Get auth token (if using auth system)
const token = localStorage.getItem('authToken');
```

**Request:**
```javascript
const response = await fetch('/api/generate-dispatch-slip.php?order_id=123', {
    method: 'GET',
    headers: {
        'Authorization': 'Bearer ' + token
    }
});

const data = await response.json();
console.log(data);
```

**Expected Response:**
```json
{
    "success": true,
    "message": "Dispatch slip generated successfully",
    "data": {
        "order_id": 123,
        "order_number": "ORD-20260124123456-abc123",
        "dispatch_slip_number": "DS-20260124123456-123",
        "customer_name": "Test User",
        "estimated_delivery_date": "2026-01-31 to 2026-02-04",
        "html": "<!DOCTYPE html>... (printable HTML)"
    }
}
```

**Print the Slip:**
```javascript
const printWindow = window.open('');
printWindow.document.write(data.data.html);
printWindow.document.close();
printWindow.print();
```

**If Failed:**
- Verify order exists in database
- Check authentication token
- Review error message returned
- Check server logs for PHP errors

## Test 3: Shopping Cart Checkout Flow

### 3.1 Basic Checkout Flow

**Step 1: Load Shopping Cart**
1. Open `shopping-cart.html` in browser
2. Should load without errors
3. Console should be clean (no JavaScript errors)

**Step 2: Add Item to Cart**
1. Click "Add to Cart" on any product
2. Verify item appears in cart
3. Verify quantity works correctly

**Step 3: Proceed to Checkout**
1. Click "Checkout" button
2. **Verify:** Delivery options load from API
   - Check Network tab in DevTools → get-delivery-options.php
   - Should return 4 delivery methods
3. Modal should display with:
   - Name field (populated from localStorage if exists)
   - Email field (populated from localStorage if exists)
   - Address field (textarea)
   - Delivery method dropdown (with all 4 options)
   - Delivery cost display

**If Failed:**
- Check browser console for errors
- Verify get-delivery-options.php is accessible
- Check Network tab for failed requests
- Verify shopping-cart.html has fetchDeliveryOptions() function

### 3.2 Select Delivery Method

1. Click on "Delivery method" dropdown
2. Select "Standard Delivery (5-7 days)"
3. **Verify:** 
   - "Delivery Cost: UGX 5000" displays below dropdown
   - Price updates correctly when changing selection
4. Try each delivery method:
   - Standard: 5000
   - Express: 15000
   - Next Day: 25000
   - Pickup: 0

**If Failed:**
- Check updateDeliveryPrice() function in shopping-cart.html
- Verify data-cost attribute is set on option elements
- Check if dropdown change event fires properly

### 3.3 Fill Order Form

1. Enter name: "Test User"
2. Enter email: "test@example.com"
3. Enter address: "123 Test Street, Kampala"
4. Select delivery method: "Standard Delivery"
5. Click "Submit Order" button

**Verify:**
- All fields are required (test by leaving empty and submitting)
- Error message shows for missing fields
- Form data stored in localStorage for persistence

**If Failed:**
- Check submitCheckoutInfo() function validation
- Verify error messages display properly
- Review browser console for JavaScript errors

### 3.4 Order Confirmation

1. After submitting form, should see confirmation dialog:
   ```
   Order Confirmation:
   Name: Test User
   Email: test@example.com
   Items: [list of items with quantities]
   Subtotal: UGX 100,000
   Delivery: UGX 5,000
   Total: UGX 105,000
   ```

2. Click "Confirm" to proceed with order

**Verify:**
- Total includes delivery cost
- Items list is correct
- Customer info is correct

**If Failed:**
- Check checkout() function confirmation dialog
- Verify total calculation includes delivery_cost
- Review console for errors

### 3.5 Order Submission to API

1. After clicking "Confirm" in dialog, order should submit to API
2. Check Network tab in DevTools:
   - Request: POST to `/api/create-order.php`
   - Request body should include:
     ```json
     {
       "customerName": "Test User",
       "customerEmail": "test@example.com",
       "shippingAddress": "123 Test Street, Kampala",
       "deliveryMethodId": 1,
       "items": [...],
       "total": 105000
     }
     ```

3. Response should show:
   ```json
   {
     "success": true,
     "orderId": 123,
     "orderNumber": "ORD-..."
   }
   ```

**Verify:**
- Request is POST (not GET)
- Request body is valid JSON
- Response status is 200 or 201
- orderId is returned

**If Failed:**
- Check submitOrderToAPI() function
- Verify Content-Type header is set
- Check if /api/create-order.php is accessible
- Review server error logs

### 3.6 Post-Order Actions

1. After successful order submission:
   - **Cart should clear** (no items remaining)
   - **Modal should close**
   - **Success message** should display (if implemented)
   - **Confirmation email** should send (check email inbox)

2. Verify in database:
   ```sql
   SELECT * FROM orders WHERE customer_email = 'test@example.com';
   ```
   
   Should show:
   - delivery_method_id = 1
   - delivery_cost = 5000
   - shipping_address = "123 Test Street, Kampala"

**If Failed:**
- Check browser console for errors
- Verify order appears in database (may be delayed)
- Check email spam folder
- Review sendOrderConfirmationEmail() function

## Test 4: Admin Panel Verification

### 4.1 View Orders with Delivery Info

1. Go to admin-orders.html
2. Orders list should appear
3. Each order should show:
   - Order number
   - Customer name
   - **Delivery method** (new)
   - **Delivery cost** (new)
   - Order total (includes delivery)
   - Status
   - Date created

**If Failed:**
- Verify admin-orders.html displays delivery fields
- Check if orders are fetched from API
- Verify database columns exist
- Review admin-orders.js for display logic

### 4.2 Generate Dispatch Slip from Admin

1. Find the test order created above
2. Click "Generate Dispatch Slip" button (or run API call)
3. Dispatch slip should:
   - Generate with unique slip number (DS-YYYYMMDDHH:mm:ss-OrderID)
   - Display in new window
   - Show order details, address, items, delivery method
   - Include packing checklist
   - Be print-ready

**Test Print:**
1. Press Ctrl+P (Windows) or Cmd+P (Mac)
2. Select printer or "Save as PDF"
3. Preview should show properly formatted slip
4. Print should work without issues

**If Failed:**
- Check generate-dispatch-slip.php file
- Verify authentication is working
- Check order exists in database
- Review generated HTML formatting

### 4.3 Update Order Status

1. In admin orders list, find test order
2. Click to open order details
3. Change status from "Processing" to "Shipped"
4. Click save/update
5. Verify in database:
   ```sql
   SELECT status FROM orders WHERE order_id = 123;
   ```
   Should show: "Shipped"

**If Failed:**
- Check if update endpoint is working
- Verify API returns success response
- Confirm status change persists in database

## Test 5: Error Handling

### 5.1 Invalid Delivery Method

**Request:**
```javascript
const orderData = {
    ...
    deliveryMethodId: 999,  // Invalid ID
    ...
};
```

**Expected:** Error response with message "Invalid delivery method"

### 5.2 Missing Required Fields

**Request:**
```javascript
const orderData = {
    customerName: "Test",
    // Missing email, address, items
};
```

**Expected:** Error response indicating missing fields

### 5.3 Wrong Total Calculation

**Request:**
```javascript
const orderData = {
    ...
    items: [{ price: 50000, quantity: 1 }],
    total: 50000  // Should be 55000 with delivery
};
```

**Expected:** Error response "Total calculation mismatch"

### 5.4 Order Not Found for Slip

**Request:**
```javascript
fetch('/api/generate-dispatch-slip.php?order_id=99999')
```

**Expected:** Error response "Order not found"

## Test 6: Data Persistence

### 6.1 localStorage Persistence

1. Fill checkout form with:
   - Name: "John Doe"
   - Email: "john@example.com"
   - Address: "123 Main St"
   - Delivery: "Express"

2. Refresh page (F5)
3. Click checkout again
4. **Verify:** Fields are pre-filled with previous values

**If Failed:**
- Check localStorage save/restore logic
- Verify keys are correct (should use specific key names)
- Test in incognito mode (no localStorage persistence expected)

### 6.2 Database Persistence

1. Create order
2. Refresh page
3. Check admin orders
4. **Verify:** Order still appears
5. Generate dispatch slip again
6. **Verify:** Same slip number (not regenerated)

**If Failed:**
- Check database - order may not have saved
- Verify dispatch_slip_number column is unique

## Test 7: Cross-Browser Testing

Test each scenario in:
- [ ] Chrome
- [ ] Firefox
- [ ] Safari
- [ ] Edge

**Known Issues to Watch:**
- localStorage not available in private/incognito modes
- Print styling may differ between browsers
- Date formatting may vary

## Test 8: Mobile Responsiveness

1. Open shopping-cart.html on mobile device
2. Add item to cart
3. Click checkout
4. **Verify:**
   - Modal displays properly
   - All fields are accessible
   - Dropdown works smoothly
   - Submit button is clickable
   - Confirmation displays correctly

**If Failed:**
- Check CSS media queries
- Verify modal is responsive
- Test keyboard input on mobile

## Test 9: Performance

1. Create order with 50+ items
2. **Verify:** API response time < 2 seconds
3. Check dispatch slip generation
4. **Verify:** Slip generates < 1 second

**If Failed:**
- Check database indexes
- Optimize query in API files
- Consider pagination for large orders

## Test 10: Email Integration

1. Complete order submission
2. Check inbox for confirmation email
3. **Verify email contains:**
   - Order number
   - Items list with quantities and prices
   - Delivery method and cost
   - **Total including delivery cost**
   - Shipping address
   - Estimated delivery date

**If Failed:**
- Verify email config is correct
- Check email log: `/logs/emails.log`
- Test send-order-email.php directly
- Verify email provider hasn't blocked

## Final Verification Checklist

- [ ] All 4 delivery methods appear in checkout
- [ ] Delivery costs display correctly
- [ ] Order total includes delivery cost
- [ ] Orders save to database with delivery info
- [ ] Dispatch slips generate with unique numbers
- [ ] Printed slips are properly formatted
- [ ] Admin panel shows delivery details
- [ ] All error messages are user-friendly
- [ ] localStorage persists user data
- [ ] Cross-browser compatibility verified
- [ ] Mobile responsiveness confirmed
- [ ] Email confirmations send correctly
- [ ] No JavaScript errors in console
- [ ] No PHP errors in server logs

## Deployment Sign-Off

Once all tests pass, you can deploy to production:

1. ✅ Database migration executed
2. ✅ All API files in place
3. ✅ shopping-cart.html updated
4. ✅ Testing complete
5. ✅ No console errors
6. ✅ No server errors
7. ✅ Email system working
8. ✅ Admin panel functional

---

**Test Date:** _______________  
**Tester Name:** _______________  
**Status:** ⏳ Ready for Testing  
**Pass/Fail:** _______________
