# 🚀 PAYMENT SETUP - QUICK START (30 MINUTES)

## Step 1: Configure Your Payment Methods (5 min)
**Open:** `http://localhost/seee/setup-payments.php` in your browser

Fill in:
- ✅ **Bank Details** (takes 1 minute)
- ✅ **Mobile Money Numbers** (takes 1 minute)
- ✅ Save (takes 3 minutes)

👉 **You're NOW accepting Bank Transfer & Mobile Money payments!**

---

## Step 2: Add PayPal (Optional but Recommended - 10 min)

### Get PayPal Credentials:
1. Go to https://developer.paypal.com
2. Login with your **personal PayPal account**
3. Click **Apps & Credentials** → **Sandbox** tab
4. Copy **Client ID** and **Secret**

### Add to Configuration:
1. Open `http://localhost/seee/setup-payments.php`
2. Check "Enable PayPal Payments"
3. Paste Client ID and Secret
4. Keep Mode = "Sandbox" (for testing)
5. Save

### Test PayPal:
- Go to shopping cart
- Try a purchase with PayPal
- Use test card: `4111 1111 1111 1111` with any future date/CVC
- Payment should succeed in test mode

### Go Live (when ready):
1. Get **Live credentials** from PayPal Dashboard
2. Replace Sandbox credentials with Live ones
3. Change Mode from "Sandbox" → "Live"
4. Save - Done! Real PayPal payments now active

---

## Step 3: Add Stripe (Optional - Best for Cards - 10 min)

### Get Stripe Keys:
1. Go to https://stripe.com/register
2. Sign up with your email
3. Answer business questions (startup is fine)
4. Go to **Developers** → **API Keys**
5. Copy **Publishable Key** and **Secret Key**

### Add to Configuration:
1. Open `http://localhost/seee/setup-payments.php`
2. Check "Enable Stripe Card Payments"
3. Paste Publishable Key and Secret Key
4. Save

### Test Stripe:
- Go to shopping cart
- Try a purchase with card payment
- Use test card: `4242 4242 4242 4242`
- Any future date, any 3-digit CVC
- Payment succeeds immediately

### Go Live (when ready):
1. In Stripe Dashboard, enable Live mode
2. Get Live API keys
3. Replace test keys with Live keys
4. Save - Done! Real card payments now active

---

## Payment Methods Available

| Method | Setup Time | Registration Required | Instant Payout |
|--------|-----------|----------------------|-----------------|
| 🏦 Bank Transfer | 1 min | ❌ No | ❌ Manual |
| 📱 Mobile Money | 1 min | ❌ No | ❌ Manual |
| 🅿️ PayPal | 5 min | ✅ Personal* | ✅ Yes |
| 💳 Stripe Card | 5 min | ✅ Personal* | ✅ Yes |
| 📦 Pay on Delivery | 0 min | ❌ No | ❌ Manual |

*Personal account = No business registration needed!

---

## File Locations (Reference)

- **Configuration Interface:** `setup-payments.php`
- **Configuration File:** `.env` (created automatically)
- **Payment Config:** `config/payment-config.php`
- **API Endpoints:**
  - `api/payment/get-methods.php` - List payment methods
  - `api/payment/get-payment-instructions.php` - Get payment details
- **Documentation:** `docs/PAYMENT_SETUP_GUIDE.md`

---

## Testing Checklist

### Bank Transfer ✓
- [ ] Bank details show in checkout
- [ ] Customer can see complete payment instructions
- [ ] Payment instructions email sent

### Mobile Money ✓
- [ ] MTN/Airtel numbers show in checkout
- [ ] Customer receives payment instructions with phone number
- [ ] Reference number included

### PayPal ✓
- [ ] PayPal button appears when enabled
- [ ] Can process test payment
- [ ] Payment status updates automatically

### Stripe ✓
- [ ] Card payment option shows when enabled
- [ ] Can process test card payment
- [ ] Payment status updates automatically

---

## Payment Status Management (Admin)

After orders are placed:

**For Automatic Methods (PayPal, Stripe):**
- ✅ Payment confirmed instantly
- ✅ Receipt email sent automatically
- ✅ No manual action needed

**For Manual Methods (Bank Transfer, Mobile Money):**
1. Customer makes payment
2. You verify in your bank account
3. Go to Admin → Orders
4. Find order → Click "Mark as Paid"
5. Receipt email sent automatically

---

## Security Notes

⚠️ **Important:**
- Never share your **Stripe Secret Key** or **PayPal Secret**
- Keep `.env` file private (already done - permissions 0600)
- Always test in Sandbox mode first
- Verify SSL certificate when going live

---

## Support Resources

- **PayPal Docs:** https://developer.paypal.com/docs
- **Stripe Docs:** https://stripe.com/docs
- **Your Setup Page:** `setup-payments.php`

---

## What's Next?

✅ **This Week:**
1. Configure Bank Transfer + Mobile Money
2. Set up PayPal (5 min)
3. Start accepting payments!

📦 **Next Week:**
- Add Stripe for better card experience
- Monitor first payments in admin panel

---

**You're all set! 🎉 Start accepting payments now!**
