# Dev B — PBI-2 Lupa Password + PBI-3 Notifikasi Login

Modul: `Auth` (hanya berkas reset password) dan `Notification` (milik Anda sepenuhnya).

## Persiapan (sambil menunggu fondasi)

1. Clone repo, `cp .env.example .env`, `docker compose up -d --build`.
2. Baca PRD: baris PBI-2 dan PBI-3 di "Kebutuhan fungsional", tabel "Auth dan Notification" di API contract.
3. Review PR fondasi Dev A: periksa kolom `password_changed_at` dan event `UserLoggedIn`.

Setelah fondasi di-merge, kerjakan dua branch terpisah. **PBI-3 lebih dulu** karena tidak bergantung pada siapa pun.

---

## Bagian 1 — PBI-3 Notifikasi Login

Branch: `feature/pbi-3-notifikasi-login`.

### Endpoint

| Metode | Path | Akses |
| --- | --- | --- |
| GET | `/api/v1/notifications` | Login (query `unread=1`) |
| GET | `/api/v1/notifications/unread-count` | Login |
| PATCH | `/api/v1/notifications/{id}/read` | Login |
| PATCH | `/api/v1/notifications/read-all` | Login |

### Berkas yang dibuat (semua di `app/Modules/Notification/`)

```
Database/Migrations/xxxx_create_notifications_table.php
Http/Controllers/NotificationController.php
Http/Resources/NotificationResource.php
Services/NotificationService.php
Repositories/NotificationRepository.php
Notifications/LoginDetected.php
Listeners/SendLoginNotification.php
Tests/Feature/NotificationEndpointsTest.php
Tests/Feature/LoginNotificationTest.php
```

### Langkah

1. **Tabel.** `php artisan make:notifications-table`, lalu pindahkan berkasnya ke `app/Modules/Notification/Database/Migrations/`.

2. **Notifikasi `LoginDetected`.** Channel `database`. Isi `data`: `title`, `message`, `ip`, `user_agent`, `logged_in_at`.

3. **Listener `SendLoginNotification`.** Mendengarkan `App\Modules\Auth\Events\UserLoggedIn`, mengambil user, memanggil `$user->notify(new LoginDetected(...))`.
   - Daftarkan di `NotificationServiceProvider::boot()` dengan `Event::listen(...)`. Panggil `parent::boot()` agar route dan migration tetap dimuat.
   - Ini satu-satunya titik sentuh dengan modul Auth, dan hanya lewat event.

4. **Endpoint.**
   - Daftar: berhalaman (20 per halaman), terbaru di atas, filter `unread=1`.
   - Unread count: `{ "data": { "count": 3 } }`.
   - Read: hanya notifikasi milik sendiri; milik orang lain `404`.
   - Read-all: tandai semua milik user.

5. **Route** di `app/Modules/Notification/Routes/api.php`, middleware `auth:api`.

### Tes yang wajib ada

- Memicu `UserLoggedIn` membuat tepat satu baris notifikasi untuk user tersebut.
- Daftar hanya menampilkan notifikasi milik sendiri.
- `unread-count` berkurang setelah `read`.
- Menandai notifikasi user lain dijawab `404`.
- Tanpa login `401`.

> Di tes, pakai `$this->actingAs($user, 'api')` dan picu event secara langsung. Anda **tidak perlu menunggu** endpoint login Dev A selesai.

---

## Bagian 2 — PBI-2 Lupa Password

Branch: `feature/pbi-2-lupa-password`.

### Endpoint

| Metode | Path | Akses |
| --- | --- | --- |
| POST | `/api/v1/auth/forgot-password` | Publik |
| POST | `/api/v1/auth/reset-password` | Publik |

### Berkas yang dibuat (di `app/Modules/Auth/`, semuanya berkas baru)

```
Http/Controllers/ForgotPasswordController.php
Http/Controllers/ResetPasswordController.php
Http/Requests/ForgotPasswordRequest.php
Http/Requests/ResetPasswordRequest.php
Services/PasswordResetService.php
Tests/Feature/PasswordResetTest.php
```

### Langkah

1. **Forgot password.** `PasswordResetService::sendLink()` memakai `Password::sendResetLink()`.
   - Jawaban **selalu `200`** dengan pesan yang sama, baik email terdaftar maupun tidak.
   - `throttle:5,1`.

2. **Tautan reset mengarah ke frontend.** Minta Dev A menambahkan (atau buat PR kecil terpisah):
   - env `FRONTEND_URL` dan `ResetPassword::createUrlUsing(...)` yang menghasilkan `{FRONTEND_URL}/reset-password?token=...&email=...`
   - `config/auth.php`: `passwords.users.expire = 30`

3. **Reset password.** `PasswordResetService::reset()` memakai `Password::reset()`.
   - Validasi: `token`, `email`, `password` minimal 8 + `confirmed`.
   - Di callback: simpan hash password baru **dan isi `password_changed_at = now()`**. Kolom inilah yang membuat semua JWT lama ditolak.
   - Token salah atau kedaluwarsa: `422`.

4. **Email di lokal.** `MAIL_MAILER=log`; isi email muncul di `storage/logs/laravel.log`.

5. **Route** di `app/Modules/Auth/Routes/api.php`, dalam blok `// PBI-2`. Jangan mengubah blok `// PBI-1`.

### Tes yang wajib ada

- Forgot password dengan email terdaftar mengirim notifikasi (`Notification::fake()`).
- Forgot password dengan email tidak terdaftar tetap `200` dan tidak mengirim apa pun.
- Reset dengan token sah mengubah password dan mengisi `password_changed_at`.
- Token yang sama tidak bisa dipakai dua kali.
- Token kedaluwarsa (lewat 30 menit, pakai `travel()`) `422`.

### Koordinasi

- **Dev A** memiliki `config/auth.php`, `.env.example`, dan `AuthServiceProvider`. Perubahan di sana lewat A.
- Penolakan JWT lama berdasarkan `password_changed_at` dikerjakan A (PBI-1 langkah 5). Setelah keduanya masuk, tambahkan satu tes integrasi: login, reset password, cookie lama dijawab `401`.
