// Service layer: halaman hanya memanggil fungsi di sini.
//
// VITE_USE_MOCK=true  -> mode demo penuh, semua data dari ./mock.js (tanpa backend).
// VITE_USE_MOCK=false -> endpoint yang SUDAH ada di backend memakai ./api.js;
//                        fitur yang endpoint-nya BELUM ada tetap memakai ./mock.js.
//
// Saat backend menambah endpoint: tulis fungsinya di api.js dengan bentuk data yang sama,
// lalu pindahkan ekspornya dari blok "masih mock" ke blok "sudah tersambung".
import * as api from './api'
import * as mock from './mock'

export const USE_MOCK = import.meta.env.VITE_USE_MOCK !== 'false'
const src = USE_MOCK ? mock : api

// ---------- Sudah tersambung ke backend (Sprint 1) ----------
// PBI-1 registrasi & login, PBI-2 lupa password
export const { me, login, register, logout, forgotPassword, resetPassword } = src
// PBI-5 formulir laporan, PBI-7 status laporan
export const { getMyReports, getMyStats, createReport, suggestTags } = src
// PBI-3 notifikasi
export const { getNotifications, getUnreadCount, markNotificationRead, markAllNotificationsRead } = src

// Detail laporan: endpoint-nya ada, tetapi daftar laporan untuk Admin dan Pemerintah masih mock,
// jadi hanya pelapor (role user) yang membaca detail dari API.
export const getReport = (id, role) => (!USE_MOCK && role === 'user' ? api.getReport(id) : mock.getReport(id))

// ---------- Masih mock: endpoint belum ada di backend ----------
export const { getReports, getStats, updateReport, updateMyReport, toggleLike, getAudit } = mock

// Fitur pelapor yang belum punya endpoint disembunyikan saat memakai API asli.
export const FEATURES = { editReport: USE_MOCK, cancelReport: USE_MOCK }
