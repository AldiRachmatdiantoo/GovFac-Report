import { useState, useEffect, useCallback, useRef } from 'react'
import { Link } from 'react-router-dom'
import { Loader2, X, Inbox, AlertTriangle, Search, MapPin, CalendarDays, Clock, Loader, ShieldCheck, BadgeCheck, CalendarCheck, XCircle, Ban, Check, Send, ChevronLeft, ChevronRight } from 'lucide-react'
import { STATUS_STYLE, STATUS_HEX, STATUS_LABEL, PIPELINE } from '../constants'

export const cx = (...a) => a.filter(Boolean).join(' ')

export function useAsync(fn, deps = []) {
  const [s, set] = useState({ loading: true, data: null, error: null })
  // Hanya jawaban dari permintaan terakhir yang dipakai; jawaban lama yang datang terlambat
  // (misalnya setelah filter diganti) diabaikan agar tidak menimpa data yang lebih baru.
  const seq = useRef(0)
  const run = useCallback(() => {
    const id = ++seq.current
    set(p => ({ ...p, loading: true, error: null }))
    fn().then(data => { if (id === seq.current) set({ loading: false, data, error: null }) })
      .catch(e => { if (id === seq.current) set({ loading: false, data: null, error: e.message }) })
  }, deps)
  useEffect(() => { run() }, [run])
  return { ...s, reload: run }
}

export function Button({ variant = 'primary', loading, className, children, ...p }) {
  const v = {
    primary: 'bg-brand text-white hover:bg-brand-hover',
    outline: 'border border-slate-200 bg-white text-slate-700 hover:border-teal-200 hover:bg-teal-50 hover:text-teal-800',
    danger: 'bg-rose-600 text-white hover:bg-rose-700',
  }[variant]
  return <button {...p} disabled={loading || p.disabled} className={cx('inline-flex items-center justify-center gap-2 rounded-ctl px-4 py-2.5 text-[13px] font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50', v, className)}>{loading && <Loader2 className="h-4 w-4 animate-spin" />}{children}</button>
}

export function Field({ label, error, hint, required, icon: Icon, right, as: T = 'input', className, ...p }) {
  return <label className="block text-[13px]"><span className="mb-1.5 block font-semibold text-ink">{label}{required && <span className="ml-0.5 text-rose-600" aria-hidden="true">*</span>}</span><div className="relative">{Icon && <Icon size={16} className="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted" />}<T {...p} aria-invalid={!!error} className={cx('w-full rounded-ctl border bg-white px-3.5 py-2.5 text-sm text-ink outline-none transition placeholder:text-slate-400 focus:border-brand focus:ring-3 focus:ring-brand/15', Icon && 'pl-10', right && 'pr-11', error ? 'border-rose-400' : 'border-line', className)} />{right && <span className="absolute right-1.5 top-1/2 -translate-y-1/2">{right}</span>}</div>{error ? <span className="mt-1.5 block text-xs font-medium text-rose-600">{error}</span> : hint && <span className="mt-1.5 block text-xs text-muted">{hint}</span>}</label>
}
export function Modal({ open, title, onClose, children }) {
  useEffect(() => {
    if (!open) return
    const k = e => e.key === 'Escape' && onClose?.()
    window.addEventListener('keydown', k)
    return () => window.removeEventListener('keydown', k)
  }, [open])
  if (!open) return null
  return <div className="anim-fade fixed inset-0 z-50 flex items-end justify-center bg-ink/50 backdrop-blur-sm sm:items-center sm:p-4" onClick={onClose}><div role="dialog" aria-modal="true" aria-label={title} className="anim-pop w-full max-w-md rounded-t-sheet bg-white p-5 shadow-pop sm:rounded-sheet sm:p-6" onClick={e => e.stopPropagation()}><div className="mb-4 flex items-center justify-between"><h3 className="text-base font-semibold text-ink">{title}</h3><button onClick={onClose} aria-label="Tutup" className="rounded-lg p-1.5 text-muted hover:bg-canvas"><X size={18} /></button></div>{children}</div></div>
}
export const StatusBadge = ({ status }) => { const I = STATUS_ICON[status] || Clock; return <span className={cx('inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold', STATUS_STYLE[status] || 'bg-slate-100 text-slate-600')}><I size={13} />{STATUS_LABEL[status] || status}</span> }
export const Card = ({ title, children, className }) => <section className={cx('rounded-card border border-line bg-white p-4 shadow-card sm:p-5', className)}>{title && <h2 className="mb-4 text-[15px] font-semibold text-ink">{title}</h2>}{children}</section>
export const PageHeader = ({ title, sub, action }) => <div className="mb-6 flex flex-wrap items-end justify-between gap-4"><div><h1 className="text-[22px] font-bold tracking-tight text-ink sm:text-[28px]">{title}</h1>{sub && <p className="mt-1 max-w-2xl text-sm text-muted">{sub}</p>}</div>{action}</div>
export const StatCard = ({ icon: I, label, value, alert }) => <div className="rounded-card border border-line bg-white p-4 shadow-card sm:p-5"><div className="flex items-center justify-between gap-2"><p className="text-[13px] font-medium text-muted">{label}</p><span className={cx('flex h-8 w-8 shrink-0 items-center justify-center rounded-ctl', alert ? 'bg-amber-50 text-amber-600' : 'bg-brand-50 text-brand')}><I size={16} /></span></div><p className="mt-3 text-[28px] font-bold leading-none tracking-tight text-ink">{value}</p></div>
export const Empty = ({ text = 'Belum ada data.' }) => <div className="rounded-card border border-dashed border-line bg-white px-5 py-12 text-center"><div className="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-ctl bg-brand-50 text-brand"><Inbox size={22} /></div><p className="text-sm font-medium text-muted">{text}</p></div>
export function Async({ state, children }) {
  if (state.loading && !state.data) return <div className="flex justify-center rounded-2xl border border-slate-100 bg-white py-16"><Loader2 className="h-7 w-7 animate-spin text-teal-700" /></div>
  if (state.error) return <div className="rounded-2xl border border-rose-200 bg-rose-50 p-6 text-center text-sm text-rose-700"><AlertTriangle className="mx-auto mb-2 h-6 w-6" />{state.error}<div className="mt-3"><Button variant="outline" onClick={state.reload}>Coba lagi</Button></div></div>
  return children(state.data)
}

export function Filters({ q, setQ, status, setStatus, statuses }) {
  return <div className="mb-4 flex flex-col gap-2.5 rounded-2xl border border-slate-100 bg-white p-3 shadow-[0_5px_20px_rgba(27,76,66,.025)] sm:flex-row"><div className="relative flex-1"><Search className="absolute left-3.5 top-3 h-4 w-4 text-slate-400"/><input value={q} onChange={e => setQ(e.target.value)} placeholder="Cari judul laporan..." className="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-3 text-[13px] outline-none focus:border-teal-400 focus:ring-2 focus:ring-teal-100"/></div>{setStatus && <select value={status} onChange={e => setStatus(e.target.value)} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-[13px] font-medium text-slate-600"><option value="">Semua status</option>{(statuses || Object.keys(STATUS_LABEL)).map(s => <option key={s} value={s}>{STATUS_LABEL[s]}</option>)}</select>}</div>
}

export function ReportList({ items, base, showAuthor }) {
  if (!items.length) return <Empty text="Tidak ada laporan yang cocok." />
  return <div className="overflow-hidden rounded-card border border-line bg-white shadow-card">{items.map((r, i) => <Link key={r.id} to={`${base}/${r.id}`} className={cx('group block px-4 py-3.5 transition hover:bg-brand-50/60 sm:px-5', i > 0 && 'border-t border-line')}><div className="flex items-start justify-between gap-3"><div className="min-w-0"><p className="truncate text-sm font-semibold text-ink group-hover:text-brand"><span className="mr-1.5 font-medium text-muted">{r.code || `#${r.id}`}</span>{r.title}</p><p className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted">{r.tags?.length > 0 && <span>{r.tags.map(t => `#${t}`).join(' ')}</span>}{r.location && <span className="inline-flex items-center gap-1"><MapPin size={12} />{r.location}</span>}{showAuthor && r.author && <span>{r.author}</span>}<span className="inline-flex items-center gap-1"><CalendarDays size={12} />{fmtDate(r.date)}</span></p></div><StatusBadge status={r.status} /></div><StatusPipeline report={r} mini /></Link>)}</div>
}
export function SignaturePad({ onChange }) {
  const ref = useRef(null)
  const drawing = useRef(false)
  const pos = e => { const c = ref.current, r = c.getBoundingClientRect(); return [((e.clientX - r.left) * c.width) / r.width, ((e.clientY - r.top) * c.height) / r.height] }
  const start = e => { drawing.current = true; const g = ref.current.getContext('2d'); g.beginPath(); g.moveTo(...pos(e)); ref.current.setPointerCapture?.(e.pointerId) }
  const move = e => { if (!drawing.current) return; const g = ref.current.getContext('2d'); g.strokeStyle = '#087e75'; g.lineWidth = 2.4; g.lineCap = 'round'; g.lineJoin = 'round'; g.lineTo(...pos(e)); g.stroke() }
  const end = () => { if (drawing.current) { drawing.current = false; onChange(ref.current.toDataURL()) } }
  const clear = () => { const c = ref.current; c.getContext('2d').clearRect(0, 0, c.width, c.height); onChange(null) }
  return <div><canvas ref={ref} width={400} height={160} onPointerDown={start} onPointerMove={move} onPointerUp={end} onPointerLeave={end} className="w-full touch-none rounded-xl border border-dashed border-teal-200 bg-teal-50/30"/><button type="button" onClick={clear} className="mt-2 text-xs font-semibold text-teal-700 hover:underline">Hapus tanda tangan dan ulangi</button></div>
}

export const STATUS_ICON = { ai_validation: Loader, admin_review: Clock, approved: ShieldCheck, forwarded: Send, gov_approved: BadgeCheck, scheduled: CalendarCheck, rejected: XCircle, cancelled: Ban }
// Menerima tanggal saja (YYYY-MM-DD) maupun waktu ISO lengkap dari API.
const toDate = d => new Date(d.length === 10 ? d + 'T00:00:00' : d)
export const fmtDate = d => (d ? toDate(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-')
export const fmtDateTime = d => (d ? toDate(d).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-')

export function Pager({ meta, onPage }) {
  if (!meta || meta.lastPage <= 1) return null
  return <nav aria-label="Halaman" className="mt-4 flex items-center justify-between gap-3 text-[13px] text-muted">
    <Button variant="outline" disabled={meta.page <= 1} onClick={() => onPage(meta.page - 1)}><ChevronLeft size={15} />Sebelumnya</Button>
    <span>Halaman <b className="text-ink">{meta.page}</b> dari {meta.lastPage} ({meta.total} laporan)</span>
    <Button variant="outline" disabled={meta.page >= meta.lastPage} onClick={() => onPage(meta.page + 1)}>Berikutnya<ChevronRight size={15} /></Button>
  </nav>
}

// Posisi laporan pada alur. mini = garis progres ringkas untuk daftar; tanpa mini = timeline vertikal untuk detail.
export function StatusPipeline({ report: r, mini }) {
  const idx = PIPELINE.indexOf(r.status)
  if (mini) return <div className="mt-3 flex gap-1" aria-hidden="true">{PIPELINE.map((s, i) => <span key={s} className={cx('h-1 flex-1 rounded-full', idx >= 0 && i <= idx ? 'bg-brand' : 'bg-line')} />)}</div>
  return <ol>{PIPELINE.map((s, i) => {
    const done = i < idx, cur = i === idx
    const at = [...r.history].reverse().find(h => h.status === s)?.at
    return <li key={s} className="relative flex gap-3 pb-5 last:pb-0">
      {i < PIPELINE.length - 1 && <span className={cx('absolute left-[11px] top-6 -bottom-1 w-0.5', done ? 'bg-brand' : 'bg-line')} />}
      <span className={cx('z-10 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 text-white', done || cur ? 'border-brand bg-brand' : 'border-line bg-white')}>{(done || cur) && <Check size={12} strokeWidth={3} />}</span>
      <div className="pt-0.5"><p className={cx('text-sm font-semibold', i > idx ? 'text-muted' : 'text-ink')}>{STATUS_LABEL[s]}</p>{at && <p className="text-xs text-muted">{fmtDateTime(at)}</p>}</div>
    </li>
  })}</ol>
}

export function StatusDonut({ by }) {
  const rows = Object.keys(STATUS_HEX).filter(k => by[k])
  const total = rows.reduce((s, k) => s + by[k], 0)
  if (!total) return <p className="py-6 text-center text-sm text-muted">Belum ada laporan.</p>
  const R = 42, C = 2 * Math.PI * R
  let acc = 0
  return <div className="flex items-center gap-5">
    <div className="relative h-28 w-28 shrink-0">
      <svg viewBox="0 0 100 100" className="h-full w-full -rotate-90" role="img" aria-label="Distribusi status laporan"><circle cx="50" cy="50" r={R} fill="none" stroke="#EEF4F2" strokeWidth="12" />{rows.map(k => { const len = (by[k] / total) * C; const el = <circle key={k} cx="50" cy="50" r={R} fill="none" stroke={STATUS_HEX[k]} strokeWidth="12" strokeDasharray={`${len} ${C - len}`} strokeDashoffset={-acc} />; acc += len; return el })}</svg>
      <div className="absolute inset-0 flex flex-col items-center justify-center"><span className="text-xl font-bold leading-none text-ink">{total}</span><span className="text-[11px] text-muted">laporan</span></div>
    </div>
    <ul className="flex-1 space-y-2 text-[13px]">{rows.map(k => <li key={k} className="flex items-center gap-2"><span className="h-2.5 w-2.5 rounded-full" style={{ background: STATUS_HEX[k] }} /><span className="flex-1 text-muted">{STATUS_LABEL[k]}</span><b className="tabular-nums text-ink">{by[k]}</b></li>)}</ul>
  </div>
}

export function TagBars({ items }) {
  const c = {}
  items.forEach(r => (r.tags || []).forEach(t => { c[`#${t}`] = (c[`#${t}`] || 0) + 1 }))
  if (!Object.keys(c).length) return <p className="py-4 text-center text-sm text-muted">Belum ada data.</p>
  const max = Math.max(1, ...Object.values(c))
  return <div className="space-y-3">{Object.entries(c).sort((a, b) => b[1] - a[1]).map(([k, v]) => <div key={k} className="text-[13px]"><div className="mb-1 flex justify-between"><span className="text-muted">{k}</span><b className="tabular-nums text-ink">{v}</b></div><div className="h-2 rounded-full bg-canvas"><div className="h-2 rounded-full bg-brand" style={{ width: `${(v / max) * 100}%` }} /></div></div>)}</div>
}
