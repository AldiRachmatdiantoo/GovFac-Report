// Nilai status mengikuti enum backend (backend/app/Modules/Shared/Enums/ReportStatus.php).
// Yang tampil ke pengguna adalah STATUS_LABEL; kunci status jangan diubah tanpa menyamakan dengan backend.
export const STATUS_LABEL = {
  ai_validation: 'Validasi AI',
  admin_review: 'Ditinjau Admin',
  approved: 'Disetujui Admin',
  forwarded: 'Diteruskan',
  gov_approved: 'Disetujui Pemerintah',
  scheduled: 'Terjadwal',
  rejected: 'Ditolak',
  cancelled: 'Dibatalkan',
}
export const STATUS_STYLE = {
  ai_validation: 'bg-violet-50 text-violet-800 ring-1 ring-violet-200',
  admin_review: 'bg-amber-50 text-amber-800 ring-1 ring-amber-200',
  approved: 'bg-sky-50 text-sky-800 ring-1 ring-sky-200',
  forwarded: 'bg-indigo-50 text-indigo-800 ring-1 ring-indigo-200',
  gov_approved: 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200',
  scheduled: 'bg-emerald-700 text-white',
  rejected: 'bg-rose-50 text-rose-800 ring-1 ring-rose-200',
  cancelled: 'bg-slate-100 text-slate-600 ring-1 ring-slate-200',
}
export const STATUS_HEX = { ai_validation: '#7C3AED', admin_review: '#D97706', approved: '#0284C7', forwarded: '#4F46E5', gov_approved: '#10B981', scheduled: '#047857', rejected: '#E11D48', cancelled: '#94A3B8' }
// Alur normal sesuai tabel transisi backend (ReportStatusTransitions). rejected dan cancelled keluar dari alur.
export const PIPELINE = ['ai_validation', 'admin_review', 'approved', 'forwarded', 'gov_approved', 'scheduled']
export const STEP_INFO = {
  ai_validation: 'Laporan diperiksa otomatis oleh AI',
  admin_review: 'Lolos validasi AI, menunggu ditinjau Admin',
  approved: 'Admin menyetujui laporan',
  forwarded: 'Admin meneruskan laporan ke Pemerintah',
  gov_approved: 'Pemerintah menyetujui dengan tanda tangan digital',
  scheduled: 'Pekerjaan perbaikan dijadwalkan',
}
export const REASON_LABEL = { duplicate: 'Duplikat', unclear_evidence: 'Bukti kurang jelas', not_public_facility: 'Bukan fasilitas umum', incomplete: 'Tidak lengkap', other: 'Lainnya' }
export const CRITICALITY_LABEL = { low: 'Rendah', medium: 'Sedang', high: 'Tinggi' }
export const EDITABLE = ['admin_review', 'rejected']
export const GOV_STATUSES = ['forwarded', 'gov_approved', 'scheduled']

// Batas formulir laporan, sama dengan StoreReportRequest di backend.
export const REPORT_LIMITS = { title: 100, description: 2000, photos: 5, photoBytes: 5 * 1024 * 1024, tags: 5, tagLength: 30 }

export const HOME = { user: '/user/dashboard', admin: '/admin/dashboard', government: '/government/dashboard' }
export const ROLE_LABEL = { user: 'Masyarakat', admin: 'Admin', government: 'Pemerintah' }
