# 🗒️ Database Migration: Quick Start Guide

## One-Minute Setup

### Option 1: PHPMyAdmin (Easiest)
1. Open PHPMyAdmin → Select your database
2. Click "SQL" tab
3. Copy & paste entire contents of `migration_delivery_options.sql`
4. Click "Go" to execute
5. ✅ Done! Check for success message

### Option 2: Command Line
```bash
# Navigate to project folder
cd c:\Users\IOT PROJECT\htdocs\seee

# Run migration
mysql -u root -p < database/migration_delivery_options.sql

# Enter password when prompted
```

### Option 3: Manual (If options 1-2 don't work)
1. Copy contents of `migration_delivery_options.sql`
2. Create new query in PHPMyAdmin
3. Paste and execute
4. Verify tables created with:
```sql
SELECT * FROM delivery_options;
SELECT COUNT(*) FROM orders WHERE delivery_method_id IS NOT NULL;
```

## What Gets Created

| Item | Description |
|------|-------------|
| `delivery_options` table | New table with 4 delivery methods |
| `orders.delivery_method_id` | New column (foreign key) |
| `orders.delivery_cost` | New column (decimal) |
| `orders.delivery_date` | New column (timestamp) |
| `orders.dispatch_slip_number` | New column (unique identifier) |

## Verify Installation

After migration, run this query:
```sql
-- Should return 4 rows (Standard, Express, Next Day, Pickup)
SELECT id, name, cost, delivery_time_min, delivery_time_max 
FROM delivery_options 
WHERE is_active = 1;
```

## Next Steps

1. ✅ Run migration
2. Test shopping cart checkout:
   - Add item to cart
   - Click "Checkout"
   - Select delivery method
   - Should see delivery options with costs
3. Verify order appears in admin panel with delivery info
4. Test dispatch slip generation

## Rollback (If needed)

To undo the migration:
```sql
-- Remove new columns from orders table
ALTER TABLE orders 
DROP COLUMN dispatch_slip_number,
DROP COLUMN delivery_date,
DROP COLUMN delivery_cost,
DROP FOREIGN KEY orders_ibfk_delivery_method;

ALTER TABLE orders DROP COLUMN delivery_method_id;

-- Remove delivery_options table
DROP TABLE delivery_options;
```

## Troubleshooting

**Q: "Table already exists" error**
- Migration was already run. This is OK. Skip and continue.

**Q: "Column already exists" error**
- Migration partially ran. Check what's already there, then skip duplicates.

**Q: Can't find migration file**
- Make sure `migration_delivery_options.sql` exists in `/database/` folder

**Q: After migration, delivery options don't show in checkout**
- Check if shopping-cart.html was updated (should fetch from /api/get-delivery-options.php)
- Verify /api/get-delivery-options.php exists and is working

## No Downtime Required

✅ Migration is safe to run on live database:
- Only adds new tables/columns
- Doesn't modify existing order data
- Existing orders continue to work
- No service interruption needed

---

**Current Status:** Ready for production  
**Estimated Setup Time:** 2-3 minutes  
**Risk Level:** Very Low (additive changes only)
