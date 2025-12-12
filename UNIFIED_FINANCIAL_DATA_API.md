# Unified Financial Data API

## Endpoint

**GET** `/api/financial-data`

Returns all expenses, receipts (incomes), and debts in a single request with counts per category/type. This allows the client to make one request instead of multiple requests per type.

## Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `date_from` | date (optional) | Filter data from this date (YYYY-MM-DD) |
| `date_to` | date (optional) | Filter data until this date (YYYY-MM-DD) |
| `status` | string (optional) | Filter by status (applies to all entity types) |

## Response Structure

```json
{
  "expenses": {
    "data": [
      {
        "id": 1,
        "description": "Office supplies",
        "type": "operations",
        "amount": "150.00",
        "date": "2025-12-11",
        "supplierId": 5,
        "supplierName": "ABC Supplies",
        "status": "paid",
        "frequency": "one_time"
      }
    ],
    "countsByType": {
      "food": 10,
      "maintenance": 5,
      "equipment": 3,
      "insurance": 2,
      "operations": 8,
      "suppliers": 4,
      "management": 1
    }
  },
  "receipts": {
    "data": [
      {
        "id": 1,
        "receiptNumber": "TXN_abc123",
        "userId": 1,
        "userName": "John Doe",
        "total": "200.00",
        "status": "paid",
        "paymentMethod": "credit_card",
        "receiptDate": "2025-12-11T10:30:00.000000Z",
        "description": "Monthly membership",
        "type": "membership_fees"
      }
    ],
    "countsByType": {
      "vows": 15,
      "community_donations": 8,
      "external_donations": 3,
      "ascensions": 5,
      "online_donations": 12,
      "membership_fees": 20,
      "other": 2
    }
  },
  "debts": {
    "data": [
      {
        "id": 1,
        "memberId": 10,
        "memberName": "Jane Smith",
        "type": "neder_shabbat",
        "amount": "50.00",
        "description": "Shabbat vow",
        "dueDate": "2025-12-15",
        "status": "pending"
      }
    ],
    "countsByType": {
      "neder_shabbat": 25,
      "tikun_nezek": 10,
      "dmei_chaver": 15,
      "kiddush": 8,
      "neder_yom_shabbat": 5,
      "other": 3
    }
  }
}
```

## Expense Types

- `food`
- `maintenance`
- `equipment`
- `insurance`
- `operations`
- `suppliers`
- `management`

## Receipt Types (Incomes)

- `vows`
- `community_donations`
- `external_donations`
- `ascensions`
- `online_donations`
- `membership_fees`
- `other`

## Debt Types

- `neder_shabbat`
- `tikun_nezek`
- `dmei_chaver`
- `kiddush`
- `neder_yom_shabbat`
- `other`

## Usage

1. **Make one request** to `/api/financial-data` with optional filters
2. **Use `countsByType`** for each entity (expenses, receipts, debts) to:
   - Display tab counts
   - Show badges with number of items per category
   - Determine which tabs to show/hide
3. **Filter `data` arrays** on the client side by `type` to populate each tab
4. **No need for multiple requests** - all data is returned in one response

## Example Request

```
GET /api/financial-data?date_from=2025-12-01&date_to=2025-12-31&status=paid
```

## Benefits

- ✅ **Single request** instead of 3+ requests per type
- ✅ **Reduced server load** - one query instead of multiple
- ✅ **Faster client-side rendering** - all data available immediately
- ✅ **Category counts included** - easy tab badge implementation
- ✅ **Client-side filtering** - divide data into tabs without additional requests

