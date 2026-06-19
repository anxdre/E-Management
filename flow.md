# Spesifikasi Alur Halaman Aplikasi E-Management

## 1. Pendahuluan

Dokumen ini menyajikan spesifikasi fungsional setiap halaman yang terdapat dalam aplikasi E-Management. Aplikasi dibangun menggunakan arsitektur *Single Page Application* (SPA) dengan *framework* Inertia.js sebagai jembatan antara sisi server (Laravel 11) dan sisi klien (Vue 3 Composition API dengan TypeScript). Seluruh halaman dirender di sisi klien melalui Inertia dan dikirim sebagai respons JSON yang dihidrasi menjadi tampilan Vue.

Aplikasi melayani dua koridor akses yang terpisah secara arsitektural:
1. **Koridor Web (Admin):** Dapat diakses oleh pengguna bertipe `company` dan `superadmin` melalui peramban web. Seluruh halaman admin dilindungi oleh *middleware* autentikasi session (`auth`) dan *middleware* otorisasi berbasis tipe (`EnsureUserIsCompany`, `EnsureUserIsEmployee`, `EnsureUserWithinCompanyScope`).
2. **Koridor Mobile (Karyawan):** Dapat diakses oleh pengguna bertipe `employee` melalui aplikasi *mobile native* yang berkomunikasi dengan *endpoint* REST API berbasis token Sanctum. Tidak ada halaman web untuk koridor ini.

Routing dilakukan melalui atribut PHP 8 (YALR) yang ditempelkan langsung pada metode *controller*, sehingga `routes/web.php` hanya mendefinisikan rute publik untuk halaman *landing*. Setiap halaman yang dirender melalui Inertia menggunakan `LayoutWrapper.vue` sebagai tata letak induk yang menyediakan navigasi samping (*sidebar*), bilah atas (*navbar*), dan kerangka umum aplikasi.

---

## 2. Halaman Publik

### 2.1 Halaman Beranda (*Landing Page*)

- **URL:** `/` (GET)
- **Berkas Vue:** `Landing/LandingPage.vue`
- **Controller:** *Route closure* pada `routes/web.php`
- **Middleware:** Tidak ada (publik)

Merupakan halaman muka aplikasi yang dapat diakses oleh siapa saja tanpa autentikasi. Halaman ini menampilkan profil sistem dengan merek dagang "Teamway" melalui beberapa seksi: bagian hero dengan ajakan mendaftar, kartu fitur unggulan (*Employee Management*, *Presence Tracking*, *Smart Payroll*, *Digital Receipt*), alur kerja "How It Works", statistik pengguna dalam bentuk animasi angka, akordeon pertanyaan umum (FAQ), dan bagian footer. Halaman ini berfungsi sebagai pintu masuk (*entry point*) bagi pengunjung yang belum terautentikasi dan menyediakan tautan menuju halaman *sign-in*.

---

## 3. Halaman Autentikasi

### 3.1 Halaman Masuk (*Sign In*)

- **URL:** `/Auth/sign-in` (GET, POST)
- **Berkas Vue:** `Auth/Authentication.vue` (membungkus `FormSignIn.vue`)
- **Controller:** `AuthController@login` (GET), `AuthController@signIn` (POST)
- **Middleware:** `guest` (hanya dapat diakses sebelum login)

Halaman ini menyajikan formulir masuk dengan dua bidang masukan: alamat surel dan kata sandi. Mekanisme autentikasi menggunakan *session-based authentication* Laravel dengan dukungan *rate limiting* (maksimum lima percobaan per menit). Pada metode POST, validasi dilakukan melalui *form request* `LoginRequest`. Jika pengguna bertipe `employee`, sistem mengembalikan status HTTP 403 *Forbidden* karena karyawan tidak diizinkan mengakses antarmuka web. Pengguna bertipe `company` atau `superadmin` yang berhasil diautentikasi akan diarahkan ke halaman *dashboard*. Halaman ini juga menyediakan tautan untuk pendaftaran akun baru (*sign-up*) dan pemulihan kata sandi.

### 3.2 Halaman Pendaftaran (*Sign Up*)

- **URL:** Termuat sebagai komponen dialog di dalam `Authentication.vue`
- **Berkas Vue:** `Auth/FormSignUp.vue`
- **Controller:** Tidak memiliki rute sendiri; dikirim melalui Inertia POST

Halaman ini menampilkan formulir pendaftaran untuk pengguna baru dengan bidang: nama lengkap, alamat surel, nomor telepon (dengan validasi format nomor Indonesia melalui *regex*), alamat, dan kata sandi. Validasi dilakukan di sisi klien menggunakan *library* Vee-Validate dengan skema Zod. Setelah pendaftaran berhasil, pengguna akan menerima surel verifikasi sebelum dapat mengakses halaman *dashboard*.

### 3.3 Halaman Lupa Kata Sandi

- **URL:** `/Auth/password/forgot` (GET), `/Auth/password/send` (POST)
- **Berkas Vue:** `Auth/ForgotPassword.vue`
- **Controller:** `PasswordController@create` (GET), `PasswordController@store` (POST)

Halaman ini menampilkan formulir untuk memasukkan alamat surel terdaftar. Sistem akan mengirimkan tautan *reset* kata sandi ke surel tersebut melalui antrian. Proses pengiriman tidak mengungkapkan apakah surel terdaftar atau tidak sebagai langkah keamanan (*enumeration prevention*).

### 3.4 Halaman *Reset* Kata Sandi

- **URL:** `/Auth/password/reset/{token}` (GET), `/Auth/password/update` (PATCH)
- **Berkas Vue:** `Auth/NewPassword.vue`
- **Controller:** `PasswordController@resetPassword` (GET), `PasswordController@update` (PATCH)

Halaman ini diakses melalui tautan yang dikirimkan ke surel pengguna. Formulir menampilkan bidang kata sandi baru dan konfirmasi kata sandi, dengan token *reset* dan alamat surel yang telah diisi secara otomatis dari parameter URL. Token divalidasi sebelum kata sandi diperbarui.

### 3.5 Halaman Verifikasi Surel

- **URL:** `/verify` (GET), `/verify/{id}/{hash}` (GET), `/verification-notification` (POST)
- **Berkas Vue:** `Auth/VerifyEmail.vue`, `Auth/SuccessVerifyEmail.vue`
- **Controller:** `EmailController@warnVerifyEmail`, `EmailController@verifyEmail`, `EmailController@resendVerifyEmail`
- **Middleware:** `auth`, `signed` (untuk verifikasi), `throttle:6,1` (untuk pengiriman ulang)

Halaman `VerifyEmail.vue` menampilkan pemberitahuan bahwa pengguna harus memverifikasi alamat surel sebelum dapat melanjutkan. Halaman ini juga menyediakan tombol untuk mengirim ulang tautan verifikasi. Halaman `SuccessVerifyEmail.vue` ditampilkan setelah tautan verifikasi berhasil dikonfirmasi. Proses verifikasi menggunakan tanda tangan digital (*signed URL*) yang diverifikasi oleh Laravel untuk memastikan keabsahan tautan.

---

## 4. Halaman Dashboard Admin

### 4.1 Dashboard Utama

- **URL:** `/Dashboard` (GET)
- **Berkas Vue:** `Admin/Dashboard.vue`
- **Controller:** `DashboardController@login`
- **Middleware:** `auth`

Merupakan halaman utama setelah autentikasi yang menyajikan gambaran (*overview*) kondisi perusahaan secara *real-time*. Halaman ini terdiri dari beberapa komponen informasional:

1. **Kartu Statistik:** Empat kartu yang menampilkan jumlah total karyawan, jumlah presensi aktif hari ini, jumlah keterlambatan, dan jumlah ketidakhadiran. Setiap kartu dilengkapi dengan indikator perubahan persentase dibandingkan hari kemarin beserta ikon tren (*trending up/down*).
2. **Grafik Batang Mingguan:** Grafik yang menampilkan tren presensi tujuh hari terakhir dengan tiga kategori per hari: hadir tepat waktu, terlambat, dan alfa (tanpa presensi).
3. **Ringkasan Bulanan:** Tabel perbandingan antara bulan berjalan dengan bulan sebelumnya untuk metrik: jumlah presensi, rata-rata jam kerja, total gaji, dan tingkat ketepatan waktu.
4. **Tabel Slip Gaji Terbaru:** Daftar slip gaji yang baru dibuat dengan informasi nama karyawan, periode, total gaji, dan status.
5. **Linimasa Presensi Terkini:** Daftar presensi terbaru yang masuk dengan informasi nama, lokasi, waktu masuk, catatan, dan status admin.

Seluruh data disajikan dalam format mata uang Rupiah (IDR) untuk nilai nominal dan disertai *relative date* untuk informasi waktu.

---

## 5. Halaman Manajemen Pengguna

### 5.1 Halaman Daftar Akun Karyawan

- **URL:** `/Employee/Account` (GET)
- **Berkas Vue:** `Admin/EmployeeAccount/EmployeeAccount.vue`
- **Controller:** `EmployeeAccountController@index`
- **Middleware:** `auth`

Halaman ini menampilkan daftar seluruh akun karyawan dalam bentuk tabel berpaginasi dengan fitur pencarian berdasarkan nama atau surel. Setiap baris menampilkan nama lengkap, nomor telepon, alamat, dan status akun (aktif atau ditangguhkan) dalam bentuk *badge* berwarna. Tersedia tombol aksi untuk melihat detail akun, menghapus akun, serta tombol untuk membuka dialog pembuatan akun baru dan pengeditan akun.

### 5.2 Halaman Detail dan Pembuatan Akun

- **URL:** `/Employee/Account/create` (GET), `/Employee/Account/{id}` (GET)
- **Berkas Vue:** `Admin/EmployeeAccount/EmployeeAccountDetail.vue`
- **Controller:** `EmployeeAccountController@createPage`, `EmployeeAccountController@detail`
- **Middleware:** `auth`

Halaman ini memiliki dua modus penggunaan. Pada modus pembuatan, formulir menampilkan bidang untuk memasukkan nama lengkap, surel, nomor telepon, alamat, kata sandi, pemilihan grup, dan unggah foto profil. Pada modus detail atau ubah, halaman menampilkan data akun yang telah tersimpan dengan komponen avatar, bidang data yang telah diisi, tag grup, status penangguhan, serta tautan menuju halaman pengelolaan komponen gaji dan riwayat presensi karyawan. Dua komponen pendukung, yaitu `CreateAccount.vue` dan `EditAccount.vue`, disertakan sebagai dialog *inline* pada halaman daftar akun.

### 5.3 Halaman Daftar Grup

- **URL:** `/Employee/Group` (GET)
- **Berkas Vue:** `Admin/EmployeeGroup/EmployeeGroup.vue`
- **Controller:** `EmployeeGroupController@index`
- **Middleware:** `auth`

Halaman ini menampilkan daftar grup atau divisi perusahaan dalam bentuk tabel berpaginasi. Setiap baris menampilkan nama grup dan jumlah anggota (*employee count*). Tersedia tombol aksi untuk mengedit nama grup dan menghapus grup. Komponen `CreateGroup.vue` dan `EditGroup.vue` disertakan sebagai dialog untuk penambahan dan pengubahan data grup.

---

## 6. Halaman Manajemen Presensi

### 6.1 Halaman Daftar Lokasi Presensi

- **URL:** `/Presence/Location` (GET)
- **Berkas Vue:** `Admin/PresenceLocation/PresenceLocation.vue`
- **Controller:** `PresenceLocationController@index`
- **Middleware:** `auth`

Halaman ini menampilkan daftar titik lokasi presensi geografis dalam bentuk kartu. Setiap kartu menampilkan nama lokasi. Saat diklik, sebuah dialog terbuka yang menampilkan kode verifikasi aktif, tombol untuk menghasilkan kode verifikasi baru, tautan untuk mengedit posisi lokasi, dan opsi hapus. Halaman ini merupakan pusat kendali untuk manajemen titik absensi perusahaan.

### 6.2 Halaman Detail Lokasi Presensi

- **URL:** `/Presence/Location/create` (GET), `/Presence/Location/{id}` (GET)
- **Berkas Vue:** `Admin/PresenceLocation/PresenceLocationDetail.vue`
- **Controller:** `PresenceLocationController@detailIndex`, `PresenceLocationController@seeDetail`
- **Middleware:** `auth`

Halaman ini menyediakan antarmuka berbasis peta interaktif (menggunakan Leaflet atau TomTom) untuk mendefinisikan atau mengubah titik lokasi presensi. Fitur yang tersedia meliputi:

- Peta dengan penanda (*marker*) yang dapat digeser (*draggable*) untuk menentukan koordinat.
- Penggeser (*slider*) radius toleransi dalam meter yang divisualisasikan sebagai lingkaran pada peta.
- Bidang masukan nama lokasi dan koordinat lintang atau bujur yang diperbarui secara otomatis.
- Pemilih waktu untuk jam mulai (*start hour*), jam akhir (*end hour*), jam minimal *check-out* (*min hour*), dan jam maksimal *check-out* (*max hour*).

### 6.3 Halaman Riwayat Presensi Karyawan

- **URL:** `/Employee/{user}/Presence-History` (GET)
- **Berkas Vue:** `Employee/EmployeePresence/PresenceHistory.vue`
- **Controller:** `EmployeePresenceController@index`
- **Middleware:** `auth`, `scope-company`

Halaman ini menampilkan riwayat presensi untuk satu karyawan tertentu. Empat kartu statistik di bagian atas menampilkan total hari kerja, jumlah presensi tepat waktu, jumlah keterlambatan, dan total pendapatan periode berjalan. Tabel utama menampilkan data presensi berpaginasi dengan kolom tanggal, waktu masuk, waktu keluar, durasi jam kerja, lokasi, dan status admin. Pengguna dapat memfilter data berdasarkan lokasi atau status serta mengurutkan berdasarkan tanggal. Tersedia fitur:

1. **Dialog Detail:** Menampilkan peta lokasi *check-in*, formulir editor jam kerja, dan tombol setujui atau tolak presensi.
2. **Dialog Tambah Presensi:** Formulir untuk membuat catatan presensi baru secara manual dengan pemilih lokasi, pemilih waktu, peta, catatan, dan lampiran.
3. **Ekspor Excel:** Mengunduh data presensi dalam format *spreadsheet*.

---

## 7. Halaman Manajemen Penggajian

### 7.1 Halaman Komponen Gaji

- **URL:** `/Company/{user}/payroll` (GET)
- **Berkas Vue:** `Admin/PayrollManagement/PayrollManagement.vue`
- **Controller:** `CompanySalaryController@index`
- **Middleware:** `only-company`, `scope-company`

Halaman ini menampilkan daftar komponen gaji yang telah didefinisikan oleh perusahaan. Tabel berpaginasi menampilkan nama komponen, nilai nominal atau persentase (ditandai dengan simbol IDR atau `%`), jumlah karyawan yang ditugaskan, jenis kalkulasi (*add* atau *subtract*), dan tipe komponen (*fixed*, *hourly*, *presence*, atau *tax*). Dialog pembuatan atau penyuntingan menyediakan bidang: nama, status pajak (`is_tax`), nilai gaji atau *rate*, tipe, metode kalkulasi, status "termasuk sebagai default" (*included as default*), dan penugasan ke karyawan berdasarkan grup dengan opsi "dapat diminta" (*available to request*) per karyawan.

### 7.2 Halaman Daftar Slip Gaji

- **URL:** `/receipt` (GET)
- **Berkas Vue:** `Admin/PayrollManagement/PayrollReceipt.vue`
- **Controller:** `CompanyPayrollReceiptController@index`
- **Middleware:** `only-company`

Halaman ini menampilkan seluruh slip gaji yang telah dihasilkan dalam bentuk tabel berpaginasi. Setiap baris menampilkan nama karyawan, gaji pokok, gaji setelah pajak, status (dalam bentuk *badge* berwarna: *pending*, *approved*, *denied*), periode, dan tanggal pembuatan. Pengguna dapat memfilter data berdasarkan rentang tanggal dan melakukan pencarian. Tersedia tombol untuk mengekspor data ke Excel. Dialog pembuatan slip gaji massal (*bulk generate*) menyediakan pemilih rentang tanggal, pemilihan karyawan berdasarkan grup, dan tombol untuk memproses seluruh perhitungan gaji secara otomatis.

### 7.3 Halaman Detail Slip Gaji

- **URL:** `/{user}/Employee/payroll/{payroll}/detail` (GET)
- **Berkas Vue:** `Admin/ReceiptDetail/ReceiptDetail.vue`
- **Controller:** `EmployeePayrollTypeController@detailReceipt`
- **Middleware:** `scope-company`

Halaman ini menampilkan rincian lengkap satu slip gaji. Informasi yang ditampilkan meliputi: nama perusahaan, *badge* status, periode awal dan akhir, data karyawan (nama, surel, telepon), tabel rincian komponen gaji (nama komponen, nilai dasar, kategori, tipe, kuantitas, dan total pendapatan), total jam kerja, jumlah presensi, total gaji sebelum pajak, total pengurang, total pajak, dan gaji bersih setelah pajak. Pada status *pending*, halaman menyediakan mode pengeditan untuk menambah, menghapus, atau mengurutkan ulang komponen gaji. Tersedia tombol untuk menyetujui atau menolak slip, serta tombol untuk mengekspor dalam format PDF (menggunakan mPDF) dan Excel.

### 7.4 Halaman Slip Gaji per Karyawan

- **URL:** `/receipt/{employee}/payroll` (GET)
- **Berkas Vue:** `Employee/EmployeeSalary/EmployeePayrollReceipt.vue`
- **Controller:** `CompanyPayrollReceiptController@indexByUser`
- **Middleware:** `only-company`

Halaman ini merupakan varian dari halaman daftar slip gaji utama yang telah difilter berdasarkan satu karyawan tertentu. Fungsionalitasnya identik dengan halaman daftar slip gaji utama, namun data yang ditampilkan terbatas pada karyawan yang dipilih. Halaman ini biasanya diakses dari halaman detail akun karyawan.

### 7.5 Halaman Permintaan Komponen Gaji Tambahan

- **URL:** `/Employee/Request-Salary` (GET)
- **Berkas Vue:** `Admin/EmployeeRequestSalary/EmployeeRequestSalary.vue`
- **Controller:** `EmployeeRequestSalaryController@index`
- **Middleware:** `auth`

Halaman ini menampilkan daftar permintaan komponen gaji tambahan yang diajukan oleh karyawan. Tabel menampilkan nama karyawan, nama komponen yang diminta, kuantitas, status permintaan (*pending*, *approved*, *rejected*), tanggal pengajuan, tanggal persetujuan, dan status realisasi. Pengguna dapat memfilter data berdasarkan status, rentang tanggal, dan kata kunci pencarian. Tombol aksi untuk menyetujui atau menolak permintaan dilengkapi dengan dialog konfirmasi. Proses persetujuan akan mencatat admin yang menyetujui dan tanggal persetujuan.

---

## 8. Halaman Pendukung

### 8.1 Halaman Manajemen Perangkat

- **URL:** `/Management/{user}/device-management` (GET)
- **Berkas Vue:** `Admin/DeviceManagement/DeviceManagement.vue`
- **Controller:** `DeviceManagementController@manageDevice`
- **Middleware:** `only-company`, `scope-company`

Halaman ini menampilkan daftar karyawan yang memiliki token perangkat aktif (terautentikasi melalui aplikasi *mobile*). Tabel menampilkan alamat surel, nama perangkat, tipe perangkat, dan tanggal pembuatan token. Tersedia tombol aksi untuk menghapus token perangkat, yang secara efektif memaksa *logout* perangkat tersebut. Admin dapat menghapus seluruh token milik satu karyawan (semua perangkat) atau token perangkat individual.

### 8.2 Halaman Pengaturan Perusahaan

- **URL:** `/Company/Settings` (GET)
- **Berkas Vue:** `Admin/Company/CompanySettings.vue`
- **Controller:** `CompanySettingsController@index`
- **Middleware:** `auth`, `only-company`

Halaman ini menyediakan antarmuka untuk mengelola profil perusahaan dan konfigurasi sistem. Terdiri dari dua kelompok pengaturan:

1. **Profil Perusahaan:** Formulir untuk mengubah nama perusahaan, alamat, surel, nomor telepon, dan logo perusahaan (dengan fitur unggah gambar). Logo ditampilkan dalam bentuk *avatar* bundar dengan *fallback* inisial.

2. **Pengaturan *Auto-Approve* Presensi:** Konfigurasi untuk mengotomatiskan persetujuan presensi karyawan. Opsi yang tersedia meliputi:
   - **Status aktif** (*enable* atau *disable*): Mengaktifkan atau menonaktifkan fitur persetujuan otomatis.
   - **Mode:** `realtime` (langsung saat *check-out*) atau `cron` (terjadwal pada jam tertentu).
   - **Jam *batch*:** Waktu eksekusi untuk mode `cron`.
   - **Durasi minimal kerja** (menit): Presensi hanya otomatis disetujui jika durasi kerja mencapai ambang minimum.
   - **Izinkan koordinat duplikat:** Menentukan apakah presensi dengan koordinat yang sama dengan presensi lain diizinkan.

3. **Keamanan:** Kartu terpisah untuk mengubah surel (memerlukan konfirmasi kata sandi saat ini) dan mengubah kata sandi.

---

## 9. Antarmuka *Mobile* (API)

Seluruh halaman yang telah dijelaskan sebelumnya merupakan bagian dari koridor web yang diakses melalui peramban. Selain itu, sistem menyediakan antarmuka *mobile* berupa REST API yang dikonsumsi oleh aplikasi *mobile native* untuk karyawan. *Endpoint* API tersebut tidak memiliki halaman web dan mencakup fungsionalitas berikut:

| Grup | *Endpoint* (prefix) | Controller | Fungsi |
|------|---------------------|------------|--------|
| Autentikasi | `Auth/api/` | `MobileApi/AuthApiController` | Mendapatkan *base token* terenkripsi (AES-256-CBC), *sign-in*, *sign-out* |
| Lokasi | `Location/api/` | `MobileApi/LocationApiController` | Mendapatkan daftar lokasi presesi aktif |
| Presensi | `Presence/api/` | `MobileApi/PrensenceApiController` | *Check-in*, *check-out*, riwayat presensi, *dashboard* karyawan |
| Slip Gaji | `Receipt/api/` | `MobileApi/ReceiptApiController` | Daftar slip gaji, detail slip gaji per periode |
| *Request* Gaji | `Request/api/` | `MobileApi/RequestSalaryApiController` | Daftar komponen yang dapat diminta, mengajukan permintaan, riwayat permintaan |

---

## 10. Komponen yang Tidak Digunakan (*Stub* dan *Legacy*)

Beberapa komponen ditemukan dalam struktur direktori namun tidak difungsikan dalam alur aplikasi saat ini:

| Berkas | Status | Keterangan |
|--------|--------|------------|
| `Employee/EmployeePresence/CreatePresence.vue` | *Stub* kosong | Templat tidak memiliki konten; tidak dirender oleh rute mana pun |
| `Auth/VerifyEmail.vue` | *Legacy* | Mengandung sintaks Blade `@csrf` yang tidak sesuai dengan arsitektur Inertia murni |
| `Auth_Deprecated/*` | Tidak dipakai | Seluruh *controller* Breeze *default* tidak terhubung ke rute mana pun; fungsionalitas autentikasi telah digantikan oleh `AuthController` dan `PasswordController` |
| `ProfileController.php` | Tidak aktif | Tidak memiliki atribut YALR dan rutenya dikomentari pada `routes/web.php` |

---

## 11. Ringkasan

Secara keseluruhan, aplikasi E-Management memiliki 27 berkas komponen Vue yang tersebar dalam empat subdomain: publik (satu halaman), autentikasi (tujuh halaman), admin (14 halaman), dan karyawan (3 halaman aktif ditambah satu *stub* kosong). Dua halaman tambahan, yaitu `SuccessVerifyEmail.vue` dan `CreatePresence.vue`, bersifat transisional atau tidak difungsikan. Seluruh halaman admin dilayani oleh 10 *controller* dengan total lebih dari 40 rute bernama yang mencakup rute *render* Inertia dan *endpoint* data JSON. Antarmuka *mobile* menyediakan lima kelompok *endpoint* API untuk mendukung fungsionalitas karyawan tanpa antarmuka web.