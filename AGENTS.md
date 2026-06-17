# E-Management — Context Document

## Tech Stack
- **Backend:** Laravel 11, PHP 8.2, MySQL, Sanctum
- **Frontend:** Inertia.js + Vue 3 (Composition API, `<script setup>`), TypeScript, TailwindCSS, shadcn-vue
- **Routing:** YALR (PHP 8 attributes di controller) — `routes/web.php` hanya berisi landing page
- **State:** Pinia, Forms: Vee-Validate + Zod, Maps: Leaflet/TomTom/Google Maps

---

## Struktur Folder

```
app/Models/
  UserManagement/
    User.php              # type: company | employee | superadmin
    UserDetail.php        # fullname, phone, address, picture_profile
    CompanyGroup.php      # Grup karyawan (Engineering, Marketing, dll)
    GroupHasUser.php      # Pivot many-to-many group ↔ user
  PresenceManagement/
    PresenceLocation.php  # Titik absensi (lat/lng, radius, jam operasional)
    PresenceVerification.php  # Kode verifikasi sekali pakai per lokasi
    PresenceEmployee.php  # Rekam presensi (time_in, time_out, geo, status)
  Payroll/
    CompanySalary.php     # Komponen gaji (fixed/hourly/presence/tax)
    EmployeeSalary.php    # Pivot: komponen ditugaskan ke karyawan
    EmployeeRequestedSalary.php  # Request komponen tambahan oleh karyawan
    SalaryReceipt.php     # Slip gaji (periode, total, status)
    SalaryReceiptItem.php # Item baris dalam slip gaji
  AppMenu.php, RolePermission.php, CompanyProfile.php  # (placeholder/permission)
  PersonalAccessToken.php  # Extended Sanctum token (device_name, type, token)

app/Http/Controllers/
  AuthController.php          # Sign-in/sign-out web
  DashboardController.php     # Statistik presensi
  DeviceManagementController.php  # Manajemen token perangkat mobile
  EmailController.php, PasswordController.php, ProfileController.php
  Auth_Deprecated/            # Breeze default — TIDAK DIPAKAI
  Employee/
    EmployeeAccountController.php  # CRUD akun karyawan
    EmployeeGroupController.php    # CRUD grup
  Payroll/
    CompanySalaryController.php          # CRUD komponen gaji
    CompanyPayrollReceiptController.php  # Generate slip gaji + hitung otomatis
    EmployeePayrollTypeController.php    # Slip karyawan (web)
  Presence/
    EmployeePresenceController.php   # Presensi via web + history
    PresenceLocationController.php   # CRUD lokasi absensi
    PresenceVerificationController.php  # Generate kode verifikasi
  MobileApi/
    AuthApiController.php      # Sign-in mobile + base-token
    LocationApiController.php  # Ambil lokasi absensi
    PrensenceApiController.php # Check-in/out + history (mobile)
    ReceiptApiController.php   # Slip gaji (mobile)

app/Http/Middleware/
  EnsureUserIsCompany.php      # only-company: cek isSuper()
  EnsureUserIsEmployee.php     # only-employee: cek isEmployee()
  EnsureUserWithinCompanyScope.php  # scope-company: akses hanya ke self atau own employee

resources/js/Pages/
  Auth/          # SignIn, SignUp, ForgotPassword, VerifyEmail
  Landing/       # LandingPage (publik)
  Admin/         # Dashboard, EmployeeAccount, EmployeeGroup, PresenceLocation,
                 # PayrollManagement, PayrollReceipt, ReceiptDetail, DeviceManagement
  Employee/      # EmployeePresence/*, EmployeeSalary/*

routes/
  web.php        # Hanya `/` → LandingPage
  auth.php       # DEPRECATED (tidak dipakai)
  console.php    # Schedule: telescope:prune daily

# Routing utama via YALR attribute di masing-masing controller
```

---

## Database Schema

**Convention:** `mst_` (master), `trx_` (transaksi), `pivot_` (pivot). Laravel system tables dibiarkan.

### `mst_users`
| Column | Type | Keterangan |
|--------|------|------------|
| id | PK | |
| email | unique | |
| password | hashed | |
| type | string | `company`, `employee`, `superadmin` |
| mst_company_id | FK→mst_users.id | Self-referencing: company=null, employee→company |
| mst_user_detail_id | FK→mst_user_details.id | |
| is_suspended | bool | default false |

### `mst_user_details`
id, fullname, phone (unique), address, picture_profile

### `mst_company_groups`
id, mst_user_id (FK→mst_users), name — Grup milik suatu company

### `pivot_group_has_users` (pivot)
mst_company_group_id (FK→mst_company_groups), mst_user_id (FK→mst_users) — many-to-many

### `mst_presence_locations`
id, mst_user_id (FK→mst_users), name, latitude, longitude, tolerance (radius meter), start_hour, end_hour, min_hour, max_hour

### `trx_presence_verifications`
id, mst_presence_location_id (FK), verification_hash — Kode: `{rand(10,99)}{day}{seconds}`

### `trx_presence_employees`
id, mst_user_id (FK), mst_presence_location_id (FK), trx_presence_verification_id, latitude, longitude, time_in, time_out, extended_time, note, attachment, status_by_admin (pending|approved|rejected)

### `mst_company_salary`
id, name, salary (decimal), is_tax (bool), type (fixed|hourly|presence|tax), calculation_type (add|subtract)

### `pivot_employee_salary` (pivot user ↔ mst_company_salary)
mst_company_salary_id, mst_user_id, available_to_request (bool), included_at_default (bool)

### `trx_employee_requested_salary`
id, pivot_employee_salary_id (FK), quantity, status (pending|approved|rejected), mst_approved_by (FK→mst_users), approved_date, is_realized (bool)

### `trx_salary_receipt`
id, mst_user_id (FK), work_hour, total_salary, total_tax, salary_after_tax, total_presence_record, start_date, end_date, status (pending|approved|denied)

### `trx_salary_receipt_item`
id, trx_salary_receipt_id (FK), mst_company_salary_id (FK), trx_employee_requested_salary_id (FK), quantity, total_value

### `personal_access_tokens`
Extended: device_name, device_type, device_token — Sanctum token untuk mobile apps

### Relasi Kunci
- **User** → UserDetail (1:1 via mst_user_detail_id)
- **User** → User (self: mst_company_id, company hasMany employees)
- **User** ↔ CompanyGroup (many-to-many via pivot_group_has_users)
- **User** → PresenceEmployee (one-to-many via mst_user_id)
- **PresenceLocation** → PresenceVerification (one-to-many)
- **User** ↔ CompanySalary (many-to-many via pivot_employee_salary)
- **User** → SalaryReceipt (one-to-many)
- **SalaryReceipt** → SalaryReceiptItem (one-to-many)
- **CompanySalary** → SalaryReceiptItem (one-to-many)
- **EmployeeRequestedSalary** → SalaryReceiptItem (one-to-many, nullable)

---

## Alur Aplikasi

### Role & Akses
| Role | Sign-in Web | Sign-in Mobile | Middleware |
|------|------------|----------------|------------|
| superadmin | ✅ Dashboard penuh | ❌ | `only-company` (cek isSuper) |
| company | ✅ Dashboard penuh | ❌ | `scope-company` |
| employee | ❌ 403 diblokir | ✅ Aplikasi mobile | `only-employee` |

### 1. Auth Web (Inertia SPA)
1. User buka `/Auth/sign-in` (GET)
2. POST `/Auth/sign-in` → validasi email/password (rate-limited 5x)
3. Jika employee → **403 Forbidden**
4. Jika company/superadmin → redirect ke `/Dashboard`
5. Session-based (web guard), email verification (MustVerifyEmail)

### 2. Auth Mobile (Sanctum API)
1. POST `Auth/api/base-token/` → dapat encrypted device token (AES-256-CBC)
   Format: `encrypt("{APP_NAME}-{YYYYMMDD}-{userId}")`
2. POST `Auth/api/sign-in/` → decrypt token, cocokkan, buat Sanctum token `mobile-app`
3. Token disimpan device, dikirim via `Authorization: Bearer` header

### 3. Presensi (Check-in / Check-out)
**Admin setup:**
1. Buat `PresenceLocation` (nama, lat/lng, radius toleransi, jam operasional)
2. Generate kode verifikasi: `rand(10,99) + day + seconds` → hash

**Check-in (mobile):**
1. Employee fetch lokasi → masukkan kode verifikasi
2. POST `Presence/api/create/` (status=in, user_id, code, lat/lng, location_id)
3. Validasi: kode cocok? jam ≥ start_hour? sudah ada check-in aktif?
4. Jarak > tolerance → status `pending` + catatan "Out Of Radius"
5. Jam > end_hour → catatan "Late check in"

**Check-out (mobile):**
1. POST `Presence/api/create/` (status=out) → lookup absensi tanpa time_out
2. Set time_out = now()
3. Jika jam > max_hour → "late check out", status pending
4. Jika jam < min_hour → "Earlier check out"

**Status by admin:** pending → approved/rejected (via web)

### 4. Payroll & Slip Gaji
**Setup komponen gaji (admin):**
- Buat `CompanySalary` dengan type:
  - `fixed` → salary × quantity
  - `hourly` → salary × total jam kerja
  - `presence` → salary × jumlah presensi
  - `tax` → persentase (dihitung dari total setelah komponen lain)

**Assign ke karyawan (pivot `employee_salary`):**
- `included_at_default`: otomatis masuk kalkulasi
- `available_to_request`: bisa direquest karyawan

**Request karyawan (mobile):**
- Karyawan request komponen tambahan → `EmployeeRequestedSalary` (pending)
- Admin approve → siap direalisasikan di slip gaji

**Generate slip gaji (`calculateEmployeeSalary`):**
1. Ambil presensi dalam periode → hitung total jam
2. Ambil komponen default (`included_at_default = true`)
3. Ambil request yang approved & belum direalisasi
4. Hitung tiap komponen berdasarkan type
5. Tax = (total_sebelum_pajak × rate) / 100
6. Simpan `SalaryReceipt` + `SalaryReceiptItem`
7. Tandai request sebagai `is_realized = true`

**Status slip:** pending → approved/denied (admin via web)

### 5. Device Management
- Admin lihat daftar employee + device aktif (Sanctum tokens)
- Admin bisa force logout semua device satu employee, atau per device
- Token di-delete dari `personal_access_tokens`

---

## Catatan Penting
1. **Migration vs Model mismatch:** `presence_locations` punya `user_id` & `end_hour` di model tapi tidak di migration terbaru. State aktif DB menggunakan migration lama (obsolete).
2. **"only-company" middleware** — awalnya role disebut `company`, lalu diubah jadi `superadmin`. Nama middleware tidak diupdate. Sekarang hanya `isSuper()` yang lolos, role `company` biasa tidak bisa lewat sini.
3. **Routing:** Semua route via YALR attribute di controller. `routes/web.php` & `routes/auth.php` hampir tidak dipakai.
4. **Karyawan tidak bisa akses web** — didesain: admin via web SPA, employee via mobile app.
5. **Tidak ada service class** — semua logika di controller (termasuk kalkulasi 150 baris di `CompanyPayrollReceiptController`).
6. **`is_admin` bukan kolom DB** — hanya flag di form create akun (`$request->is_admin`) untuk menentukan type: `true → 'company'`, `false/null → 'employee'`. Otorisasi cukup dari kolom `type` + `is_suspended`. Tabel `app_menus`/`role_permission` ada tapi tidak dipakai.
