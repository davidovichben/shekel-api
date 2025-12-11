# Expenses Statistics API - Client Documentation

## Endpoint
```
GET /api/expenses/stats
```

## Query Parameters
- `month` (optional) - Month in format `YYYY-MM` (e.g., "2025-01") - defaults to current month
- `months_back` (optional) - Number of months for trend data (default: 3)

## Example Request
```
GET /api/expenses/stats?month=2025-01&months_back=3
```

## Response Structure
```json
{
  "monthlyTotal": {
    "amount": "16234.00",
    "month": "2025-01",
    "currency": "₪"
  },
  "categoryDistribution": [
    {
      "type": "management",
      "label": "הנהלה ושכר",
      "amount": "17000.00",
      "percentage": 12
    }
  ],
  "trend": [
    {
      "month": "2024-11",
      "amount": "35874.00"
    }
  ],
  "unpaidExpenses": {
    "total": "15451.00",
    "percentage": 75,
    "month": "2025-01"
  }
}
```

## Response Fields

**monthlyTotal**
- `amount` - Total expenses for the month (string, 2 decimals)
- `month` - Month in YYYY-MM format
- `currency` - Currency symbol (₪)

**categoryDistribution** (sorted by amount, descending)
- `type` - Category code (food, maintenance, equipment, insurance, operations, suppliers, management)
- `label` - Hebrew label
- `amount` - Category total (string, 2 decimals)
- `percentage` - Percentage of monthly total (integer)

**trend** (chronologically ordered)
- `month` - Month in YYYY-MM format
- `amount` - Monthly total (string, 2 decimals)

**unpaidExpenses**
- `total` - Unpaid amount (status='pending') (string, 2 decimals)
- `percentage` - Percentage of monthly total (integer)
- `month` - Month in YYYY-MM format

## Category Types & Labels

| Type | Hebrew Label |
|------|--------------|
| food | מזון |
| maintenance | תחזוקת בית הכנסת |
| equipment | ציוד וריהוט |
| insurance | ביטוחים |
| operations | תפעול פעילויות |
| suppliers | ספקים ובעלי מקצוע |
| management | הנהלה ושכר |

## JavaScript Example
```javascript
fetch('/api/expenses/stats?month=2025-01&months_back=3')
.then(response => response.json())
.then(data => {
  // Use data.monthlyTotal, data.categoryDistribution, data.trend, data.unpaidExpenses
  console.log('Total:', data.monthlyTotal.amount);
  console.log('Categories:', data.categoryDistribution);
  console.log('Trend:', data.trend);
  console.log('Unpaid:', data.unpaidExpenses.total);
});
```
