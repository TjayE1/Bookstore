# 📋 Dispatch Slip Generation & Usage Guide

## Overview

The dispatch slip system generates professional, printable shipping labels for orders. Each slip includes:
- Order details (number, date, customer)
- Shipping address
- Items list with quantities
- Delivery method and estimated arrival
- Packing checklist
- Unique slip number for tracking

## For Admin Users

### How to Generate a Dispatch Slip

#### Via JavaScript Console (for testing)
```javascript
// Step 1: Get order ID from admin panel or database
const orderId = 123;  // Replace with actual order ID

// Step 2: Fetch the dispatch slip
const response = await fetch(`/api/generate-dispatch-slip.php?order_id=${orderId}`, {
    headers: {
        'Authorization': 'Bearer ' + localStorage.getItem('authToken')
    }
});

// Step 3: Get the HTML and display it
const data = await response.json();
if (data.success) {
    console.log('Slip Number:', data.data.dispatch_slip_number);
    
    // Open in new window to print
    const printWindow = window.open('', '_blank');
    printWindow.document.write(data.data.html);
    printWindow.document.close();
    printWindow.print();
}
```

#### Via Admin Dashboard (Future Integration)
1. Go to Orders management
2. Find order in list
3. Click "Print Dispatch Slip" button
4. Slip opens in print preview
5. Click "Print" to print label

### Dispatch Slip Format

**Header:**
```
                    DISPATCH SLIP
────────────────────────────────────────────
Slip Number: DS-20260124123456-123
Order Number: ORD-20260124123456-abc123
```

**Order Information:**
```
Customer: John Doe
Email: john@example.com
Date: Jan 24, 2026 12:34:56
```

**Shipping Address:**
```
Shipping Address:
123 Main Street
Kampala, Uganda
```

**Items to Pack:**
```
Item                    Qty    Price
─────────────────────────────────────
The Great Gatsby         2     50,000
1984                     1     45,000
                    Subtotal: 145,000
              Delivery (Standard): 5,000
                        TOTAL: 150,000
```

**Delivery Information:**
```
Delivery Method: Standard Delivery (5-7 days)
Estimated Delivery: Jan 31, 2026 - Feb 4, 2026
```

**Packing Checklist:**
```
□ Verify all items are in package
□ Check quantities match order
□ Verify customer name and address
□ Secure package with tape
□ Affix dispatch slip to package
□ Enter dispatch slip number in system
```

## API Endpoint

### `POST /api/generate-dispatch-slip.php`

**Authentication:** Required (admin user with authToken)

**Parameters:**
```
GET: ?order_id=123
OR
POST body: { "order_id": 123 }
```

**Response:**
```json
{
    "success": true,
    "message": "Dispatch slip generated successfully",
    "data": {
        "order_id": 123,
        "order_number": "ORD-20260124123456-abc123",
        "customer_name": "John Doe",
        "customer_email": "john@example.com",
        "dispatch_slip_number": "DS-20260124123456-123",
        "items_count": 3,
        "total_amount": 150000,
        "delivery_method": "Standard Delivery",
        "estimated_delivery_date": "2026-01-31 to 2026-02-04",
        "html": "<!DOCTYPE html>... (full printable HTML)"
    }
}
```

**Error Response:**
```json
{
    "success": false,
    "message": "Order not found",
    "error": "Order ID 999 does not exist"
}
```

## Printing Options

### Option 1: Auto-Print
```javascript
// Opens and auto-prints slip
const printWindow = window.open('', '_blank');
printWindow.document.write(html);
printWindow.document.close();
printWindow.print();  // Triggers print dialog
```

### Option 2: Print Preview
```javascript
// Opens for manual print
const printWindow = window.open('', '_blank');
printWindow.document.write(html);
printWindow.document.close();
// User clicks Print button manually
```

### Option 3: Save as PDF
1. Open slip in browser
2. Press Ctrl+P (or Cmd+P on Mac)
3. Select "Save as PDF"
4. Choose location and save

### Option 4: Direct Print (if connected)
```javascript
// Print directly to thermal printer (if configured)
const printWindow = window.open('', '_blank');
printWindow.document.write(html);
printWindow.document.close();
printWindow.print();
printWindow.close();  // Auto-close after printing
```

## Thermal Printer Setup

For thermal printer compatibility (4x6 shipping labels):

**Edit the CSS in generate-dispatch-slip.php:**
```css
@page {
    size: 4in 6in;
    margin: 0.25in;
}
```

Current settings: Standard A4 paper (8.5x11")

## Dispatch Slip Number Format

Format: `DS-YYYYMMDDHH:mm:ss-OrderID`

Example: `DS-20260124123456-123`

Breakdown:
- `DS-` = Dispatch Slip prefix
- `20260124` = Date (YYYYMMDD)
- `123456` = Time (HHMMSS)
- `-123` = Order ID

This ensures **unique slip numbers** per order.

## Database Storage

Dispatch slip numbers are stored in:
```
orders.dispatch_slip_number (VARCHAR 50)
```

After generating a slip, the number is saved to prevent duplicates.

**Query to find slip by order:**
```sql
SELECT order_number, dispatch_slip_number, customer_name, shipping_address
FROM orders 
WHERE order_id = 123;
```

**List all slips generated today:**
```sql
SELECT dispatch_slip_number, order_number, customer_name, created_at
FROM orders 
WHERE dispatch_slip_number IS NOT NULL 
  AND DATE(created_at) = CURDATE()
ORDER BY created_at DESC;
```

## Workflow Integration

### Standard Fulfillment Workflow

```
1. Customer places order via shopping-cart.html
   └─> Order stored in database with delivery_method_id

2. Admin sees order in admin-orders.html
   └─> Status = "Processing"

3. Admin generates dispatch slip via API
   └─> Slip number assigned and stored
   └─> HTML generated with all order details

4. Admin prints dispatch slip
   └─> 4x6 label or A4 page
   └─> Affix to package

5. Warehouse packs items
   └─> Verify contents match slip
   └─> Update order status to "Shipped"

6. Customer receives email with tracking info
   └─> Includes dispatch slip number

7. Customer tracks delivery
   └─> Uses dispatch slip number
   └─> Estimated delivery date shown
```

## Error Handling

**Common Errors & Solutions:**

| Error | Cause | Solution |
|-------|-------|----------|
| "Order not found" | Order ID doesn't exist | Verify order ID in admin panel |
| "Unauthorized" | Not logged in or invalid token | Login to admin panel first |
| "Order has no delivery info" | Order created before migration | Re-run order or update manually |
| "Delivery method not found" | Corrupt data | Check delivery_options table |
| "Slip already generated" | Slip number exists | Can regenerate - overwrites previous |

## Integration with Admin Dashboard

### In admin-orders.html (add this button):

```html
<!-- In the orders table row -->
<td>
    <button onclick="generateDispatchSlip(row.order_id)" 
            class="btn btn-primary btn-sm">
        📋 Generate Slip
    </button>
</td>

<script>
async function generateDispatchSlip(orderId) {
    const response = await fetch(`/api/generate-dispatch-slip.php?order_id=${orderId}`, {
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('authToken')
        }
    });
    
    const data = await response.json();
    
    if (data.success) {
        // Open in new window
        const printWindow = window.open('', '_blank');
        printWindow.document.write(data.data.html);
        printWindow.document.close();
        printWindow.print();
        
        alert(`Dispatch Slip: ${data.data.dispatch_slip_number}`);
    } else {
        alert('Error: ' + data.message);
    }
}
</script>
```

## Best Practices

✅ **DO:**
- Print slip immediately after order is confirmed
- Verify all items are correct before packing
- Keep slip attached to package throughout delivery
- Use order number and dispatch slip number together for tracking
- Generate backup slip if original is damaged
- Store digital copies for audit trail

❌ **DON'T:**
- Modify dispatch slip numbers manually
- Use same slip for multiple orders
- Discard slips after delivery
- Print slip for incomplete orders
- Edit customer address after slip generated

## Troubleshooting

### Slip doesn't print
1. Check if pop-ups are blocked in browser
2. Make sure you're logged in as admin
3. Verify order ID is correct
4. Check browser console for errors

### Slip content is cut off
1. Adjust page size in CSS
2. Reduce font size if needed
3. Use landscape orientation
4. Check printer margins

### Dispatch slip number not saving
1. Verify database migration ran
2. Check if dispatch_slip_number column exists
3. Ensure user has UPDATE permission on orders table

## Testing

### Test with sample order:
```sql
-- Find an existing order
SELECT * FROM orders LIMIT 1;

-- Generate slip for that order
-- Use API or console command above
```

### Check if slip was saved:
```sql
SELECT order_id, dispatch_slip_number FROM orders 
WHERE dispatch_slip_number IS NOT NULL 
LIMIT 5;
```

## Support

For technical issues:
- Check `/api/generate-dispatch-slip.php` error log
- Verify authentication token is valid
- Ensure database migration completed
- Review browser console for network errors

---

**Status:** ✅ Production Ready  
**Last Updated:** Jan 24, 2026  
**Version:** 1.0
