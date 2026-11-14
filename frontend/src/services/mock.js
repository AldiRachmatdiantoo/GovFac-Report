// Data dummy di memori. Dipakai untuk (a) mode demo penuh (VITE_USE_MOCK=true) dan
// (b) fitur yang endpoint backend-nya belum ada. Bentuk datanya sama dengan ./api.js,
// dan nilai status mengikuti enum backend (lihat constants.js).

const wait = (v, ms = 400) => new Promise(r => setTimeout(() => r(structuredClone(v)), ms))
const fail = (m, status) => new Promise((_, j) => setTimeout(() => j(Object.assign(new Error(m), { status, fields: {} })), 300))
const today = (d = 0) => new Date(Date.now() + d * 864e5).toISOString().slice(0, 10)
const now = () => new Date().toISOString()
const stamp = () => new Date().toLocaleString('id-ID')
const h = (status, note, actor = null) => ({ status, from: null, note, reason: null, actor, at: now() })

// ---------- DUMMY DATA ----------
const users = [
  { id: 1, name: 'Budi Santoso', email: 'user@demo.com', password: 'password123', role: 'user' },
  { id: 2, name: 'Admin Kota', email: 'admin@demo.com', password: 'password123', role: 'admin' },
  { id: 3, name: 'Dinas PU', email: 'gov@demo.com', password: 'password123', role: 'government' },
]
const seed = (id, title, tag, location, status, userId, author, likes, x = {}) => ({
  id, code: `GFR-DEMO-${String(id).padStart(4, '0')}`, title, tags: [tag], location, status, userId, author, likes, likesCount: likes.length,
  description: `${title}. Kondisi sudah mengganggu pengguna jalan dan perlu segera ditangani oleh instansi terkait.`,
  criticality: null, photos: [], date: today(-id), schedule: null, signature: null, history: [h(status, 'Data contoh (dummy).')], ...x,
})
const reports = [
  seed(1, 'Jalan berlubang di Jl. Raya Darmo', 'jalan_rusak', 'Jl. Raya Darmo, Surabaya', 'admin_review', 1, 'Budi Santoso', [2]),
  seed(2, 'Lampu jalan padam di Jl. Ngagel', 'penerangan_jalan', 'Jl. Ngagel, Surabaya', 'approved', 1, 'Budi Santoso', []),
  seed(3, 'Drainase tersumbat di Perum Wiyung', 'drainase', 'Wiyung, Surabaya', 'forwarded', 9, 'Siti Aminah', [1, 2, 3]),
  seed(4, 'Trotoar retak di Taman Bungkul', 'fasilitas_umum', 'Taman Bungkul, Surabaya', 'gov_approved', 9, 'Siti Aminah', [1]),
  seed(5, 'Aspal amblas di Jl. Rungkut', 'jalan_rusak', 'Rungkut, Surabaya', 'scheduled', 1, 'Budi Santoso', [1, 3, 9], { schedule: { date: today(7), note: 'Perbaikan oleh Dinas PU' } }),
]
const audit = []
const paginate = (list, page, perPage) => ({
  items: list.slice((page - 1) * perPage, page * perPage),
  meta: { page, lastPage: Math.max(1, Math.ceil(list.length / perPage)), total: list.length, perPage },
})

// ---------- AUTH ----------
// Sesi demo disimpan di localStorage agar bertahan saat halaman dimuat ulang.
const SESSION = 'mock-user'
export const me = () => {
  const u = JSON.parse(localStorage.getItem(SESSION) || 'null')
  return u ? wait(u, 100) : fail('Belum login.', 401)
}
export const login = (email, password) => {
  const u = users.find(x => x.email === email && x.password === password)
  if (!u) return fail('Email atau password salah.', 401)
  const { password: _, ...user } = u
  localStorage.setItem(SESSION, JSON.stringify(user))
  return wait({ user })
}
export const register = d => {
  if (users.some(x => x.email === d.email)) return fail('Email sudah terdaftar.', 422)
  users.push({ id: users.length + 10, name: d.name, email: d.email, password: d.password, role: 'user' })
  return login(d.email, d.password)
}
export const logout = () => { localStorage.removeItem(SESSION); return wait(null, 100) }
export const forgotPassword = () => wait({ ok: true })
export const resetPassword = () => wait({ ok: true })

// ---------- LAPORAN ----------
export const getReports = (f = {}) => wait(
  reports
    .filter(r => (!f.userId || r.userId === f.userId)
      && (!f.statuses || f.statuses.includes(r.status))
      && (!f.publicOnly || !['cancelled', 'rejected', 'ai_validation'].includes(r.status)))
    .sort((a, b) => b.id - a.id)
)
export const getMyReports = (user, { status, page = 1, perPage = 10 } = {}) =>
  wait(paginate(reports.filter(r => r.userId === user.id && (!status || r.status === status)).sort((a, b) => b.id - a.id), page, perPage))
export const getReport = id => {
  const r = reports.find(x => x.id === Number(id))
  return r ? wait(r) : fail('Laporan tidak ditemukan.', 404)
}
const stats = rs => {
  const by = {}
  rs.forEach(r => { by[r.status] = (by[r.status] || 0) + 1 })
  return wait({ total: rs.length, by })
}
export const getStats = () => stats(reports)
export const getMyStats = user => stats(reports.filter(r => r.userId === user.id))

// Validasi AI: SIMULASI. Di backend ini PBI-6 (belum dikerjakan); laporan asli berhenti di ai_validation.
const validateAI = d => (d.description.trim().length < 20 ? 'Deskripsi terlalu singkat. Jelaskan kerusakan minimal 20 karakter.' : null)
const aiResult = d => {
  const reason = validateAI(d)
  return { status: reason ? 'rejected' : 'admin_review', note: reason ? `Ditolak validasi AI: ${reason}` : 'Lolos validasi AI. Menunggu ditinjau Admin.' }
}
const toPhotos = files => files.map((f, i) => ({ id: `${Date.now()}-${i}`, url: URL.createObjectURL(f) }))

export const createReport = (d, user) => {
  const { status, note } = aiResult(d)
  const id = reports.length + 1
  const r = {
    id, code: `GFR-DEMO-${String(id).padStart(4, '0')}`, title: d.title.trim(), description: d.description.trim(), tags: d.tags, photos: toPhotos(d.photos),
    location: null, criticality: null, userId: user.id, author: user.name, date: now(), likes: [], likesCount: 0, schedule: null, signature: null, history: [h(status, note)], status,
  }
  reports.push(r)
  return wait(r, 1200)
}
// Edit laporan: belum ada endpoint-nya di backend, hanya tersedia di mode demo.
export const updateMyReport = (id, d) => {
  const r = reports.find(x => x.id === Number(id))
  if (!r) return fail('Laporan tidak ditemukan.', 404)
  const { status, note } = aiResult(d)
  Object.assign(r, { title: d.title.trim(), description: d.description.trim(), tags: d.tags, status })
  if (d.photos.length) r.photos = toPhotos(d.photos)
  r.history.push(h(status, `Laporan diedit. ${note}`))
  return wait(r, 1200)
}
const TAGS = ['jalan_rusak', 'drainase', 'penerangan_jalan', 'fasilitas_umum', 'trotoar', 'jembatan', 'taman']
export const suggestTags = q => wait(TAGS.filter(t => t.startsWith((q || '').toLowerCase())).slice(0, 8), 150)

// Dipakai untuk batal, verifikasi Admin, kirim ke Pemerintah, approval, dan penjadwalan.
export const updateReport = (id, patch, note, actor) => {
  const r = reports.find(x => x.id === Number(id))
  if (!r) return fail('Laporan tidak ditemukan.', 404)
  Object.assign(r, patch)
  r.history.push(h(r.status, note, actor?.name ?? null))
  if (actor) audit.unshift({ id: Date.now() + Math.random(), at: stamp(), actor: actor.name, action: note, reportId: r.id, title: r.title })
  return wait(r, 300)
}

// ---------- KOMUNITAS ----------
export const toggleLike = (id, userId) => {
  const r = reports.find(x => x.id === Number(id))
  r.likes = r.likes.includes(userId) ? r.likes.filter(x => x !== userId) : [...r.likes, userId]
  r.likesCount = r.likes.length
  return wait(r, 150)
}

// ---------- AUDIT LOG: hanya baca, tidak ada fungsi hapus ----------
export const getAudit = () => wait(audit, 300)

// ---------- NOTIFIKASI ----------
const notifications = [{ id: 'demo-1', type: 'login', title: 'Login berhasil', message: 'Akun Anda baru saja masuk (mode demo).', readAt: null, at: now() }]
export const getNotifications = ({ page = 1, perPage = 10 } = {}) => wait(paginate(notifications, page, perPage), 200)
export const getUnreadCount = () => wait(notifications.filter(n => !n.readAt).length, 100)
export const markNotificationRead = id => { const n = notifications.find(x => x.id === id); if (n) n.readAt = now(); return wait(null, 100) }
export const markAllNotificationsRead = () => { notifications.forEach(n => { n.readAt ??= now() }); return wait(null, 100) }
