// Implementasi API asli untuk endpoint yang sudah ada di backend (Sprint 1: PBI-1, 2, 3, 5, 7).
// Tiap fungsi mengembalikan bentuk data yang sama dengan versi mock di ./mock.js,
// sehingga halaman tidak perlu tahu sumber datanya.
import { http } from './http'

// ---------- Adapter: bentuk respons backend -> bentuk yang dipakai halaman ----------
const toHistory = h => ({ status: h.to_status, from: h.from_status, note: h.note, reason: h.reason_category, actor: h.actor?.name ?? null, at: h.created_at })
const toReport = (r, history = []) => ({
  id: r.id,
  code: r.code,
  title: r.title,
  description: r.description,
  status: r.status,
  criticality: r.criticality ?? null,
  tags: r.tags ?? [],
  photos: r.photos ?? [],
  likesCount: r.likes_count ?? 0,
  userId: r.reporter?.id ?? null,
  author: r.reporter?.name ?? null,
  date: r.created_at,
  location: null,
  schedule: null,
  signature: null,
  history: history.map(toHistory),
})
const toMeta = m => ({ page: m.current_page, lastPage: m.last_page, total: m.total, perPage: m.per_page })

// ---------- AUTH (PBI-1, PBI-2) ----------
// Cookie XSRF-TOKEN dipasang bersama cookie sesi dan bisa dibaca JavaScript. Bila tidak ada,
// pengunjung pasti belum login, jadi tidak perlu bertanya ke server (menghemat /auth/me dan /auth/refresh).
const hasSessionCookie = () => document.cookie.split('; ').some(c => c.startsWith('XSRF-TOKEN='))
export const me = () => (hasSessionCookie()
  ? http.get('/auth/me').then(r => r.data.data)
  : Promise.reject(Object.assign(new Error('Belum login.'), { status: 401, fields: {} })))
export const login = (email, password) => http.post('/auth/login', { email, password }).then(r => ({ user: r.data.data.user }))
// Registrasi tidak memasang cookie sesi, jadi dilanjutkan dengan login.
export const register = async d => {
  await http.post('/auth/register', { name: d.name, email: d.email, password: d.password, password_confirmation: d.confirm })
  return login(d.email, d.password)
}
export const logout = () => http.post('/auth/logout').then(() => null)
export const forgotPassword = email => http.post('/auth/forgot-password', { email }).then(r => r.data)
export const resetPassword = d => http.post('/auth/reset-password', { token: d.token, email: d.email, password: d.password, password_confirmation: d.confirm }).then(r => r.data)

// ---------- LAPORAN (PBI-5, PBI-7) ----------
export const getMyReports = (_user, { status, page = 1, perPage = 10 } = {}) =>
  http.get('/reports/mine', { params: { status: status || undefined, page, per_page: perPage } })
    .then(r => ({ items: r.data.data.map(x => toReport(x)), meta: toMeta(r.data.meta) }))

// Belum ada endpoint statistik: hitung dari halaman pertama (maks. 50 laporan terbaru).
export const getMyStats = async user => {
  const { items, meta } = await getMyReports(user, { perPage: 50 })
  const by = {}
  items.forEach(r => { by[r.status] = (by[r.status] || 0) + 1 })
  return { total: meta.total, by }
}

export const getReport = async id => {
  const [detail, history] = await Promise.all([http.get(`/reports/${id}`), http.get(`/reports/${id}/history`)])
  return toReport(detail.data.data, history.data.data)
}

export const createReport = (d) => {
  const body = new FormData()
  body.append('title', d.title.trim())
  body.append('description', d.description.trim())
  d.photos.forEach(f => body.append('photos[]', f))
  d.tags.forEach(t => body.append('tags[]', t))
  return http.post('/reports', body).then(r => toReport(r.data.data))
}

export const suggestTags = q => http.get('/tags', { params: { q: q || undefined } }).then(r => r.data.data.map(t => t.name))

// ---------- NOTIFIKASI (PBI-3) ----------
const toNotification = n => ({ id: n.id, type: n.type, title: n.data?.title ?? 'Notifikasi', message: n.data?.message ?? '', readAt: n.read_at, at: n.created_at })
export const getNotifications = ({ page = 1, perPage = 10 } = {}) =>
  http.get('/notifications', { params: { page, per_page: perPage } }).then(r => ({ items: r.data.data.map(toNotification), meta: toMeta(r.data.meta) }))
export const getUnreadCount = () => http.get('/notifications/unread-count').then(r => r.data.data.count)
export const markNotificationRead = id => http.patch(`/notifications/${id}/read`).then(() => null)
export const markAllNotificationsRead = () => http.patch('/notifications/read-all').then(() => null)
