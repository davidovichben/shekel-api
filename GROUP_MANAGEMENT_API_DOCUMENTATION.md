# Group Management API - Client Documentation

## Overview

This document describes the API endpoints for managing groups and their members. All endpoints require authentication and filter data by business context.

---

## Endpoints

### 1. Get Single Group

**GET** `/api/groups/{groupId}`

Get a single group by ID.

**Response:**
```json
{
  "id": 1,
  "name": "Board Members"
}
```

**Example:**
```javascript
const response = await fetch('/api/groups/1', {
  method: 'GET',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json'
  }
});
const group = await response.json();
```

---

### 2. Get Group Members

**GET** `/api/groups/{groupId}/members`

Get all members in a group. Returns full member objects.

**Response:**
```json
[
  {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "full_name": "John Doe",
    "mobile": "0501234567",
    "phone": "02-1234567",
    "email": "john@example.com",
    "address": "123 Main St",
    "city": "Jerusalem",
    "type": "permanent",
    "member_number": "M001",
    "balance": 0,
    "groups": ["Board Members", "Finance Committee"],
    "created_at": "2025-01-01T00:00:00.000000Z",
    "updated_at": "2025-01-01T00:00:00.000000Z"
  }
]
```

**Example:**
```javascript
const response = await fetch('/api/groups/1/members', {
  method: 'GET',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json'
  }
});
const members = await response.json();
```

---

### 3. Update Group Name

**PUT** `/api/groups/{groupId}`

Update the name of a group.

**Request Body:**
```json
{
  "name": "Updated Group Name"
}
```

**Response:**
```json
{
  "id": 1,
  "name": "Updated Group Name"
}
```

**Example:**
```javascript
const response = await fetch('/api/groups/1', {
  method: 'PUT',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    name: 'Updated Group Name'
  })
});
const updatedGroup = await response.json();
```

---

### 4. Add Member to Group

**POST** `/api/groups/{groupId}/members`

Add a member to a group. The member ID should be sent as a string.

**Request Body:**
```json
{
  "member_id": "5"
}
```

**Response:**
```json
{
  "message": "Member added to group"
}
```

**Status Code:** `201 Created`

**Example:**
```javascript
const response = await fetch('/api/groups/1/members', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    member_id: '5'
  })
});
const result = await response.json();
```

**Note:** If the member is already in the group, the request will succeed without error (idempotent operation).

---

### 5. Remove Member from Group

**DELETE** `/api/groups/{groupId}/members/{memberId}`

Remove a member from a group. The member ID should be sent as a string in the URL.

**Response:**
- Status Code: `204 No Content` (no response body)

**Example:**
```javascript
const response = await fetch('/api/groups/1/members/5', {
  method: 'DELETE',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN'
  }
});
// Response status will be 204 if successful
```

---

## Error Responses

All endpoints may return the following error responses:

### 404 Not Found
```json
{
  "message": "No query results for model [App\\Models\\Group] 1"
}
```
Returned when the group or member doesn't exist or doesn't belong to the current business.

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."]
  }
}
```
Returned when request validation fails.

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```
Returned when authentication token is missing or invalid.

---

## Notes

1. **Member IDs**: Member IDs are accepted as strings in requests but are stored as integers in the database.

2. **Business Context**: All operations are automatically filtered by the current business context. You can only access groups and members that belong to your business.

3. **Idempotent Operations**: 
   - Adding a member to a group multiple times will not create duplicates
   - Removing a member that's not in the group will still return success (204)

4. **Member Response Format**: The `GET /api/groups/{groupId}/members` endpoint returns full member objects with all available fields, including relationships like `groups`.

5. **Route Precedence**: The routes are ordered to ensure proper matching. More specific routes (like `/groups/{groupId}/members`) come before general routes.

---

## Complete Example

```javascript
// Get a group
const groupResponse = await fetch('/api/groups/1');
const group = await groupResponse.json();
console.log('Group:', group.name);

// Get all members in the group
const membersResponse = await fetch('/api/groups/1/members');
const members = await membersResponse.json();
console.log('Members:', members.length);

// Update group name
await fetch('/api/groups/1', {
  method: 'PUT',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({ name: 'New Name' })
});

// Add a member
await fetch('/api/groups/1/members', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({ member_id: '5' })
});

// Remove a member
await fetch('/api/groups/1/members/5', {
  method: 'DELETE',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN'
  }
});
```

