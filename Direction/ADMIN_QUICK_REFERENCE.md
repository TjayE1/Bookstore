# Admin Panel - Database Integration Quick Reference

## What Changed

### Before (localStorage)
```javascript
// Data stored in browser only
let bookedAppointments = JSON.parse(localStorage.getItem('bookedAppointments')) || [];

function markComplete(index) {
    bookedAppointments[index].completed = true;
    localStorage.setItem('bookedAppointments', JSON.stringify(bookedAppointments));
}
```

### After (Database API)
```javascript
// Data fetched from server
let bookedAppointments = [];

async function fetchBookings() {
    const response = await fetch('/api/get-bookings.php', {
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem('authToken') }
    });
    const data = await response.json();
    bookedAppointments = data.data;
    renderBookings();
}

async function markBookingComplete(bookingId) {
    const response = await fetch('/api/update-booking-status.php', {
        method: 'POST',
        body: JSON.stringify({ id: bookingId, status: 'completed' }),
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem('authToken') }
    });
    const data = await response.json();
    if (data.success) await fetchBookings();
}
```

## API Endpoints Quick Reference

| Operation | Method | Endpoint | Body |
|-----------|--------|----------|------|
| Get Bookings | GET | `/api/get-bookings.php?status=pending` | - |
| Get Orders | GET | `/api/get-orders.php?status=pending` | - |
| Update Booking | POST | `/api/update-booking-status.php` | `{id, status, notes}` |
| Update Order | POST | `/api/update-order-status.php` | `{id, status, payment_status}` |
| Block Date | POST | `/api/add-unavailable-date.php` | `{unavailable_date, reason}` |
| Delete Booking | DELETE | `/api/delete-booking.php` | `{id}` |
| Delete Order | DELETE | `/api/delete-order.php` | `{id}` |
| Unblock Date | DELETE | `/api/delete-unavailable-date.php` | `{id}` |

## Key Differences

### Status Values

**Bookings:**
- `pending` (new)
- `confirmed` (acknowledged)
- `completed` (session completed)
- `cancelled` (cancelled)

**Orders:**
- `pending` (just created)
- `processing` (being prepared)
- `shipped` (on the way)
- `delivered` (arrived)
- `cancelled` (cancelled)

### Database Field Names

| Display | Database |
|---------|----------|
| Customer Name | customer_name |
| Customer Email | customer_email |
| Booking Date | booking_date |
| Booking Time | booking_time |
| Order Date | created_at |
| Total Price | total_amount |
| Order Status | status |

## Automatic Polling

Both admin pages automatically refresh data every **30 seconds** from the server:

```javascript
// Auto-refresh every 30 seconds
setInterval(async () => {
    await fetchBookings();  // or fetchAndRenderOrders()
}, 30000);
```

This means:
- Changes made in one browser tab appear in another tab within 30 seconds
- No manual refresh needed
- Multiple admins see updates in real-time

## Error Handling

All endpoints return structured JSON responses:

**Success Response:**
```json
{
    "success": true,
    "message": "Operation completed",
    "data": { /* response data */ }
}
```

**Error Response:**
```json
{
    "success": false,
    "error": "Error description"
}
```

**Status Codes:**
- `200` - Success
- `201` - Created
- `400` - Bad request (validation error)
- `401` - Unauthorized (not logged in)
- `404` - Not found
- `405` - Method not allowed
- `409` - Conflict (e.g., duplicate date)
- `500` - Server error

## Browser Console Debugging

All operations log to browser console:

```javascript
console.log('🔍 Admin Check:', { isAdmin: true });
console.log('✅ Admin detected - showing dashboard');
console.error('❌ Failed to fetch bookings:', error);
```

Enable Developer Tools (F12) and check the Console tab for any errors.

## Production Notes

1. **Authentication** - Currently uses demo credentials (admin/admin123)
   - Integrate with secure authentication system
   - Use proper session tokens or JWT

2. **HTTPS** - Must be enabled in production
   - Uncomment HTTPS redirect in .htaccess

3. **Rate Limiting** - Currently per-IP
   - May need adjustment for multiple admin users on same network

4. **Data Retention** - No automatic cleanup
   - Consider archiving old orders/bookings periodically

5. **Concurrent Updates** - Polling interval may cause conflicts
   - For critical operations, implement optimistic locking or conflict detection

## Troubleshooting

### "Unauthorized - Please log in"
- Session expired
- Click Login button and enter credentials (admin/admin123)
- Refresh page if issue persists

### "Failed to fetch bookings from database"
- Check network tab (F12) for API errors
- Verify `/api/get-bookings.php` exists and returns data
- Check browser console for specific error message

### No changes appear
- Wait 30 seconds for automatic refresh
- Or refresh page manually
- Check if you have the right permissions

### Data shows as [object Object]
- Browser console JavaScript error
- Check F12 Developer Tools Console tab
- Report the exact error message

## Support

For issues or questions:
1. Check browser console (F12 → Console)
2. Check network requests (F12 → Network)
3. Review API response (should be valid JSON)
4. Check /api endpoints are returning data
