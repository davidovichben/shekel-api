# Debt Payment via Charge API - Client Documentation

## Overview

The `POST /api/billing/charge` endpoint now supports paying debts automatically when charging a credit card. You can pay a single debt or multiple debts in one transaction.

---

## Endpoint

**POST** `/api/billing/charge`

---

## Request Parameters

### Additional Parameters for Debt Payment

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `debt_id` | integer | No | ID of a single debt to pay |
| `debt_ids` | array | No | Array of debt IDs for bulk payment |

**Note:** Use either `debt_id` OR `debt_ids`, not both.

### Amount Calculation

- **Single Debt:** `amount` must equal the debt amount exactly
- **Bulk Debts:** `amount` must equal `(sum of all debt amounts) × 1.17` (includes 17% VAT)

---

## Examples

### Single Debt Payment

```json
{
  "credit_card_id": 1,
  "amount": 100.00,
  "debt_id": 5,
  "description": "Payment for debt",
  "type": "other"
}
```

### Bulk Debts Payment

```json
{
  "credit_card_id": 1,
  "amount": 351.00,
  "debt_ids": [5, 6, 7],
  "description": "Payment for multiple debts",
  "type": "other"
}
```

**Calculation Example:**
- Debt 5: 100.00
- Debt 6: 100.00
- Debt 7: 100.00
- Subtotal: 300.00
- VAT (17%): 51.00
- **Total: 351.00**

---

## Response

### Success Response (200 OK)

```json
{
  "success": true,
  "transaction": {
    "id": "TXN_abc123xyz4567890",
    "amount": "351.00",
    "credit_card_id": 1,
    "last_digits": "1234",
    "description": "Payment for multiple debts",
    "status": "completed"
  },
  "paidDebts": [
    {
      "id": 5,
      "amount": "100.00",
      "description": "Monthly fee"
    },
    {
      "id": 6,
      "amount": "100.00",
      "description": "Service charge"
    },
    {
      "id": 7,
      "amount": "100.00",
      "description": "Late fee"
    }
  ],
  "receipt": {
    "id": 1,
    "receipt_number": "TXN_abc123xyz4567890",
    "total": "351.00",
    "status": "paid",
    "type": "other"
  }
}
```

**Response Fields:**
- `paidDebts` - Array of paid debt objects (only present when debts are paid)
  - `id` - Debt ID
  - `amount` - Debt amount (formatted)
  - `description` - Debt description

---

## Error Responses

### Debt Not Found (404)

```json
{
  "message": "One or more debts not found"
}
```

### Debt Owner Mismatch (422)

```json
{
  "message": "Debt does not belong to the credit card owner",
  "debt_id": 5
}
```

### Debt Not Pending (422)

```json
{
  "message": "Debt is not pending (already paid or cancelled)",
  "debt_id": 5,
  "status": "paid"
}
```

### Amount Mismatch - Single Debt (422)

```json
{
  "message": "Amount does not match debt amount",
  "expected": "100.00",
  "provided": "150.50"
}
```

### Amount Mismatch - Bulk Debts (422)

```json
{
  "message": "Amount does not match calculated total (debts + 17% VAT)",
  "debts_subtotal": "300.00",
  "vat_amount": "51.00",
  "expected_total": "351.00",
  "provided": "400.00"
}
```

---

## JavaScript Examples

### Single Debt Payment

```javascript
async function paySingleDebt(creditCardId, debtId) {
  // Fetch debt to get amount
  const debtResponse = await fetch(`/api/debts/${debtId}`, {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  const debt = await debtResponse.json();
  
  // Charge with exact debt amount
  const response = await fetch('/api/billing/charge', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      credit_card_id: creditCardId,
      amount: parseFloat(debt.amount),
      debt_id: debtId,
      type: 'other'
    })
  });
  
  return await response.json();
}
```

### Bulk Debts Payment

```javascript
async function payMultipleDebts(creditCardId, debtIds) {
  // Fetch all debts
  const debtsPromises = debtIds.map(id => 
    fetch(`/api/debts/${id}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    }).then(r => r.json())
  );
  
  const debts = await Promise.all(debtsPromises);
  
  // Calculate total with VAT
  const subtotal = debts.reduce((sum, debt) => sum + parseFloat(debt.amount), 0);
  const total = subtotal * 1.17; // Add 17% VAT
  
  // Charge with calculated total
  const response = await fetch('/api/billing/charge', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      credit_card_id: creditCardId,
      amount: total,
      debt_ids: debtIds,
      type: 'other'
    })
  });
  
  return await response.json();
}
```

---

## Validation Rules

1. ✅ Credit card owner must match debt owner(s)
2. ✅ All debts must exist and belong to current business
3. ✅ All debts must have status `pending`
4. ✅ Amount must match exactly (single) or match calculated total with VAT (bulk)

---

## Notes

- Debts are automatically updated to `paid` status after successful charge
- Debt validation happens **before** the charge is processed
- If validation fails, the charge will not proceed
- All other charge endpoint features (receipt creation, PDF upload, etc.) work the same way

