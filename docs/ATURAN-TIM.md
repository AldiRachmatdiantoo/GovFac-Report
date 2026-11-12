# Aturan Tim Backend GovFac-Report

Berlaku untuk semua developer dan semua agen AI (Claude, Codex, dan lainnya) yang bekerja di repositori ini.

## Sebelum mulai

1. Pastikan Anda tahu sedang bekerja sebagai Dev A, B, C, atau D. Agen AI: bila belum jelas, **tanyakan dulu**.
2. Baca panduan sprint yang berjalan: `docs/sprint-1/README.md` dan `docs/sprint-1/dev-<huruf>.md`.
3. Buat branch dari `main` terbaru. Satu branch per PBI: `feature/pbi-<nomor>-<nama>`.

## Berkas bersama: hanya Dev A yang mengubah

Jangan mengubah berkas berikut di branch fitur. Butuh perubahan? Minta ke Dev A atau buat PR kecil terpisah yang hanya berisi perubahan itu.

- `composer.json`, `composer.lock`, `package.json`
- `Dockerfile`, `compose.yaml`, `docker/*`
- `config/*`, `bootstrap/*`, `phpunit.xml`, `.env.example`
- `app/Modules/Shared/*`
- `CLAUDE.md`, `AGENTS.md`, `boost.json`, `docs/ATURAN-TIM.md`

**Laravel Boost sudah terpasang.** Jangan menjalankan `composer require laravel/boost`, `php artisan boost:install`, atau `php artisan boost:update` di branch fitur; perintah itu menulis ulang berkas bersama dan menimbulkan konflik.

**Jangan menambah atau memperbarui dependensi** (`composer require`, `composer update`, `npm install <paket>`) tanpa persetujuan Dev A.

## Arsitektur

Modular monolith. Setiap modul ada di `app/Modules/<Nama>` dan memakai lapisan yang sama:

```
Controller  ->  Service  ->  Repository  ->  Model
```

- **Controller**: terima FormRequest, panggil Service, kembalikan Resource. Tanpa query, tanpa aturan bisnis.
- **Service**: aturan bisnis dan transaksi. Tidak menyentuh objek `Request`.
- **Repository**: semua query Eloquent.
- **Antar modul** hanya lewat interface di `Contracts/` atau event. Pengecualian: model `User` dan `Report` boleh diimpor untuk relasi dan type hint.
- **Status laporan hanya berubah lewat `ReportStatusService`.** Jangan menulis `reports.status` atau `report_status_histories` secara langsung.
- `role` pada `User` dan `status` pada `Report` tidak fillable; set secara eksplisit di Service.

## Agar tidak konflik

- **Satu berkas, satu pemilik.** Di modul yang dikerjakan dua orang, tambah berkas baru; jangan mengedit berkas rekan.
- **`Routes/api.php`**: tulis di blok milik Anda yang diberi komentar nomor PBI (`// PBI-5`).
- **Migration yang sudah di-merge tidak diedit.** Butuh kolom baru? Buat migration baru.
- Tidak ada yang push langsung ke `main`. Semua lewat pull request.
- `git pull --rebase origin main` sebelum mulai bekerja dan sebelum membuka PR.

## Menjalankan perintah

Semua perintah dijalankan di dalam kontainer, bukan langsung di mesin:

```bash
docker compose up -d
docker compose exec app php artisan migrate
docker compose exec app php artisan test
docker compose exec app php artisan route:list --path=api
docker compose logs -f app          # log aplikasi
```

Tes memakai SQLite in-memory dan tidak menyentuh basis data pengembangan.

## Autentikasi di route dan tes

- Route: `->middleware('auth:api')` dan `->middleware('role:admin')` (bisa beberapa: `role:admin,government`).
- Tes: `$this->actingAs($user, 'api')`. Factory: `User::factory()->admin()`, `->government()`.

## Sebelum membuka PR

- [ ] Endpoint sesuai API contract (path, body, kode status).
- [ ] Ada feature test untuk tiap endpoint, termasuk kasus gagal (401, 403, 404, 409, 422).
- [ ] `docker compose exec app php artisan test` lolos seluruhnya.
- [ ] Tidak ada perubahan pada berkas bersama atau berkas milik developer lain.
- [ ] Sudah rebase ke `main` terbaru.
