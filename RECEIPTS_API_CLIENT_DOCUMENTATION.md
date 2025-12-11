# Receipts API - Client Documentation

Complete API documentation for the Receipts feature including CRUD operations, filters, statistics, and export functionality.

---

## Table of Contents

1. [List Receipts (Datatable)](#list-receipts-datatable)
2. [Get Single Receipt](#get-single-receipt)
3. [Update Receipt](#update-receipt)
4. [Delete Receipt](#delete-receipt)
5. [Receipt Statistics](#receipt-statistics)
6. [Export Receipts](#export-receipts)
7. [Receipt Types](#receipt-types)
8. [Status Values](#status-values)
9. [Payment Methods](#payment-methods)

---

## List Receipts (Datatable)

**GET** `/api/receipts`

Returns a paginated list of receipts with filtering and sorting options.

### Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `page` | integer | Page number (default: 1) |
| `limit` | integer | Items per page (default: 15) |
| `status` | string | Filter by status: `pending`, `paid`, `cancelled`, `refunded` |
| `type` | string | Filter by receipt type (see [Receipt Types](#receipt-types)) |
| `user_id` | integer | Filter by user ID |
| `payment_method` | string | Filter by payment method |
| `date_from` | date | Filter receipts from this date (YYYY-MM-DD) |
| `date_to` | date | Filter receipts until this date (YYYY-MM-DD) |
| `sort_by` | string | Sort field: `receipt_date`, `amount`, `total_amount`, `type`, `status`, `payment_method`, `user` |
| `sort_order` | string | Sort direction: `asc` or `desc` (default: `desc`) |

### Example Request

```
GET /api/receipts?page=1&limit=10&status=paid&type=membership_fees&sort_by=receipt_date&sort_order=desc
```

### Response

```json
{
  "rows": [
    {
      "id": 1,
      "receiptNumber": "TXN_abc123xyz4567890",
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

## Get Single Receipt

**GET** `/api/receipts/{id}`

Returns details of a specific receipt.

### Example Request

```
GET /api/receipts/1
```

### Response

```json
{
  "id": "1",
  "receiptNumber": "TXN_abc123xyz4567890",
  "userId": "1",
  "userName": "John Doe",
  "totalAmount": "150.50",
  "taxAmount": "0.00",
  "subtotal": "150.50",
  "status": "paid",
  "paymentMethod": "credit_card",
  "receiptDate": "2025-12-11T10:30:00.000000Z",
  "notes": "Monthly membership fee",
  "type": "membership_fees",
  "createdAt": "2025-12-11T10:30:00.000000Z",
  "updatedAt": "2025-12-11T10:30:00.000000Z"
}
```

---

## Update Receipt

**PUT** `/api/receipts/{id}`

Updates an existing receipt.

### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `receipt_number` | string | No | Receipt number (must be unique) |
| `user_id` | integer | No | User ID (must exist in users table) |
| `total_amount` | decimal | No | Total amount (min: 0) |
| `tax_amount` | decimal | No | Tax amount (min: 0) |
| `subtotal` | decimal | No | Subtotal amount (min: 0) |
| `status` | enum | No | Status: `pending`, `paid`, `cancelled`, `refunded` |
| `payment_method` | string | No | Payment method |
| `receipt_date` | date | No | Receipt date |
| `notes` | string | No | Notes/comments |
| `type` | enum | No | Receipt type (see [Receipt Types](#receipt-types)) |

### Example Request

```json
{
  "status": "paid",
  "notes": "Payment received successfully"
}
```

### Response

Returns the updated receipt object (same format as Get Single Receipt).

---

## Delete Receipt

**DELETE** `/api/receipts/{id}`

Deletes a receipt.

### Example Request

```
DELETE /api/receipts/1
```

### Response

- **204 No Content** - Receipt deleted successfully

---

## Receipt Statistics

**GET** `/api/receipts/stats`

Returns statistics for receipts dashboard.

### Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `month` | string | Month in format `YYYY-MM` (default: current month) |
| `months_back` | integer | Number of months for trend data (default: 3) |

### Example Request

```
GET /api/receipts/stats?month=2025-01&months_back=3
```

### Response

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
    },
    {
      "type": "vows",
      "label": "נדרים",
      "amount": "1530.00",
      "percentage": 6
    }
  ],
  "trend": [
    {
      "month": "2024-11",
      "amount": "35874.00"
    },
    {
      "month": "2024-12",
      "amount": "31125.00"
    },
    {
      "month": "2025-01",
      "amount": "25234.00"
    }
  ],
  "uncollectedReceipts": {
    "total": "3451.00",
    "percentage": 14,
    "month": "2025-01"
  }
}
```

### Response Fields

**monthlyTotal**
- `amount` - Total receipts for the month (string, 2 decimals)
- `month` - Month in YYYY-MM format
- `currency` - Currency symbol (₪)

**categoryDistribution** (sorted by amount, descending)
- `type` - Receipt type code
- `label` - Hebrew label
- `amount` - Category total (string, 2 decimals)
- `percentage` - Percentage of monthly total (integer)

**trend** (chronologically ordered)
- `month` - Month in YYYY-MM format
- `amount` - Monthly total (string, 2 decimals)

**uncollectedReceipts**
- `total` - Uncollected amount (status='pending') (string, 2 decimals)
- `percentage` - Percentage of monthly total (integer)
- `month` - Month in YYYY-MM format

---

## Export Receipts

**POST** `/api/receipts/export`

Exports receipts to Excel (XLS), CSV, or PDF format.

### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `file_type` | enum | Yes | Export format: `xls`, `csv`, `pdf` |
| `ids` | array | No | Array of receipt IDs to export (if not provided, uses filters) |
| `status` | string | No | Filter by status |
| `type` | string | No | Filter by type |
| `user_id` | integer | No | Filter by user ID |
| `payment_method` | string | No | Filter by payment method |
| `date_from` | date | No | Filter from date |
| `date_to` | date | No | Filter to date |

### Example Request

```json
{
  "file_type": "xls",
  "status": "paid",
  "type": "membership_fees",
  "date_from": "2025-01-01",
  "date_to": "2025-01-31"
}
```

### Response

Returns a file download (Excel, CSV, or PDF) with Hebrew headings and RTL layout.

**Excel/CSV Columns:**
- מספר קבלה (Receipt Number)
- משתמש (User)
- סכום כולל (Total Amount)
- מע"מ (Tax)
- סכום לפני מע"מ (Subtotal)
- סטטוס (Status)
- אמצעי תשלום (Payment Method)
- תאריך (Date)
- הערות (Notes)
- סוג (Type)

---

## Receipt Types

| Type Code | Hebrew Label |
|-----------|--------------|
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

## Payment Methods

Common payment methods include:
- `credit_card` - Credit card payment
- `bank_transfer` - Bank transfer
- `cash` - Cash payment
- `check` - Check payment

---

## JavaScript Examples

### List Receipts

```javascript
async function getReceipts(filters = {}) {
  const params = new URLSearchParams({
    page: filters.page || 1,
    limit: filters.limit || 15,
    ...filters
  });
  
  const response = await fetch(`/api/receipts?${params}`, {
    headers: {
      'Accept': 'application/json',
    },
  });
  
  return await response.json();
}

// Usage
getReceipts({
  status: 'paid',
  type: 'membership_fees',
  sort_by: 'receipt_date',
  sort_order: 'desc'
}).then(data => {
  console.log('Receipts:', data.rows);
  console.log('Total:', data.totalSum);
});
```

### Get Statistics

```javascript
async function getReceiptStats(month = null, monthsBack = 3) {
  const params = new URLSearchParams({
    months_back: monthsBack
  });
  
  if (month) {
    params.append('month', month);
  }
  
  const response = await fetch(`/api/receipts/stats?${params}`, {
    headers: {
      'Accept': 'application/json',
    },
  });
  
  return await response.json();
}

// Usage
getReceiptStats('2025-01', 3).then(data => {
  console.log('Monthly Total:', data.monthlyTotal.amount);
  console.log('Categories:', data.categoryDistribution);
  console.log('Trend:', data.trend);
  console.log('Uncollected:', data.uncollectedReceipts.total);
});
```

### Update Receipt

```javascript
async function updateReceipt(id, updates) {
  const response = await fetch(`/api/receipts/${id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify(updates),
  });
  
  return await response.json();
}

// Usage
updateReceipt(1, {
  status: 'paid',
  notes: 'Payment confirmed'
}).then(receipt => {
  console.log('Updated receipt:', receipt);
});
```

### Export Receipts

```javascript
async function exportReceipts(fileType, filters = {}) {
  const response = await fetch('/api/receipts/export', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      file_type: fileType,
      ...filters
    }),
  });
  
  if (!response.ok) {
    throw new Error('Export failed');
  }
  
  const blob = await response.blob();
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `receipts.${fileType === 'xls' ? 'xlsx' : fileType}`;
  document.body.appendChild(a);
  a.click();
  window.URL.revokeObjectURL(url);
  document.body.removeChild(a);
}

// Usage
exportReceipts('xls', {
  status: 'paid',
  date_from: '2025-01-01',
  date_to: '2025-01-31'
});
```

---

## Notes

- All endpoints require authentication (Bearer token)
- All monetary amounts are returned as strings with 2 decimal places
- Dates are returned in ISO 8601 format (YYYY-MM-DDTHH:mm:ss.ssssssZ)
- Receipts are automatically filtered by `business_id` based on the authenticated user
- Receipts are automatically created when a charge is successful via `/api/billing/charge`

