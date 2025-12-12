# Dashboard Stats and Reports API - Client Documentation

## Overview

This document describes the dashboard statistics endpoint and PDF report generation endpoints for the financial dashboard.

---

## 1. Dashboard Statistics

**Endpoint:** `GET /api/dashboard/stats`

Returns comprehensive dashboard statistics including summary cards, charts data, and financial metrics.

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `month` | string | No | Month in format `YYYY-MM` (e.g., "2025-01"). Defaults to current month. |

### Example Request

```javascript
// Get current month stats
fetch('/api/dashboard/stats', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN'
  }
})

// Get specific month stats
fetch('/api/dashboard/stats?month=2025-01', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN'
  }
})
```

### Response Structure

```json
{
  "summaryCards": {
    "donations": {
      "amount": "18450.00",
      "changePercent": -5,
      "changeLabel": "מהחודש שעבר"
    },
    "expenses": {
      "amount": "12104.00",
      "changePercent": 2,
      "changeLabel": "מהחודש שעבר"
    },
    "openDebts": {
      "amount": "5450.00",
      "count": 18,
      "label": "חובות פעילים"
    },
    "monthlyBalance": {
      "amount": "234.00",
      "label": "הכנסות מחות הוצאות"
    }
  },
  "lastMonthBalance": {
    "income": "3118.00",
    "expenses": "1487.00",
    "balance": "1631.00",
    "label": "ביתרה"
  },
  "debtDistribution": {
    "total": "2549.00",
    "categories": [
      {
        "type": "neder_shabbat",
        "label": "נדר שבת",
        "amount": "850.00",
        "percentage": 33
      },
      {
        "type": "kiddush",
        "label": "עליות",
        "amount": "600.00",
        "percentage": 24
      },
      {
        "type": "dmei_chaver",
        "label": "דמי חבר",
        "amount": "1099.00",
        "percentage": 43
      }
    ]
  },
  "semiAnnualTrend": {
    "months": [
      {
        "month": "2025-01",
        "income": "35874.00",
        "expenses": "35874.00"
      },
      {
        "month": "2025-02",
        "income": "31250.00",
        "expenses": "28900.00"
      }
    ]
  }
}
```

### Response Fields

**summaryCards**
- `donations.amount` - Total donations (receipts) for the month (string, 2 decimals)
- `donations.changePercent` - Percentage change from previous month (integer, can be negative)
- `donations.changeLabel` - Hebrew label "מהחודש שעבר"
- `expenses.amount` - Total expenses for the month (string, 2 decimals)
- `expenses.changePercent` - Percentage change from previous month (integer)
- `expenses.changeLabel` - Hebrew label "מהחודש שעבר"
- `openDebts.amount` - Total amount of open (pending) debts (string, 2 decimals)
- `openDebts.count` - Number of active open debts (integer)
- `openDebts.label` - Hebrew label "חובות פעילים"
- `monthlyBalance.amount` - Monthly balance = donations - expenses (string, 2 decimals)
- `monthlyBalance.label` - Hebrew label "הכנסות מחות הוצאות"

**lastMonthBalance**
- `income` - Total income (receipts) for previous month (string, 2 decimals)
- `expenses` - Total expenses for previous month (string, 2 decimals)
- `balance` - Balance = income - expenses (string, 2 decimals)
- `label` - Hebrew label "ביתרה"

**debtDistribution**
- `total` - Total amount of all debts (string, 2 decimals)
- `categories` - Array of debt types with:
  - `type` - Debt type code
  - `label` - Hebrew label
  - `amount` - Total for this type (string, 2 decimals)
  - `percentage` - Percentage of total (integer)

**semiAnnualTrend**
- `months` - Array of 6 months (current + 5 previous)
  - `month` - Month in YYYY-MM format
  - `income` - Total income for that month (string, 2 decimals)
  - `expenses` - Total expenses for that month (string, 2 decimals)

### Debt Type Labels

| Type Code | Hebrew Label |
|-----------|--------------|
| `neder_shabbat` | נדר שבת |
| `tikun_nezek` | תיקון נזק |
| `dmei_chaver` | דמי חבר |
| `kiddush` | קידוש שבת |
| `neder_yom_shabbat` | נדר יום שבת |
| `other` | אחר |

---

## 2. PDF Reports

All report endpoints return PDF files for the **current month** automatically. The PDFs are generated with Hebrew labels and RTL (right-to-left) layout.

### 2.1 Expense Report

**Endpoint:** `GET /api/reports/expenses`

Generates a PDF report of all expenses for the current month.

**Response:** PDF file download

**Example Request:**
```javascript
fetch('/api/reports/expenses', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN'
  }
})
.then(response => response.blob())
.then(blob => {
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'expense_report_2025-01.pdf';
  a.click();
});
```

**PDF Columns:**
- תדירות (Frequency)
- סטטוס (Status)
- תאריך (Date)
- תיאור (Description)
- סכום (Amount)
- סוג הוצאה (Expense Type)
- שם ספק (Supplier Name)

---

### 2.2 Donations and Income Report

**Endpoint:** `GET /api/reports/donations`

Generates a PDF report of all donations (receipts/incomes) for the current month.

**Response:** PDF file download

**Example Request:**
```javascript
fetch('/api/reports/donations', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN'
  }
})
.then(response => response.blob())
.then(blob => {
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'donations_report_2025-01.pdf';
  a.click();
});
```

**PDF Columns:**
- סוג (Type)
- הערות (Description)
- תאריך (Date)
- אמצעי תשלום (Payment Method)
- סטטוס (Status)
- סכום כולל (Total Amount)
- משתמש (User)
- מספר קבלה (Receipt Number)

---

### 2.3 Community Debts Report

**Endpoint:** `GET /api/reports/debts`

Generates a PDF report of all debts for the current month.

**Response:** PDF file download

**Example Request:**
```javascript
fetch('/api/reports/debts', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN'
  }
})
.then(response => response.blob())
.then(blob => {
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'debts_report_2025-01.pdf';
  a.click();
});
```

**PDF Columns:**
- תאריך תזכורת אחרונה (Last Reminder Date)
- סטטוס (Status)
- תאריך יעד (Due Date)
- תיאור (Description)
- סכום (Amount)
- סוג חוב (Debt Type)
- שם מלא (Full Name)

---

### 2.4 Financial Balance Report

**Endpoint:** `GET /api/reports/balance`

Generates a comprehensive financial balance report showing income, expenses, and balance for the current month.

**Response:** PDF file download

**Example Request:**
```javascript
fetch('/api/reports/balance', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN'
  }
})
.then(response => response.blob())
.then(blob => {
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'balance_report_2025-01.pdf';
  a.click();
});
```

**PDF Structure:**
- Summary row with totals
- Expenses section with all expense details
- Income section with all receipt details
- Columns: תיאור (Description), תאריך (Date), סוג (Type), סטטוס (Status), הוצאות (Expenses), הכנסות (Income), מאזן (Balance)

---

## Error Responses

All endpoints return standard error responses:

**404 Not Found:**
```json
{
  "message": "No expenses found for current month"
}
```

**500 Internal Server Error:**
```json
{
  "message": "Failed to generate expense report",
  "error": "Error details..."
}
```

---

## Authentication

All endpoints require authentication. Include the JWT token in the Authorization header:

```
Authorization: Bearer YOUR_JWT_TOKEN
```

---

## JavaScript Examples

### Complete Dashboard Stats Implementation

```javascript
async function loadDashboardStats(month = null) {
  const url = month 
    ? `/api/dashboard/stats?month=${month}`
    : '/api/dashboard/stats';
  
  try {
    const response = await fetch(url, {
      headers: {
        'Authorization': `Bearer ${getAuthToken()}`,
        'Content-Type': 'application/json'
      }
    });
    
    if (!response.ok) {
      throw new Error('Failed to load dashboard stats');
    }
    
    const data = await response.json();
    
    // Update summary cards
    updateSummaryCard('donations', data.summaryCards.donations);
    updateSummaryCard('expenses', data.summaryCards.expenses);
    updateSummaryCard('openDebts', data.summaryCards.openDebts);
    updateSummaryCard('monthlyBalance', data.summaryCards.monthlyBalance);
    
    // Update charts
    renderLastMonthBalance(data.lastMonthBalance);
    renderDebtDistribution(data.debtDistribution);
    renderSemiAnnualTrend(data.semiAnnualTrend);
    
  } catch (error) {
    console.error('Error loading dashboard stats:', error);
  }
}

function updateSummaryCard(type, data) {
  const card = document.querySelector(`[data-card="${type}"]`);
  card.querySelector('.amount').textContent = `₪ ${data.amount}`;
  
  if (data.changePercent !== undefined) {
    const changeElement = card.querySelector('.change');
    changeElement.textContent = `${data.changePercent > 0 ? '+' : ''}${data.changePercent}% ${data.changeLabel}`;
    changeElement.classList.toggle('positive', data.changePercent > 0);
    changeElement.classList.toggle('negative', data.changePercent < 0);
  }
  
  if (data.count !== undefined) {
    card.querySelector('.count').textContent = `${data.count} ${data.label}`;
  }
}
```

### Generate PDF Report

```javascript
async function generateReport(reportType) {
  const reportEndpoints = {
    'expenses': '/api/reports/expenses',
    'donations': '/api/reports/donations',
    'debts': '/api/reports/debts',
    'balance': '/api/reports/balance'
  };
  
  const endpoint = reportEndpoints[reportType];
  if (!endpoint) {
    console.error('Invalid report type');
    return;
  }
  
  try {
    const response = await fetch(endpoint, {
      headers: {
        'Authorization': `Bearer ${getAuthToken()}`
      }
    });
    
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Failed to generate report');
    }
    
    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${reportType}_report_${new Date().toISOString().slice(0, 7)}.pdf`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    
  } catch (error) {
    console.error('Error generating report:', error);
    alert('Failed to generate report: ' + error.message);
  }
}

// Usage
generateReport('expenses');
generateReport('donations');
generateReport('debts');
generateReport('balance');
```

---

## Notes

- All monetary values are returned as strings with 2 decimal places
- Percentage values are integers (no decimals)
- All dates in responses use ISO format (YYYY-MM-DD or YYYY-MM)
- PDF reports are generated with Hebrew labels and RTL layout
- Reports are generated for the current month automatically
- Empty reports return 404 with an appropriate message
- All endpoints respect business_id filtering (multi-tenancy)

