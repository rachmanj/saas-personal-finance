# Phase 6: Frontend — Budgets + Recurring Transactions + Dashboard Update

## EXISTING PATTERNS TO FOLLOW
- Look at resources/js/Components/Transactions/TransactionTable.jsx for ProTable patterns
- Look at resources/js/Components/Transactions/TransactionForm.jsx for Modal Form patterns
- Look at resources/js/Components/Transactions/QuickAddModal.jsx for Modal patterns
- Look at resources/js/Pages/Transactions/Index.jsx for Page patterns with AppLayout
- Look at resources/js/Pages/Dashboard.jsx for Dashboard layout
- API utility: resources/js/utils/api.js has apiGet, apiPost, apiPut, apiDelete
- All components use Ant Design 5 (antd) and @ant-design/pro-table
- Use import { App } from 'antd' for message/notification
- Dark mode is default

## TASK 1: Budgets Frontend

### 1a. BudgetProgressBar.jsx
File: resources/js/Components/Budgets/BudgetProgressBar.jsx

Props: { percent, status } — percent is number, status is 'ok'|'warning'|'over'

Use Ant Design Progress component:
- strokeColor: green (#52c41a) if status==='ok', orange (#faad14) if status==='warning', red (#ff4d4f) if status==='over'
- percent={percent}
- showInfo=true
- size="small"

### 1b. BudgetList.jsx
File: resources/js/Components/Budgets/BudgetList.jsx

Use Ant Design Table (not ProTable — simpler):
- Columns: Category (category.name), Amount (amount formatted), Period, Utilization (BudgetProgressBar), Actions (Edit/Delete)
- Props: { budgets, onEdit, onDelete }
- Fetch budgets via apiGet('/api/budgets') on mount
- Handle loading/error states

### 1c. BudgetForm.jsx
File: resources/js/Components/Budgets/BudgetForm.jsx

Ant Design Modal with Form:
- Fields: category_id (Select), amount (InputNumber), currency (Select), period (Select: monthly/yearly/custom), start_date (DatePicker), end_date (DatePicker, shown only if custom), notification_threshold (InputNumber 1-100)
- Fetch categories from apiGet('/api/categories') for the Select
- Props: { open, onClose, budget, onSuccess } — if budget is passed, it's edit mode, pre-fill form
- Submit: apiPost('/api/budgets', values) or apiPut('/api/budgets/'+budget.id, values)
- Use App.useApp() message.success/error for feedback

### 1d. BudgetAlert.jsx
File: resources/js/Components/Budgets/BudgetAlert.jsx

Banner showing budgets near/over threshold:
- Fetch from apiGet('/api/budgets/alerts') on mount
- Display as Ant Design Alert or List with warning/error styling
- Show budget name, category, utilization percent, status badge
- If no alerts, show Empty

### 1e. Budgets Index Page
File: resources/js/Pages/Budgets/Index.jsx

Follow Pages/Transactions/Index.jsx pattern:
- Use AuthenticatedLayout with title="Budgets"
- Use Head from @inertiajs/react
- Contains BudgetList, BudgetForm (modal), BudgetAlert
- State: modalOpen, selectedBudget, budgets

### 1f. Update Sidebar Navigation
File: resources/js/Layouts/AuthenticatedLayout.jsx

Add Budgets menu item:
```
{ key: '/budgets', icon: <PieChartOutlined />, label: <Link href="/budgets">Budgets</Link> },
```
Import PieChartOutlined from @ant-design/icons

### 1g. Add Budgets Web Route
File: routes/web.php

Add:
```php
Route::get('/budgets', fn () => inertia('Budgets/Index'))->name('budgets');
```

## TASK 2: Recurring Transactions Frontend

### 2a. RecurringList.jsx
File: resources/js/Components/Recurring/RecurringList.jsx

Ant Design Table:
- Columns: Description, Type (badge), Amount, Frequency (badge), Account (account.name), Next Due Date, Status (active/inactive Tag), Actions
- Actions column: Skip button, Post Now button, Edit, Delete
- Props: { recurring, onEdit, onDelete, onSkip, onPostNow }
- Fetch from apiGet('/api/recurring-transactions')

### 2b. RecurringForm.jsx
File: resources/js/Components/Recurring/RecurringForm.jsx

Ant Design Modal with Form:
- Fields: account_id (Select), category_id (Select, nullable), type (Select: income/expense/transfer), amount (InputNumber), currency (Select), description (Input), frequency (Select: daily/weekly/monthly/yearly/custom), interval (InputNumber min 1, default 1), start_date (DatePicker), end_date (DatePicker nullable), template_type (Select: subscription/bill/salary/rent/custom), is_active (Switch)
- Fetch accounts from apiGet('/api/accounts') and categories from apiGet('/api/categories')
- Props: { open, onClose, recurring, onSuccess }
- Submit: apiPost or apiPut

### 2c. UpcomingPreview.jsx
File: resources/js/Components/Recurring/UpcomingPreview.jsx

Preview next 30 days of recurring transactions:
- Fetch from apiGet('/api/recurring-transactions/upcoming')
- Display as a Timeline or List with date, description, amount, type badge
- Group by date
- Props: none (self-fetching)

### 2d. RecurringTransactions Index Page
File: resources/js/Pages/RecurringTransactions/Index.jsx

Follow Pages/Transactions/Index.jsx pattern:
- AuthenticatedLayout with title="Recurring Transactions"
- Head from @inertiajs/react
- Contains RecurringList, RecurringForm (modal), UpcomingPreview
- State: modalOpen, selectedRecurring, recurring

### 2e. Add Web Route
In routes/web.php:
```php
Route::get('/recurring-transactions', fn () => inertia('RecurringTransactions/Index'))->name('recurring-transactions');
```

### 2f. Update Sidebar
Add to AuthenticatedLayout.jsx:
```
{ key: '/recurring-transactions', icon: <SyncOutlined />, label: <Link href="/recurring-transactions">Recurring</Link> },
```
Import SyncOutlined from @ant-design/icons

## TASK 3: Dashboard Integration

### 3a. Update BudgetAlertWidget.jsx
File: resources/js/Components/Dashboard/BudgetAlertWidget.jsx

Replace the placeholder with real implementation:
- Fetch from apiGet('/api/budgets/alerts') on mount
- Show List of alerts with category name, utilization percent, BudgetProgressBar
- Show Empty if no alerts
- Handle loading state with Spin

### 3b. Update UpcomingWidget.jsx
File: resources/js/Components/Dashboard/UpcomingWidget.jsx

Replace the placeholder with real implementation:
- Fetch from apiGet('/api/recurring-transactions/upcoming') on mount
- Show List of upcoming items with date, description, amount, type badge
- Show Empty if no upcoming
- Handle loading state with Spin

### 3c. Keep Dashboard.jsx as-is
Dashboard.jsx already passes budgets and upcoming_recurring from the summary API. The widgets now fetch their own data independently.

## TASK 4: Build & Verify

After all frontend files are created:
1. Run `npm run build` to verify Vite builds successfully
2. Run `php artisan test` to ensure no tests break
3. Commit all changes

## IMPORTANT
- All React components must be clean, dark-mode compatible
- Use Ant Design components (Table, Modal, Form, Progress, Select, DatePicker, InputNumber, etc.)
- Use App.useApp() for message notifications
- API calls use the apiGet/apiPost/apiPut/apiDelete utilities from utils/api.js
- Every component handles loading, error, and empty states
- Follow existing code patterns exactly