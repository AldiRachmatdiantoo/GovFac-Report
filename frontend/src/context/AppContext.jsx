import { createContext, useContext, useState } from 'react'

const Ctx = createContext(null)
export const useApp = () => useContext(Ctx)

const TOAST = { success: 'bg-brand', error: 'bg-rose-600', info: 'bg-sky-700' }

export function AppProvider({ children }) {
  const [user, setUser] = useState(() => JSON.parse(localStorage.getItem('user') || 'null'))
  const [toasts, setToasts] = useState([])

  const toast = (msg, type = 'success') => {
    const id = Math.random()
    setToasts(t => [...t, { id, msg, type }])
    setTimeout(() => setToasts(t => t.filter(x => x.id !== id)), 3500)
  }
  const signIn = ({ user, token }) => {
    localStorage.setItem('user', JSON.stringify(user))
    localStorage.setItem('token', token)
    setUser(user)
  }
  const signOut = () => { localStorage.clear(); setUser(null) }

  return (
    <Ctx.Provider value={{ user, signIn, signOut, toast }}>
      {children}
      <div role="status" aria-live="polite" className="fixed inset-x-4 bottom-4 z-[60] space-y-2 sm:left-auto sm:right-6 sm:w-80">
        {toasts.map(t => (
          <div key={t.id} className={`anim-pop rounded-ctl px-4 py-3 text-sm font-medium text-white shadow-pop ${TOAST[t.type]}`}>{t.msg}</div>
        ))}
      </div>
    </Ctx.Provider>
  )
}
