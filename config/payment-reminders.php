<?php
/**
 * Payment Reminder Configuration
 * Times are in minutes to allow fractional hours.
 */

// Enable/disable reminder sending
if (!defined('PAYMENT_REMINDERS_ENABLED')) {
    define('PAYMENT_REMINDERS_ENABLED', true);
}

// First reminder delay (2.5 hours)
if (!defined('PAYMENT_REMINDER_FIRST_MINUTES')) {
    define('PAYMENT_REMINDER_FIRST_MINUTES', 150);
}

// Repeat reminder interval (3 hours)
if (!defined('PAYMENT_REMINDER_REPEAT_MINUTES')) {
    define('PAYMENT_REMINDER_REPEAT_MINUTES', 180);
}

// Max reminders per order
if (!defined('PAYMENT_REMINDER_MAX_COUNT')) {
    define('PAYMENT_REMINDER_MAX_COUNT', 5);
}

// Max reminders sent per run
if (!defined('PAYMENT_REMINDER_BATCH_LIMIT')) {
    define('PAYMENT_REMINDER_BATCH_LIMIT', 50);
}
