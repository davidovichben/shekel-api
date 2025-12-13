# Reports Feature API - Client Documentation

## Overview

The Reports Feature API provides a flexible system for generating customizable PDF reports and exporting data to Hashavshevet accounting software. Reports can be configured with custom columns, filters, sorting, and date ranges.

---

## 1. Get Report Categories and Types

**Endpoint:** `GET /api/reports/categories`

Returns all available report categories and their associated report types.

### Response Structure

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
    },
    {
      "id": "expenses",
      "label": "דוחות הוצאות",
      "reports": [
        {
          "id": "expenses_monthly",
          "label": "דוח הוצאות חודשיות",
          "category": "expenses"
        },
        {
          "id": "expenses_high",
          "label": "דוח הוצאות גבוהות (מעל 1000₪)",
          "category": "expenses"
        }
      ]
    }
  ]
}
```

### Example Request

```javascript
fetch('/api/reports/categories', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN'
  }
})
.then(response => response.json())
.then(data => {
  console.log('Categories:', data.categories);
});
```

---

## 2. Get Report Configuration Options

**Endpoint:** `GET /api/reports/{reportTypeId}/config`

Returns configuration options for a specific report type, including available columns, filters, and sort options.

### Path Parameters

- `reportTypeId` (string) - The ID of the report type (e.g., `income_monthly`, `expenses_monthly`)

### Response Structure

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
    },
    {
      "id": "hebrew_date",
      "label": "תאריך עברי",
      "required": false
    },
    {
      "id": "payer_name",
      "label": "שם משלם",
      "required": false
    }
  ],
  "sortOptions": [
    {
      "value": "receipt_date",
      "label": "תאריך"
    },
    {
      "value": "amount",
      "label": "סכום"
    }
  ],
  "filters": [
    {
      "key": "type",
      "label": "סוג הכנסה",
      "options": [
        {
          "value": "vows",
          "label": "נדרים"
        },
        {
          "value": "community_donations",
          "label": "תרומות מהקהילה"
        }
      ]
    }
  ],
  "supportsDateRange": true,
  "supportsResultLimit": true
}
```

### Response Fields

- `reportName` - Display name of the report in Hebrew
- `columns` - Array of available columns
  - `id` - Column identifier (use this in the generate request)
  - `label` - Display name in Hebrew
  - `required` - If `true`, column must be included in the report
- `sortOptions` - Available sorting options
  - `value` - Sort field identifier
  - `label` - Display name in Hebrew
- `filters` - Available filters
  - `key` - Filter identifier
  - `label` - Display name in Hebrew
  - `options` - Array of filter values with labels
- `supportsDateRange` - Whether date range filtering is supported
- `supportsResultLimit` - Whether result limit is supported

### Example Request

```javascript
fetch('/api/reports/income_monthly/config', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN'
  }
})
.then(response => response.json())
.then(config => {
  console.log('Report config:', config);
  console.log('Available columns:', config.columns);
});
```

---

## 3. Generate Report

**Endpoint:** `POST /api/reports/{reportTypeId}/generate`

Generates a PDF report based on the provided configuration.

### Path Parameters

- `reportTypeId` (string) - The ID of the report type

### Request Body

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
    "amount",
    "type"
  ]
}
```

### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `dateFrom` | string \| null | No | Start date in ISO format (YYYY-MM-DD) |
| `dateTo` | string \| null | No | End date in ISO format (YYYY-MM-DD) |
| `sortBy` | string | Yes | Sort field identifier (from `sortOptions`) |
| `sortOrder` | string | Yes | Sort direction: `asc` or `desc` |
| `filters` | object | No | Key-value pairs for filter values |
| `resultLimit` | string | Yes | `unlimited` or number as string (`10`, `25`, `50`, `100`) |
| `columns` | array | Yes | Array of column IDs in desired order |

### Response

- **Content-Type:** `application/pdf`
- **Body:** PDF file binary data

### Notes

- The order of columns in the `columns` array determines their order in the generated report
- Only columns included in `columns` array will appear in the report
- Required columns (with `required: true`) must always be included
- If `resultLimit` is `unlimited`, all matching records are included

### Example Request

```javascript
async function generateReport(reportTypeId, config) {
  const response = await fetch(`/api/reports/${reportTypeId}/generate`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer YOUR_TOKEN'
    },
    body: JSON.stringify({
      dateFrom: config.dateFrom,
      dateTo: config.dateTo,
      sortBy: config.sortBy,
      sortOrder: config.sortOrder,
      filters: config.filters || {},
      resultLimit: config.resultLimit || 'unlimited',
      columns: config.columns
    })
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to generate report');
  }

  const blob = await response.blob();
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `report_${reportTypeId}_${Date.now()}.pdf`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  window.URL.revokeObjectURL(url);
}

// Usage
generateReport('income_monthly', {
  dateFrom: '2025-01-01',
  dateTo: '2025-01-31',
  sortBy: 'receipt_date',
  sortOrder: 'desc',
  filters: { type: 'vows' },
  resultLimit: '50',
  columns: ['receipt_number', 'receipt_date', 'payer_name', 'amount', 'type']
});
```

---

## 4. Export to Hashavshevet

**Endpoint:** `POST /api/reports/{reportTypeId}/export/hashavshevet`

Exports report data in CSV format compatible with Hashavshevet accounting software.

### Path Parameters

- `reportTypeId` (string) - The ID of the report type

### Request Body

Same structure as the Generate Report endpoint.

### Response

- **Content-Type:** `text/csv` or `application/octet-stream`
- **Body:** CSV file binary data

### Example Request

```javascript
async function exportToHashavshevet(reportTypeId, config) {
  const response = await fetch(`/api/reports/${reportTypeId}/export/hashavshevet`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer YOUR_TOKEN'
    },
    body: JSON.stringify({
      dateFrom: config.dateFrom,
      dateTo: config.dateTo,
      sortBy: config.sortBy,
      sortOrder: config.sortOrder,
      filters: config.filters || {},
      resultLimit: config.resultLimit || 'unlimited',
      columns: config.columns
    })
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to export');
  }

  const blob = await response.blob();
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `hashavshevet_${reportTypeId}_${Date.now()}.csv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  window.URL.revokeObjectURL(url);
}
```

---

## Available Report Types

### Income Reports (דוחות הכנסות)

- `income_monthly` - דוח הכנסות חודשיות

### Expense Reports (דוחות הוצאות)

- `expenses_monthly` - דוח הוצאות חודשיות
- `expenses_high` - דוח הוצאות גבוהות (מעל 1000₪)

### Donation Reports (דוחות תרומות)

- `donations_community` - תרומות מהקהילה
- `donations_external` - תרומות מחוץ לקהילה

### Debt Reports (דוחות חובות וגבייה)

- `debts_open` - דוח חובות פתוחים
- `debts_by_type` - דוח חובות לפי סוג חוב
- `debts_by_debtor` - דוח חובות לפי חייב

### Member Reports (דוחות מתפללים וניהול קהילה)

- `members_active` - דוח חברים פעילים
- `members_recent` - דוח חברים שהצטרפו בשלושת החודשים האחרונים
- `members_no_donation` - דוח מתפללים שלא תרמו בשלושת החודשים האחרונים
- `members_no_auto_payment` - דוח חברי קהילה ללא תשלום אוטומטי

---

## Column IDs Reference

### Income/Receipt Columns

- `receipt_number` - מספר קבלה
- `receipt_date` - תאריך
- `hebrew_date` - תאריך עברי
- `payer_name` - שם משלם
- `amount` - סכום
- `type` - סוג הכנסה
- `payment_method` - אמצעי תשלום
- `status` - סטטוס
- `description` - תיאור

### Expense Columns

- `date` - תאריך
- `hebrew_date` - תאריך עברי
- `description` - תיאור
- `amount` - סכום
- `type` - סוג הוצאה
- `supplier` - ספק
- `status` - סטטוס
- `frequency` - תדירות

### Debt Columns

- `debtor_name` - שם חייב
- `amount` - סכום
- `type` - סוג חוב
- `due_date` - תאריך יעד
- `hebrew_due_date` - תאריך יעד עברי
- `description` - תיאור
- `last_reminder` - תאריך תזכורת אחרונה

### Member Columns

- `member_number` - מספר חבר
- `full_name` - שם מלא
- `type` - סוג חבר
- `email` - אימייל
- `mobile` - נייד
- `phone` - טלפון
- `address` - כתובת
- `city` - עיר

---

## Error Responses

### Report Type Not Found (404)

```json
{
  "error": "Report type not found",
  "message": "Report type 'invalid_type' does not exist"
}
```

### Missing Required Columns (400)

```json
{
  "error": "Missing required columns",
  "message": "The following required columns must be included: receipt_number, receipt_date",
  "missingColumns": ["receipt_number", "receipt_date"]
}
```

### Validation Error (422)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "sortBy": ["The sort by field is required."],
    "sortOrder": ["The sort order must be asc or desc."]
  }
}
```

### Server Error (500)

```json
{
  "error": "Failed to generate report",
  "message": "Error details..."
}
```

---

## Complete Example Workflow

```javascript
// Step 1: Get all categories
const categoriesResponse = await fetch('/api/reports/categories', {
  headers: { 'Authorization': 'Bearer YOUR_TOKEN' }
});
const { categories } = await categoriesResponse.json();

// Step 2: Get config for a specific report
const configResponse = await fetch('/api/reports/income_monthly/config', {
  headers: { 'Authorization': 'Bearer YOUR_TOKEN' }
});
const config = await configResponse.json();

// Step 3: Generate report with custom configuration
const reportResponse = await fetch('/api/reports/income_monthly/generate', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer YOUR_TOKEN'
  },
  body: JSON.stringify({
    dateFrom: '2025-01-01',
    dateTo: '2025-01-31',
    sortBy: 'receipt_date',
    sortOrder: 'desc',
    filters: {
      type: 'vows',
      status: 'paid'
    },
    resultLimit: '50',
    columns: [
      'receipt_number',
      'receipt_date',
      'payer_name',
      'amount',
      'type',
      'status'
    ]
  })
});

// Step 4: Download PDF
const blob = await reportResponse.blob();
const url = window.URL.createObjectURL(blob);
const a = document.createElement('a');
a.href = url;
a.download = 'income_report.pdf';
a.click();
window.URL.revokeObjectURL(url);
```

---

## Notes

1. All dates should be in ISO format (YYYY-MM-DD) or `null`
2. All text labels are in Hebrew
3. Column IDs in the `columns` array must match IDs returned in the config endpoint
4. The order of columns in the `columns` array determines their order in the generated report
5. Required columns (with `required: true`) must always be included
6. Filter values should match option values provided in the config endpoint
7. PDF reports are generated with Hebrew labels and RTL (right-to-left) layout
8. CSV exports use UTF-8 encoding and are compatible with Hashavshevet import

