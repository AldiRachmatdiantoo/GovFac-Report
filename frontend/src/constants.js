// CATATAN: daftar status & urutan alur adalah ASUMSI SEMENTARA (belum didefinisikan di Jira). Sesuaikan dengan tim backend.
export const STATUS_STYLE = {
  'Menunggu': 'bg-amber-50 text-amber-800 ring-1 ring-amber-200',
  'Diproses': 'bg-sky-50 text-sky-800 ring-1 ring-sky-200',
  'Terverifikasi': 'bg-indigo-50 text-indigo-800 ring-1 ring-indigo-200',
  'Disetujui': 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200',
  'Terjadwal': 'bg-emerald-700 text-white',
  'Ditolak AI': 'bg-rose-50 text-rose-800 ring-1 ring-rose-200',
  'Dibatalkan': 'bg-slate-100 text-slate-600 ring-1 ring-slate-200',
}
export const STATUS_HEX = { 'Menunggu': '#D97706', 'Diproses': '#0284C7', 'Terverifikasi': '#4F46E5', 'Disetujui': '#10B981', 'Terjadwal': '#047857', 'Ditolak AI': '#E11D48', 'Dibatalkan': '#94A3B8' }
export const PIPELINE = ['Menunggu', 'Diproses', 'Terverifikasi', 'Disetujui', 'Terjadwal']
export const STEP_INFO = {
  'Menunggu': 'Lolos validasi AI, menunggu ditinjau Admin',
  'Diproses': 'Admin meninjau detail laporan',
  'Terverifikasi': 'Admin meneruskan laporan ke Pemerintah',
  'Disetujui': 'Pemerintah menyetujui dengan tanda tangan digital',
  'Terjadwal': 'Pekerjaan perbaikan dijadwalkan',
}
export const EDITABLE = ['Menunggu', 'Ditolak AI']
export const GOV_STATUSES = ['Terverifikasi', 'Disetujui', 'Terjadwal']
export const CATEGORIES = ['Jalan Rusak', 'Drainase', 'Penerangan Jalan', 'Fasilitas Umum', 'Lainnya']
export const HOME = { user: '/user/dashboard', admin: '/admin/dashboard', government: '/government/dashboard' }
export const ROLE_LABEL = { user: 'Masyarakat', admin: 'Admin', government: 'Pemerintah' }
