import { createContext, useContext, useEffect, useState } from 'react'
import { me, logout } from '../services'
import { setSessionExpiredHandler } from '../services/http'

const Ctx = createContext(null)
export const useApp = () => useContext(Ctx)

const TOAST = { success: 'bg-brand', error: 'bg-rose-600', info: 'bg-sky-700' }

export function AppProvider({ children }) {
  // Sesi ada di cookie httpOnly, jadi tidak ada token maupun data user di localStorage.
  // `ready` false sampai /auth/me menjawab siapa yang sedang login.
  const [user, setUser] = useState(null)
  const [ready, setReady] = useState(false)
  const [toasts, setToasts] = useState([])

  useEffect(() => {
    setSessionExpiredHandler(() => setUser(null))
    me().then(setUser).catch(() => setUser(null)).finally(() => setReady(true))
  }, [])

  const toast = (msg, type = 'success') => {
    const id = Math.random()
    setToasts(t => [...t, { id, msg, type }])
    setTimeout(() => setToasts(t => t.filter(x => x.id !== id)), 3500)
  }
  const signIn = ({ user }) => setUser(user)
  // Logout di sisi klien tetap jalan walau permintaan ke server gagal.
  const signOut = async () => {
    try { await logout() } catch { /* cookie dibersihkan server bila terjangkau */ }
    setUser(null)
  }

  return (
    <Ctx.Provider value={{ user, ready, signIn, signOut, toast }}>
      {children}
      <div role="status" aria-live="polite" className="fixed inset-x-4 bottom-4 z-[60] space-y-2 sm:left-auto sm:right-6 sm:w-80">
        {toasts.map(t => (
          <div key={t.id} className={`anim-pop rounded-ctl px-4 py-3 text-sm font-medium text-white shadow-pop ${TOAST[t.type]}`}>{t.msg}</div>
        ))}
      </div>
    </Ctx.Provider>
  )
}
