# API Documentation

All responses use `JsonBody` resource format:

```json
{
  "status_code": 200,
  "data": { ... },
  "message": "ok"
}
```

Auth: Web endpoints use session (`auth` middleware). Mobile API uses `auth:sanctum` (Bearer token).

---

## Mobile API

### Auth

Base URL: `/Auth/api/`

#### `POST /base-token/`
Generate encrypted device token (pre-auth step).

**Request:**
```json
{
  "email": "employee@email.com",
  "password": "secret"
}
```

**Response:** `200`
```json
{
  "data": "<encrypted-base64-token>"
}
```
Token format: `encrypt(APP_NAME-YYYYMMDD-userId)`. Expires daily.

---

#### `POST /sign-in/`
Sign in mobile device. Requires base-token from previous step.

**Request:**
```json
{
  "device_name": "Pixel 7 Pro",
  "device_token": "<base64-encrypted-token>",
  "device_type": "android",
  "email": "employee@email.com",
  "password": "secret"
}
```

**Response:** `200`
```json
{
  "data": {
    "token": "<sanctum-plain-text-token>",
    "user": {
      "id": 1,
      "email": "employee@email.com",
      "type": "employee",
      "userDetail": { "fullname": "...", "phone": "...", "address": "...", "picture_profile": null }
    }
  }
}
```

**Errors:**
- `401` — Invalid app/device/user credentials
- `403` — Token already exists (device already registered)
- `422` — Validation failed

---

#### `DELETE /delete-device/{id}`
Force logout employee device(s). Admin only (`only-company`, `scope-company`).

**URL params:** `{id}` = employee user ID

**Request body (optional):**
```json
{
  "device_token": "<token-to-logout>"
}
```
If `device_token` omitted, all devices for that employee are logged out.

**Response:** `200`
```json
{
  "message": "Employee logged out successfully."
}
```

---

### Presence

Base URL: `/Presence/` (middleware: `auth:sanctum`)

#### `GET /{user}`
Get presence history for employee.

**URL params:** `{user}` = employee user ID

**Query params:**
| Param | Type | Description |
|-------|------|-------------|
| `mst_presence_location_id` | int? | Filter by location |
| `date_start` | date? | Start date (Y-m-d) |
| `date_end` | date? | End date (Y-m-d) |
| `status` | string? | `pending`, `approved`, `rejected` |
| `orderBy` | string? | `asc` / `desc` (default: desc) |
| `currentPage` | int? | Page number (default: 1) |

**Response:** `200` (paginated)
```json
{
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "mst_user_id": 1,
        "mst_presence_location_id": 1,
        "latitude": "-6.1234",
        "longitude": "106.5678",
        "time_in": "2026-06-18T08:00:00.000000Z",
        "time_out": "2026-06-18T17:00:00.000000Z",
        "note": null,
        "status_by_admin": "approved",
        "presenceLocation": { "id": 1, "name": "Main Office", ... },
        "created_at": "..."
      }
    ],
    "total": 42,
    "per_page": 10,
    ...
  }
}
```

---

#### `GET /{user}/latest`
Get latest active check-in (no check-out yet).

**URL params:** `{user}` = employee user ID

**Query params:**
| Param | Type | Description |
|-------|------|-------------|
| `mst_presence_location_id` | int? | Filter by location |

**Response:** `200`
```json
{
  "data": {
    "id": 1,
    "time_in": "2026-06-18T08:00:00.000000Z",
    "work_hour": "9 jam 0 menit",
    "presenceLocation": { "id": 1, "name": "Main Office", ... }
  }
}
```
If no active presence, returns `data: null` with message `"No presence history found"`.

---

#### `POST /api/create/`
Check-in or check-out.

**Request (multipart/form-data):**
| Field | Type | Description |
|-------|------|-------------|
| `mst_user_id` | int | Employee user ID |
| `status` | string | `in` or `out` |
| `location_id` | int | Presence location ID |
| `code` | string? | Verification code from admin |
| `latitude` | float | Current latitude (-90 to 90) |
| `longitude` | float | Current longitude (-180 to 180) |
| `note` | string? | Optional note |
| `attachment` | file? | Image/PDF/DOC attachment |

**Validation rules:**
- If `status = 'in'`:
  - Checks `start_hour` — rejects if before operating hours
  - Checks for existing active check-in — rejects if already checked in
  - Late check-in (after `end_hour`) → `note` appended with late warning, `status_by_admin` = `pending`
- If `status = 'out'`:
  - Rejects if no active check-in found
  - Late check-out (exceeds `max_hour`) → `pending`
  - Early check-out (before `min_hour`) → `pending`
- Always validates geo-distance vs location `tolerance` (radius):
  - Out-of-radius → `note` appended, `status_by_admin` = `pending`
- Verification code checked (403 if wrong)

**Response:** `200`
```json
{
  "data": { "id": 1, "mst_user_id": 1, "status_by_admin": "pending", ... },
  "message": "Success"
}
```

**Errors:**
- `403` — Wrong code / active check-in exists / no check-in detected
- `404` — Verification not set up
- `422` — Before operating hours / validation failed

---

### Locations

Base URL: `/Location/api/` (middleware: `auth:sanctum`)

#### `GET /all/json`
Get all presence locations for a company.

**Query params:**
| Param | Type | Description |
|-------|------|-------------|
| `company_id` | int | Company user ID |
| `search` | string? | Filter by location name |

**Response:** `200`
```json
{
  "data": [
    {
      "id": 1,
      "mst_user_id": 2,
      "name": "Main Office",
      "latitude": "-6.2088",
      "longitude": "106.8456",
      "tolerance": 100,
      "start_hour": "08:00:00",
      "end_hour": "17:00:00",
      "min_hour": null,
      "max_hour": "10:00:00",
      "single_verification": [{ "id": 1, "verification_hash": "99181234", ... }]
    }
  ]
}
```

---

### Receipts

Base URL: `/Receipt/api/` (middleware: `auth:sanctum`)

#### `GET /{id}`
Get salary receipts for an employee.

**URL params:** `{id}` = employee user ID

**Query params:**
| Param | Type | Description |
|-------|------|-------------|
| `date_start` | date? | Filter start (Y-m-d) |
| `date_end` | date? | Filter end (Y-m-d) |

**Response:** `200` (paginated)
```json
{
  "data": {
    "data": [
      {
        "id": 1,
        "mst_user_id": 1,
        "work_hour": 40,
        "total_salary": 5000000,
        "total_tax": 500000,
        "salary_after_tax": 4500000,
        "total_presence_record": 20,
        "start_date": "2026-06-01",
        "end_date": "2026-06-30",
        "status": "pending",
        "user": { "id": 1, "userDetail": { "fullname": "John Doe" }, ... },
        "salaryReceiptItems": [ ... ]
      }
    ],
    "total": 5,
    ...
  }
}
```

---

#### `GET /{payroll}/detail`
Get salary receipt detail.

**URL params:** `{payroll}` = SalaryReceipt ID

**Response:** `200`
```json
{
  "data": {
    "id": 1,
    "mst_user_id": 1,
    "work_hour": 40,
    "total_salary": 5000000,
    "salary_after_tax": 4500000,
    "user": {
      "userDetail": { "fullname": "John Doe", "phone": "08123456789", ... },
      "company": { "userDetail": { "fullname": "PT Example" } }
    },
    "companySalaryItem": [
      { "id": 1, "name": "Gaji Pokok", "quantity": 1, "total_value": 4000000, ... },
      { "id": 2, "name": "PPh 21", "quantity": 1, "total_value": 500000, ... }
    ]
  }
}
```

---

## Web JSON Endpoints (Admin SPA)

All web JSON endpoints return `JsonBody` format. Accessible via `auth` session middleware unless noted.

### Auth

| Method | Route | Auth | Description |
|--------|-------|------|-------------|
| `GET` | `/Auth/sign-in` | none | Render sign-in page |
| `POST` | `/Auth/sign-in` | none | Sign in (rate-limited: 5/min) |
| `POST` | `/Auth/sign-out` | auth | Sign out, invalidate session |

**`POST /Auth/sign-in`**

Request:
```json
{
  "email": "admin@company.com",
  "password": "secret"
}
```
Employee → `403 Forbidden`. Company/superadmin → redirect to `/Dashboard`.

---

### Email Verification

| Method | Route | Auth | Description |
|--------|-------|------|-------------|
| `GET` | `/verify` | auth | Show verify-email page |
| `GET` | `/verify/{id}/{hash}` | auth+signed | Verify email |
| `POST` | `/verification-notification` | auth+throttle:6,1 | Resend verification link |

---

### Password

| Method | Route | Auth | Description |
|--------|-------|------|-------------|
| `GET` | `/Auth/password/forgot` | none | Show forgot password page |
| `POST` | `/Auth/password/send` | none | Send reset link |
| `GET` | `/Auth/password/reset/{token}` | none | Show reset password page |
| `PATCH` | `/Auth/password/update` | none | Update password |

**`POST /Auth/password/send`**
```json
{ "email": "admin@company.com" }
```

**`PATCH /Auth/password/update`**
```json
{
  "token": "reset-token",
  "email": "admin@company.com",
  "password": "new-secret",
  "password_confirmation": "new-secret"
}
```

---

### Dashboard

| Method | Route | Auth | Description |
|--------|-------|------|-------------|
| `GET` | `/Dashboard` | auth | Render dashboard with stats |

Returns Inertia page (not JSON). Props include `stats`:
- `total_employee`, `active_today`, `active_yesterday`, `late_today`, `late_yesterday`
- `on_time_today`, `no_presence_today`, `no_presence_yesterday`
- `weekly_presence` — array of `{ date, label, hadir, terlambat, alpha }`
- `monthly_stats` — `{ current, previous }` with `total_presence`, `avg_work_hours`, `total_salary`, `on_time_rate`
- `recent_salary` — last 10 salary receipts
- `recent_presence` — last 10 presence records

---

### Employee Account

Base: `/Employee/Account/` (middleware: `auth`)

| Method | Route | Scope | Description |
|--------|-------|-------|-------------|
| `GET` | `/` | — | Render employee list page |
| `GET` | `/create` | — | Render create account page (with groups) |
| `GET` | `/{id}` | — | Render account detail/edit page |
| `GET` | `/json/{user}` | scope-company | Get account JSON |
| `GET` | `/all/json` | — | Get paginated employee list (JSON) |
| `GET` | `/all/by-group/json` | — | Get employees by group (JSON) |
| `POST` | `/add/json` | — | Create account |
| `POST` | `/update/json` | — | Update account |
| `DELETE` | `/delete/json` | — | Delete account |

**`GET /all/json`**

Query: `?search=<keyword>&page=<n>`

**Response:**
```json
{
  "data": {
    "data": [
      {
        "id": 1,
        "email": "employee@email.com",
        "type": "employee",
        "is_suspended": false,
        "userDetail": { "fullname": "John", "phone": "081234", "address": "Jakarta", "picture_profile": null }
      }
    ],
    "total": 10, "per_page": 10, ...
  }
}
```

**`GET /all/by-group/json`**

Query: `?group_id=<int>`

Response — array of users in group:
```json
{
  "data": [
    { "id": 1, "email": "...", "userDetail": { ... }, "pivot": { "mst_company_group_id": 1, "mst_user_id": 1 } }
  ]
}
```

**`POST /add/json`**

```json
{
  "name": "John Doe",
  "email": "john@company.com",
  "phone": "08123456789",
  "address": "Jakarta",
  "password": "secret",
  "password_confirmation": "secret",
  "is_admin": false,
  "company_group": [1, 2],
  "npwp": "optional",
  "profile_picture": "(file, max 15KB)"
}
```
`is_admin: true` creates `company` type; `false/null` creates `employee` type.

**`POST /update/json`**

```json
{
  "id": 1,
  "name": "John Updated",
  "email": "john@company.com",
  "password": "optional-new-password",
  "phone": "08123456789",
  "address": "Jakarta",
  "is_suspended": false,
  "company_group": [1],
  "profile_picture": "(file)"
}
```

**`DELETE /delete/json`**
```json
{ "data_id": 1 }
```
Also deletes associated tokens and user detail.

---

### Employee Group

Base: `/Employee/Group/` (middleware: `auth`)

| Method | Route | Description |
|--------|-------|-------------|
| `GET` | `/` | Render group list page |
| `GET` | `/all/json` | Get paginated groups with employee count |
| `POST` | `/add/json` | Create group |
| `PUT` | `/update/json` | Update group |
| `DELETE` | `/delete/json` | Delete group |

**`GET /all/json`**

Query: `?search=<keyword>&page=<n>`

Response:
```json
{
  "data": {
    "data": [
      { "id": 1, "name": "Engineering", "mst_user_id": 1, "total_employee": 12, ... }
    ],
    ...
  }
}
```

**`POST /add/json`**
```json
{ "name": "Engineering" }
```

**`PUT /update/json`**
```json
{ "id": 1, "name": "Engineering Updated" }
```

**`DELETE /delete/json`**
```json
{ "data_id": 1 }
```

---

### Presence Location

Base: `/Presence/Location/` (middleware: `auth`)

| Method | Route | Description |
|--------|-------|-------------|
| `GET` | `/` | Render location list page |
| `GET` | `/all/json` | Get all locations (paginated) with verification code |
| `GET` | `/create` | Render create location page |
| `GET` | `/{id}` | Render edit location page |
| `POST` | `/create` | Create or update location |
| `DELETE` | `/delete/json` | Delete location |

**`GET /all/json`**

Query: `?search=<keyword>&page=<n>`

Response:
```json
{
  "data": {
    "data": [
      {
        "id": 1, "mst_user_id": 1, "name": "Main Office",
        "latitude": "-6.2088", "longitude": "106.8456", "tolerance": 100,
        "start_hour": "08:00:00", "max_hour": null, "min_hour": null,
        "single_verification": [{ "id": 1, "verification_hash": "99181234" }]
      }
    ], ...
  }
}
```

**`POST /create`**

Request (form data):
```json
{
  "id": null,
  "mst_user_id": 1,
  "name": "Main Office",
  "latitude": -6.2088,
  "longitude": 106.8456,
  "radius": 100,
  "start_hour": "08:00",
  "max_hour": "10:00",
  "min_hour": "06:00"
}
```
`id` omitted or `null` → create; `id` present → update.

**`DELETE /delete/json`**
```json
{ "id": 1 }
```

---

### Presence Verification

Base: `/Presence/Location/Verification/` (middleware: `auth`)

| Method | Route | Description |
|--------|-------|-------------|
| `POST` | `/New` | Generate new verification code for location |

**`POST /New`**
```json
{ "id": 1 }
```

Response:
```json
{
  "data": [{ "id": 2, "mst_presence_location_id": 1, "verification_hash": "99181234" }],
  "message": "new verification code, generated"
}
```

Code formula: `rand(10,99) + day + seconds` (e.g., `99181234`).

---

### Employee Presence (Web)

Base: `/Employee/{user}/` (middleware: `auth`, `scope-company` where applicable)

| Method | Route | Scope | Description |
|--------|-------|-------|-------------|
| `GET` | `/Presence-History` | scope-company | Render presence history page |
| `GET` | `/json` | scope-company | Get paginated presence records JSON |
| `POST` | `/create/json` | scope-company | Create presence record (admin override) |
| `DELETE` | `/delete/json` | scope-company, only-company | Delete a presence record |
| `GET` | `/export/excel` | scope-company | Export presence history to Excel |

**`GET /{user}/json`**

Query params:
| Param | Type | Description |
|-------|------|-------------|
| `status` | string? | `pending`, `approved`, `rejected` |
| `mst_presence_location_id` | int? | Filter by location |
| `time.*` | string? | Time frame filter |
| `orderBy` | string? | `asc` / `desc` (default: desc) |
| `currentPage` | int? | Page number |

**`POST /{user}/create/json`**

```json
{
  "status": "in",
  "location_id": 1,
  "code": "99181234",
  "latitude": -6.2088,
  "longitude": 106.8456,
  "note": "optional",
  "time_in": "2026-06-18T08:00:00",
  "time_out": "2026-06-18T17:00:00",
  "attachment": "(file)"
}
```
If admin (company type), `status_by_admin` set to `approved`.

**`DELETE /{user}/delete/json`**
```json
{ "id": 1 }
```

**`GET /{user}/export/excel`**

Query: `?status=<>&location_id=<>&date_start=<>&date_end=<>&orderBy=desc`

Returns `.xlsx` download.

---

### Payroll — Company Salary Components

Base: `/Company/{company}/` (middleware: `only-company`, `scope-company`)

| Method | Route | Description |
|--------|-------|-------------|
| `GET` | `/payroll` | Render payroll management page |
| `GET` | `/all/json` | Get all salary components (paginated) with assigned employees |
| `POST` | `/add/json` | Create salary component |
| `PUT` | `/update/json` | Update salary component |
| `DELETE` | `/delete/json` | Delete salary component |

**`GET /all/json`**

Query: `?search=<keyword>&page=<n>`

Response:
```json
{
  "data": {
    "data": [
      {
        "id": 1, "name": "Gaji Pokok", "salary": 5000000,
        "type": "fixed", "is_tax": false, "calculation_type": "add",
        "assigned_to": [
          { "id": 1, "pivot": { "available_to_request": false, "included_at_default": true } }
        ]
      }
    ], ...
  }
}
```

**`POST /add/json`**
```json
{
  "name": "Gaji Pokok",
  "salary": 5000000,
  "type": "fixed",
  "calculation_type": "add",
  "is_tax": false,
  "assigned_to": [
    { "id": 1, "pivot": { "available_to_request": false, "included_at_default": true } }
  ]
}
```
`type` values: `fixed`, `hourly`, `presence`, `tax`
`calculation_type` values: `add`, `subtract`

**`PUT /update/json`**

Same fields as add, plus `id`.

**`DELETE /delete/json`**
```json
{ "id": 1 }
```

---

### Payroll — Receipts

Base: `/receipt/` (middleware: `only-company`)

| Method | Route | Description |
|--------|-------|-------------|
| `GET` | `/` | Render receipt list page |
| `GET` | `/{employee}/payroll` | Render employee payroll receipts page |
| `GET` | `/all/json` | Get all receipts (paginated) |
| `POST` | `/add/bulk/json` | Generate receipts for multiple employees |
| `PUT` | `/update/json` | Update/override receipt |
| `PUT` | `/confirm/json` | Approve or reject receipt |
| `DELETE` | `/delete/json` | Delete receipt |
| `GET` | `/export/excel` | Export all receipts to Excel |
| `GET` | `/{payroll}/export/detail-excel` | Export single receipt detail to Excel |
| `GET` | `/{payroll}/export/detail-pdf` | Export single receipt detail to PDF |

**`GET /all/json`**

Query params:
| Param | Type | Description |
|-------|------|-------------|
| `search` | string? | Search by employee name/email |
| `employee_id` | int? | Filter by specific employee |
| `date_filter[start]` | date? | Start date |
| `date_filter[end]` | date? | End date |
| `page` | int? | Page number |

Response:
```json
{
  "data": {
    "data": [
      {
        "id": 1, "mst_user_id": 1, "work_hour": 168,
        "total_salary": 5000000, "total_tax": 500000, "salary_after_tax": 4500000,
        "total_presence_record": 21, "start_date": "2026-06-01", "end_date": "2026-06-30",
        "status": "pending",
        "user": { "userDetail": { "fullname": "John" }, "groups": [...] },
        "salaryReceiptItems": [...]
      }
    ], ...
  }
}
```

**`POST /add/bulk/json`**
```json
{
  "user_id": [1, 2, 3],
  "date_start": "2026-06-01",
  "date_end": "2026-06-30"
}
```
Generates receipts by:
1. Counting presence records in period → work hours & presence count
2. Taking `included_at_default = true` components + approved `EmployeeRequestedSalary` entries
3. Component calculation:
   - `fixed` → salary × quantity
   - `hourly` → salary × work hours
   - `presence` → salary × presence count
   - `tax` → percentage of total before tax
4. Tax deducted from total
5. Requested salaries marked `is_realized = true`

**`PUT /update/json`**
```json
{
  "id": 1,
  "company_salary_item": [
    { "id": 1, "quantity": 2 },
    { "id": 2 }
  ],
  "start_date": "2026-06-01",
  "end_date": "2026-06-30"
}
```
Recalculates receipt using specified components (overrides defaults).

**`PUT /confirm/json`**
```json
{
  "id": 1,
  "status": true
}
```
`status: true` → `approved`, `false` → `denied`.

**`DELETE /delete/json`**
```json
{ "id": 1 }
```

---

### Employee Payroll (Per-Employee)

Base: `/{company}/Employee/` (middleware: `scope-company`)

| Method | Route | Description |
|--------|-------|-------------|
| `GET` | `/payroll` | Render employee payroll page |
| `GET` | `/payroll/{payroll}/detail` | Render receipt detail page |
| `GET` | `/payroll/{payroll}/detail/json` | Get receipt detail JSON |
| `GET` | `/all/json` | Get all salary components for company |

**`GET /payroll/{payroll}/detail/json`**

Response — receipt detail with requested salary info:
```json
{
  "data": {
    "id": 1, "mst_user_id": 1,
    "total_salary": 5000000, "salary_after_tax": 4500000,
    "user": { "userDetail": { "fullname": "John" } },
    "companySalaryItem": [
      {
        "id": 1, "name": "Gaji Pokok", "quantity": 1, "total_value": 4000000,
        "is_requested": false, "request_info": null
      },
      {
        "id": 3, "name": "Lembur", "quantity": 10, "total_value": 500000,
        "is_requested": true,
        "request_info": {
          "id": 1, "quantity": 10, "status": "approved",
          "approvedBy": { "userDetail": { "fullname": "Manager" } }
        }
      }
    ],
    "company_profile": { "company_name": "PT Example", ... },
    "total_subtract": 0
  }
}
```

---

### Company Settings

Base: `/Company/Settings/` (middleware: `auth`, `only-company`)

| Method | Route | Description |
|--------|-------|-------------|
| `GET` | `/` | Render settings page (Inertia) |
| `POST` | `/update/json` | Update company profile |
| `POST` | `/change-password/json` | Change password |
| `POST` | `/change-email/json` | Change email |

**`POST /update/json`**
```json
{
  "company_name": "PT Example",
  "company_phone": "021-123456",
  "company_address": "Jakarta",
  "company_email": "info@example.com",
  "npwp": "01.234.567.8-901.000",
  "company_logo": "(file)"
}
```

**`POST /change-password/json`**
```json
{
  "current_password": "old-secret",
  "password": "new-secret",
  "password_confirmation": "new-secret"
}
```

**`POST /change-email/json`**
```json
{
  "email": "new@email.com",
  "current_password": "secret"
}
```

---

### Device Management

Base: `/Management/{user}/device-management/` (middleware: `only-company`, `scope-company`)

| Method | Route | Description |
|--------|-------|-------------|
| `GET` | `/` | Render device management page |
| `GET` | `/all/json` | Get employees with active tokens (paginated) |
| `DELETE` | `/delete-device/{id}/json` | Force logout device(s) |

**`GET /all/json`**

Query: `?search=<keyword>&page=<n>`

Response:
```json
{
  "data": {
    "data": [
      {
        "id": 1, "email": "employee@email.com", "type": "employee",
        "tokens": [
          { "id": 1, "name": "mobile-app", "device_name": "Pixel 7 Pro", "device_type": "android", "last_used_at": "..." }
        ]
      }
    ], ...
  }
}
```

**`DELETE /delete-device/{id}/json`**

URL: `{id}` = employee user ID

Request body:
```json
{
  "device_token": "<optional-token>"
}
```
No `device_token` → all tokens deleted. With `device_token` → single token deleted.
