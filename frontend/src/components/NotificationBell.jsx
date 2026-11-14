import { useEffect, useRef, useState } from 'react'
import { useLocation } from 'react-router-dom'
import { Bell, CheckCheck, Loader2 } from 'lucide-react'
import { useApp } from '../context/AppContext'
import { cx, fmtDateTime } from './ui'
import { getNotifications, getUnreadCount, markAllNotificationsRead, markNotificationRead } from '../services'

// Lonceng notifikasi (PBI-3) untuk semua role. Jumlah belum dibaca disegarkan tiap pindah halaman.
export default function NotificationBell() {
  const { toast } = useApp()
  const { pathname } = useLocation()
  const [count, setCount] = useState(0)
  const [open, setOpen] = useState(false)
  const [items, setItems] = useState(null)
  const box = useRef(null)

  useEffect(() => { getUnreadCount().then(setCount).catch(() => {}) }, [pathname])
  useEffect(() => { setOpen(false) }, [pathname])
  useEffect(() => {
    if (!open) return
    setItems(null)
    getNotifications().then(r => setItems(r.items)).catch(e => { toast(e.message, 'error'); setOpen(false) })
    const out = e => { if (!box.current?.contains(e.target)) setOpen(false) }
    const esc = e => e.key === 'Escape' && setOpen(false)
    document.addEventListener('mousedown', out)
    window.addEventListener('keydown', esc)
    return () => { document.removeEventListener('mousedown', out); window.removeEventListener('keydown', esc) }
  }, [open])

  const read = async n => {
    if (n.readAt) return
    try {
      await markNotificationRead(n.id)
      setItems(list => list.map(x => (x.id === n.id ? { ...x, readAt: new Date().toISOString() } : x)))
      setCount(c => Math.max(0, c - 1))
    } catch (e) { toast(e.message, 'error') }
  }
  const readAll = async () => {
    try {
      await markAllNotificationsRead()
      setItems(list => list?.map(x => ({ ...x, readAt: x.readAt || new Date().toISOString() })) ?? list)
      setCount(0)
    } catch (e) { toast(e.message, 'error') }
  }

  return (
    <div className="relative" ref={box}>
      <button onClick={() => setOpen(o => !o)} aria-expanded={open} aria-label={count ? `Notifikasi, ${count} belum dibaca` : 'Notifikasi'} className="relative rounded-ctl border border-line bg-white p-2 text-muted transition hover:text-brand">
        <Bell size={17} />
        {count > 0 && <span className="absolute -right-1 -top-1 min-w-4 rounded-full bg-rose-500 px-1 text-center text-[10px] font-bold leading-4 text-white">{count > 99 ? '99+' : count}</span>}
      </button>
      {open && (
        <div role="dialog" aria-label="Notifikasi" className="anim-pop absolute right-0 top-full z-30 mt-2 w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-card border border-line bg-white shadow-pop">
          <div className="flex items-center justify-between border-b border-line px-4 py-3">
            <p className="text-sm font-semibold text-ink">Notifikasi</p>
            {count > 0 && items?.length > 0 && <button onClick={readAll} className="inline-flex items-center gap-1 text-xs font-semibold text-brand hover:underline"><CheckCheck size={14} />Tandai semua dibaca</button>}
          </div>
          {!items ? <div className="flex justify-center py-8"><Loader2 className="h-5 w-5 animate-spin text-brand" /></div>
            : !items.length ? <p className="px-4 py-8 text-center text-sm text-muted">Belum ada notifikasi.</p>
            : <ul className="max-h-96 divide-y divide-line overflow-y-auto">{items.map(n => (
              <li key={n.id}>
                <button onClick={() => read(n)} className={cx('flex w-full gap-3 px-4 py-3 text-left transition hover:bg-canvas', !n.readAt && 'bg-brand-50/50')}>
                  <span className={cx('mt-1.5 h-2 w-2 shrink-0 rounded-full', n.readAt ? 'bg-transparent' : 'bg-brand')} />
                  <span className="min-w-0">
                    <span className="block text-[13px] font-semibold text-ink">{n.title}</span>
                    <span className="block text-[13px] leading-relaxed text-muted">{n.message}</span>
                    <span className="mt-0.5 block text-xs text-muted/70">{fmtDateTime(n.at)}</span>
                  </span>
                </button>
              </li>
            ))}</ul>}
        </div>
      )}
    </div>
  )
}
