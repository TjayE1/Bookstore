# Booking Management Guide

## How to Update Counselling Appointment Times

This guide explains how to manage the booking system in `shopping-cart.html`.

---

## 1. Update Booked Appointments

**Location:** Lines 741-746 in shopping-cart.html

```javascript
let bookedAppointments = [
    { date: '2026-01-10', time: '10:00' },
    { date: '2026-01-10', time: '14:00' },
    { date: '2026-01-12', time: '09:00' },
    { date: '2026-01-15', time: '13:00' }
];
```

**To add a booked appointment:**
```javascript
{ date: 'YYYY-MM-DD', time: 'HH:MM' },
```

**Example:**
```javascript
let bookedAppointments = [
    { date: '2026-01-10', time: '10:00' },
    { date: '2026-01-16', time: '15:00' },  // New booking added
];
```

---

## 2. Set Unavailable Dates

**Location:** Lines 748-754 in shopping-cart.html

```javascript
const unavailableDates = [
    '2026-01-11', // Sunday
    '2026-01-18', // Sunday  
    '2026-01-25', // Sunday
    '2026-01-20'  // Holiday
];
```

**To add a day off or holiday:**
```javascript
'YYYY-MM-DD',  // Description
```

**Example:**
```javascript
const unavailableDates = [
    '2026-01-11', // Sunday
    '2026-01-26', // Vacation day
    '2026-02-14', // Holiday
];
```

**Note:** Weekends (Saturdays and Sundays) are automatically blocked by the system.

---

## 3. Modify Available Time Slots

**Location:** Lines 756-765 in shopping-cart.html

```javascript
const availableTimeSlots = [
    { value: '09:00', label: '9:00 AM' },
    { value: '10:00', label: '10:00 AM' },
    { value: '11:00', label: '11:00 AM' },
    { value: '13:00', label: '1:00 PM' },
    { value: '14:00', label: '2:00 PM' },
    { value: '15:00', label: '3:00 PM' },
    { value: '16:00', label: '4:00 PM' },
    { value: '17:00', label: '5:00 PM' }
];
```

**To add a new time slot:**
```javascript
{ value: 'HH:MM', label: 'Display Name' },
```

**Examples:**

Add 8:00 AM slot:
```javascript
{ value: '08:00', label: '8:00 AM' },
```

Add 6:00 PM slot:
```javascript
{ value: '18:00', label: '6:00 PM' },
```

Remove lunch hour (remove 1:00 PM):
```javascript
// Just delete or comment out this line:
// { value: '13:00', label: '1:00 PM' },
```

---

## Quick Reference

### Date Format
- Use: `'YYYY-MM-DD'` (e.g., `'2026-01-15'`)

### Time Format
- Use 24-hour format: `'HH:MM'` (e.g., `'14:00'` for 2:00 PM)
- The system will automatically display in 12-hour format to users

### Common Times
- `'08:00'` = 8:00 AM
- `'09:00'` = 9:00 AM
- `'12:00'` = 12:00 PM
- `'13:00'` = 1:00 PM
- `'14:00'` = 2:00 PM
- `'17:00'` = 5:00 PM
- `'18:00'` = 6:00 PM

---

## How the System Works

1. **Automatic Weekend Blocking**: Saturdays and Sundays are automatically unavailable
2. **Date Selection**: When a user selects a date, the system checks:
   - Is it a weekend? ❌
   - Is it in the unavailable dates list? ❌
   - Are there available time slots? ✅
3. **Time Display**: Only available times are shown; booked slots appear as "(Booked)"
4. **Double-Booking Prevention**: System verifies availability before final confirmation

---

## Example: Full Update

```javascript
// Already booked sessions
let bookedAppointments = [
    { date: '2026-01-10', time: '10:00' },
    { date: '2026-01-10', time: '14:00' },
    { date: '2026-01-15', time: '09:00' },
    { date: '2026-01-16', time: '13:00' },
    { date: '2026-01-20', time: '15:00' }
];

// Days you're not available
const unavailableDates = [
    '2026-01-11', // Sunday
    '2026-01-18', // Sunday
    '2026-01-22', // Personal day
    '2026-01-26', // Holiday
];

// Your working hours
const availableTimeSlots = [
    { value: '08:00', label: '8:00 AM' },
    { value: '09:00', label: '9:00 AM' },
    { value: '10:00', label: '10:00 AM' },
    { value: '11:00', label: '11:00 AM' },
    // Lunch break
    { value: '14:00', label: '2:00 PM' },
    { value: '15:00', label: '3:00 PM' },
    { value: '16:00', label: '4:00 PM' },
    { value: '17:00', label: '5:00 PM' }
];
```

---

**Last Updated:** January 5, 2026
