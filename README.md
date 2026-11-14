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

Aturan kerja tim backend ada di [backend/docs/ATURAN-TIM.md](backend/docs/ATURAN-TIM.md).
