# GovFac-Report — Frontend

Aplikasi web pelaporan kerusakan fasilitas umum. React 18, Vite, Tailwind 4, React Router, axios.

Catatan desain ada di [README-REDESIGN.md](README-REDESIGN.md).

## Menjalankan

Backend harus berjalan lebih dulu (lihat [../backend/README.md](../backend/README.md)).

```bash
cd frontend
cp .env.example .env
npm install
npm run dev
```

Buka `http://localhost:5173`. Port ini dikunci karena backend hanya mengizinkan origin `FRONTEND_URL` (bawaan `http://localhost:5173`) untuk CORS.

| Variabel | Arti |
| --- | --- |
| `VITE_USE_MOCK` | `true`: mode demo penuh, semua data dummy, tanpa backend. `false`: endpoint yang sudah ada memakai API asli, sisanya tetap dummy. |
| `VITE_API_BASE_URL` | Alamat API, bawaan `http://localhost:8000/api/v1`. |

Akun demo (`user@demo.com`, `admin@demo.com`, `gov@demo.com`) hanya ada di mode demo.

## Sambungan ke backend

Halaman hanya memanggil fungsi di `src/services/index.js`. Berkas itu memilih sumber data per fungsi:

| Berkas | Isi |
| --- | --- |
| `src/services/http.js` | Klien axios: cookie sesi, header `X-XSRF-TOKEN`, refresh otomatis saat `401`, pesan error. |
| `src/services/api.js` | Pemanggilan API asli dan adapter bentuk datanya. |
| `src/services/mock.js` | Data dummy, bentuk datanya sama dengan `api.js`. |

Sesi memakai cookie httpOnly dari backend. Frontend tidak menyimpan token maupun data user di `localStorage`; saat dimuat, aplikasi menanyakan `GET /auth/me`.

Nilai status laporan mengikuti enum backend (`ai_validation`, `admin_review`, `rejected`, `approved`, `forwarded`, `gov_approved`, `scheduled`, `cancelled`). Label yang tampil diatur di `src/constants.js`.

### Status sambungan (Sprint 1)

| Fitur | Sumber data |
| --- | --- |
| Daftar, masuk, keluar, sesi | API (PBI-1) |
| Lupa dan atur ulang password | API (PBI-2) |
| Notifikasi | API (PBI-3) |
| Buat laporan, saran tag | API (PBI-5) |
| Laporan saya, detail, riwayat status | API (PBI-7) |
| Statistik dashboard pelapor | Dihitung dari `GET /reports/mine` (maks. 50 laporan terbaru) |
| Edit dan batalkan laporan | Hanya di mode demo; disembunyikan saat memakai API |
| Komunitas dan like | Dummy |
| Semua layar Admin dan Pemerintah | Dummy (login-nya tetap lewat API) |

### Menyambungkan endpoint baru

1. Tulis fungsinya di `api.js` dengan bentuk data yang sama dengan versi di `mock.js`.
2. Di `index.js`, pindahkan ekspornya dari blok "masih mock" ke blok "sudah tersambung".
