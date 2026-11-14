# Dev C — PBI-5 Formulir Laporan

Modul: `Report`, bagian **tulis** (membuat laporan, foto, tagar). Dev D memegang bagian baca dan status di modul yang sama.

## Persiapan (sambil menunggu fondasi)

1. Clone repo, `cp .env.example .env`, `docker compose up -d --build`.
2. Baca PRD: baris PBI-5 di "Kebutuhan fungsional", tabel `reports`, `report_photos`, `tags`, `report_tag` di "Model data", dan bentuk objek laporan di API contract.
3. Review PR fondasi Dev A: **periksa kolom migration Report dan tanda tangan `ReportStatusService`**. Ini kesempatan mengubahnya tanpa biaya.

Branch: `feature/pbi-5-formulir-laporan` (dari `main` setelah fondasi masuk).

## Endpoint

| Metode | Path | Akses |
| --- | --- | --- |
| POST | `/api/v1/reports` | User (`multipart/form-data`) |
| GET | `/api/v1/tags` | Login (query `q`) |

## Berkas yang dibuat (semua di `app/Modules/Report/`)

```
Http/Controllers/CreateReportController.php
Http/Controllers/TagController.php
Http/Requests/StoreReportRequest.php
Http/Resources/ReportResource.php
Http/Resources/ReportPhotoResource.php
Services/CreateReportService.php
Services/ReportCodeGenerator.php
Services/ReportPhotoStorage.php
Repositories/ReportRepository.php
Repositories/TagRepository.php
Events/ReportSubmitted.php
Tests/Feature/CreateReportTest.php
Tests/Feature/TagSuggestionTest.php
Tests/Unit/ReportCodeGeneratorTest.php
```

`ReportResource` dipakai juga oleh Dev D. **Anda pemiliknya**; D menambah field lewat Anda atau lewat PR kecil.

## Langkah

1. **`StoreReportRequest`.**
   - `title`: wajib, maksimal 100
   - `description`: wajib, maksimal 2000
   - `photos`: array, 1 sampai 5
   - `photos.*`: `image`, `mimes:jpeg,png`, `max:5120`
   - `tags`: array, 1 sampai 5
   - `tags.*`: string, maksimal 30, hanya huruf, angka, garis bawah
   - `authorize()`: hanya peran `user`

2. **`ReportCodeGenerator`.** Menghasilkan `GFR-YYYY-NNNNNN`, unik. Aman terhadap dua permintaan bersamaan (pakai transaksi atau turunkan dari `id` setelah insert).

3. **`ReportPhotoStorage`.** Simpan ke disk dari `config('filesystems.default')` di folder `reports/{report_id}/`, nama berkas acak (`Str::uuid()`), jangan memakai nama asli. Kembalikan `path`, `mime`, `size`.

4. **`CreateReportService::create()`** dalam satu `DB::transaction`:
   1. Buat `Report` dengan `reporter_id`, `status = ai_validation`, `revision = 1`.
   2. Simpan foto dan baris `report_photos` berurutan (`sort_order`).
   3. Normalisasi tagar: huruf kecil, buang `#` di depan, `firstOrCreate` di `tags`, lalu `sync` ke `report_tag`.
   4. Panggil `ReportStatusService::initialize($report, $actor)` (interface dari fondasi; implementasi Dev D).
   5. Setelah commit, picu event `ReportSubmitted` (dipakai PBI-6 di sprint berikutnya).

   Jika transaksi gagal, hapus berkas foto yang sudah terunggah.

5. **Controller.** Terima `StoreReportRequest`, panggil service, jawab `201` dengan `ReportResource`.

6. **`ReportResource`.** Ikuti bentuk di PRD: `id`, `code`, `title`, `description`, `status`, `criticality`, `revision`, `tags`, `photos` (id + url), `likes_count`, `reporter` (hanya `id` dan `name`), `created_at`, `updated_at`. **Email pelapor tidak boleh ikut.**

7. **`GET /tags`.** Saran tagar berdasarkan awalan `q`, maksimal 10, diurutkan dari yang paling sering dipakai.

8. **Route** di `app/Modules/Report/Routes/api.php`, dalam blok `// PBI-5`, middleware `auth:api` dan `role:user` untuk POST.

## Bekerja sebelum implementasi Dev D siap

`ReportStatusService` baru punya interface. Agar tidak menunggu:

- Di tes, ikat implementasi palsu: `$this->mock(ReportStatusService::class)->shouldReceive('initialize')->once();`
- Jangan menulis baris `report_status_histories` sendiri. Itu wilayah D.

Setelah PR D masuk, rebase dan tambahkan satu tes yang memastikan riwayat awal benar-benar tercatat.

## Tes yang wajib ada

- Laporan sah: `201`, status `ai_validation`, foto tersimpan (`Storage::fake()`), tagar ternormalisasi.
- Tanpa foto, atau 6 foto: `422`.
- Foto bukan JPEG/PNG, atau lebih dari 5 MB: `422`.
- Judul 101 karakter: `422`.
- Peran `admin` atau `government` mencoba membuat laporan: `403`.
- Tanpa login: `401`.
- Tagar `#LampuJalan` dan `lampujalan` menghasilkan satu baris `tags`.
- Respons tidak memuat email pelapor.
- Event `ReportSubmitted` terpicu.

> Pakai `$this->actingAs($user, 'api')`. Anda tidak perlu menunggu endpoint login Dev A.

## Koordinasi

- **Dev D** memakai `ReportRepository` dan `ReportResource` Anda. Sepakati lebih dulu method repository yang ia butuhkan (`findById`, `paginateByReporter`), atau biarkan D menambah **method baru** di berkas terpisah `ReportReadRepository.php`.
- Kolom baru di `reports`? Buat migration baru, jangan mengedit migration fondasi.
