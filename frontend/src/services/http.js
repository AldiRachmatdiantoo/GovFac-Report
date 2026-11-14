// Klien HTTP untuk API Laravel.
// Sesi memakai cookie httpOnly: frontend TIDAK pernah memegang token. Yang dikirim hanya
// cookie (withCredentials) dan header X-XSRF-TOKEN yang nilainya dibaca dari cookie XSRF-TOKEN.
import axios from 'axios'

export const http = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1',
  withCredentials: true,
  withXSRFToken: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
  headers: { Accept: 'application/json' },
})

const STATUS_MESSAGE = {
  403: 'Anda tidak berhak melakukan tindakan ini.',
  404: 'Data tidak ditemukan.',
  413: 'Ukuran berkas terlalu besar.',
  429: 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.',
}

// Ubah error axios menjadi Error biasa: `message` siap ditampilkan,
// `status` kode HTTP, `fields` pesan validasi per field (422).
export function toError(err) {
  if (err?.isApiError) return err
  const res = err?.response
  const fields = {}
  Object.entries(res?.data?.errors || {}).forEach(([k, v]) => { fields[k] = Array.isArray(v) ? v[0] : String(v) })
  let message
  if (!res) message = 'Tidak dapat terhubung ke server. Pastikan backend sedang berjalan.'
  else if (res.status === 422) message = Object.values(fields)[0] || res.data?.message || 'Data yang dikirim tidak valid.'
  else if (res.status >= 500) message = 'Terjadi kesalahan pada server. Coba lagi nanti.'
  else message = (res.status !== 429 && res.data?.message) || STATUS_MESSAGE[res.status] || 'Permintaan gagal diproses.'
  const e = new Error(message)
  e.isApiError = true
  e.status = res?.status
  e.fields = fields
  return e
}

// Access token hanya berlaku 15 menit. Saat mendapat 401, tukar sekali lewat /auth/refresh
// lalu ulangi permintaan. Bila refresh gagal, sesi dianggap berakhir.
const NO_REFRESH = ['/auth/login', '/auth/register', '/auth/refresh', '/auth/logout', '/auth/forgot-password', '/auth/reset-password']
let refreshing = null
let onSessionExpired = () => {}
export const setSessionExpiredHandler = fn => { onSessionExpired = fn }

http.interceptors.response.use(r => r, async err => {
  const { config, response } = err
  const canRefresh = response?.status === 401 && config && !config._retried && !NO_REFRESH.some(p => config.url.startsWith(p))
  if (canRefresh) {
    config._retried = true
    try {
      refreshing ??= http.post('/auth/refresh').finally(() => { refreshing = null })
      await refreshing
    } catch {
      onSessionExpired()
      throw toError(err)
    }
    return http(config)
  }
  throw toError(err)
})
