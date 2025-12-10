# Debts API - Client Documentation

## Overview
Complete guide for using the Debts API endpoints, including creating debts, sending reminders, and managing debt records.

---

## Base URL
```
http://localhost:8000/api/debts
```

---

## Endpoints

### 1. Create Debt (POST)
**Endpoint:** `POST /api/debts`

**Description:** Create a new debt record.

**Request Body:**
```json
{
  "memberId": 2,
  "debtType": "neder_shabbat",
  "amount": 150.00,
  "description": "Monthly dues",
  "gregorianDate": "25/12/2015",
  "status": "pending",
  "sendImmediateReminder": false
}
```

**Fields:**
- `memberId` (required) - Member ID who owes the debt
- `debtType` (optional, default: "other") - Type of debt:
  - `"neder_shabbat"` - נדר שבת
  - `"tikun_nezek"` - תיקון נזק
  - `"dmei_chaver"` - דמי חבר
  - `"kiddush"` - קידוש שבת
  - `"neder_yom_shabbat"` - נדר יום שבת
  - `"other"` - אחר
- `amount` (required) - Debt amount (numeric)
- `description` (optional) - Description of the debt (max 500 characters)
- `gregorianDate` (optional) - Due date in DD/MM/YYYY format (e.g., "25/12/2015")
- `status` (optional, default: "pending") - Debt status:
  - `"pending"` - Pending payment
  - `"paid"` - Paid
  - `"overdue"` - Overdue
  - `"cancelled"` - Cancelled
- `sendImmediateReminder` (optional, default: false) - If `true`, sets `lastReminderSentAt` to current timestamp

**Response:** 201 Created
```json
{
  "id": 1,
  "memberId": 2,
  "memberName": "Floy Zemlak",
  "type": "neder_shabbat",
  "amount": "150.00",
  "description": "Monthly dues",
  "gregorianDate": "2015-12-25",
  "status": "pending",
  "lastReminderSentAt": null,
  "createdAt": "2025-01-15T10:00:00.000000Z",
  "updatedAt": "2025-01-15T10:00:00.000000Z"
}
```

**Example with immediate reminder:**
```json
POST /api/debts
{
  "memberId": 2,
  "debtType": "neder_shabbat",
  "amount": 150.00,
  "gregorianDate": "25/12/2015",
  "sendImmediateReminder": true
}
```

**Response:**
```json
{
  "id": 1,
  "memberId": 2,
  "memberName": "Floy Zemlak",
  "type": "neder_shabbat",
  "amount": "150.00",
  "description": null,
  "gregorianDate": "2015-12-25",
  "status": "pending",
  "lastReminderSentAt": "2025-01-15T10:00:00.000000Z",
  "createdAt": "2025-01-15T10:00:00.000000Z",
  "updatedAt": "2025-01-15T10:00:00.000000Z"
}
```

---

### 2. Send Reminder (POST)
**Endpoint:** `POST /api/debts/{id}/reminder`

**Description:** Send a reminder for a specific debt. Updates the `lastReminderSentAt` timestamp.

**Example:**
```bash
POST /api/debts/2/reminder
```

**Response:** 200 OK
```json
{
  "id": 2,
  "memberId": 2,
  "memberName": "Floy Zemlak",
  "type": "other",
  "amount": "6.00",
  "description": "77",
  "gregorianDate": "2015-12-25",
  "status": "pending",
  "lastReminderSentAt": "2025-01-15T14:30:00.000000Z",
  "createdAt": "2025-01-01T10:00:00.000000Z",
  "updatedAt": "2025-01-15T14:30:00.000000Z"
}
```

---

### 3. List All Debts (GET)
**Endpoint:** `GET /api/debts`

**Query Parameters:**
- `limit` - Items per page (default: 15)
- `status` - Filter by status
- `member_id` - Filter by member ID
- `type` - Filter by debt type
- `sort` - Sort column (e.g., `amount`, `gregorianDate`, `status`, `type`, `-amount` for descending)

**Example:**
```
GET /api/debts?limit=50&status=pending&sort=-amount
```

---

### 4. Get Single Debt (GET)
**Endpoint:** `GET /api/debts/{id}`

**Response:**
```json
{
  "id": 2,
  "memberId": 2,
  "memberName": "Floy Zemlak",
  "type": "other",
  "amount": "6.00",
  "description": "77",
  "gregorianDate": "2015-12-25",
  "status": "pending",
  "lastReminderSentAt": "2025-01-15T14:30:00.000000Z",
  "createdAt": "2025-01-01T10:00:00.000000Z",
  "updatedAt": "2025-01-15T14:30:00.000000Z"
}
```

---

### 5. Update Debt (PUT/PATCH)
**Endpoint:** `PUT /api/debts/{id}` or `PATCH /api/debts/{id}`

**Request Body (all fields optional):**
```json
{
  "debtType": "tikun_nezek",
  "amount": 200.00,
  "gregorianDate": "30/12/2015",
  "status": "paid",
  "lastReminderSentAt": "25/12/2015"
}
```

---

### 6. Delete Debt (DELETE)
**Endpoint:** `DELETE /api/debts/{id}`

**Response:** 204 No Content

---

## Date Formats

### Input Formats (Accepted)
- `DD/MM/YYYY` - e.g., "25/12/2015"
- `DD/MM/YY` - e.g., "25/12/15" (interpreted as 2015)
- `YYYY-MM-DD` - e.g., "2015-12-25"

### Output Format
All dates are returned in ISO 8601 format:
- Dates: `"2015-12-25"`
- Timestamps: `"2025-01-15T14:30:00.000000Z"`

---

## Debt Types Reference

| Code | Hebrew Name |
|------|-------------|
| `neder_shabbat` | נדר שבת |
| `tikun_nezek` | תיקון נזק |
| `dmei_chaver` | דמי חבר |
| `kiddush` | קידוש שבת |
| `neder_yom_shabbat` | נדר יום שבת |
| `other` | אחר |

---

## Status Values

- `pending` - Debt is pending payment
- `paid` - Debt has been paid
- `overdue` - Debt is overdue
- `cancelled` - Debt has been cancelled

---

## Common Use Cases

### Create debt and send immediate reminder
```json
POST /api/debts
{
  "memberId": 2,
  "debtType": "neder_shabbat",
  "amount": 150.00,
  "gregorianDate": "25/12/2015",
  "sendImmediateReminder": true
}
```

### Send reminder for existing debt
```bash
POST /api/debts/2/reminder
```

### Update debt status to paid
```json
PUT /api/debts/2
{
  "status": "paid"
}
```

### Filter debts by status
```
GET /api/debts?status=pending&limit=100
```

---

## Notes

- All date fields accept DD/MM/YYYY format from the client
- Dates are automatically converted to YYYY-MM-DD format for storage
- `sendImmediateReminder` can be `true`, `false`, `"true"`, `"false"`, `1`, or `0`
- If `debtType` is not provided, it defaults to `"other"`
- If `status` is not provided, it defaults to `"pending"`
- `lastReminderSentAt` is automatically set when using `sendImmediateReminder: true` or calling the reminder endpoint
