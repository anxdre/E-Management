# Spesifikasi Basis Data E-Management

## 1. Pendahuluan

Dokumen ini menyajikan spesifikasi skema basis data yang digunakan dalam sistem E-Management. Basis data dibangun di atas mesin penyimpanan InnoDB dengan pendekatan *relational database management system* (RDBMS) dan menerapkan prinsip normalisasi hingga bentuk normal ketiga (3NF). Konvensi penamaan tabel mengikuti tiga kategori prefix yang merepresentasikan fungsi masing-masing entitas, yaitu `mst_` (master) untuk data referensial yang relatif statis, `trx_` (transaksi) untuk data operasional yang terus bertumbuh seiring waktu, dan `pivot_` untuk tabel perantara (*junction table*) yang memfasilitasi relasi *many-to-many*. Sebagian kecil tabel sistem Laravel dan Sanctum tidak mengikuti konvensi tersebut karena merupakan bawaan *framework*.

Secara garis besar, basis data terdiri dari enam belas tabel yang dikelompokkan ke dalam empat subdomain fungsional: manajemen pengguna dan grup, manajemen presensi, manajemen penggajian, serta profil perusahaan dan sistem. Setiap tabel dilengkapi dengan kolom `created_at` dan `updated_at` sebagai penanda waktu pembuatan dan perubahan terakhir (*timestamps*), dan beberapa tabel telah dilengkapi dengan kolom `deleted_at` untuk mendukung *soft deletes*.

---

## 2. Entity Relationship Diagram (Deskripsi Tekstual)

Berikut disajikan relasi antar entitas dalam bentuk deskripsi tekstual:

1. **mst_users** (1) ── (1) **mst_user_details**: Satu baris data pengguna merujuk tepat satu baris data detail melalui foreign key `mst_user_detail_id`. Relasi bersifat opsional (*nullable*) setelah migrasi profil perusahaan.
2. **mst_company_groups** (M) ── (M) **mst_users**: Relasi many-to-many yang dijembatani oleh tabel **pivot_group_has_users**.
3. **mst_users** (1) ── (M) **mst_presence_locations**: Satu pengguna (bertindak sebagai perusahaan) dapat mendefinisikan banyak titik lokasi presensi.
4. **mst_presence_locations** (1) ── (M) **trx_presence_verifications**: Satu lokasi presensi dapat memiliki banyak kode verifikasi yang dihasilkan secara periodik.
5. **mst_presence_locations** (1) ── (M) **trx_presence_employees**: Satu lokasi presensi dapat merekam banyak presensi karyawan.
6. **mst_users** (1) ── (M) **trx_presence_employees**: Satu karyawan dapat memiliki banyak catatan presensi.
7. **trx_presence_verifications** (1) ── (M) **trx_presence_employees**: Satu kode verifikasi dapat digunakan dalam satu atau lebih presensi.
8. **mst_company_salary** (M) ── (M) **mst_users**: Relasi many-to-many yang dijembatani oleh tabel **pivot_employee_salary**, merepresentasikan komponen gaji yang ditugaskan kepada karyawan.
9. **mst_company_salary** (1) ── (M) **trx_employee_requested_salary**: Satu komponen gaji dapat diminta oleh banyak karyawan.
10. **mst_users** (1) ── (M) **trx_employee_requested_salary**: Satu karyawan dapat mengajukan banyak permintaan komponen gaji.
11. **mst_users** (1) ── (M) **trx_salary_receipt**: Satu karyawan dapat memiliki banyak slip gaji.
12. **trx_salary_receipt** (1) ── (M) **trx_salary_receipt_item**: Satu slip gaji terdiri dari banyak item komponen gaji.
13. **mst_company_salary** (1) ── (M) **trx_salary_receipt_item**: Satu komponen gaji dapat muncul di banyak item slip.
14. **trx_employee_requested_salary** (1) ── (M) **trx_salary_receipt_item**: Satu permintaan yang telah direalisasi tercatat dalam satu item slip.
15. **mst_app_menus** (1) ── (M) **pivot_role_permission**: Satu menu aplikasi dapat memiliki banyak pengaturan izin per grup.
16. **mst_company_groups** (1) ── (M) **pivot_role_permission**: Satu grup dapat memiliki banyak pengaturan izin.

---

## 3. Spesifikasi Tabel

### 3.1 Manajemen Pengguna dan Grup

#### 3.1.1 `mst_users`

Tabel master pengguna yang menyimpan data akun untuk seluruh entitas pengguna sistem, mencakup tiga jenis (*type*): `company` (perusahaan), `employee` (karyawan), dan `superadmin` (administrator super). Setiap baris pada tabel ini merepresentasikan satu akun yang dapat diautentikasi melalui mekanisme *session-based authentication* (Laravel web guard) untuk pengguna bertipe `company` dan `superadmin`, atau melalui *token-based authentication* (Laravel Sanctum) untuk pengguna bertipe `employee` yang mengakses sistem melalui aplikasi *mobile*. Tabel ini merupakan turunan dari kelas `Illuminate\Foundation\Auth\User` dan mengimplementasikan antarmuka `MustVerifyEmail`.

| Kolom | Tipe Data | Constraint | Deskripsi |
|-------|-----------|------------|-----------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY`, `AUTO_INCREMENT` | Identitas unik numerik pengguna |
| `email` | `VARCHAR(255)` | `UNIQUE NOT NULL` | Alamat surel pengguna yang digunakan sebagai *username* autentikasi |
| `password` | `VARCHAR(255)` | `NOT NULL` | *Hash* kata sandi menggunakan algoritma bawaan Laravel (bcrypt) |
| `type` | `VARCHAR(255)` | `NOT NULL` | Jenis pengguna: `company`, `employee`, atau `superadmin` |
| `mst_user_detail_id` | `BIGINT UNSIGNED` | `FOREIGN KEY → mst_user_details(id)`, `nullable` | Referensi ke detail profil pengguna; bersifat opsional setelah migrasi profil perusahaan |
| `is_suspended` | `TINYINT(1)` | `DEFAULT false`, `NOT NULL` | Status penangguhan akun; jika `true`, akun tidak dapat digunakan |
| `email_verified_at` | `DATETIME` | `nullable` | Waktu verifikasi surel; `null` jika belum diverifikasi |
| `remember_token` | `VARCHAR(100)` | `nullable` | Token *"remember me"* untuk sesi autentikasi web |
| `created_at` | `DATETIME` | `nullable` | Waktu pembuatan data |
| `updated_at` | `DATETIME` | `nullable` | Waktu perubahan terakhir |
| `deleted_at` | `DATETIME` | `nullable` | Waktu penghapusan lunak (*soft delete*) |

**Relasi:**
- *One-to-one* dengan `mst_user_details` melalui `mst_user_detail_id`.
- *One-to-many* dengan `trx_presence_employees` melalui `mst_user_id`.
- *One-to-many* dengan `trx_employee_requested_salary` melalui `mst_user_id`.
- *One-to-many* dengan `trx_salary_receipt` melalui `mst_user_id`.
- *Many-to-many* dengan `mst_company_groups` melalui tabel pivot `pivot_group_has_users`.
- *Many-to-many* dengan `mst_company_salary` melalui tabel pivot `pivot_employee_salary`.

---

#### 3.1.2 `mst_user_details`

Tabel master yang menyimpan data detail profil pengguna. Setiap baris pada tabel ini berkorespondensi secara *one-to-one* dengan satu baris pada `mst_users`, namun pemisahan ini dilakukan untuk memisahkan data autentikasi dari data profil guna meningkatkan keamanan dan fleksibilitas skema.

| Kolom | Tipe Data | Constraint | Deskripsi |
|-------|-----------|------------|-----------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY`, `AUTO_INCREMENT` | Identitas unik numerik detail pengguna |
| `fullname` | `VARCHAR(255)` | `NOT NULL` | Nama lengkap pengguna |
| `phone` | `VARCHAR(255)` | `UNIQUE NOT NULL` | Nomor telepon pengguna yang bersifat unik |
| `picture_profile` | `VARCHAR(255)` | `nullable` | Path atau URL foto profil pengguna |
| `address` | `VARCHAR(255)` | `NOT NULL` | Alamat tempat tinggal pengguna |
| `created_at` | `DATETIME` | `nullable` | Waktu pembuatan data |
| `updated_at` | `DATETIME` | `nullable` | Waktu perubahan terakhir |
| `deleted_at` | `DATETIME` | `nullable` | Waktu penghapusan lunak |

**Relasi:**
- *One-to-one* dengan `mst_users` melalui `id` yang dirujuk oleh `mst_users.mst_user_detail_id`.

---

#### 3.1.3 `mst_company_groups`

Tabel master yang menyimpan data grup atau divisi dalam suatu perusahaan. Grup digunakan untuk mengelompokkan karyawan ke dalam unit-unit organisasi seperti Engineering, Marketing, atau Finance. Setiap grup dimiliki oleh satu pengguna bertipe `company`.

| Kolom | Tipe Data | Constraint | Deskripsi |
|-------|-----------|------------|-----------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY`, `AUTO_INCREMENT` | Identitas unik numerik grup |
| `name` | `VARCHAR(255)` | `NOT NULL` | Nama grup atau divisi |
| `created_at` | `DATETIME` | `nullable` | Waktu pembuatan data |
| `updated_at` | `DATETIME` | `nullable` | Waktu perubahan terakhir |

**Relasi:**
- *Many-to-many* dengan `mst_users` melalui tabel pivot `pivot_group_has_users`.

---

#### 3.1.4 `pivot_group_has_users`

Tabel pivot yang memfasilitasi relasi *many-to-many* antara `mst_users` dan `mst_company_groups`. Setiap baris merepresentasikan keanggotaan seorang pengguna dalam suatu grup. Tabel ini menggunakan *composite foreign key* dan tidak memiliki kolom *primary key* tersendiri pada rancangan awal; kolom `id` ditambahkan kemudian melalui migrasi tetapi tidak difungsikan sebagai *auto-increment primary key*.

| Kolom | Tipe Data | Constraint | Deskripsi |
|-------|-----------|------------|-----------|
| `mst_company_group_id` | `BIGINT UNSIGNED` | `FOREIGN KEY → mst_company_groups(id)`, `NOT NULL` | Referensi ke grup |
| `mst_user_id` | `BIGINT UNSIGNED` | `FOREIGN KEY → mst_users(id)`, `NOT NULL` | Referensi ke pengguna |
| `created_at` | `DATETIME` | `nullable` | Waktu pembuatan data |
| `updated_at` | `DATETIME` | `nullable` | Waktu perubahan terakhir |

**Relasi:**
- *Many-to-one* dengan `mst_company_groups` melalui `mst_company_group_id`.
- *Many-to-one* dengan `mst_users` melalui `mst_user_id`.

---

### 3.2 Manajemen Presensi

#### 3.2.1 `mst_presence_locations`

Tabel master yang menyimpan data titik lokasi presensi geografis. Setiap lokasi didefinisikan oleh perusahaan dan digunakan sebagai acuan validasi presensi berbasis lokasi. Validasi dilakukan dengan menghitung jarak antara koordinat *check-in* karyawan dengan koordinat lokasi menggunakan rumus *haversine*; jika jarak melebihi nilai `tolerance`, maka status presensi otomatis ditetapkan sebagai `pending`.

| Kolom | Tipe Data | Constraint | Deskripsi |
|-------|-----------|------------|-----------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY`, `AUTO_INCREMENT` | Identitas unik numerik lokasi |
| `mst_user_id` | `BIGINT UNSIGNED` | `FOREIGN KEY → mst_users(id)`, `nullable` | Referensi ke pengguna (perusahaan) yang mendefinisikan lokasi |
| `name` | `VARCHAR(255)` | `NOT NULL` | Nama lokasi presensi (misal: "Kantor Pusat") |
| `latitude` | `DOUBLE` | `NOT NULL` | Koordinat lintang lokasi dalam derajat desimal |
| `longitude` | `DOUBLE` | `NOT NULL` | Koordinat bujur lokasi dalam derajat desimal |
| `tolerance` | `DOUBLE` | `NOT NULL` | Radius toleransi dalam meter; presensi di luar radius ini ditandai `pending` |
| `start_hour` | `TIME` | `nullable` | Batas awal jam operasional presensi; presensi sebelum jam ini dicatat sebagai keterlambatan |
| `end_hour` | `TIME` | `nullable` | Batas akhir jam operasional untuk *check-in* |
| `min_hour` | `TIME` | `nullable` | Batas minimal jam untuk *check-out*; *check-out* sebelum jam ini dicatat sebagai "Earlier check out" |
| `max_hour` | `TIME` | `nullable` | Batas maksimal jam untuk *check-out*; *check-out* setelah jam ini dicatat sebagai keterlambatan |
| `created_at` | `DATETIME` | `nullable` | Waktu pembuatan data |
| `updated_at` | `DATETIME` | `nullable` | Waktu perubahan terakhir |

**Relasi:**
- *Many-to-one* dengan `mst_users` melalui `mst_user_id`.
- *One-to-many* dengan `trx_presence_verifications` melalui `id`.
- *One-to-many* dengan `trx_presence_employees` melalui `id`.

---

#### 3.2.2 `trx_presence_verifications`

Tabel transaksi yang menyimpan kode verifikasi sekali pakai (*one-time verification code*) untuk setiap lokasi presensi. Kode verifikasi dihasilkan secara berkala oleh admin dengan format: dua digit acak (10–99) ditambah hari dalam bulan (1–31) ditambah detik saat ini, kemudian di-*hash* sebelum disimpan. Kode ini merupakan lapisan keamanan tambahan untuk memastikan bahwa karyawan berada di lokasi pada saat melakukan presensi.

| Kolom | Tipe Data | Constraint | Deskripsi |
|-------|-----------|------------|-----------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY`, `AUTO_INCREMENT` | Identitas unik numerik verifikasi |
| `mst_presence_location_id` | `BIGINT UNSIGNED` | `FOREIGN KEY → mst_presence_locations(id)`, `NOT NULL` | Referensi ke lokasi presensi |
| `verification_hash` | `VARCHAR(255)` | `NOT NULL` | Nilai *hash* dari kode verifikasi yang dihasilkan |
| `created_at` | `DATETIME` | `nullable` | Waktu pembuatan data |
| `updated_at` | `DATETIME` | `nullable` | Waktu perubahan terakhir |

**Relasi:**
- *Many-to-one* dengan `mst_presence_locations` melalui `mst_presence_location_id`.
- *One-to-many* dengan `trx_presence_employees` melalui `id`.

---

#### 3.2.3 `trx_presence_employees`

Tabel transaksi utama yang merekam setiap aktivitas presensi karyawan. Setiap baris merepresentasikan satu sesi presensi yang dimulai dengan *check-in* (diisi kolom `time_in`) dan diakhiri dengan *check-out* (diisi kolom `time_out`). Status presensi (`status_by_admin`) secara *default* bernilai `pending` dan harus diverifikasi oleh admin menjadi `approved` atau `rejected`. Sistem secara otomatis memberikan catatan tertentu, seperti "Out Of Radius", "Late check in", atau "Earlier check out", berdasarkan hasil validasi terhadap data lokasi dan jam.

| Kolom | Tipe Data | Constraint | Deskripsi |
|-------|-----------|------------|-----------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY`, `AUTO_INCREMENT` | Identitas unik numerik presensi |
| `mst_user_id` | `BIGINT UNSIGNED` | `FOREIGN KEY → mst_users(id)`, `NOT NULL` | Referensi ke karyawan yang melakukan presensi |
| `mst_presence_location_id` | `BIGINT UNSIGNED` | `FOREIGN KEY → mst_presence_locations(id)`, `NOT NULL` | Referensi ke lokasi presensi yang digunakan |
| `trx_presence_verification_id` | `BIGINT UNSIGNED` | `FOREIGN KEY → trx_presence_verifications(id)`, `nullable` | Referensi ke kode verifikasi yang digunakan saat *check-in* |
| `latitude` | `DOUBLE` | `NOT NULL` | Koordinat lintang saat *check-in* |
| `longitude` | `DOUBLE` | `NOT NULL` | Koordinat bujur saat *check-in* |
| `time_in` | `DATETIME` | `NOT NULL` | Waktu *check-in* |
| `time_out` | `DATETIME` | `nullable` | Waktu *check-out*; bernilai `null` jika sesi presensi masih berlangsung |
| `extended_time` | `DATETIME` | `nullable` | Waktu perpanjangan (jika ada) |
| `note` | `TEXT` | `nullable` | Catatan tambahan, termasuk catatan otomatis sistem |
| `attachment` | `VARCHAR(255)` | `nullable` | Path file lampiran pendukung |
| `status_by_admin` | `ENUM(pending, approved, rejected)` | `DEFAULT pending`, `NOT NULL` | Status verifikasi oleh admin |
| `created_at` | `DATETIME` | `nullable` | Waktu pembuatan data |
| `updated_at` | `DATETIME` | `nullable` | Waktu perubahan terakhir |

**Relasi:**
- *Many-to-one* dengan `mst_users` melalui `mst_user_id`.
- *Many-to-one* dengan `mst_presence_locations` melalui `mst_presence_location_id`.
- *Many-to-one* dengan `trx_presence_verifications` melalui `trx_presence_verification_id`.

---

### 3.3 Manajemen Penggajian

#### 3.3.1 `mst_company_salary`

Tabel master yang menyimpan definisi komponen gaji yang tersedia dalam sistem. Setiap komponen memiliki jenis (*type*) yang menentukan metode kalkulasinya, yaitu: `fixed` (nilai tetap × kuantitas), `hourly` (nilai per jam × total jam kerja), `presence` (nilai per presensi × jumlah presensi), dan `tax` (persentase dari total kalkulasi sebelum pajak). Kolom `calculation_type` menentukan apakah komponen bersifat menambah (`add`) atau mengurangi (`subtract`) total gaji. Tabel ini mendukung *soft deletes*.

| Kolom | Tipe Data | Constraint | Deskripsi |
|-------|-----------|------------|-----------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY`, `AUTO_INCREMENT` | Identitas unik numerik komponen gaji |
| `name` | `VARCHAR(255)` | `NOT NULL` | Nama komponen gaji (misal: "Gaji Pokok", "Tunjangan Transport") |
| `salary` | `DECIMAL(15)` | `NOT NULL` | Nilai nominal komponen (dalam satuan mata uang) |
| `is_tax` | `TINYINT(1)` | `DEFAULT false`, `NOT NULL` | Penanda apakah komponen ini termasuk objek pajak |
| `type` | `ENUM(fixed, hourly, presence, tax)` | `DEFAULT fixed`, `NOT NULL` | Jenis metode kalkulasi komponen |
| `calculation_type` | `ENUM(add, subtract)` | `DEFAULT add`, `NOT NULL` | Arah kalkulasi: `add` untuk penambah, `subtract` untuk pengurang |
| `created_at` | `DATETIME` | `nullable` | Waktu pembuatan data |
| `updated_at` | `DATETIME` | `nullable` | Waktu perubahan terakhir |
| `deleted_at` | `DATETIME` | `nullable` | Waktu penghapusan lunak |

**Relasi:**
- *Many-to-many* dengan `mst_users` melalui tabel pivot `pivot_employee_salary`.
- *One-to-many* dengan `trx_salary_receipt_item` melalui `id`.
- *One-to-many* dengan `trx_employee_requested_salary` melalui `id`.

---

#### 3.3.2 `pivot_employee_salary`

Tabel pivot yang menghubungkan komponen gaji (`mst_company_salary`) dengan karyawan (`mst_users`). Setiap baris menetapkan suatu komponen gaji kepada seorang karyawan disertai dua flag konfigurasi: `included_at_default` menentukan apakah komponen tersebut otomatis dimasukkan ke dalam kalkulasi slip gaji, dan `available_to_request` menentukan apakah karyawan dapat mengajukan permintaan untuk menambahkan komponen ini ke dalam slip gaji periode berjalan.

| Kolom | Tipe Data | Constraint | Deskripsi |
|-------|-----------|------------|-----------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY`, `AUTO_INCREMENT` | Identitas unik numerik |
| `mst_company_salary_id` | `BIGINT UNSIGNED` | `FOREIGN KEY → mst_company_salary(id)`, `NOT NULL` | Referensi ke komponen gaji |
| `mst_user_id` | `BIGINT UNSIGNED` | `FOREIGN KEY → mst_users(id)`, `NOT NULL` | Referensi ke karyawan |
| `available_to_request` | `TINYINT(1)` | `DEFAULT false`, `NOT NULL` | Status apakah komponen dapat diminta oleh karyawan |
| `included_at_default` | `TINYINT(1)` | `DEFAULT false`, `NOT NULL` | Status apakah komponen otomatis termasuk dalam kalkulasi default |
| `created_at` | `DATETIME` | `nullable` | Waktu pembuatan data |
| `updated_at` | `DATETIME` | `nullable` | Waktu perubahan terakhir |

**Constraint Unik:** Kombinasi `(mst_company_salary_id, mst_user_id)` bersifat unik.

**Relasi:**
- *Many-to-one* dengan `mst_company_salary` melalui `mst_company_salary_id`.
- *Many-to-one* dengan `mst_users` melalui `mst_user_id`.

---

#### 3.3.3 `trx_employee_requested_salary`

Tabel transaksi yang mencatat permintaan komponen gaji tambahan yang diajukan oleh karyawan. Karyawan dapat meminta komponen gaji yang telah ditandai `available_to_request` pada tabel `pivot_employee_salary` untuk direalisasikan dalam slip gaji periode berjalan. Permintaan harus melalui proses persetujuan admin: status `pending` → `approved` atau `rejected`. Setelah disetujui admin dan direalisasikan dalam slip gaji, kolom `is_realized` ditetapkan menjadi `true` saat admin mengonfirmasi receipt (bukan saat generate).

| Kolom | Tipe Data | Constraint | Deskripsi |
|-------|-----------|------------|-----------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY`, `AUTO_INCREMENT` | Identitas unik numerik permintaan |
| `mst_user_id` | `BIGINT UNSIGNED` | `FOREIGN KEY → mst_users(id)`, `nullable` | Referensi ke karyawan pengaju |
| `mst_company_salary_id` | `BIGINT UNSIGNED` | `FOREIGN KEY → mst_company_salary(id)`, `nullable` | Referensi ke komponen gaji yang diminta |
| `quantity` | `INT` | `DEFAULT 1` | Kuantitas komponen yang diminta (bisa diubah admin saat approve) |
| `quantity_snapshot` | `INT` | `nullable` | Frozen kuantitas original saat request diajukan |
| `salary_snapshot` | `DECIMAL(15,2)` | `nullable` | Frozen nilai rate master saat request diajukan |
| `status` | `ENUM(pending, approved, rejected)` | `DEFAULT pending`, `NOT NULL` | Status persetujuan permintaan |
| `approved_date` | `DATETIME` | `nullable` | Waktu persetujuan oleh admin |
| `mst_approved_by` | `BIGINT UNSIGNED` | `FOREIGN KEY → mst_users(id)`, `nullable` | Referensi ke admin yang menyetujui |
| `is_realized` | `TINYINT(1)` | `DEFAULT false`, `NOT NULL` | Status realisasi; `true` jika telah masuk slip gaji yang di-approve |
| `created_at` | `DATETIME` | `nullable` | Waktu pembuatan data |
| `updated_at` | `DATETIME` | `nullable` | Waktu perubahan terakhir |
| `deleted_at` | `DATETIME` | `nullable` | Waktu penghapusan lunak |

**Relasi:**
- *Many-to-one* dengan `mst_users` melalui `mst_user_id`.
- *Many-to-one* dengan `mst_company_salary` melalui `mst_company_salary_id`.
- *Many-to-one* dengan `mst_users` (approver) melalui `mst_approved_by`.
- *One-to-many* dengan `trx_salary_receipt_item` melalui `id`.

---

#### 3.3.4 `trx_salary_receipt`

Tabel transaksi utama yang menyimpan slip gaji karyawan untuk suatu periode tertentu. Setiap slip gaji dihasilkan melalui proses kalkulasi otomatis yang mencakup: (1) pengambilan data presensi dalam periode untuk menghitung total jam kerja, (2) pengambilan komponen gaji default (`included_at_default = true`), (3) pengambilan permintaan komponen yang telah disetujui dan belum direalisasi, (4) kalkulasi nilai setiap komponen berdasarkan tipenya, dan (5) kalkulasi pajak berdasarkan total sebelum pajak dikalikan persentase komponen bertipe `tax`. Hasil akhir disimpan dalam kolom `total_salary` (sebelum pajak) dan `salary_after_tax` (setelah pajak).

| Kolom | Tipe Data | Constraint | Deskripsi |
|-------|-----------|------------|-----------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY`, `AUTO_INCREMENT` | Identitas unik numerik slip gaji |
| `mst_user_id` | `BIGINT UNSIGNED` | `FOREIGN KEY → mst_users(id)`, `NOT NULL` | Referensi ke karyawan penerima |
| `work_hour` | `INT` | `NOT NULL` | Total jam kerja dalam periode (dihitung dari presensi) |
| `total_salary` | `DECIMAL(15)` | `DEFAULT 0`, `NOT NULL` | Total gaji sebelum pajak |
| `total_tax` | `DECIMAL(15)` | `DEFAULT 0`, `nullable` | Total potongan pajak |
| `salary_after_tax` | `DECIMAL(15)` | `DEFAULT 0`, `NOT NULL` | Total gaji setelah pajak |
| `total_presence_record` | `INT` | `nullable` | Jumlah total presensi dalam periode |
| `start_date` | `DATETIME` | `NOT NULL` | Tanggal awal periode penggajian |
| `end_date` | `DATETIME` | `NOT NULL` | Tanggal akhir periode penggajian |
| `status` | `ENUM(pending, approved, denied)` | `DEFAULT pending`, `NOT NULL` | Status verifikasi slip gaji oleh admin |
| `created_at` | `DATETIME` | `nullable` | Waktu pembuatan data |
| `updated_at` | `DATETIME` | `nullable` | Waktu perubahan terakhir |
| `deleted_at` | `DATETIME` | `nullable` | Waktu penghapusan lunak |

**Indeks:**
- Indeks pada kolom `status`.
- Indeks komposit pada `(start_date, end_date)`.

**Relasi:**
- *Many-to-one* dengan `mst_users` melalui `mst_user_id`.
- *One-to-many* dengan `trx_salary_receipt_item` melalui `id`.

---

#### 3.3.5 `trx_salary_receipt_item`

Tabel transaksi yang menyimpan rincian item komponen gaji dalam setiap slip gaji. Setiap baris merepresentasikan satu komponen gaji yang telah dihitung nilainya untuk periode tertentu, termasuk komponen default, komponen hasil permintaan yang direalisasi, dan komponen pajak. Kolom `quantity` menyimpan kuantitas (misal: jumlah jam, jumlah presensi, atau 1 untuk komponen fixed), dan `total_value` menyimpan hasil kalkulasi (nilai komponen × kuantitas). Nama dan rate komponen di-snapshot dari master saat generate agar nilai receipt tidak berubah walau master diedit.

| Kolom | Tipe Data | Constraint | Deskripsi |
|-------|-----------|------------|-----------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY`, `AUTO_INCREMENT` | Identitas unik numerik item |
| `trx_salary_receipt_id` | `BIGINT UNSIGNED` | `FOREIGN KEY → trx_salary_receipt(id)`, `NOT NULL` | Referensi ke slip gaji induk |
| `mst_company_salary_id` | `BIGINT UNSIGNED` | `FOREIGN KEY → mst_company_salary(id)`, `NOT NULL` | Referensi ke komponen gaji |
| `trx_employee_requested_salary_id` | `BIGINT UNSIGNED` | `FOREIGN KEY → trx_employee_requested_salary(id)`, `nullable` | Referensi ke permintaan komponen (jika item berasal dari request) |
| `quantity` | `INT` | `NOT NULL` | Kuantitas yang digunakan dalam kalkulasi |
| `total_value` | `DECIMAL(15)` | `NOT NULL` | Nilai total hasil kalkulasi (nilai komponen × kuantitas) |
| `salary_name_snapshot` | `VARCHAR(255)` | `nullable` | Nama komponen yang dibekukan saat generate receipt |
| `salary_rate_snapshot` | `DECIMAL(15,2)` | `nullable` | Rate komponen yang dibekukan saat generate receipt |
| `created_at` | `DATETIME` | `nullable` | Waktu pembuatan data |
| `updated_at` | `DATETIME` | `nullable` | Waktu perubahan terakhir |

**Relasi:**
- *Many-to-one* dengan `trx_salary_receipt` melalui `trx_salary_receipt_id`.
- *Many-to-one* dengan `mst_company_salary` melalui `mst_company_salary_id`.
- *Many-to-one* dengan `trx_employee_requested_salary` melalui `trx_employee_requested_salary_id`.

---

### 3.4 Profil Perusahaan dan Sistem Pendukung

#### 3.4.1 `mst_company_profile`

Tabel master yang menyimpan data profil perusahaan. Setelah migrasi data, tabel ini dipisahkan dari `mst_user_details` sehingga data profil perusahaan dikelola secara independen dari data profil karyawan. Tabel ini menyimpan informasi umum perusahaan seperti nama, kontak, alamat, logo, dan konfigurasi *auto-approve* presensi.

| Kolom | Tipe Data | Constraint | Deskripsi |
|-------|-----------|------------|-----------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY`, `AUTO_INCREMENT` | Identitas unik numerik profil perusahaan |
| `company_name` | `VARCHAR(255)` | `NOT NULL` | Nama resmi perusahaan |
| `company_phone` | `VARCHAR(255)` | `nullable` | Nomor telepon perusahaan |
| `company_address` | `VARCHAR(255)` | `nullable` | Alamat kantor perusahaan |
| `company_logo` | `VARCHAR(255)` | `nullable` | Path atau URL logo perusahaan |
| `company_email` | `VARCHAR(255)` | `nullable` | Alamat surel perusahaan |
| `auto_approve` | `TINYINT(1)` | `DEFAULT false`, `NOT NULL` | Status apakah presensi karyawan otomatis disetujui |
| `auto_approve_mode` | `ENUM(realtime, cron)` | `DEFAULT realtime`, `NOT NULL` | Mode *auto-approve*: `realtime` (langsung) atau `cron` (terjadwal) |
| `auto_approve_batch_hour` | `TIME` | `DEFAULT 17:00:00`, `nullable` | Waktu eksekusi batch untuk mode `cron` |
| `auto_approve_min_duration` | `INT` | `DEFAULT 60`, `NOT NULL` | Durasi minimal presensi (menit) agar eligible untuk *auto-approve* |
| `auto_approve_duplicate_coords` | `TINYINT(1)` | `DEFAULT false`, `NOT NULL` | Status apakah koordinat duplikat diizinkan dalam *auto-approve* |
| `created_at` | `DATETIME` | `nullable` | Waktu pembuatan data |
| `updated_at` | `DATETIME` | `nullable` | Waktu perubahan terakhir |

---

#### 3.4.2 `mst_app_menus`

Tabel master yang menyimpan data menu aplikasi untuk sistem manajemen izin akses berbasis grup. Tabel ini merupakan bagian dari sistem *role-based access control* (RBAC) yang direncanakan namun saat ini belum diimplementasikan secara aktif. Setiap baris merepresentasikan satu menu atau modul dalam aplikasi.

| Kolom | Tipe Data | Constraint | Deskripsi |
|-------|-----------|------------|-----------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY`, `AUTO_INCREMENT` | Identitas unik numerik menu |
| `name` | `VARCHAR(255)` | `NOT NULL` | Nama menu atau modul aplikasi |
| `created_at` | `DATETIME` | `nullable` | Waktu pembuatan data |
| `updated_at` | `DATETIME` | `nullable` | Waktu perubahan terakhir |

**Relasi:**
- *One-to-many* dengan `pivot_role_permission` melalui `id`.

---

#### 3.4.3 `pivot_role_permission`

Tabel pivot yang menghubungkan menu aplikasi (`mst_app_menus`) dengan grup perusahaan (`mst_company_groups`) untuk menetapkan hak akses berupa izin *create*, *read*, *update*, dan *delete* (CRUD). Setiap baris mendefinisikan serangkaian izin yang dimiliki oleh suatu grup terhadap suatu menu. Sama seperti `mst_app_menus`, tabel ini belum diimplementasikan secara aktif dalam logika aplikasi.

| Kolom | Tipe Data | Constraint | Deskripsi |
|-------|-----------|------------|-----------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY`, `AUTO_INCREMENT` | Identitas unik numerik izin |
| `mst_app_menus_id` | `BIGINT UNSIGNED` | `FOREIGN KEY → mst_app_menus(id)`, `NOT NULL` | Referensi ke menu aplikasi |
| `mst_company_groups_id` | `BIGINT UNSIGNED` | `FOREIGN KEY → mst_company_groups(id)`, `NOT NULL` | Referensi ke grup perusahaan |
| `is_create` | `TINYINT(1)` | `DEFAULT false`, `NOT NULL` | Izin membuat data baru |
| `is_view` | `TINYINT(1)` | `DEFAULT false`, `NOT NULL` | Izin melihat data |
| `is_update` | `TINYINT(1)` | `DEFAULT false`, `NOT NULL` | Izin mengubah data |
| `is_delete` | `TINYINT(1)` | `DEFAULT false`, `NOT NULL` | Izin menghapus data |
| `created_at` | `DATETIME` | `nullable` | Waktu pembuatan data |
| `updated_at` | `DATETIME` | `nullable` | Waktu perubahan terakhir |

**Relasi:**
- *Many-to-one* dengan `mst_app_menus` melalui `mst_app_menus_id`.
- *Many-to-one* dengan `mst_company_groups` melalui `mst_company_groups_id`.

---

### 3.5 Autentikasi Perangkat

#### 3.5.1 `personal_access_tokens`

Tabel sistem Laravel Sanctum yang menyimpan *personal access token* untuk autentikasi *mobile*. Tabel ini diperluas (*extended*) dari skema bawaan Sanctum dengan menambahkan kolom `device_name`, `device_type`, dan `device_token` untuk mendukung manajemen perangkat. Setiap baris merepresentasikan satu token yang diterbitkan untuk satu sesi autentikasi pada satu perangkat. Proses autentikasi *mobile* melibatkan dua tahap: (1) permintaan *base token* terenkripsi melalui endpoint `Auth/api/base-token/`, dan (2) penukaran *base token* dengan *personal access token* Sanctum melalui endpoint `Auth/api/sign-in/`.

| Kolom | Tipe Data | Constraint | Deskripsi |
|-------|-----------|------------|-----------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY`, `AUTO_INCREMENT` | Identitas unik numerik token |
| `tokenable_type` | `VARCHAR(255)` | `NOT NULL` | Nama kelas model pemilik token (polimorfik) |
| `tokenable_id` | `BIGINT UNSIGNED` | `NOT NULL` | Identitas model pemilik token (polimorfik) |
| `name` | `TEXT` | `NOT NULL` | Nama token (misal: `mobile-app`) |
| `token` | `VARCHAR(64)` | `UNIQUE NOT NULL` | Nilai *hash* token unik 64 karakter |
| `abilities` | `TEXT` | `nullable` | Kemampuan atau izin yang dimiliki token |
| `device_name` | `VARCHAR(255)` | `nullable` | Nama perangkat pengguna |
| `device_type` | `VARCHAR(255)` | `nullable` | Jenis perangkat (misal: `android`, `ios`) |
| `device_token` | `VARCHAR(255)` | `UNIQUE`, `nullable` | Token perangkat untuk *push notification* |
| `last_used_at` | `DATETIME` | `nullable` | Waktu terakhir token digunakan |
| `expires_at` | `DATETIME` | `nullable` | Waktu kedaluwarsa token |
| `created_at` | `DATETIME` | `nullable` | Waktu pembuatan data |
| `updated_at` | `DATETIME` | `nullable` | Waktu perubahan terakhir |

**Indeks:**
- Indeks komposit pada `(tokenable_type, tokenable_id)`.

---

## 4. Matriks Relasi Antar Tabel

Berikut disajikan ringkasan seluruh relasi *foreign key* dalam basis data:

| Tabel Sumber | Kolom Sumber | Tabel Tujuan | Kolom Tujuan | Aksi Hapus |
|--------------|-------------|--------------|-------------|------------|
| `mst_users` | `mst_user_detail_id` | `mst_user_details` | `id` | `CASCADE` |
| `mst_presence_locations` | `mst_user_id` | `mst_users` | `id` | `CASCADE` |
| `mst_company_profile` | `mst_user_id` | `mst_users` | `id` | `CASCADE` (telah dihapus) |
| `pivot_group_has_users` | `mst_company_group_id` | `mst_company_groups` | `id` | `CASCADE` |
| `pivot_group_has_users` | `mst_user_id` | `mst_users` | `id` | `CASCADE` |
| `trx_presence_employees` | `mst_user_id` | `mst_users` | `id` | `NO ACTION` |
| `trx_presence_employees` | `mst_presence_location_id` | `mst_presence_locations` | `id` | `NO ACTION` |
| `trx_presence_employees` | `trx_presence_verification_id` | `trx_presence_verifications` | `id` | (implisit) |
| `trx_presence_verifications` | `mst_presence_location_id` | `mst_presence_locations` | `id` | `NO ACTION` |
| `pivot_employee_salary` | `mst_company_salary_id` | `mst_company_salary` | `id` | `CASCADE` |
| `pivot_employee_salary` | `mst_user_id` | `mst_users` | `id` | `CASCADE` |
| `trx_employee_requested_salary` | `mst_user_id` | `mst_users` | `id` | `CASCADE` |
| `trx_employee_requested_salary` | `mst_company_salary_id` | `mst_company_salary` | `id` | `CASCADE` |
| `trx_employee_requested_salary` | `mst_approved_by` | `mst_users` | `id` | `SET NULL` |
| `trx_salary_receipt` | `mst_user_id` | `mst_users` | `id` | `CASCADE` |
| `trx_salary_receipt_item` | `trx_salary_receipt_id` | `trx_salary_receipt` | `id` | `CASCADE` |
| `trx_salary_receipt_item` | `mst_company_salary_id` | `mst_company_salary` | `id` | `CASCADE` |
| `trx_salary_receipt_item` | `trx_employee_requested_salary_id` | `trx_employee_requested_salary` | `id` | `SET NULL` |
| `pivot_role_permission` | `mst_app_menus_id` | `mst_app_menus` | `id` | `CASCADE` |
| `pivot_role_permission` | `mst_company_groups_id` | `mst_company_groups` | `id` | `CASCADE` |

---

## 5. Konvensi dan Catatan Arsitektural

1. **Konvensi Prefix:**
   - `mst_` (*master*): Data referensial yang relatif statis, seperti pengguna, detail pengguna, grup, lokasi presensi, komponen gaji, menu aplikasi, dan profil perusahaan.
   - `trx_` (*transaksi*): Data operasional yang terus bertumbuh, seperti presensi karyawan, verifikasi, permintaan gaji, slip gaji, dan item slip gaji.
   - `pivot_` (*pivot*): Tabel perantara untuk relasi *many-to-many*, seperti pivot grup-pengguna, pivot karyawan-komponen gaji, dan pivot peran-izin.

2. **Engine InnoDB:** Seluruh tabel menggunakan mesin penyimpanan InnoDB yang mendukung *transactions*, *foreign key constraints*, dan *row-level locking*.

3. **Soft Deletes:** Tabel `mst_users`, `mst_user_details`, `mst_company_salary`, `trx_employee_requested_salary`, dan `trx_salary_receipt` telah dilengkapi kolom `deleted_at` untuk mendukung penghapusan lunak, sehingga data tidak benar-benar dihapus dari basis data melainkan hanya ditandai sebagai terhapus. Tabel `trx_salary_receipt_item` menggunakan hard delete karena snapshot sudah cukup sebagai audit trail, dan soft delete hanya menyebabkan bloat setiap kali receipt diedit.

4. **Enumerasi Status:** Sistem menggunakan tipe `ENUM` untuk kolom-kolom yang memiliki nilai terbatas dan tetap, seperti `type` pada `mst_users` (`company`, `employee`, `superadmin`), `status_by_admin` pada `trx_presence_employees` (`pending`, `approved`, `rejected`), dan `status` pada tabel transaksi penggajian (`pending`, `approved`, `denied`).

5. **Dua Tabel Tidak Aktif:** Tabel `mst_app_menus` dan `pivot_role_permission` merupakan bagian dari sistem RBAC yang direncanakan namun belum diintegrasikan ke dalam logika aplikasi. Otorisasi akses saat ini masih dilakukan secara sederhana berdasarkan kolom `type` pada `mst_users` dan diverifikasi melalui *middleware* kustom (`EnsureUserIsCompany`, `EnsureUserIsEmployee`, `EnsureUserWithinCompanyScope`).