# Receipts API - Client Documentation

## Endpoints

### List Receipts (Datatable)
**GET** `/api/receipts`

**Query Parameters:**
- `page` (integer) - Page number (default: 1)
- `limit` (integer) - Items per page (default: 15)
- `status` (string) - Filter: `pending`, `paid`, `cancelled`, `refunded`
- `type` (string) - Filter by receipt type (see types below)
- `user_id` (integer) - Filter by user ID
- `payment_method` (string) - Filter by payment method
- `date_from` (date) - Filter from date (YYYY-MM-DD)
- `date_to` (date) - Filter to date (YYYY-MM-DD)
- `sort_by` (string) - Sort field: `receipt_date`, `amount`, `type`, `status`, `payment_method`, `user`
- `sort_order` (string) - `asc` or `desc` (default: `desc`)

**Response:**
```json
{
  "rows": [
    {
      "id": 1,
      "receiptNumber": "TXN_abc123",
      "userId": 1,
      "userName": "John Doe",
      "totalAmount": "150.50",
      "taxAmount": "0.00",
      "subtotal": "150.50",
      "status": "paid",
      "paymentMethod": "credit_card",
      "receiptDate": "2025-12-11T10:30:00.000000Z",
      "notes": "Monthly membership fee",
      "type": "membership_fees"
    }
  ],
  "counts": {
    "totalRows": 50,
    "totalPages": 5
  },
  "totalSum": "7525.00"
}
```

---

### Get Single Receipt
**GET** `/api/receipts/{id}`

**Response:** Single receipt object (same structure as rows above, plus `createdAt`, `updatedAt`)

---

### Update Receipt
**PUT** `/api/receipts/{id}`

**Request Body:**
```json
{
  "status": "paid",
  "notes": "Payment received",
  "type": "membership_fees",
  "total_amount": 150.50,
  "tax_amount": 0.00,
  "subtotal": 150.50,
  "payment_method": "credit_card",
  "receipt_date": "2025-12-11",
  "user_id": 1
}
```

**Response:** Updated receipt object

---

### Delete Receipt
**DELETE** `/api/receipts/{id}`

**Response:** 204 No Content

---

### Receipt Statistics
**GET** `/api/receipts/stats`

**Query Parameters:**
- `month` (string) - Month in `YYYY-MM` format (default: current month)
- `months_back` (integer) - Number of months for trend (default: 3)

**Response:**
```json
{
  "monthlyTotal": {
    "amount": "25234.00",
    "month": "2025-01",
    "currency": "₪"
  },
  "categoryDistribution": [
    {
      "type": "membership_fees",
      "label": "דמי חברים",
      "amount": "9000.00",
      "percentage": 36
    }
  ],
  "trend": [
    {
      "month": "2024-11",
      "amount": "35874.00"
    }
  ],
  "uncollectedReceipts": {
    "total": "3451.00",
    "percentage": 14,
    "month": "2025-01"
  }
}
```

---

### Export Receipts
**POST** `/api/receipts/export`

**Request Body:**
```json
{
  "file_type": "xls",
  "status": "paid",
  "type": "membership_fees",
  "date_from": "2025-01-01",
  "date_to": "2025-01-31"
}
```

**File Types:** `xls`, `csv`, `pdf`

**Response:** File download with Hebrew headings

---

## Receipt Types

| Type | Hebrew Label |
|------|--------------|
| `vows` | נדרים |
| `community_donations` | תרומות מהקהילה |
| `external_donations` | תרומות חיצוניות |
| `ascensions` | עליות |
| `online_donations` | תרומות אונליין |
| `membership_fees` | דמי חברים |
| `other` | אחר |

---

## Status Values

| Status | Hebrew Label |
|--------|--------------|
| `pending` | ממתין |
| `paid` | שולם |
| `cancelled` | בוטל |
| `refunded` | הוחזר |

---

## JavaScript Example

```javascript
// List receipts
fetch('/api/receipts?page=1&limit=10&status=paid&sort_by=receipt_date&sort_order=desc')
  .then(res => res.json())
  .then(data => console.log(data.rows));

// Get statistics
fetch('/api/receipts/stats?month=2025-01&months_back=3')
  .then(res => res.json())
  .then(data => {
    console.log('Total:', data.monthlyTotal.amount);
    console.log('Categories:', data.categoryDistribution);
    console.log('Trend:', data.trend);
    console.log('Uncollected:', data.uncollectedReceipts.total);
  });

// Update receipt
fetch('/api/receipts/1', {
  method: 'PUT',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ status: 'paid' })
})
  .then(res => res.json())
  .then(data => console.log('Updated:', data));
```

---

## Notes

- Receipts are automatically created when a charge succeeds via `/api/billing/charge`
- All endpoints require authentication (Bearer token)
- All amounts are returned as strings with 2 decimal places
- Receipts are filtered by business_id automatically

