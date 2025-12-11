# PDF Receipt Generation - Client Documentation

## Generate PDF Receipt on Charge

**POST** `/api/billing/charge`

When charging a credit card, you can automatically generate and save a PDF receipt by including the `createReceipt` parameter.

### Request Body

```json
{
  "credit_card_id": 1,
  "amount": 150.50,
  "description": "Monthly membership fee",
  "type": "membership_fees",
  "createReceipt": true
}
```

**Parameters:**
- `createReceipt` (boolean, optional) - Set to `true` to generate and save a PDF receipt

### Response

When `createReceipt` is `true`, the response includes the PDF file path:

```json
{
  "success": true,
  "transaction": {
    "id": "TXN_abc123xyz4567890",
    "amount": "150.50",
    "status": "completed"
  },
  "receipt": {
    "id": 1,
    "receipt_number": "TXN_abc123xyz4567890",
    "total_amount": "150.50",
    "status": "paid",
    "type": "membership_fees",
    "pdf_file": "receipts/receipt_1_1234567890.pdf"
  }
}
```

**Note:** If PDF generation fails, the receipt is still created successfully, but `pdf_file` will be `null`.

---

## Download PDF Receipt

**GET** `/api/receipts/{id}/pdf`

Download a previously generated PDF receipt.

### Example Request

```
GET /api/receipts/1/pdf
```

### Response

- **200 OK** - Returns PDF file download
- **404 Not Found** - PDF file not found or doesn't exist

---

## JavaScript Example

### Charge with PDF Receipt Generation

```javascript
async function chargeWithReceipt(creditCardId, amount, description, type, createReceipt = false) {
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
      type: type,
      createReceipt: createReceipt
    }),
  });

  const data = await response.json();
  
  if (data.receipt.pdf_file) {
    console.log('PDF generated:', data.receipt.pdf_file);
    // PDF is saved and can be downloaded later
  }
  
  return data;
}

// Usage
chargeWithReceipt(1, 150.50, 'Monthly fee', 'membership_fees', true)
  .then(result => {
    console.log('Receipt created:', result.receipt);
    if (result.receipt.pdf_file) {
      console.log('PDF available at:', result.receipt.pdf_file);
    }
  });
```

### Download PDF Receipt

```javascript
async function downloadReceiptPdf(receiptId) {
  const response = await fetch(`/api/receipts/${receiptId}/pdf`, {
    headers: {
      'Accept': 'application/pdf',
      'Authorization': `Bearer ${yourToken}`
    }
  });

  if (!response.ok) {
    throw new Error('PDF not found');
  }

  const blob = await response.blob();
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `receipt_${receiptId}.pdf`;
  document.body.appendChild(a);
  a.click();
  window.URL.revokeObjectURL(url);
  document.body.removeChild(a);
}

// Usage
downloadReceiptPdf(1);
```

---

## Notes

- PDF receipts are saved in `storage/app/public/receipts/`
- Filename format: `receipt_{receipt_id}_{timestamp}.pdf`
- PDF includes: receipt number, date, user, type, status, amounts, payment method, notes
- PDF is formatted with RTL (right-to-left) layout and Hebrew labels
- If `createReceipt` is not provided or `false`, no PDF is generated
- PDF can be generated later by updating the receipt (future feature)
- PDF files are automatically deleted when receipt is deleted

