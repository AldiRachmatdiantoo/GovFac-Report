// Service layer: halaman hanya memanggil fungsi di sini.
// Saat backend siap: set VITE_USE_MOCK=false, lalu isi cabang API pada fungsi `call` (gunakan `http`).
import axios from 'axios'

const USE_MOCK = import.meta.env.VITE_USE_MOCK !== 'false'
export const http = axios.create({ baseURL: import.meta.env.VITE_API_BASE_URL })
http.interceptors.request.use(c => {
  const t = localStorage.getItem('token')
  if (t) c.headers.Authorization = `Bearer ${t}`
  return c
})

const wait = (v, ms = 400) => new Promise(r => setTimeout(() => r(structuredClone(v)), ms))
const fail = m => new Promise((_, j) => setTimeout(() => j(new Error(m)), 300))
const call = mock => (USE_MOCK ? mock() : fail('Menunggu kesepakatan API dengan tim backend.'))
const today = (d = 0) => new Date(Date.now() + d * 864e5).toISOString().slice(0, 10)
const stamp = () => new Date().toLocaleString('id-ID')
const h = (status, note) => ({ status, note, at: stamp() })

// ---------- DUMMY DATA ----------
const users = [
  { id: 1, name: 'Budi Santoso', email: 'user@demo.com', password: 'password123', role: 'user' },
  { id: 2, name: 'Admin Kota', email: 'admin@demo.com', password: 'password123', role: 'admin' },
  { id: 3, name: 'Dinas PU', email: 'gov@demo.com', password: 'password123', role: 'government' },
]
const seed = (id, title, category, location, status, userId, author, likes, x = {}) => ({
  id, title, category, location, status, userId, author, likes,
  description: `${title}. Kondisi sudah mengganggu pengguna jalan dan perlu segera ditangani oleh instansi terkait.`,
  photo: null, date: today(-id), schedule: null, signature: null, history: [h(status, 'Data contoh (dummy).')], ...x,
})
const reports = [
  seed(1, 'Jalan berlubang di Jl. Raya Darmo', 'Jalan Rusak', 'Jl. Raya Darmo, Surabaya', 'Menunggu', 1, 'Budi Santoso', [2]),
  seed(2, 'Lampu jalan padam di Jl. Ngagel', 'Penerangan Jalan', 'Jl. Ngagel, Surabaya', 'Diproses', 1, 'Budi Santoso', []),
  seed(3, 'Drainase tersumbat di Perum Wiyung', 'Drainase', 'Wiyung, Surabaya', 'Terverifikasi', 9, 'Siti Aminah', [1, 2, 3]),
  seed(4, 'Trotoar retak di Taman Bungkul', 'Fasilitas Umum', 'Taman Bungkul, Surabaya', 'Disetujui', 9, 'Siti Aminah', [1]),
  seed(5, 'Aspal amblas di Jl. Rungkut', 'Jalan Rusak', 'Rungkut, Surabaya', 'Terjadwal', 1, 'Budi Santoso', [1, 3, 9], { schedule: { date: today(7), note: 'Perbaikan oleh Dinas PU' } }),
]
const audit = []

// ---------- AUTH (SCRUM-7, 8, 9) ----------
export const login = (email, password) => call(() => {
  const u = users.find(x => x.email === email && x.password === password)
  if (!u) return fail('Email atau password salah.')
  const { password: _, ...user } = u
  return wait({ user, token: `mock-token-${u.id}` })
})
export const register = d => call(() => {
  if (users.some(x => x.email === d.email)) return fail('Email sudah terdaftar.')
  const u = { id: users.length + 10, name: d.name, email: d.email, password: d.password, role: 'user' }
  users.push(u)
  const { password: _, ...user } = u
  return wait({ user, token: `mock-token-${u.id}` })
})
export const forgotPassword = () => call(() => wait({ ok: true }))

// ---------- LAPORAN ----------
export const getReports = (f = {}) => call(() => wait(
  reports
    .filter(r => (!f.userId || r.userId === f.userId)
      && (!f.statuses || f.statuses.includes(r.status))
      && (!f.publicOnly || !['Dibatalkan', 'Ditolak AI'].includes(r.status)))
    .sort((a, b) => b.id - a.id)
))
export const getReport = id => call(() => {
  const r = reports.find(x => x.id === Number(id))
  return r ? wait(r) : fail('Laporan tidak ditemukan.')
})
export const getStats = userId => call(() => {
  const rs = reports.filter(r => !userId || r.userId === userId)
  const by = {}
  rs.forEach(r => { by[r.status] = (by[r.status] || 0) + 1 })
  return wait({ total: rs.length, by })
})

// Validasi AI (SCRUM-12): SIMULASI. Aturan asli menunggu klarifikasi/backend.
const validateAI = d => (d.description.trim().length < 20
  ? 'Deskripsi terlalu singkat. Jelaskan kerusakan minimal 20 karakter.'
  : !d.photo ? 'Foto bukti kerusakan belum dilampirkan.' : null)

export const saveReport = (d, user, id) => call(() => {
  const reason = validateAI(d)
  const status = reason ? 'Ditolak AI' : 'Menunggu'
  const note = reason ? `Ditolak validasi AI: ${reason}` : 'Lolos validasi AI. Laporan diterima.'
  let r
  if (id) {
    r = reports.find(x => x.id === Number(id))
    Object.assign(r, d, { status })
    r.history.push(h(status, `Laporan diedit. ${note}`))
  } else {
    r = { id: reports.length + 1, ...d, userId: user.id, author: user.name, date: today(), likes: [], schedule: null, signature: null, history: [h(status, note)], status }
    reports.push(r)
  }
  return wait({ report: r, ai: { ok: !reason, reason } }, 1500)
})

// Dipakai untuk batal (15), auto switch status (22), kirim ke Pemerintah (23), approval (26), jadwal (27, 28)
export const updateReport = (id, patch, note, actor) => call(() => {
  const r = reports.find(x => x.id === Number(id))
  if (!r) return fail('Laporan tidak ditemukan.')
  Object.assign(r, patch)
  r.history.push(h(r.status, note))
  if (actor) audit.unshift({ id: Date.now() + Math.random(), at: stamp(), actor: actor.name, action: note, reportId: r.id, title: r.title })
  return wait(r, 300)
})

// ---------- KOMUNITAS (SCRUM-17, 18) ----------
export const toggleLike = (id, userId) => call(() => {
  const r = reports.find(x => x.id === Number(id))
  r.likes = r.likes.includes(userId) ? r.likes.filter(x => x !== userId) : [...r.likes, userId]
  return wait(r, 150)
})

// ---------- AUDIT LOG (SCRUM-28): hanya baca, tidak ada fungsi hapus ----------
export const getAudit = () => call(() => wait(audit, 300))
