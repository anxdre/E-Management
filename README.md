# E-Management

Employee attendance & payroll management system. Web dashboard for company admins, mobile API for employee check-in/out.

## Tech Stack

**Backend:** Laravel 11, PHP 8.2, MySQL/SQLite, Sanctum  
**Frontend:** Inertia.js + Vue 3 (Composition API, `<script setup>`), TypeScript, TailwindCSS, shadcn-vue  
**Routing:** YALR (PHP 8 attributes on controllers)  
**Maps:** Leaflet, TomTom, Google Maps  
**Export:** Maatwebsite Excel, mpdf PDF

## Architecture

### Roles & Access

| Role | Web Access | Mobile Access |
|------|-----------|---------------|
| superadmin | Full dashboard | ❌ |
| company | Full dashboard | ❌ |
| employee | ❌ (403) | ✅ via Sanctum API |

### Routing

All routes defined via YALR attributes in controllers. `routes/web.php` only serves landing page (`/`). Route config in `config/routes.php`.

- **Web group:** Auth, Dashboard, Employee/Group CRUD, Presence Location/Verification, Payroll, Receipt, Company Settings, Device Management
- **API group:** Mobile auth (base-token + sign-in), Presence check-in/out, Location list, Salary receipts

### Middleware

| Alias | Class | Purpose |
|-------|-------|---------|
| `only-company` | `EnsureUserIsCompany` | Only superadmin passes (`isSuper()`) |
| `only-employee` | `EnsureUserIsEmployee` | Only employee role |
| `scope-company` | `EnsureUserWithinCompanyScope` | Self or own employees only |

## Modules

### Auth Web
- Inertia SPA sign-in at `/Auth/sign-in`
- Session-based (web guard), email verification (`MustVerifyEmail`)
- Employee → 403 Forbidden

### Auth Mobile
- `POST Auth/api/base-token/` → encrypted device token (AES-256-CBC)
- `POST Auth/api/sign-in/` → validate token, create Sanctum `mobile-app` token

### Presence (Check-in/out)
- Admin creates `PresenceLocation` with lat/lng, radius, operating hours
- Verification code generated per location: `rand(10,99) + day + seconds`
- Geo-validated: distance > tolerance → `pending` status
- Late check-in/early check-out detected via operating hours
- Admin approves/rejects pending records via web

### Payroll
- Salary components: `fixed`, `hourly`, `presence`, `tax` with `add`/`subtract` calculation
- Components assigned to employees via pivot with `included_at_default` / `available_to_request` flags
- Employees can request additional components (mobile) → admin approval
- Salary receipt auto-calculated: work hours from presence records + default components + approved requests
- Tax = percentage of total before tax
- PDF/Excel export

### Device Management
- Admin views employee devices (Sanctum tokens)
- Force logout all / single device

## Database Schema

Tables prefixed `mst_` (master), `trx_` (transaction), `pivot_` (pivot).

Key models:
- `mst_users` — `type` (company/employee/superadmin), `mst_company_id` (self-ref), `mst_user_detail_id`
- `mst_user_details` — fullname, phone, address, picture_profile
- `mst_company_groups` + `pivot_group_has_users` — employee groups
- `mst_presence_locations` — name, lat/lng, tolerance, hours
- `trx_presence_verifications` — one-time codes per location
- `trx_presence_employees` — attendance records
- `mst_company_salary` + `pivot_employee_salary` — salary components
- `trx_salary_receipt` + `trx_salary_receipt_item` — salary slips

## Frontend Pages

```
Pages/
  Landing/LandingPage.vue         # Public landing
  Auth/                           # SignIn, ForgotPassword, VerifyEmail, etc.
  Admin/
    Dashboard.vue                 # Stats, charts, recent salary
    Company/CompanySettings.vue    # Profile, email, password
    DeviceManagement/             # Employee device tokens
    EmployeeAccount/              # CRUD employees + company admins
    EmployeeGroup/                # CRUD groups
    PresenceLocation/             # CRUD locations + verification codes
    PayrollManagement/            # Salary components + receipts
    ReceiptDetail/                # Salary slip detail
  Employee/
    EmployeePresence/             # Presence history (web view)
    EmployeeSalary/               # Payroll receipts (web view)
```

## Getting Started

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate
npm run dev
```

## Docker

```bash
docker compose up -d
```

## Cron Setup

Laravel scheduler requires **one** server cron entry to run:

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Laravel evaluates all scheduled tasks internally (`->daily()`, `->everyMinute()`, etc). The server does not need individual cron entries per command.

### Defined Tasks

| Command | Schedule | Description |
|---------|----------|-------------|
| `presence:auto-approve` | Every minute | Batch-approve pending presences (cron mode, runs at configured hour only) |
| `telescope:prune` | Daily | Prune old Telescope entries |

`presence:auto-approve` respects the company's `auto_approve_batch_hour` setting — runs every minute but only executes approval logic at the configured hour.

## Notes

- `only-company` middleware checks `isSuper()` (not `isCompany()`), superadmin-only
- Migration/model mismatches exist for `presence_locations` — DB uses older schema
- No service classes; business logic in controllers
