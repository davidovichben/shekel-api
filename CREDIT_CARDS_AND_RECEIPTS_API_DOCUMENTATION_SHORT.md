# Credit Cards & Receipts API - Client Documentation

## Credit Card Endpoints

### List All Credit Cards for a Member
**GET** `/api/members/{memberId}/credit-cards`

Returns all credit cards associated with a member.

**Example Request:**
```
GET /api/members/1/credit-cards
```

**Response:**
```json
[
  {
    "id": 1,
    "member_id": 1,
    "token": "TRZ_abc123xyz456",
    "last_digits": "1234",
    "company": "visa",
    "expiration": "12/25",
    "full_name": "John Doe",
    "is_default": true,
    "created_at": "2025-12-11T10:30:00.000000Z",
    "updated_at": "2025-12-11T10:30:00.000000Z"
  }
]
```

---

### Create Credit Card (Manual Entry)
**POST** `/api/members/{memberId}/credit-cards`

Manually create a credit card entry (without token).

**Request Body:**
```json
{
  "last_digits": "1234",
  "company": "visa",
  "expiration": "12/25",
  "full_name": "John Doe"
}
```

**Company Values:** `visa`, `mastercard`, `amex`, `discover`, `jcb`, `diners`, `unknown`

**Response:**
```json
{
  "id": 1,
  "member_id": 1,
  "last_digits": "1234",
  "company": "visa",
  "expiration": "12/25",
  "full_name": "John Doe",
  "is_default": false,
  "created_at": "2025-12-11T10:30:00.000000Z",
  "updated_at": "2025-12-11T10:30:00.000000Z"
}
```

---

### Create Credit Card (Tokenized - Payment Gateway)
**POST** `/api/billing/store`

Store a tokenized credit card from payment gateway (includes token).

**Request Body:**
```json
{
  "member_id": 1,
  "last_digits": "1234",
  "company": "visa",
  "expiration": "12/25",
  "full_name": "John Doe"
}
```

**Response:**
```json
{
  "success": true,
  "credit_card": {
    "id": 1,
    "last_digits": "1234",
    "company": "visa",
    "expiration": "12/25",
    "full_name": "John Doe",
    "is_default": true
  }
}
```

**Note:** Token is automatically generated (mock: `TRZ_` + random string). In production, token comes from Tranzila.

---

### Get Single Credit Card
**GET** `/api/members/{memberId}/credit-cards/{id}`

**Response:** Single credit card object

---

### Update Credit Card
**PUT** `/api/members/{memberId}/credit-cards/{id}`

**Request Body:**
```json
{
  "last_digits": "5678",
  "company": "mastercard",
  "expiration": "06/26",
  "full_name": "Jane Doe"
}
```

**Response:** Updated credit card object

---

### Delete Credit Card
**DELETE** `/api/members/{memberId}/credit-cards/{id}`

**Response:** 204 No Content

---

### Set Default Credit Card
**PUT** `/api/members/{memberId}/credit-cards/{id}/set-default`

Sets a credit card as the default for the member.

**Response:**
```json
{
  "message": "Credit card set as default."
}
```

---

## Receipt Endpoints

### Create Receipt (Manual)
**POST** `/api/receipts`

Manually create a receipt.

**Request Body:**
```json
{
  "receipt_number": "RCP-5001",
  "user_id": 1,
  "total_amount": 150.50,
  "tax_amount": 0.00,
  "subtotal": 150.50,
  "status": "paid",
  "payment_method": "credit_card",
  "receipt_date": "2025-12-11",
  "notes": "Monthly membership fee",
  "type": "membership_fees"
}
```

**Receipt Types:** `vows`, `community_donations`, `external_donations`, `ascensions`, `online_donations`, `membership_fees`, `other`

**Status Values:** `pending`, `paid`, `cancelled`, `refunded`

**Response:** Created receipt object

---

### Create Receipt (Automatic - via Charge)
**POST** `/api/billing/charge`

Charge a credit card and automatically create a receipt.

**Request Body:**
```json
{
  "credit_card_id": 1,
  "amount": 150.50,
  "description": "Monthly membership fee",
  "type": "membership_fees"
}
```

**Response:**
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

**Note:** Receipt is automatically created with:
- `receipt_number` = transaction `id`
- `status` = `paid`
- `payment_method` = `credit_card`
- `user_id` = authenticated user
- `receipt_date` = current timestamp

---

## JavaScript Examples

### List Member Credit Cards
```javascript
async function getMemberCreditCards(memberId) {
  const response = await fetch(`/api/members/${memberId}/credit-cards`, {
    headers: { 'Accept': 'application/json' }
  });
  return await response.json();
}

// Usage
getMemberCreditCards(1).then(cards => {
  console.log('Credit cards:', cards);
});
```

### Create Credit Card
```javascript
async function createCreditCard(memberId, cardData) {
  const response = await fetch(`/api/members/${memberId}/credit-cards`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify(cardData)
  });
  return await response.json();
}

// Usage
createCreditCard(1, {
  last_digits: '1234',
  company: 'visa',
  expiration: '12/25',
  full_name: 'John Doe'
}).then(card => {
  console.log('Created:', card);
});
```

### Charge and Create Receipt
```javascript
async function chargeAndCreateReceipt(creditCardId, amount, description, type) {
  const response = await fetch('/api/billing/charge', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({
      credit_card_id: creditCardId,
      amount: amount,
      description: description,
      type: type || 'other'
    })
  });
  return await response.json();
}

// Usage
chargeAndCreateReceipt(1, 150.50, 'Monthly fee', 'membership_fees')
  .then(result => {
    console.log('Transaction:', result.transaction);
    console.log('Receipt created:', result.receipt);
  });
```

### Create Receipt Manually
```javascript
async function createReceipt(receiptData) {
  const response = await fetch('/api/receipts', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify(receiptData)
  });
  return await response.json();
}

// Usage
createReceipt({
  receipt_number: 'RCP-5001',
  user_id: 1,
  total_amount: 150.50,
  tax_amount: 0.00,
  subtotal: 150.50,
  status: 'paid',
  payment_method: 'credit_card',
  receipt_date: '2025-12-11',
  notes: 'Monthly membership fee',
  type: 'membership_fees'
}).then(receipt => {
  console.log('Receipt created:', receipt);
});
```

---

## Notes

- All endpoints require authentication (Bearer token)
- Credit cards are stored in `member_credit_cards` table
- Receipts are stored in `receipts` table
- Receipts created via charge are automatically linked to the authenticated user
- Use charge endpoint for automatic receipt creation, or create receipt manually for other payment methods

