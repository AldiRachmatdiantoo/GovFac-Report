# Sprint 1 — Panduan Pengerjaan Backend

Periode: 3–17 November 2026. Acuan utama: [PRD Backend GovFac-Report](../../../README.md#prd-backend) (API contract, ERD, transisi status).

## Pembagian

| Dev | PBI | Modul | Panduan |
| --- | --- | --- | --- |
| A | PBI-1 Registrasi & Login + fondasi | Auth, Shared | [dev-a.md](dev-a.md) |
| B | PBI-2 Lupa Password, PBI-3 Notifikasi Login | Auth, Notification | [dev-b.md](dev-b.md) |
| C | PBI-5 Formulir Laporan | Report (tulis) | [dev-c.md](dev-c.md) |
| D | PBI-7 Status Laporan | Report (baca dan status) | [dev-d.md](dev-d.md) |

## Urutan kerja

1. **Dev A** membuat branch `chore/fondasi`, mengerjakan Bagian 1 di [dev-a.md](dev-a.md), lalu PR ke `main`.
2. Tiga dev lain me-review PR fondasi. Nama kolom, enum, dan tanda tangan interface disepakati **di PR ini**.
3. Setelah fondasi di-merge, **semua** membuat branch fitur dari `main` terbaru.
4. PR kecil dan sering. `git pull --rebase origin main` setiap pagi.

Sambil menunggu fondasi, B, C, dan D bisa menyiapkan lingkungan (langkah "Persiapan" di tiap panduan) dan membaca bagian PRD yang relevan.

## Aturan agar tidak konflik

- **Satu berkas, satu pemilik.** Di modul yang dibagi dua (Auth: A dan B; Report: C dan D), tambah berkas baru, jangan mengedit berkas rekan.
- **`Routes/api.php`**: tiap dev menulis di blok miliknya sendiri yang diberi komentar nama PBI.
- **Berkas bersama dimiliki Dev A**: `composer.json`, `composer.lock`, `config/*`, `.env.example`, `bootstrap/*`, `app/Modules/Shared/*`. Butuh perubahan di sana? Minta ke A atau buat PR kecil terpisah.
- **Migration yang sudah di-merge tidak diedit.** Butuh kolom baru? Buat migration baru.
- **Antar modul hanya lewat `Contracts/` atau event.** Jangan mengimpor Service, Repository, atau Model modul lain. Pengecualian: model `User` (modul Auth) dan `Report` (modul Report) boleh diimpor untuk relasi dan type hint, tetapi `reports.status` tetap hanya diubah lewat `ReportStatusService`.
- **`role` pada `User` dan `status` pada `Report` tidak fillable.** Keduanya tidak bisa diisi lewat `create([...])` dari input request; set secara eksplisit di Service.
- Tidak ada yang push langsung ke `main`.

## Lapisan (wajib sama di semua modul)

```
Controller  ->  Service  ->  Repository  ->  Model
```

- Controller: terima FormRequest, panggil Service, kembalikan Resource. Tanpa query, tanpa aturan bisnis.
- Service: aturan bisnis dan transaksi. Tidak menyentuh objek `Request`.
- Repository: semua query Eloquent.

## Definisi selesai (tiap PR)

- [ ] Endpoint sesuai API contract di PRD (path, body, kode status).
- [ ] Kriteria penerimaan PBI di PRD terpenuhi.
- [ ] Ada feature test untuk tiap endpoint, termasuk kasus gagal (401, 403, 409, 422).
- [ ] `php artisan test` lolos.
- [ ] Tidak menyentuh berkas milik dev lain.
- [ ] Sudah rebase ke `main` terbaru.

## Perintah harian

```bash
docker compose up -d
docker compose exec app php artisan migrate
docker compose exec app php artisan test
docker compose exec app php artisan route:list --path=api
```

## Catatan lingkup sprint

PBI-6 (Validasi AI) tidak masuk sprint ini. Laporan yang dibuat lewat PBI-5 akan berhenti di status `ai_validation`, dan itu perilaku yang diharapkan untuk sprint 1.
