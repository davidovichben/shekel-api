# Billing Charge API - Client Documentation

## Endpoint

**POST** `/api/billing/charge`

Charge a credit card and automatically create a receipt.

---

## Request

### Headers
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

### Body Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `credit_card_id` | integer | Yes | ID of the credit card to charge |
| `amount` | decimal | Yes | Amount to charge (minimum: 0.01) |
| `description` | string | No | Optional description/notes for the receipt |
| `type` | string | No | Receipt type (default: `other`) - see types below |
| `debt_id` | integer | No | ID of a single debt to pay (mutually exclusive with `debt_ids`) |
| `debt_ids` | array | No | Array of debt IDs for bulk payment (mutually exclusive with `debt_id`) |

### Receipt Types

| Type | Hebrew Label |
|------|--------------|
| `vows` | נדרים |
| `community_donations` | תרומות מהקהילה |
| `external_donations` | תרומות חיצוניות |
| `ascensions` | עליות |
| `online_donations` | תרומות אונליין |
| `membership_fees` | דמי חברים |
| `other` | אחר |

### Example Requests

**Regular Charge (No Debt Payment)**
```json
{
  "credit_card_id": 1,
  "amount": 150.50,
  "description": "Monthly membership fee",
  "type": "membership_fees"
}
```

**Single Debt Payment**
```json
{
  "credit_card_id": 1,
  "amount": 100.00,
  "description": "Payment for debt",
  "type": "other",
  "debt_id": 5
}
```

**Bulk Debts Payment (Multiple Debts)**
```json
{
  "credit_card_id": 1,
  "amount": 351.00,
  "description": "Payment for multiple debts",
  "type": "other",
  "debt_ids": [5, 6, 7]
}
```

### Debt Payment Rules

1. **Single Debt Payment** (`debt_id`):
   - Amount must match the debt amount **exactly**
   - Debt must belong to the same member as the credit card
   - Debt status must be `pending`

2. **Bulk Debts Payment** (`debt_ids`):
   - Amount must match: `sum of all debt amounts + 17% VAT`
   - All debts must belong to the same member as the credit card
   - All debts must have status `pending`
   - Example: If debts are 100, 100, 100 → Total = 300 + (300 × 0.17) = 351.00

3. **Validation**:
   - Credit card owner must match debt owner(s)
   - Debts must exist and belong to current business
   - Debts must be in `pending` status

---

## Response

### Success Response (200 OK)

**Regular Charge Response**
```json
{
  "success": true,
  "transaction": {
    "id": "TXN_abc123xyz4567890",
    "amount": "150.50",
    "credit_card_id": 1,
    "last_digits": "1234",
    "description": "Monthly membership fee",
    "status": "completed"
  },
  "receipt": {
    "id": 1,
    "receipt_number": "TXN_abc123xyz4567890",
    "total": "150.50",
    "status": "paid",
    "type": "membership_fees"
  }
}
```

**Debt Payment Response**
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

### Response Fields

**transaction**
- `id` - Transaction identifier (used as receipt_number)
- `amount` - Charged amount (formatted to 2 decimals)
- `credit_card_id` - ID of the credit card used
- `last_digits` - Last 4 digits of the credit card
- `description` - Transaction description
- `status` - Always "completed" on success

**receipt** (only if `createReceipt` is true or PDF is uploaded)
- `id` - Receipt ID (use this to fetch full receipt details)
- `receipt_number` - Receipt number (same as transaction ID)
- `total` - Receipt total amount
- `status` - Receipt status (always "paid" on successful charge)
- `type` - Receipt type
- `pdf_file` - Path to PDF file (if generated/uploaded)

**paidDebts** (only if `debt_id` or `debt_ids` provided)
- Array of paid debt objects, each containing:
  - `id` - Debt ID
  - `amount` - Debt amount (formatted)
  - `description` - Debt description

---

## Error Responses

### Validation Error (422 Unprocessable Entity)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "credit_card_id": ["The credit card id field is required."],
    "amount": ["The amount must be at least 0.01."]
  }
}
```

### Credit Card Not Found (404 Not Found)

```json
{
  "message": "No query results for model [App\\Models\\MemberCreditCard] {id}"
}
```

### Debt Validation Errors (422 Unprocessable Entity)

**Debt Not Found**
```json
{
  "message": "One or more debts not found"
}
```

**Debt Owner Mismatch**
```json
{
  "message": "Debt does not belong to the credit card owner",
  "debt_id": 5
}
```

**Debt Not Pending**
```json
{
  "message": "Debt is not pending (already paid or cancelled)",
  "debt_id": 5,
  "status": "paid"
}
```

**Amount Mismatch (Single Debt)**
```json
{
  "message": "Amount does not match debt amount",
  "expected": "100.00",
  "provided": "150.50"
}
```

**Amount Mismatch (Bulk Debts)**
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

### Regular Charge

```javascript
async function chargeCreditCard(creditCardId, amount, description = null, type = 'other') {
  try {
    const response = await fetch('/api/billing/charge', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${yourToken}`
      },
      body: JSON.stringify({
        credit_card_id: creditCardId,
        amount: amount,
        description: description,
        type: type
      }),
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Charge failed');
    }

    const data = await response.json();
    
    console.log('Transaction ID:', data.transaction.id);
    if (data.receipt) {
      console.log('Receipt ID:', data.receipt.id);
      console.log('Receipt Number:', data.receipt.receipt_number);
    }
    
    return data;
  } catch (error) {
    console.error('Charge error:', error);
    throw error;
  }
}

// Usage
chargeCreditCard(1, 150.50, 'Monthly membership fee', 'membership_fees')
  .then(result => {
    console.log('Charge successful!', result);
  })
  .catch(error => {
    console.error('Charge failed:', error);
  });
```

### Single Debt Payment

```javascript
async function payDebt(creditCardId, debtId) {
  // First, fetch the debt to get its amount
  const debtResponse = await fetch(`/api/debts/${debtId}`, {
    headers: {
      'Authorization': `Bearer ${yourToken}`
    }
  });
  const debt = await debtResponse.json();
  
  // Charge with exact debt amount
  const response = await fetch('/api/billing/charge', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': `Bearer ${yourToken}`
    },
    body: JSON.stringify({
      credit_card_id: creditCardId,
      amount: parseFloat(debt.amount),
      description: `Payment for debt: ${debt.description}`,
      type: 'other',
      debt_id: debtId
    }),
  });

  const data = await response.json();
  
  if (data.paidDebts) {
    console.log('Debt paid successfully:', data.paidDebts);
  }
  
  return data;
}
```

### Bulk Debts Payment

```javascript
async function payMultipleDebts(creditCardId, debtIds) {
  // First, fetch all debts to calculate total
  const debtsPromises = debtIds.map(id => 
    fetch(`/api/debts/${id}`, {
      headers: { 'Authorization': `Bearer ${yourToken}` }
    }).then(r => r.json())
  );
  
  const debts = await Promise.all(debtsPromises);
  
  // Calculate subtotal
  const subtotal = debts.reduce((sum, debt) => sum + parseFloat(debt.amount), 0);
  
  // Calculate total with 17% VAT
  const total = subtotal * 1.17;
  
  // Charge with calculated total
  const response = await fetch('/api/billing/charge', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': `Bearer ${yourToken}`
    },
    body: JSON.stringify({
      credit_card_id: creditCardId,
      amount: total,
      description: `Payment for ${debts.length} debts`,
      type: 'other',
      debt_ids: debtIds
    }),
  });

  const data = await response.json();
  
  if (data.paidDebts) {
    console.log(`Successfully paid ${data.paidDebts.length} debts:`, data.paidDebts);
  }
  
  return data;
}

// Usage
payMultipleDebts(1, [5, 6, 7])
  .then(result => {
    console.log('Bulk payment successful!', result);
  })
  .catch(error => {
    console.error('Bulk payment failed:', error);
  });
```

---

## Notes

### Receipt Creation
- Receipt is **automatically created** when `createReceipt` is `true` or PDF is uploaded
- Receipt `receipt_number` = transaction `id` (only if receipt is generated)
- Receipt `status` is set to `paid` automatically
- Receipt `payment_method` is set to `credit_card`
- Receipt `user_id` is set to the authenticated user
- Receipt `receipt_date` is set when receipt number is generated
- Use `receipt.id` to fetch full receipt details via `GET /api/receipts/{id}`

### Debt Payment
- When `debt_id` or `debt_ids` is provided, the corresponding debt(s) status is automatically updated to `paid` after successful charge
- Debt validation ensures:
  - Credit card owner matches debt owner(s)
  - All debts are in `pending` status
  - Amount matches exactly (single debt) or matches calculated total with VAT (bulk)
- If debt payment fails validation, the charge will not proceed
- Debts are updated within a database transaction to ensure data consistency

