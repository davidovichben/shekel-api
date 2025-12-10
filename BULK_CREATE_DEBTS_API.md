# Bulk Create Debts API Documentation

## Endpoint
```
POST /api/debts/bulk
```

## Description
Create multiple debts in a single API call. All debts are created within a database transaction, ensuring that either all debts are created successfully or none at all (all-or-nothing behavior).

---

## Request

### Method
`POST`

### URL
```
http://localhost:8000/api/debts/bulk
```

### Headers
```
Content-Type: application/json
```

### Request Body
Array of debt objects:

```json
[
  {
    "memberId": "123",
    "debtType": "neder_shabbat",
    "amount": 540,
    "description": "נדר שבת שובה - ראשונה",
    "gregorianDate": "15/08/2025",
    "sendImmediateReminder": true,
    "status": "pending"
  },
  {
    "memberId": "456",
    "debtType": "neder_shabbat",
    "amount": 320,
    "description": "נדר שבת שובה - שנייה",
    "gregorianDate": "15/08/2025",
    "sendImmediateReminder": false,
    "status": "pending"
  }
]
```

---

## Request Fields

### Required Fields
- `memberId` (string/number) - **Required** - The ID of the member who owes the debt

### Optional Fields
- `debtType` (string) - Type of debt. Defaults to `"other"` if not provided.
  - Valid values:
    - `"neder_shabbat"` - נדר שבת
    - `"tikun_nezek"` - תיקון נזק
    - `"dmei_chaver"` - דמי חבר
    - `"kiddush"` - קידוש שבת
    - `"neder_yom_shabbat"` - נדר יום שבת
    - `"other"` - אחר (default)
- `amount` (number) - **Required** - The debt amount (e.g., 540, 320.50)
- `description` (string) - Description of the debt (max 500 characters)
- `gregorianDate` (string) - Due date in **DD/MM/YYYY** format (e.g., "15/08/2025")
- `sendImmediateReminder` (boolean) - If `true`, sets `lastReminderSentAt` to current date/time. Defaults to `false`
- `status` (string) - Debt status. Defaults to `"pending"` if not provided.
  - Valid values:
    - `"pending"` - Pending payment (default)
    - `"paid"` - Paid
    - `"overdue"` - Overdue
    - `"cancelled"` - Cancelled

---

## Response

### Success Response (200 OK)

Returns an array of created debt objects:

```json
[
  {
    "id": "101",
    "memberId": "123",
    "memberName": "משה המנשה",
    "debtType": "neder_shabbat",
    "amount": "540.00",
    "description": "נדר שבת שובה - ראשונה",
    "gregorianDate": "15/08/2025",
    "status": "pending",
    "lastReminderSentAt": "15/08/2025",
    "createdAt": "2025-01-15T10:00:00.000000Z",
    "updatedAt": "2025-01-15T10:00:00.000000Z"
  },
  {
    "id": "102",
    "memberId": "456",
    "memberName": "אברהם יצחק",
    "debtType": "neder_shabbat",
    "amount": "320.00",
    "description": "נדר שבת שובה - שנייה",
    "gregorianDate": "15/08/2025",
    "status": "pending",
    "lastReminderSentAt": null,
    "createdAt": "2025-01-15T10:00:00.000000Z",
    "updatedAt": "2025-01-15T10:00:00.000000Z"
  }
]
```

### Error Response (422 Unprocessable Entity)

If validation fails for any debt, the entire operation is rolled back and no debts are created:

```json
{
  "message": "Failed to create debts",
  "error": "The member id field is required."
}
```

---

## Response Fields

- `id` (string) - Unique debt ID
- `memberId` (string) - Member ID
- `memberName` (string) - Full name of the member
- `debtType` (string) - Type of debt
- `amount` (string) - Debt amount (formatted as decimal)
- `description` (string|null) - Debt description
- `gregorianDate` (string|null) - Due date in **DD/MM/YYYY** format
- `status` (string) - Debt status
- `lastReminderSentAt` (string|null) - Last reminder sent date in **DD/MM/YYYY** format (set if `sendImmediateReminder` was `true`)
- `createdAt` (string) - Creation timestamp (ISO 8601)
- `updatedAt` (string) - Last update timestamp (ISO 8601)

---

## Important Notes

### Transaction Behavior
- **All debts are created in a single database transaction**
- If **any** debt fails validation, **the entire operation is rolled back**
- No partial creation - it's all or nothing

### Date Format
- **Input:** Dates must be in `DD/MM/YYYY` format (e.g., "15/08/2025")
- **Output:** Dates are returned in `DD/MM/YYYY` format
- Supports both 2-digit and 4-digit years:
  - `"15/08/25"` → interpreted as `"15/08/2025"`
  - `"15/08/2025"` → used as-is

### Immediate Reminder
- If `sendImmediateReminder: true`, the `lastReminderSentAt` field is automatically set to the current date/time
- The date is returned in `DD/MM/YYYY` format in the response

### Default Values
- If `debtType` is not provided or empty → defaults to `"other"`
- If `status` is not provided → defaults to `"pending"`
- If `sendImmediateReminder` is not provided → defaults to `false`

---

## Example Usage

### cURL Example
```bash
curl -X POST http://localhost:8000/api/debts/bulk \
  -H "Content-Type: application/json" \
  -d '[
    {
      "memberId": "123",
      "debtType": "neder_shabbat",
      "amount": 540,
      "description": "נדר שבת שובה - ראשונה",
      "gregorianDate": "15/08/2025",
      "sendImmediateReminder": true,
      "status": "pending"
    },
    {
      "memberId": "456",
      "debtType": "neder_shabbat",
      "amount": 320,
      "description": "נדר שבת שובה - שנייה",
      "gregorianDate": "15/08/2025",
      "sendImmediateReminder": false,
      "status": "pending"
    }
  ]'
```

### JavaScript/Fetch Example
```javascript
const debts = [
  {
    memberId: "123",
    debtType: "neder_shabbat",
    amount: 540,
    description: "נדר שבת שובה - ראשונה",
    gregorianDate: "15/08/2025",
    sendImmediateReminder: true,
    status: "pending"
  },
  {
    memberId: "456",
    debtType: "neder_shabbat",
    amount: 320,
    description: "נדר שבת שובה - שנייה",
    gregorianDate: "15/08/2025",
    sendImmediateReminder: false,
    status: "pending"
  }
];

fetch('http://localhost:8000/api/debts/bulk', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify(debts)
})
  .then(response => response.json())
  .then(data => console.log('Success:', data))
  .catch((error) => console.error('Error:', error));
```

---

## Validation Rules

- `memberId` - Must exist in the members table
- `debtType` - Must be one of the valid enum values
- `amount` - Must be a valid number
- `description` - Maximum 500 characters
- `gregorianDate` - Must be a valid date in DD/MM/YYYY format
- `status` - Must be one of: "pending", "paid", "overdue", "cancelled"

---

## Error Handling

If any debt in the array fails validation:
- The entire operation is rolled back
- No debts are created
- A 422 error is returned with details about what went wrong

Example error response:
```json
{
  "message": "Failed to create debts",
  "error": "The member id field is required. (and 1 more error)"
}
```
