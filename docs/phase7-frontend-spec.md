# Phase 7 — Bank Import Frontend Spec

## Context
- Laravel 13 + Inertia.js + React 19 + Ant Design 5
- npm install with `--legacy-peer-deps`
- API base: `/api/imports`
- Auth: Sanctum SPA, includes CSRF token in headers
- API utility: `resources/js/utils/api.js` (apiGet, apiPost, apiDelete)
- usePollingJob hook: `resources/js/Hooks/usePollingJob.jsx` (reuse for ImportProgress)
- Dark mode default (AntD ConfigProvider dark algorithm)
- All UI labels in Bahasa Indonesia

## API Endpoints
- GET `/api/imports` — list imports (paginated, params: page, pageSize)
- POST `/api/imports/upload` — multipart: file + account_id. Returns { id, file_type, total_rows, preview: { headers, rows } for CSV or { account_info, transactions, total_rows } for OFX }
- GET `/api/imports/{import}/preview` — re-parse preview
- POST `/api/imports/{import}/process` — start processing (mostly for OFX)
- POST `/api/imports/{import}/confirm` — body: { column_mapping: { date, description, amount, selected_rows } }
- DELETE `/api/imports/{import}` — delete

## Components to Create

### 1. resources/js/Components/Imports/ImportUploader.jsx
AntD `Upload.Dragger` component:
- Accepts .csv and .ofx files
- Shows drag-and-drop UI with icon
- Props: onUpload(file, accountId) — callback when file is uploaded
- Requires account_id selection before upload (use AntD Select)
- Shows file name and size after selection

### 2. resources/js/Components/Imports/CSVColumnMapper.jsx
AntD Table-based column mapping:
- Shows detected CSV headers from the API preview response
- Each header row has a Select dropdown to map to target field: date, description, amount, category (optional)
- The Select options: "Pilih Field..." (default), "Tanggal", "Deskripsi", "Jumlah", "Kategori"
- Each target field must be unique (can't map two headers to same field)
- Props: headers (string[]), onMapping(mapping) — callback with { date, description, amount, category }
- Validate that at least date, description, and amount are mapped before allowing next step

### 3. resources/js/Components/Imports/ImportPreview.jsx
Preview of parsed rows:
- Shows first N rows in a table (AntD Table)
- For CSV: shows columns based on mapping
- For OFX: shows date, description, amount, type
- Each row has a checkbox (default checked) to include/exclude
- Highlights potential duplicate rows in yellow/orange (if API returns duplicate info)
- Props: rows (array), fileType ('csv'|'ofx'), onSelectionChange(selectedIndices)

### 4. resources/js/Components/Imports/ImportProgress.jsx
Polling progress display:
- Reuses `usePollingJob` hook: `usePollingJob('/api/imports/{importId}/preview', { enabled: true })`
- Shows AntD Progress bar based on status
- Shows status text: "Menunggu..." / "Memproses..." / "Selesai" / "Gagal"
- Shows imported_rows, skipped_rows, total_rows
- On 'completed' status: show success message with counts
- On 'failed' status: show error from error_log
- Props: importId (number)

### 5. resources/js/Pages/Imports/Create.jsx
Wizard page using AntD Steps:
- Step 0: "Upload File" — ImportUploader component (select account + upload file)
- Step 1: "Pemetaan Kolom" — CSVColumnMapper (only for CSV; skip for OFX)
- Step 2: "Pratinjau" — ImportPreview
- Step 3: "Konfirmasi" — Show summary + confirm button
- After confirm: show ImportProgress component
- Uses Inertia page pattern (not a separate route, just a component in AppLayout)

### 6. resources/js/Pages/Imports/Index.jsx
Import history page:
- Title: "Riwayat Import"
- AntD Table showing: Tanggal, Tipe File, Akun, Status, Total Baris, Diimpor, Dilewati, Aksi
- Status column uses AntD Tag with colors: pending=blue, processing=orange, completed=green, failed=red
- Actions: Delete (with confirmation modal), View detail
- Pagination via server-side (pageSize, current params)
- "Import Baru" button linking to Create page

## Routing
Add Inertia routes for:
- GET `/imports` → renders `Pages/Imports/Index`
- GET `/imports/create` → renders `Pages/Imports/Create`

In `routes/web.php` under the authenticated middleware group.

## Styling
- Dark mode compatible (use AntD theme tokens)
- Use AntD components: Steps, Upload.Dragger, Table, Select, Button, Tag, Progress, Card, Space, Typography
- All text in Bahasa Indonesia

## Implementation Notes
- Reuse `usePollingJob` hook from Phase 5 for ImportProgress
- Use `apiPost` from utils/api.js for API calls
- For file upload, use FormData (not JSON) since it's multipart
- Import the utils/api.js functions: apiGet, apiPost, apiDelete

## After Implementation
1. Run `npm run build` to verify the frontend builds
2. Verify no build errors
3. Create the directories if they don't exist:
   - `resources/js/Components/Imports/`
   - `resources/js/Pages/Imports/`