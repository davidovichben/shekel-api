# Debts CRUD API Documentation

## Overview
Full CRUD (Create, Read, Update, Delete) operations for debts are now available.

## API Endpoints

### 1. List All Debts (GET)
```
GET /api/debts
```

**Query Parameters:**
- `limit` - Number of items per page (default: 15)
- `status` - Filter by status: `pending`, `paid`, `overdue`, `cancelled`
- `member_id` - Filter by member ID
- `sort` - Sort column (e.g., `amount`, `dueDate`, `status`, `-amount` for descending)

**Example:**
```
GET /api/debts?limit=50&status=pending&sort=-amount
```

**Response:**
```json
{
  "rows": [
    {
      "id": 1,
      "memberId": 5,
      "memberName": "John Doe",
      "amount": "150.00",
      "description": "Monthly dues",
      "dueDate": "2025-01-15",
      "status": "pending"
    }
  ],
  "counts": {
    "totalRows": 100,
    "totalPages": 7,
    "currentPage": 1,
    "perPage": 15
  }
}
```

### 2. Get Single Debt (GET)
```
GET /api/debts/{id}
```

**Response:**
```json
{
  "id": 1,
  "memberId": 5,
  "memberName": "John Doe",
  "amount": "150.00",
  "description": "Monthly dues",
  "dueDate": "2025-01-15",
  "status": "pending",
  "createdAt": "2025-01-01T10:00:00.000000Z",
  "updatedAt": "2025-01-01T10:00:00.000000Z"
}
```

### 3. Create Debt (POST)
```
POST /api/debts
```

**Request Body (supports both camelCase and snake_case):**
```json
{
  "memberId": 5,
  "amount": 150.00,
  "description": "Monthly dues",
  "dueDate": "2025-01-15",
  "status": "pending"
}
```

**Or:**
```json
{
  "member_id": 5,
  "amount": 150.00,
  "description": "Monthly dues",
  "due_date": "2025-01-15",
  "status": "pending"
}
```

**Validation Rules:**
- `memberId` / `member_id` - Required, must exist in members table
- `amount` - Required, numeric
- `description` - Optional, max 500 characters
- `dueDate` / `due_date` - Optional, valid date
- `status` - Required, one of: `pending`, `paid`, `overdue`, `cancelled`

**Response:** 201 Created with debt details

### 4. Update Debt (PUT/PATCH)
```
PUT /api/debts/{id}
PATCH /api/debts/{id}
```

**Request Body (all fields optional, supports camelCase and snake_case):**
```json
{
  "amount": 200.00,
  "status": "paid",
  "description": "Updated description"
}
```

**Response:** Updated debt details

### 5. Delete Debt (DELETE)
```
DELETE /api/debts/{id}
```

**Response:** 204 No Content

## Additional Endpoints

### Get Member Debts by Status
```
GET /api/members/{memberId}/debts/{status}
```

**Status values:**
- `open` - Returns pending and overdue debts
- `closed` - Returns paid and cancelled debts

**Query Parameters:**
- `sort` - Sort column (e.g., `amount`, `dueDate`, `status`)

**Example:**
```
GET /api/members/5/debts/open?sort=-amount
```

## Data Format

### Debt Object
- `id` - Debt ID
- `memberId` - Member ID (foreign key)
- `memberName` - Full name of the member (computed)
- `amount` - Debt amount (decimal, 2 decimal places)
- `description` - Debt description
- `dueDate` - Due date (YYYY-MM-DD format)
- `status` - Status: `pending`, `paid`, `overdue`, `cancelled`
- `createdAt` - Creation timestamp
- `updatedAt` - Last update timestamp

## Features

✅ Full CRUD operations
✅ Pagination support with `limit` parameter
✅ Filtering by status and member
✅ Sorting support
✅ camelCase/snake_case conversion (accepts both formats)
✅ Member relationship loaded automatically
✅ Proper validation
✅ Consistent response format

## Example Usage

### Create a debt
```bash
curl -X POST http://localhost:8000/api/debts \
  -H "Content-Type: application/json" \
  -d '{
    "memberId": 1,
    "amount": 250.50,
    "description": "Annual membership fee",
    "dueDate": "2025-02-01",
    "status": "pending"
  }'
```

### List all pending debts
```bash
curl http://localhost:8000/api/debts?status=pending&limit=100
```

### Update debt status to paid
```bash
curl -X PUT http://localhost:8000/api/debts/1 \
  -H "Content-Type: application/json" \
  -d '{
    "status": "paid"
  }'
```

### Delete a debt
```bash
curl -X DELETE http://localhost:8000/api/debts/1
```

