# System Architecture — Personal Finance Tracker SaaS

**Last Updated:** 2026-07-27
**Status:** Phase 1-10 COMPLETE — 165 tests, 594 assertions

## Project Overview

Multi-tenant Personal Finance Tracker SaaS delivered as an installable PWA. Users track transactions across multiple accounts and currencies via three input methods (manual form, photo receipt OCR, voice), with AI-assisted categorization, budgets, recurring transactions, bank import, saving goals, bill reminders, rich reports, and export (PDF/CSV/OFX/Google Sheets). Billing is subscription-based via Stripe.

## Technology Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 13 (PHP 8.5) |
| Frontend | React 19 + Inertia.js + Ant Design 5 + ProTable + Recharts |
| Database | MySQL 8.4 (personal_finance) |
| Cache/Queue | Redis 7 (Laravel Horizon) |
| PWA | vite-plugin-pwa + Workbox |
| Auth | Laravel Sanctum (SPA, cookie-based) |
| Multi-tenant | Teams + BelongsToTeam trait + EnsureTeamContext middleware |
| Billing | Stripe Cashier on Team model |
| OCR | Tesseract.js (client) + Google Cloud Vision (server, stub) |
| Voice | MediaRecorder API + Deepgram/Whisper (server, stub) |
| NLP | OpenAI GPT-4o-mini (stub) |
| PDF | barryvdh/laravel-dompdf |
| CSV | league/csv |
| Push | laravel-notification-channels/webpush |
| Test | PHPUnit 165 tests, 594 assertions |

## Architecture

```
Browser/PWA (React 19 + Inertia + AntD + Workbox SW)
        ↓
Laravel 13 App (Actions/Services/Jobs pattern + Horizon Redis Queue)
        ↓
MySQL 8.4 + Redis 7 + File Storage + External APIs
```

## Database (20+ tables)

**Identity:** users, teams, team_user, team_invitations
**Billing:** subscriptions, subscription_items, payment_methods, invoices
**Core:** accounts, categories, tags, transactions, transaction_splits, transaction_tags
**Budgets:** budgets
**Recurring:** recurring_transactions, recurring_transaction_logs
**Goals:** saving_goals, goal_contributions
**Reminders:** bill_reminders, push_subscriptions
**AI:** categorization_rules, ai_categorization_logs
**Import/Export:** imports, ocr_jobs, voice_jobs, exchange_rates
**Audit:** audit_logs

## Code Organization

- `app/Actions/` — single-action classes (CreateTransactionAction, BuildDashboardSummaryAction, etc.)
- `app/Services/` — external API wrappers (CurrencyConverterService, ExchangeRateService, OcrService, etc.)
- `app/Enums/` — backed string enums (TransactionType, AccountType, Frequency, etc.)
- `app/Jobs/` — queued jobs (ProcessOcrJob, PostRecurringTransactions, etc.)
- `app/Notifications/` — mail + webpush notifications (BillReminderDue)
- `app/Models/Concerns/BelongsToTeam.php` — global scope for multi-tenant isolation

## API Routes

All under `/api/` with `auth:sanctum` middleware:
- `accounts`, `categories`, `tags`, `transactions` (CRUD + bulk + splits + suggestions)
- `transactions/ocr`, `transactions/voice` (job-based async processing)
- `dashboard/summary` (single endpoint, all widgets)
- `budgets`, `recurring-transactions` (CRUD + skip/post-now/upcoming)
- `imports` (upload → preview → confirm pipeline)
- `reports` (6 endpoints: spending-by-category, income-vs-expense, monthly-summary, trend, year-over-year, net-worth)
- `saving-goals` (CRUD + contributions)
- `bill-reminders` (CRUD + paid toggle + subscribe)
- `export` (pdf, csv, ofx, google-sheets)
- `ai` (categorize, categorize/batch, categorization-rules, categorization-accuracy)

## PWA

- Service worker via Workbox (injectManifest)
- Cache strategies: NetworkFirst (API), CacheFirst (fonts/assets)
- Web App Manifest: name 'Personal Finance Tracker', theme #141414
- Offline indicator + install prompt components
- Push notifications via webpush (VAPID)

## Scheduled Jobs

- `PostRecurringTransactions` — daily at 00:05
- `SendBillReminders` — daily at 08:00
- `FetchExchangeRates` — daily at 01:00
- `TrainCategorizationModel` — weekly

## Phases Completed

| # | Phase | Tests |
|---|-------|-------|
| 1 | Scaffold + Auth + Multi-tenant | 7 |
| 2 | Accounts + Categories + Tags | 24 |
| 3 | Transaction Management | 22 |
| 4 | Dashboard | 7 |
| 5 | OCR + Voice Input | 6 |
| 6 | Budgets + Recurring | 23 |
| 7 | Bank Import CSV/OFX | 18 |
| 8 | Reports + Multi-Currency + Goals | 20 |
| 9 | PWA + Bill Reminders + Export | 13 |
| 10 | AI Auto-Categorization + Billing + Polish | 25 |
| **Total** | | **165** |