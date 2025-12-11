# Billing Charge API Documentation

## Endpoint

**POST** `/api/billing/charge`

Charge a credit card and process a payment transaction.

---

## Request

### Headers
```
Content-Type: application/json
Accept: application/json
```

### Body Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `credit_card_id` | integer | Yes | ID of the credit card to charge (must exist in `member_credit_cards` table) |
| `amount` | decimal | Yes | Amount to charge (minimum: 0.01) |
| `description` | string | No | Optional description for the transaction (max 500 characters) |

### Example Request

```json
{
  "credit_card_id": 1,
  "amount": 150.50,
  "description": "Monthly membership fee"
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
  }
}
```

### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `success` | boolean | Indicates if the charge was successful |
| `transaction.id` | string | Unique transaction identifier |
| `transaction.amount` | string | Charged amount (formatted to 2 decimal places) |
| `transaction.credit_card_id` | integer | ID of the credit card used |
| `transaction.last_digits` | string | Last 4 digits of the credit card |
| `description` | string\|null | Transaction description (if provided) |
| `status` | string | Transaction status (always "completed" on success) |

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
async function chargeCreditCard(creditCardId, amount, description = null) {
  try {
    const response = await fetch('/api/billing/charge', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        credit_card_id: creditCardId,
        amount: amount,
        description: description,
      }),
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Charge failed');
    }

    const data = await response.json();
    console.log('Transaction ID:', data.transaction.id);
    console.log('Amount charged:', data.transaction.amount);
    return data;
  } catch (error) {
    console.error('Charge error:', error);
    throw error;
  }
}

// Usage
chargeCreditCard(1, 150.50, 'Monthly membership fee')
  .then(result => {
    // Handle success - you can now create a Receipt using the transaction data
    console.log('Charge successful:', result);
  })
  .catch(error => {
    // Handle error
    console.error('Charge failed:', error);
  });
```

---

## Notes

- This endpoint currently returns a mock transaction response. In production, it will integrate with Tranzila payment gateway.
- The transaction includes a simulated 0.5 second processing delay.
- After a successful charge, you can use the returned transaction data to create a Receipt record via the Receipts API.
- The `transaction.id` can be used as a reference when creating the receipt.

