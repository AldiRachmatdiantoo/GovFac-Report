# GovFac-Report — Backend

API pelaporan kerusakan fasilitas umum. Laravel 13, modular monolith, PostgreSQL, Redis, JWT di cookie httpOnly, Docker.

Backend berada di folder `backend/` pada repositori; semua path di berkas ini relatif terhadap folder itu. Frontend (React) ada di `frontend/` dan dimiliki tim frontend.

Proyek ini dikerjakan beberapa developer secara paralel, masing-masing pada modulnya sendiri. **Baca dan patuhi aturan tim sebelum mengubah berkas apa pun:**

@docs/ATURAN-TIM.md

Pedoman Laravel untuk aplikasi ini (dihasilkan Laravel Boost):

@AGENTS.md

## Ringkasan yang paling sering dilanggar

- **Laravel Boost sudah terpasang.** Jangan menjalankan `composer require laravel/boost`, `php artisan boost:install`, atau `boost:update`.
- **Jangan mengubah berkas bersama** (`composer.*`, `Dockerfile`, `compose.yaml`, `config/*`, `bootstrap/*`, `phpunit.xml`, `app/Modules/Shared/*`, berkas ini) kecuali developer menyatakan dirinya Dev A.
- **Tanyakan** developer ini Dev A, B, C, atau D bila belum disebutkan, lalu ikuti `docs/sprint-1/dev-<huruf>.md`.
- Jalankan perintah lewat `docker compose exec app ...` dari folder `backend/`, bukan langsung di mesin.
- Status laporan hanya berubah lewat `ReportStatusService`.
