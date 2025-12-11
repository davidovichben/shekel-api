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

### Example Request

```json
{
  "credit_card_id": 1,
  "amount": 150.50,
  "description": "Monthly membership fee",
  "type": "membership_fees"
}
```

---

## Response

### Success Response (200 OK)

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
    "total_amount": "150.50",
    "status": "paid",
    "type": "membership_fees"
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

**receipt**
- `id` - Receipt ID (use this to fetch full receipt details)
- `receipt_number` - Receipt number (same as transaction ID)
- `total_amount` - Receipt total amount
- `status` - Receipt status (always "paid" on successful charge)
- `type` - Receipt type

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

---

## JavaScript Example

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
    console.log('Receipt ID:', data.receipt.id);
    console.log('Receipt Number:', data.receipt.receipt_number);
    
    return data;
  } catch (error) {
    console.error('Charge error:', error);
    throw error;
  }
}

// Usage
chargeCreditCard(1, 150.50, 'Monthly membership fee', 'membership_fees')
  .then(result => {
    // Receipt is automatically created
    // You can fetch full receipt details using: GET /api/receipts/{receipt.id}
    console.log('Charge successful! Receipt created:', result.receipt);
  })
  .catch(error => {
    console.error('Charge failed:', error);
  });
```

---

## Notes

- Receipt is **automatically created** when charge succeeds
- Receipt `receipt_number` = transaction `id`
- Receipt `status` is set to `paid` automatically
- Receipt `payment_method` is set to `credit_card`
- Receipt `user_id` is set to the authenticated user
- Receipt `receipt_date` is set to current timestamp
- Receipt `subtotal` = amount, `tax_amount` = 0 (can be updated later)
- Use `receipt.id` to fetch full receipt details via `GET /api/receipts/{id}`

