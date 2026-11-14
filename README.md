# GovFac-Report

Aplikasi pelaporan kerusakan fasilitas umum.

| Folder | Isi | Panduan |
| --- | --- | --- |
| [`backend/`](backend) | API Laravel 13 (modular monolith), PostgreSQL, Redis, Docker | [backend/README.md](backend/README.md) |
| [`frontend/`](frontend) | Aplikasi web React + Vite + Tailwind | [frontend/README.md](frontend/README.md) |

## Menjalankan keduanya

Backend (API di `http://localhost:8000/api/v1`):

```bash
cd backend
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan jwt:secret
docker compose exec app php artisan migrate
```

Frontend (web di `http://localhost:5173`):

```bash
cd frontend
cp .env.example .env
npm install
npm run dev
```

## Dokumen

| Dokumen | Isi |
| --- | --- |
| [PRD Backend](backend/docs/PRD.md) | Lingkup, peran, [user flow](backend/docs/PRD.md#user-flow-per-peran), [ERD](backend/docs/PRD.md#model-data-erd), [API contract](backend/docs/PRD.md#api-contract), transisi status |
| [Aturan tim backend](backend/docs/ATURAN-TIM.md) | Pembagian berkas, arsitektur, dan alur kerja PR |
| [Panduan Sprint 1](backend/docs/sprint-1/README.md) | Pembagian PBI per developer |
| [Sambungan frontend ke API](frontend/README.md#sambungan-ke-backend) | Fitur mana yang sudah memakai API asli |
