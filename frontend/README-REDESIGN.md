# Lapor Infrastruktur — Modern Public Service Redesign

## Arah desain
- Visual Modern Public Service dengan teal/emerald sebagai warna identitas.
- Latar belakang hijau-keabu-abuan yang lembut, surface putih, border halus, dan aksen warna status.
- Sidebar role-based, topbar kontekstual, avatar inisial, dan navigasi responsif.
- Komponen UI konsisten untuk tombol, field, modal, kartu statistik, status, filter, daftar laporan, dan empty/loading/error state.
- Halaman autentikasi menggunakan layout split-panel pada desktop dan layout ringkas pada mobile.

## Fitur yang dipertahankan
- Authentication: login, register, lupa password.
- Masyarakat: dashboard, buat/edit laporan, laporan saya, detail laporan, komunitas, like laporan.
- Admin: dashboard, daftar laporan, detail/verifikasi dan penerusan laporan.
- Pemerintah: dashboard, laporan terverifikasi, persetujuan dengan tanda tangan digital, penjadwalan, audit log.
- Service layer tetap mendukung dummy data dan disiapkan untuk integrasi API Laravel.

## File utama yang diperbarui
- `src/index.css`: design tokens dan global styling.
- `src/layouts/Layout.jsx`: sidebar dan topbar responsif untuk setiap role.
- `src/components/ui.jsx`: komponen UI bersama.
- `src/pages/Auth.jsx`: tampilan login, registrasi, dan lupa password.
- `src/pages/UserPages.jsx`: hero dashboard dan penyegaran form pelaporan.
- `src/pages/AdminPages.jsx`: hero dashboard admin.
- `src/pages/GovPages.jsx`: hero dashboard pemerintah.
- `src/constants.js`: penyegaran warna badge status.
- `src/context/AppContext.jsx`: warna notifikasi.
- `index.html`: metadata dan font.

## Menjalankan project
Pastikan Node.js dan npm terpasang, lalu dari folder project jalankan:

```bash
npm install
npm run dev
```

Untuk build produksi:

```bash
npm run build
```

`.env` masih menggunakan `VITE_USE_MOCK=true`; data yang tampil tetap data dummy dari service layer. Jangan ubah ke mode API sebelum base URL dan kontrak API disepakati dengan anggota backend.

## Catatan verifikasi
Source JSX/JavaScript telah diperiksa untuk kesalahan sintaks. Build Vite belum dapat dijalankan di lingkungan penyusunan ini karena instalasi dependency npm tidak berhasil selesai (timeout). Jalankan `npm install` dan `npm run build` secara lokal untuk verifikasi akhir.
