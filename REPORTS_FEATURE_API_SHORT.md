# Reports Feature API - Short Client Documentation

## Endpoints

### 1. Get Categories
**GET** `/api/reports/categories`

Returns all report categories and types.

**Response:**
```json
{
  "categories": [
    {
      "id": "income",
      "label": "דוחות הכנסות",
      "reports": [
        {
          "id": "income_monthly",
          "label": "דוח הכנסות חודשיות",
          "category": "income"
        }
      ]
    }
  ]
}
```

---

### 2. Get Report Config
**GET** `/api/reports/{reportTypeId}/config`

Returns configuration for a specific report type.

**Response:**
```json
{
  "reportName": "דוח הכנסות חודשיות",
  "columns": [
    {
      "id": "receipt_number",
      "label": "מספר קבלה",
      "required": true
    },
    {
      "id": "receipt_date",
      "label": "תאריך",
      "required": true
    }
  ],
  "sortOptions": [
    { "value": "receipt_date", "label": "תאריך" },
    { "value": "amount", "label": "סכום" }
  ],
  "filters": [
    {
      "key": "type",
      "label": "סוג הכנסה",
      "options": [
        { "value": "vows", "label": "נדרים" }
      ]
    }
  ],
  "supportsDateRange": true,
  "supportsResultLimit": true
}
```

---

### 3. Generate PDF Report
**POST** `/api/reports/{reportTypeId}/generate`

**Request Body:**
```json
{
  "dateFrom": "2025-01-01",
  "dateTo": "2025-01-31",
  "sortBy": "receipt_date",
  "sortOrder": "desc",
  "filters": {
    "type": "vows",
    "status": "paid"
  },
  "resultLimit": "50",
  "columns": [
    "receipt_number",
    "receipt_date",
    "payer_name",
    "amount"
  ]
}
```

**Response:** PDF file (binary)

**Notes:**
- `dateFrom`/`dateTo`: ISO format (YYYY-MM-DD) or `null`
- `resultLimit`: `"unlimited"` or `"10"`, `"25"`, `"50"`, `"100"`
- `columns`: Array of column IDs in desired order
- Required columns (with `required: true`) must be included

---

### 4. Export to Hashavshevet
**POST** `/api/reports/{reportTypeId}/export/hashavshevet`

**Request Body:** Same as Generate Report

**Response:** CSV file (binary)

---

## Report Types

| ID | Label |
|----|-------|
| `income_monthly` | דוח הכנסות חודשיות |
| `expenses_monthly` | דוח הוצאות חודשיות |
| `expenses_high` | דוח הוצאות גבוהות (מעל 1000₪) |
| `donations_community` | תרומות מהקהילה |
| `donations_external` | תרומות מחוץ לקהילה |
| `debts_open` | דוח חובות פתוחים |
| `debts_by_type` | דוח חובות לפי סוג חוב |
| `debts_by_debtor` | דוח חובות לפי חייב |
| `members_active` | דוח חברים פעילים |
| `members_recent` | דוח חברים שהצטרפו בשלושת החודשים האחרונים |
| `members_no_donation` | דוח מתפללים שלא תרמו בשלושת החודשים האחרונים |
| `members_no_auto_payment` | דוח חברי קהילה ללא תשלום אוטומטי |

---

## JavaScript Example

```javascript
// Step 1: Get categories
const categories = await fetch('/api/reports/categories', {
  headers: { 'Authorization': 'Bearer TOKEN' }
}).then(r => r.json());

// Step 2: Get config
const config = await fetch('/api/reports/income_monthly/config', {
  headers: { 'Authorization': 'Bearer TOKEN' }
}).then(r => r.json());

// Step 3: Generate report
const response = await fetch('/api/reports/income_monthly/generate', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer TOKEN'
  },
  body: JSON.stringify({
    dateFrom: '2025-01-01',
    dateTo: '2025-01-31',
    sortBy: 'receipt_date',
    sortOrder: 'desc',
    filters: { type: 'vows' },
    resultLimit: '50',
    columns: ['receipt_number', 'receipt_date', 'payer_name', 'amount']
  })
});

// Step 4: Download PDF
const blob = await response.blob();
const url = window.URL.createObjectURL(blob);
const a = document.createElement('a');
a.href = url;
a.download = 'report.pdf';
a.click();
window.URL.revokeObjectURL(url);
```

---

## Error Responses

**404 - Report Not Found:**
```json
{
  "error": "Report type not found",
  "message": "Report type 'invalid' does not exist"
}
```

**400 - Missing Required Columns:**
```json
{
  "error": "Missing required columns",
  "message": "The following required columns must be included: receipt_number",
  "missingColumns": ["receipt_number"]
}
```

**422 - Validation Error:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "sortBy": ["The sort by field is required."]
  }
}
```

---

## Quick Reference

- **Date Format:** ISO (YYYY-MM-DD) or `null`
- **Sort Order:** `"asc"` or `"desc"`
- **Result Limit:** `"unlimited"` or `"10"`, `"25"`, `"50"`, `"100"`
- **Columns:** Array of column IDs from config
- **Filters:** Object with key-value pairs matching filter options
- **PDF Response:** Binary file, Content-Type: `application/pdf`
- **CSV Response:** Binary file, Content-Type: `text/csv`

