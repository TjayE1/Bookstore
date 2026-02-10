# Payment Integration Checklist ✅

## Installation Verification

- [ ] **config/payment-config.php** exists
- [ ] **api/payment/get-methods.php** exists
- [ ] **api/payment/get-payment-instructions.php** exists
- [ ] **includes/EnvironmentConfig.php** exists
- [ ] **setup-payments.php** accessible
- [ ] **validate-payments.php** accessible
- [ ] Documentation files created

## Configuration Setup (15 minutes)

### Step 1: Access Setup Page
- [ ] Open `http://localhost/seee/setup-payments.php` in browser
- [ ] Page loads without errors
- [ ] Form displays correctly

### Step 2: Bank Transfer (1 minute)
- [ ] Enter bank name
- [ ] Enter account holder name
- [ ] Enter account number
- [ ] Select currency (usually UGX)
- [ ] Save successfully

### Step 3: Mobile Money (1 minute)
- [ ] Enter MTN number (format: +256XXXXXXXXX)
- [ ] Enter Airtel number (format: +256XXXXXXXXX)
- [ ] Save successfully

### Step 4: PayPal Setup (Optional - 5 minutes)
- [ ] Account created at paypal.com
- [ ] Developer account set up at developer.paypal.com
- [ ] Sandbox Client ID copied
- [ ] Sandbox Secret copied
- [ ] Entered in setup page
- [ ] Mode set to "Sandbox"
- [ ] Saved successfully

### Step 5: Stripe Setup (Optional - 5 minutes)
- [ ] Account created at stripe.com
- [ ] Test API keys copied from Dashboard
- [ ] Publishable Key entered
- [ ] Secret Key entered
- [ ] Saved successfully

## Validation Testing

- [ ] Run `http://localhost/seee/validate-payments.php`
- [ ] All checks show ✓ PASS
- [ ] No errors reported

## Integration Testing

### Shopping Cart Integration
- [ ] Open shopping-cart.html
- [ ] Add items to cart
- [ ] Click Checkout
- [ ] Payment method selection appears
- [ ] All enabled methods show in dropdown

### Bank Transfer Testing
- [ ] Select "Bank Transfer" in checkout
- [ ] Proceed to payment
- [ ] Bank details display correctly
- [ ] Order number shown as reference
- [ ] Total amount correct
- [ ] Order created successfully

### Mobile Money Testing
- [ ] Select "Mobile Money" in checkout
- [ ] Choose MTN or Airtel
- [ ] Phone number displays correctly
- [ ] Order number shown as reference
- [ ] Total amount correct
- [ ] Order created successfully

### PayPal Testing (if enabled)
- [ ] Select "PayPal" in checkout
- [ ] PayPal button/redirect appears
- [ ] Can complete test payment with test card: `4111 1111 1111 1111`
- [ ] Payment confirmation received
- [ ] Order status updates automatically

### Stripe Testing (if enabled)
- [ ] Select "Card Payment" in checkout
- [ ] Stripe payment form appears
- [ ] Can enter test card: `4242 4242 4242 4242`
- [ ] Any future date, any 3-digit CVC
- [ ] Payment processes successfully
- [ ] Order status updates automatically

### Pay on Delivery Testing
- [ ] Select "Pay on Delivery" in checkout
- [ ] Order creates without payment gateway
- [ ] Confirmation email sent
- [ ] Order shows pending payment in admin

## Admin Panel Testing

- [ ] Go to Admin → Orders
- [ ] All test orders appear
- [ ] Payment status shows correctly for each method
- [ ] For manual methods: Can manually mark as "Paid"
- [ ] For automatic methods: Status updated automatically
- [ ] Receipt emails sent correctly

## Email System Testing

- [ ] Bank Transfer order → Email sent with payment details
- [ ] Mobile Money order → Email sent with payment details
- [ ] PayPal order → Receipt email received
- [ ] Stripe order → Receipt email received
- [ ] All emails contain order details and total amount

## Security Verification

- [ ] .env file created in root directory
- [ ] .env file permissions set to 0600 (restricted)
- [ ] .env not committed to git (if using git)
- [ ] No sensitive keys in PHP files
- [ ] CORS validation working
- [ ] Rate limiting functional
- [ ] CSRF tokens present on forms

## Documentation Complete

- [ ] START_HERE_PAYMENTS.md - Main guide
- [ ] PAYMENT_QUICK_START.md - Quick reference
- [ ] docs/PAYMENT_SETUP_GUIDE.md - Detailed setup
- [ ] PAYMENT_IMPLEMENTATION_COMPLETE.md - Implementation guide
- [ ] PAYMENT_VISUAL_GUIDE.md - Visual architecture

## Live Setup (When Ready)

### PayPal Live Activation
- [ ] Get Live credentials from PayPal Dashboard
- [ ] Replace Sandbox credentials with Live keys
- [ ] Change Mode from "Sandbox" to "Live"
- [ ] Test with small amount
- [ ] Verify funds received in PayPal account

### Stripe Live Activation
- [ ] Get Live API keys from Stripe Dashboard
- [ ] Replace test keys with live keys
- [ ] Disable test mode
- [ ] Test with small amount
- [ ] Verify charges appear in Stripe Dashboard

## Post-Launch

- [ ] Monitor first payments
- [ ] Verify customer communications
- [ ] Check order fulfillment workflow
- [ ] Verify admin notifications
- [ ] Monitor for payment failures
- [ ] Keep backups of configuration
- [ ] Document any issues

## Troubleshooting Checklist

If payments not working:

### For All Methods
- [ ] Check if payment-config.php is loaded
- [ ] Verify database connection working
- [ ] Check .env file exists and readable
- [ ] Run validate-payments.php for diagnostics

### For Bank Transfer/Mobile Money
- [ ] Verify bank details entered correctly
- [ ] Check mobile numbers have correct format
- [ ] Verify order number generated
- [ ] Check email system working

### For PayPal
- [ ] Verify Client ID is correct
- [ ] Verify Secret is correct
- [ ] Check Mode matches environment (Sandbox vs Live)
- [ ] Verify PayPal button loading in browser console
- [ ] Check browser for JavaScript errors

### For Stripe
- [ ] Verify Public Key is correct
- [ ] Verify Secret Key is correct
- [ ] Check Stripe.js loading correctly
- [ ] Verify card element rendering
- [ ] Check browser console for JavaScript errors

## Sign-Off

- [ ] All checklist items completed
- [ ] System tested end-to-end
- [ ] Team trained on payment verification
- [ ] Documentation reviewed
- [ ] Ready for customer payments

---

## Quick Links

- **Setup:** http://localhost/seee/setup-payments.php
- **Validate:** http://localhost/seee/validate-payments.php
- **Main Guide:** [START_HERE_PAYMENTS.md](START_HERE_PAYMENTS.md)
- **Quick Ref:** [PAYMENT_QUICK_START.md](PAYMENT_QUICK_START.md)
- **Full Docs:** [docs/PAYMENT_SETUP_GUIDE.md](docs/PAYMENT_SETUP_GUIDE.md)

---

**Version:** 1.0
**Last Updated:** February 5, 2026
**Status:** Ready for Production ✅
