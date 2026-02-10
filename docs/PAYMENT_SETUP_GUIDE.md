# Payment Integration Setup Guide

## Quick Summary
Your system now supports **5 payment methods** - get started immediately with Bank Transfer and Mobile Money (no registration required!), then add PayPal and Stripe later.

---

## 1. BANK TRANSFER (Immediate - No Setup Required) ✅

### Setup in 2 Steps:
**Step 1:** Add your bank details to `.env` file:
```
BANK_NAME="Your Bank Name"
ACCOUNT_NAME="Your Business Name"
ACCOUNT_NUMBER="1234567890"
BANK_CURRENCY="UGX"
SWIFT_CODE="XXXXX" # optional for international
IBAN="UX00XXXX..." # optional for international
```

**Step 2:** That's it! Customers will see payment instructions on checkout.

### How it Works:
- Customer selects "Bank Transfer"
- Gets bank details and order reference
- Makes manual transfer
- You verify and mark as paid in admin panel

---

## 2. MOBILE MONEY (Immediate - No Setup Required) ✅

### Setup in 2 Steps:
**Step 1:** Add your mobile money numbers to `.env`:
```
MTN_NUMBER="+256700000000"
AIRTEL_NUMBER="+256700000001"
```

**Step 2:** Done! Customers can pay via MTN or Airtel Money.

---

## 3. PAYPAL (No Business Registration Needed!) 🎯

### Why PayPal is Perfect:
- ✅ Works with **Personal Account** (no business registration)
- ✅ Instant setup (5 minutes)
- ✅ No monthly fees
- ✅ Automatic payment confirmation
- ✅ Available in 190+ countries

### Setup Steps:

**Step 1: Create Personal PayPal Account (if you don't have)**
1. Go to https://www.paypal.com/signup
2. Sign up with your personal email
3. Add a payment method (credit card or bank)

**Step 2: Set Up Developer Credentials**
1. Go to https://developer.paypal.com
2. Login with your PayPal account
3. Click "Apps & Credentials" → "Sandbox" tab
4. Copy your **Client ID** and **Secret**
5. Also get **Live Credentials** (you'll use later)

**Step 3: Add to `.env` file:**
```
PAYPAL_ENABLED=true
PAYPAL_CLIENT_ID="your_sandbox_client_id_here"
PAYPAL_SECRET="your_sandbox_secret_here"
PAYPAL_MODE="sandbox"  # Change to "live" when ready
```

**Step 4: Test Mode** (sandbox)
- Use test credentials from Step 2
- Test payments work immediately

**Step 5: Go Live** (take live payments)
- Switch Client ID/Secret to Live credentials
- Change `PAYPAL_MODE="live"`
- Done!

### Using PayPal Button (Add to Checkout):
```html
<div id="paypal-button-container"></div>

<script src="https://www.paypal.com/sdk/js?client-id=YOUR_CLIENT_ID"></script>
<script>
paypal.Buttons({
    createOrder: function(data, actions) {
        return actions.order.create({
            purchase_units: [{
                amount: { value: "29.99" }
            }]
        });
    },
    onApprove: function(data, actions) {
        return actions.order.capture().then(function(orderData) {
            console.log('Order captured:', orderData);
            // Update order status in your system
        });
    }
}).render('#paypal-button-container');
</script>
```

---

## 4. STRIPE (Best for Card Payments) 💳

### Why Stripe is Ideal:
- ✅ No business registration required (works with personal account)
- ✅ Automatic activation (3-5 minutes)
- ✅ 3.5% + $0.30 per transaction
- ✅ Works in 135+ countries
- ✅ Better UX than PayPal

### Setup Steps:

**Step 1: Create Stripe Account**
1. Go to https://stripe.com/register
2. Use your personal email
3. Answer business questions honestly (startup is fine)

**Step 2: Get API Keys**
1. Login to Stripe Dashboard
2. Go to Developers → API Keys
3. Copy:
   - **Publishable Key** (starts with `pk_test_` or `pk_live_`)
   - **Secret Key** (starts with `sk_test_` or `sk_live_`)

**Step 3: Add to `.env`:**
```
STRIPE_ENABLED=true
STRIPE_PUBLIC_KEY="pk_test_your_key"
STRIPE_SECRET_KEY="sk_test_your_key"
```

**Step 4: Test Stripe**
- Use test card: `4242 4242 4242 4242`
- Any future date, any CVC
- Payments show in test dashboard

**Step 5: Go Live**
- Switch to Live API Keys
- Stripe automatically processes real payments

### Stripe Integration (Simple):
```html
<script src="https://js.stripe.com/v3/"></script>
<div id="card-element"></div>
<button id="card-button">Pay Now</button>

<script>
const stripe = Stripe('pk_test_your_key');
const elements = stripe.elements();
const cardElement = elements.create('card');
cardElement.mount('#card-element');

document.getElementById('card-button').addEventListener('click', async () => {
    const {token} = await stripe.createToken(cardElement);
    if (token) {
        // Send token to your backend to process payment
        console.log(token);
    }
});
</script>
```

---

## 5. PAY ON DELIVERY (Already Working) 📦

- No setup needed
- Customers pay when order arrives
- You mark as paid in admin

---

## Environment File (.env) Template

Create a `.env` file in your root directory:

```bash
# DATABASE
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=readers_haven

# BANK TRANSFER
BANK_NAME="Your Bank"
ACCOUNT_NAME="Business Name"
ACCOUNT_NUMBER="1234567890"
BANK_CURRENCY="UGX"

# MOBILE MONEY
MTN_NUMBER="+256700000000"
AIRTEL_NUMBER="+256700000001"

# PAYPAL (Personal Account)
PAYPAL_ENABLED=true
PAYPAL_CLIENT_ID="Adoxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
PAYPAL_SECRET="EEexxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
PAYPAL_MODE="sandbox"

# STRIPE
STRIPE_ENABLED=true
STRIPE_PUBLIC_KEY="pk_test_xxxxxxxxxxxxxxxxxxxxxxxxx"
STRIPE_SECRET_KEY="sk_test_xxxxxxxxxxxxxxxxxxxxxxxxx"
STRIPE_WEBHOOK_SECRET="whsec_xxxxxxxxxxxxxxxxxxxxxx"
```

---

## Recommended Payment Method Priority

For **immediate launch**:
1. ✅ **Bank Transfer** - Zero setup
2. ✅ **Mobile Money** - Just add phone numbers
3. ✅ **Pay on Delivery** - Already working

For **within 1 week**:
4. 🎯 **PayPal** - 5 min setup, personal account works
5. 💳 **Stripe** - 5 min setup, best card experience

---

## Admin Payment Management

All payments (manual & automatic) show in:
- **Admin Dashboard** → Orders
- Filter by payment status: Pending, Completed, Failed

### For Manual Methods (Bank Transfer, Mobile Money):
1. Customer pays manually
2. Verify payment in your bank
3. Go to admin → Click order → Mark as "Paid"
4. System auto-sends receipt email

### For Automatic Methods (PayPal, Stripe):
- Payments confirmed instantly
- Automatic receipt emails sent
- No manual intervention needed

---

## Next Steps

1. **TODAY**: Add bank details & mobile numbers
2. **This Week**: Set up PayPal (5 min) + test
3. **Next Week**: Add Stripe (5 min) + test

That's it! You're ready to accept payments now! 🚀
