# Dev D — PBI-7 Status Laporan

Modul: `Report`, bagian **baca dan status**. Anda pemilik satu-satunya pintu perubahan status laporan di seluruh aplikasi. Dev C memegang bagian tulis di modul yang sama.

## Persiapan (sambil menunggu fondasi)

1. Clone repo, `cp .env.example .env`, `docker compose up -d --build`.
2. Baca PRD: baris PBI-7 di "Kebutuhan fungsional", tabel **"Transisi status"**, dan tabel `report_status_histories` di "Model data".
3. Review PR fondasi Dev A: **periksa enum `ReportStatus` dan interface `ReportStatusService`**. Tanda tangan itu akan dipakai semua modul di sprint berikutnya.

Branch: `feature/pbi-7-status-laporan` (dari `main` setelah fondasi masuk).

## Endpoint

| Metode | Path | Akses |
| --- | --- | --- |
| GET | `/api/v1/reports/mine` | User (query `status`) |
| GET | `/api/v1/reports/{id}` | Pemilik, Admin, Pemerintah |
| GET | `/api/v1/reports/{id}/history` | Sama dengan detail |

## Berkas yang dibuat (semua di `app/Modules/Report/`)

```
Services/EloquentReportStatusService.php
Services/ReportStatusTransitions.php
Services/ReportQueryService.php
Repositories/ReportReadRepository.php
Repositories/ReportStatusHistoryRepository.php
Http/Controllers/MyReportsController.php
Http/Controllers/ShowReportController.php
Http/Controllers/ReportHistoryController.php
Http/Resources/ReportStatusHistoryResource.php
Policies/ReportPolicy.php
Events/ReportStatusChanged.php
Tests/Unit/ReportStatusTransitionsTest.php
Tests/Feature/ReportStatusServiceTest.php
Tests/Feature/MyReportsTest.php
Tests/Feature/ShowReportTest.php
Tests/Feature/ReportHistoryTest.php
```

## Langkah

Kerjakan **langkah 1 sampai 3 lebih dulu dan buka PR tersendiri** (`feature/pbi-7a-status-service`). Dev C menunggu implementasi ini untuk integrasi.

1. **`ReportStatusTransitions`.** Peta transisi yang sah, persis tabel di PRD:

   | Dari | Ke |
   | --- | --- |
   | `ai_validation` | `admin_review`, `rejected`, `cancelled` |
   | `admin_review` | `approved`, `rejected`, `cancelled` |
   | `rejected` | `ai_validation` |
   | `approved` | `forwarded`, `cancelled` |
   | `forwarded` | `gov_approved`, `cancelled` |
   | `gov_approved` | `scheduled` |
   | `scheduled`, `cancelled` | (akhir) |

   Method: `canTransition(ReportStatus $from, ReportStatus $to): bool`.

   > Kelas ini hanya memeriksa **status**. Siapa yang boleh memicu (peran, kepemilikan) diperiksa oleh Policy modul pemanggil.

2. **`EloquentReportStatusService`** mengimplementasikan interface dari fondasi.
   - `initialize()`: tulis baris riwayat `from_status = null`, `to_status = ai_validation`.
   - `transition()` dalam satu `DB::transaction`:
     1. Kunci baris laporan (`lockForUpdate`).
     2. Jika `canTransition` salah, lempar `InvalidStatusTransition` (menjadi `409`).
     3. Perbarui `reports.status`.
     4. Tambah satu baris `report_status_histories` (`actor_id` kosong berarti sistem).
     5. Setelah commit, picu `ReportStatusChanged`.
   - Riwayat **hanya ditambah**. Tidak ada update atau delete.

3. **Binding.** Di `ReportServiceProvider`:

   ```php
   protected array $contracts = [
       ReportStatusService::class => EloquentReportStatusService::class,
   ];
   ```

   Beri tahu Dev C begitu PR ini masuk.

4. **`ReportPolicy::view()`.**
   - Pemilik: selalu boleh.
   - Admin: selalu boleh.
   - Pemerintah: hanya status `forwarded`, `gov_approved`, `scheduled`. Selain itu jawab **`404`**, bukan `403`, agar keberadaan laporan tidak bocor.
   - User lain: `404` di sprint ini (akses lewat feed baru ada di PBI-11).

5. **`GET /reports/mine`.** Laporan milik user yang login, berhalaman, terbaru di atas, filter `status` opsional (validasi terhadap enum). Hindari N+1: `with(['photos', 'tags'])`.

6. **`GET /reports/{id}`.** Detail lengkap dengan `ReportResource` milik Dev C.

7. **`GET /reports/{id}/history`.** Linimasa terurut dari yang paling lama. Tiap baris: `from_status`, `to_status`, `actor` (`id`, `name`, `role`, atau `null` untuk sistem), `reason_category`, `note`, `created_at`.

8. **Route** di `app/Modules/Report/Routes/api.php`, dalam blok `// PBI-7`. Letakkan `/reports/mine` **sebelum** `/reports/{id}`, dan batasi `{id}` dengan `whereNumber('id')`.

## Tes yang wajib ada

**Unit (`ReportStatusTransitionsTest`)** — uji seluruh matriks 8 x 8 dengan data provider: setiap pasangan yang sah lolos, setiap pasangan lain ditolak.

**Feature**

- `transition()` yang sah mengubah status dan menambah **tepat satu** baris riwayat.
- `transition()` yang tidak sah melempar exception, status tidak berubah, riwayat tidak bertambah.
- `initialize()` menulis riwayat `null -> ai_validation`.
- `/reports/mine` hanya berisi laporan milik sendiri; filter `status` bekerja; status tak dikenal `422`.
- Detail: pemilik `200`; user lain `404`; admin `200`; pemerintah `404` untuk `admin_review` dan `200` untuk `forwarded`.
- History terurut naik dan memuat catatan penolakan.
- Tanpa login `401`.

> Buat data dengan `ReportFactory` dan `$this->actingAs($user, 'api')`. Anda tidak perlu menunggu endpoint login Dev A maupun formulir Dev C.

## Koordinasi

- **Dev C** memiliki `ReportResource` dan `ReportRepository`. Query baca Anda taruh di `ReportReadRepository.php` agar tidak mengedit berkasnya.
- **Sprint berikutnya**: modul Verification, Government, dan AiValidation akan memanggil `ReportStatusService::transition()`. Jaga tanda tangannya tetap stabil; perubahan harus diumumkan ke tim.
