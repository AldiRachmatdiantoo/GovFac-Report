import { Fragment, useEffect, useState } from 'react'
import { NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom'
import { LayoutDashboard, FilePlus, FileText, Users, Inbox, BadgeCheck, CalendarClock, ScrollText, Menu, LogOut, Construction, X, ChevronRight } from 'lucide-react'
import { useApp } from '../context/AppContext'
import { getReports } from '../services'
import { cx } from '../components/ui'
import NotificationBell from '../components/NotificationBell'
import { ROLE_LABEL } from '../constants'

const NAV = {
  user: [['/user/dashboard', 'Dashboard', LayoutDashboard], ['/user/reports/create', 'Buat laporan', FilePlus], ['/user/reports', 'Laporan saya', FileText], ['/user/community', 'Komunitas', Users]],
  admin: [['/admin/dashboard', 'Dashboard', LayoutDashboard], ['/admin/reports', 'Laporan masuk', Inbox]],
  government: [['/government/dashboard', 'Dashboard', LayoutDashboard], ['/government/verified-reports', 'Laporan terverifikasi', BadgeCheck], ['/government/schedules', 'Penjadwalan', CalendarClock], ['/government/audit-log', 'Audit log', ScrollText]],
}
const initials = n => (n || '').split(' ').map(x => x[0]).slice(0, 2).join('').toUpperCase()
const Avatar = ({ name, cls = 'h-9 w-9' }) => <div className={cx('flex shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-bold text-brand', cls)}>{initials(name)}</div>

export default function Layout() {
  const { user, signOut } = useApp()
  const [open, setOpen] = useState(false)
  const [pending, setPending] = useState(0)
  const { pathname } = useLocation()
  const nav = useNavigate()
  const items = NAV[user.role]

  useEffect(() => {
    setOpen(false)
    if (user.role === 'admin') getReports({ statuses: ['admin_review'] }).then(r => setPending(r.length)).catch(() => {})
  }, [pathname, user.role])

  // Breadcrumb kontekstual: halaman utama, atau "Menu induk > #id" untuk detail/edit
  const cur = items.find(([to]) => to === pathname)
  const parent = cur ? null : items.filter(([to]) => pathname.startsWith(to + '/')).sort((a, b) => b[0].length - a[0].length)[0]
  const id = pathname.split('/')[3]
  const crumb = cur ? [cur[1]] : parent ? [parent[1], pathname.endsWith('/edit') ? `Edit #${id}` : `#${id}`] : ['Detail laporan']

  return (
    <div className="min-h-screen bg-canvas lg:flex">
      {open && <button aria-label="Tutup navigasi" className="anim-fade fixed inset-0 z-30 bg-ink/40 backdrop-blur-sm lg:hidden" onClick={() => setOpen(false)} />}
      <aside className={cx('fixed inset-y-0 left-0 z-40 flex w-[248px] flex-col border-r border-line bg-white transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 print:hidden', open ? 'translate-x-0' : '-translate-x-full')}>
        <div className="flex h-16 items-center gap-2.5 px-5">
          <div className="flex h-9 w-9 items-center justify-center rounded-ctl bg-brand text-white"><Construction size={19} /></div>
          <p className="text-[15px] font-bold tracking-tight text-ink">Lapor Infrastruktur</p>
          <button className="ml-auto rounded-lg p-1.5 text-muted hover:bg-canvas lg:hidden" onClick={() => setOpen(false)} aria-label="Tutup menu"><X size={18} /></button>
        </div>
        <nav className="flex-1 space-y-1 px-3 py-4" aria-label="Navigasi utama">
          {items.map(([to, label, Icon]) => (
            <NavLink key={to} to={to} end className={({ isActive }) => cx('flex items-center gap-3 rounded-ctl px-3 py-2.5 text-sm font-semibold transition', isActive ? 'bg-brand-50 text-brand' : 'text-muted hover:bg-canvas hover:text-ink')}>
              <Icon size={18} /><span className="flex-1">{label}</span>
              {user.role === 'admin' && label === 'Laporan masuk' && pending > 0 && <span className="rounded-full bg-rose-500 px-2 py-0.5 text-[11px] font-bold text-white">{pending}</span>}
            </NavLink>
          ))}
        </nav>
        <div className="border-t border-line p-3">
          <div className="flex items-center gap-3 px-2 py-2"><Avatar name={user.name} /><div className="min-w-0"><p className="truncate text-[13px] font-semibold text-ink">{user.name}</p><p className="text-xs text-muted">{ROLE_LABEL[user.role]}</p></div></div>
          <button onClick={async () => { await signOut(); nav('/login') }} className="flex w-full items-center gap-2.5 rounded-ctl px-3 py-2 text-[13px] font-semibold text-muted transition hover:bg-rose-50 hover:text-rose-600"><LogOut size={16} />Keluar</button>
        </div>
      </aside>
      <div className="min-w-0 flex-1">
        <header className="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-line bg-white/85 px-4 backdrop-blur sm:px-6 lg:px-8 print:hidden">
          <div className="flex min-w-0 items-center gap-3">
            <button className="rounded-ctl border border-line bg-white p-2 text-ink lg:hidden" onClick={() => setOpen(true)} aria-label="Buka menu"><Menu size={18} /></button>
            <nav aria-label="Breadcrumb" className="flex min-w-0 items-center gap-1.5 text-sm">
              {crumb.map((c, i) => <Fragment key={c}>{i > 0 && <ChevronRight size={14} className="shrink-0 text-muted" />}<span className={cx('truncate', i === crumb.length - 1 ? 'font-semibold text-ink' : 'text-muted')}>{c}</span></Fragment>)}
            </nav>
          </div>
          <div className="flex items-center gap-3">
            <NotificationBell />
            <Avatar name={user.name} cls="h-8 w-8" />
          </div>
        </header>
        <main className="mx-auto w-full max-w-[1200px] p-4 sm:p-6 lg:p-8"><Outlet /></main>
      </div>
    </div>
  )
}
