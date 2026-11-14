# Dev A — Fondasi + PBI-1 Registrasi & Login

Modul: `Shared`, `Auth`. Anda juga pemilik berkas bersama (`composer.json`, `config/*`, `.env.example`, `bootstrap/*`).

Pekerjaan Anda menentukan kapan tiga dev lain bisa mulai, jadi **Bagian 1 dikerjakan dan di-merge lebih dulu**.

---

## Bagian 1 — Fondasi

Branch: `chore/fondasi`. Isinya hanya hal yang dipakai lebih dari satu dev. Tanpa logika fitur.

### Langkah

1. **Pindahkan model User ke modul Auth.**
   - `app/Models/User.php` menjadi `app/Modules/Auth/Models/User.php` (namespace `App\Modules\Auth\Models`).
   - Perbarui `config/auth.php` (`providers.users.model`) dan `database/factories/UserFactory.php`.

2. **Migration `users`.** Pindahkan migration bawaan ke `app/Modules/Auth/Database/Migrations/` dan tambahkan kolom:
   - `role` (string, default `user`): `user`, `admin`, `government`
   - `position` (nullable), `agency` (nullable)
   - `password_changed_at` (timestamp, nullable)

3. **Enum di Shared.**
   - `app/Modules/Shared/Enums/UserRole.php`: `User`, `Admin`, `Government`
   - `app/Modules/Shared/Enums/ReportStatus.php`: `ai_validation`, `admin_review`, `rejected`, `approved`, `forwarded`, `gov_approved`, `scheduled`, `cancelled`
   - `app/Modules/Shared/Enums/Criticality.php`: `low`, `medium`, `high`

4. **Skema Report** (disepakati bersama C dan D di PR ini).
   - Migration di `app/Modules/Report/Database/Migrations/`: `reports`, `report_photos`, `tags`, `report_tag`, `report_status_histories`. Kolom mengikuti tabel "Model data" di PRD.
   - Model kosong dengan `$fillable`, cast enum, dan relasi dasar: `Report`, `ReportPhoto`, `Tag`, `ReportStatusHistory` di `app/Modules/Report/Models/`.
   - `ReportFactory` di `app/Modules/Report/Database/Factories/`.

5. **Interface status** di `app/Modules/Report/Contracts/ReportStatusService.php`:

   ```php
   interface ReportStatusService
   {
       /** Catat status awal laporan baru (riwayat: null -> ai_validation). */
       public function initialize(Report $report, ?User $actor = null): void;

       /** Pindahkan status. Lempar InvalidStatusTransition bila tidak sah. */
       public function transition(
           Report $report,
           ReportStatus $to,
           ?User $actor = null,
           ?string $note = null,
           ?ReasonCategory $reasonCategory = null,
       ): Report;
   }
   ```

   Implementasinya milik Dev D. Di fondasi cukup interface-nya.

6. **Format respons dan error** di Shared.
   - `app/Modules/Shared/Exceptions/InvalidStatusTransition.php`: dirender sebagai `409` dengan `message`, `code: invalid_status_transition`, `current_status`.
   - Pastikan error API selalu JSON (sudah diatur di `bootstrap/app.php`; verifikasi untuk 401, 403, 404, 422).

7. **Event login** di `app/Modules/Auth/Events/UserLoggedIn.php` dengan properti `userId`, `ip`, `userAgent`, `loggedInAt`. Dev B mendengarkan event ini; Anda yang memicunya di Bagian 2.

8. **Middleware peran** di `app/Modules/Shared/Http/Middleware/EnsureRole.php`, alias `role`. Pemakaian: `->middleware('role:admin')`. Peran tidak cocok dijawab `403`.

9. **Konfigurasi JWT.**
   - `php artisan vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"`
   - `php artisan jwt:secret`; tambahkan `JWT_SECRET=` (kosong), `JWT_TTL=15`, `JWT_REFRESH_TTL=10080` ke `.env.example`.
   - `config/auth.php`: guard `api` dengan driver `jwt`, jadikan default.
   - `User` mengimplementasikan `JWTSubject`.

10. **Tes fondasi**: migration jalan dari nol (`migrate:fresh`), factory `User` dan `Report` bisa membuat data, middleware `role` menolak peran yang salah.

11. PR ke `main`. Minta B, C, dan D me-review nama kolom dan tanda tangan interface sebelum merge.

### Selesai bila

- [ ] `php artisan migrate:fresh` dan `php artisan test` lolos.
- [ ] B, C, D menyetujui skema dan interface.

---

## Bagian 2 — PBI-1 Registrasi & Login

Branch: `feature/pbi-1-registrasi-login` (dari `main` setelah fondasi masuk).

### Endpoint

| Metode | Path | Akses |
| --- | --- | --- |
| POST | `/api/v1/auth/register` | Publik |
| POST | `/api/v1/auth/login` | Publik |
| POST | `/api/v1/auth/refresh` | Login |
| POST | `/api/v1/auth/logout` | Login |
| GET | `/api/v1/auth/me` | Login |

### Berkas yang dibuat (semua di `app/Modules/Auth/`)

```
Http/Controllers/RegisterController.php
Http/Controllers/LoginController.php
Http/Controllers/TokenController.php        (refresh, logout)
Http/Controllers/MeController.php
Http/Requests/RegisterRequest.php
Http/Requests/LoginRequest.php
Http/Resources/UserResource.php
Http/Middleware/JwtFromCookie.php
Http/Middleware/VerifyXsrfHeader.php
Services/AuthService.php
Services/AuthCookieFactory.php
Repositories/UserRepository.php
Tests/Feature/RegisterTest.php
Tests/Feature/LoginTest.php
Tests/Feature/TokenTest.php
```

### Langkah

1. **Register.** Validasi: `name` wajib, `email` wajib + unik, `password` minimal 8 + `confirmed`. Simpan dengan `role = user`. Jawab `201` dengan `UserResource`.

2. **Login.**
   - `AuthService::login()` memanggil `auth('api')->attempt()`.
   - Gagal: `401` dengan pesan yang sama untuk email maupun password salah.
   - Berhasil: pasang dua cookie lewat `AuthCookieFactory`:
     - `access_token`: JWT, `httpOnly`, `Secure` (kecuali lokal), `SameSite=Lax`
     - `XSRF-TOKEN`: string acak, **bukan** httpOnly (frontend perlu membacanya)
   - Body respons hanya `{ "data": { "user": ... } }`. **Token tidak boleh ada di body.**
   - Picu event `UserLoggedIn`.

3. **Middleware `JwtFromCookie`.** Menyalin cookie `access_token` ke header `Authorization: Bearer ...` sebelum guard berjalan. Daftarkan di grup middleware `api`.

4. **Middleware `VerifyXsrfHeader`.** Untuk metode selain GET/HEAD/OPTIONS pada route yang butuh login: header `X-XSRF-TOKEN` harus sama dengan cookie `XSRF-TOKEN`; bila tidak, `419` atau `403`.

5. **Tolak token lama.** Di guard atau middleware: jika `iat` token lebih lama dari `users.password_changed_at`, jawab `401`. (Dev B yang mengisi kolom itu saat reset password.)

6. **Refresh.** Tukar token yang masih dalam masa refresh, pasang cookie baru, token lama masuk blacklist.

7. **Logout.** `auth('api')->logout()` (blacklist) dan hapus kedua cookie.

8. **Me.** Kembalikan `UserResource` user yang login.

9. **Rate limit.** `throttle:5,1` pada login dan register.

10. **CORS.** `config/cors.php`: `supports_credentials = true`, `allowed_origins` dari env `FRONTEND_URL` (bukan `*`).

11. **Route** di `app/Modules/Auth/Routes/api.php`, dalam blok `// PBI-1`.

### Tes yang wajib ada

- Register sukses; email ganda `422`; password pendek `422`.
- Login sukses memasang cookie `access_token` httpOnly dan body tidak memuat token.
- Login salah `401`; percobaan ke-6 dalam semenit `429`.
- `/auth/me` tanpa cookie `401`; dengan cookie `200`.
- POST tanpa header `X-XSRF-TOKEN` ditolak.
- Setelah logout, cookie lama tidak bisa dipakai.
- Event `UserLoggedIn` terpicu saat login sukses (`Event::fake()`).

### Koordinasi

- **Dev B** menambah berkas di modul Auth untuk reset password. Jangan saling mengedit berkas; route B ada di blok `// PBI-2`.
- **Dev C dan D** memakai `auth:api` dan `role:user` di route mereka. Beri tahu mereka begitu middleware siap.
