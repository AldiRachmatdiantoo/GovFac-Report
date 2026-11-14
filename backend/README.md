# GovFac-Report — Backend

API pelaporan kerusakan fasilitas umum. Laravel 13, modular monolith (layered + module based), PostgreSQL, Redis, Docker multi-stage.

Acuan produk: [PRD Backend](../README.md#prd-backend), berisi user flow, ERD, API contract, dan transisi status.

## Menjalankan dengan Docker

Semua perintah dijalankan dari folder `backend/`.

```bash
cd backend
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan jwt:secret
docker compose exec app php artisan migrate
```

## Autentikasi

Sesi memakai JWT di cookie httpOnly; frontend tidak pernah memegang token.

- `POST /auth/login` memasang cookie `access_token` (httpOnly) dan `XSRF-TOKEN` (bisa dibaca JavaScript).
- Frontend mengirim setiap permintaan dengan `credentials: 'include'`.
- Permintaan selain GET wajib membawa header `X-XSRF-TOKEN` berisi nilai cookie `XSRF-TOKEN`; tanpa itu dijawab `419`.
- Access token berlaku 15 menit. Saat mendapat `401`, panggil `POST /auth/refresh` lalu ulangi permintaan. Sesi bisa diperpanjang sampai 7 hari sejak login.
- Mengganti password mencabut semua sesi lama.
- Origin frontend diatur lewat `FRONTEND_URL` di `.env` (dipakai CORS dan tautan email).

Di route modul, pakai `->middleware('auth:api')` dan `->middleware('role:admin')`. Di tes, pakai `$this->actingAs($user, 'api')`.

API tersedia di `http://localhost:8000/api/v1`. Cek kesehatan: `http://localhost:8000/up`.

| Layanan | Fungsi |
| --- | --- |
| `app` | PHP-FPM (stage `dev`), kode di-mount sebagai volume; `vendor/` di volume Docker tersendiri |
| `queue` | Worker antrean dari image yang sama |
| `nginx` | Web server, port `8000` |
| `postgres` | PostgreSQL 16, port `5432` |
| `redis` | Cache, antrean, sesi |

`vendor/` kontainer disimpan di volume Docker `vendor` supaya cepat, jadi terpisah dari `vendor/` di folder proyek. Setelah `composer.lock` berubah (atau setelah volume dihapus), jalankan lagi `docker compose exec app composer install`. Bila `docker/php/dev.ini` berubah, bangun ulang image dengan `docker compose up -d --build`.

Image produksi: `docker build --target production -t govfac-report:prod .`

## Struktur modul

Setiap modul berada di `app/Modules/<Nama>` dan memakai lapisan yang sama:

```
Http/Controllers   menerima request, memanggil Service, mengembalikan Resource
Http/Requests      FormRequest: validasi dan otorisasi input
Http/Resources     bentuk JSON keluaran
Services           aturan bisnis dan transaksi
Repositories       seluruh query Eloquent
Models             model Eloquent dan relasi
Policies           hak akses per objek
Events, Listeners, Jobs
Contracts          interface yang boleh dipakai modul lain
Database           Migrations, Factories, Seeders
Routes/api.php     otomatis di bawah /api/v1
Tests              Feature dan Unit
Providers          <Nama>ServiceProvider
```

| Modul | PBI |
| --- | --- |
| Auth | 1, 2 |
| Notification | 3, 14 |
| Report | 5, 7, 8, 9, 10 |
| AiValidation | 6 |
| Community | 11, 12 |
| Verification | 15, 16, 17 |
| Government | 18–22 |
| Analytics | 4, 13 |
| Shared | kode bersama |

## Aturan

- Arah panggilan: Controller → Service → Repository → Model.
- Modul lain hanya boleh memakai interface di `Contracts/` atau mendengarkan event.
- Binding `Contracts` didaftarkan di properti `$contracts` pada ServiceProvider modul.
- Route dan migration modul dimuat otomatis oleh `Shared/Providers/ModuleServiceProvider`.
- Status laporan hanya berubah lewat `ReportStatusService` di modul Report.

## Tes

```bash
docker compose exec app php artisan test
```

Tes modul diletakkan di `app/Modules/<Nama>/Tests` dan ikut berjalan lewat test suite `Modules`.
