import { useEffect, useMemo, useRef, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { FilePlus, FileText, Clock, ShieldCheck, BadgeCheck, ThumbsUp, ImageOff, X, ImagePlus } from 'lucide-react'
import { useApp } from '../context/AppContext'
import { useAsync, Async, Button, Card, Empty, Field, Filters, Modal, PageHeader, Pager, ReportList, StatCard, StatusBadge, StatusDonut } from '../components/ui'
import { createReport, getMyReports, getMyStats, getReport, getReports, suggestTags, toggleLike, updateMyReport } from '../services'
import { EDITABLE, REPORT_LIMITS as LIMIT } from '../constants'

export function UserDashboard() {
  const { user } = useApp()
  const s = useAsync(() => Promise.all([getMyStats(user), getMyReports(user, { perPage: 5 })]), [])
  return (
    <>
      <PageHeader title={`Halo, ${user.name.split(' ')[0]}`} sub="Ringkasan laporan Anda dan perkembangannya."
        action={<Link to="/user/reports/create"><Button><FilePlus size={16} />Buat laporan</Button></Link>} />
      <Async state={s}>{([st, list]) => {
        const sum = (...keys) => keys.reduce((n, k) => n + (st.by[k] || 0), 0)
        const waiting = sum('ai_validation', 'admin_review')
        return (
          <div className="space-y-5">
            <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
              <StatCard icon={FileText} label="Total laporan" value={st.total} />
              <StatCard icon={Clock} label="Menunggu tinjauan" value={waiting} alert={!!waiting} />
              <StatCard icon={ShieldCheck} label="Dalam proses" value={sum('approved', 'forwarded')} />
              <StatCard icon={BadgeCheck} label="Disetujui / terjadwal" value={sum('gov_approved', 'scheduled')} />
            </div>
            <div className="grid gap-5 lg:grid-cols-[1fr_340px]">
              <section>
                <div className="mb-3 flex items-center justify-between"><h2 className="text-base font-semibold text-ink">Laporan terbaru</h2><Link to="/user/reports" className="text-[13px] font-semibold text-brand hover:underline">Lihat semua</Link></div>
                {list.items.length ? <ReportList items={list.items} base="/user/reports" /> : <Empty text="Anda belum pernah membuat laporan." />}
              </section>
              <Card title="Distribusi status"><StatusDonut by={st.by} /></Card>
            </div>
          </div>
        )
      }}</Async>
    </>
  )
}

// Tag: huruf, angka, dan garis bawah saja (sama dengan aturan backend). Spasi diubah menjadi garis bawah.
const cleanTag = v => v.trim().replace(/^#+/, '').replace(/\s+/g, '_').toLowerCase()
const TAG_OK = /^[\p{L}\p{N}_]+$/u

function TagInput({ value, onChange, error }) {
  const [text, setText] = useState('')
  const [hints, setHints] = useState([])
  const [warn, setWarn] = useState('')

  // Saran tag dari backend (GET /tags?q=), ditunda sebentar agar tidak memanggil API tiap ketikan.
  useEffect(() => {
    const q = cleanTag(text)
    const t = setTimeout(() => { suggestTags(q).then(setHints).catch(() => setHints([])) }, 250)
    return () => clearTimeout(t)
  }, [text])

  const add = raw => {
    const tag = cleanTag(raw)
    if (!tag) return
    if (!TAG_OK.test(tag)) return setWarn('Tag hanya boleh berisi huruf, angka, dan garis bawah.')
    if (tag.length > LIMIT.tagLength) return setWarn(`Tag maksimal ${LIMIT.tagLength} karakter.`)
    if (value.includes(tag)) return setText('')
    if (value.length >= LIMIT.tags) return setWarn(`Maksimal ${LIMIT.tags} tag.`)
    onChange([...value, tag]); setText(''); setWarn('')
  }
  const key = e => {
    if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); add(text) }
    if (e.key === 'Backspace' && !text && value.length) onChange(value.slice(0, -1))
  }
  const rest = hints.filter(h => !value.includes(h))

  return (
    <div>
      <Field required label="Tag" placeholder="Contoh: jalan_rusak, lalu tekan Enter" value={text} onChange={e => { setText(e.target.value); setWarn('') }} onKeyDown={key} onBlur={() => add(text)}
        error={warn || error} hint={`Jenis kerusakan atau lokasi. 1 sampai ${LIMIT.tags} tag.`} />
      {value.length > 0 && <div className="mt-2 flex flex-wrap gap-1.5">{value.map(t => (
        <span key={t} className="inline-flex items-center gap-1 rounded-full bg-brand-50 py-1 pl-3 pr-1.5 text-xs font-semibold text-brand">#{t}
          <button type="button" onClick={() => onChange(value.filter(x => x !== t))} aria-label={`Hapus tag ${t}`} className="rounded-full p-0.5 hover:bg-brand/15"><X size={12} /></button>
        </span>
      ))}</div>}
      {rest.length > 0 && value.length < LIMIT.tags && <div className="mt-2 flex flex-wrap items-center gap-1.5 text-xs text-muted">Saran:{rest.slice(0, 6).map(t => (
        <button key={t} type="button" onMouseDown={e => e.preventDefault()} onClick={() => add(t)} className="rounded-full border border-line bg-white px-2.5 py-1 font-medium text-ink transition hover:border-brand hover:text-brand">#{t}</button>
      ))}</div>}
    </div>
  )
}

function PhotoInput({ value, onChange, error, required }) {
  const input = useRef(null)
  const [warn, setWarn] = useState('')
  const previews = useMemo(() => value.map(f => URL.createObjectURL(f)), [value])
  useEffect(() => () => previews.forEach(u => URL.revokeObjectURL(u)), [previews])

  const pick = e => {
    const files = [...e.target.files]
    e.target.value = ''
    const ok = files.filter(f => ['image/jpeg', 'image/png'].includes(f.type) && f.size <= LIMIT.photoBytes)
    const next = [...value, ...ok].slice(0, LIMIT.photos)
    setWarn(ok.length < files.length ? 'Sebagian berkas dilewati: hanya JPEG atau PNG, maksimal 5 MB per foto.'
      : value.length + ok.length > LIMIT.photos ? `Maksimal ${LIMIT.photos} foto.` : '')
    onChange(next)
  }

  return (
    <div className="text-[13px]">
      <span className="mb-1.5 block font-semibold text-ink">Foto bukti{required && <span className="ml-0.5 text-rose-600" aria-hidden="true">*</span>}</span>
      <div className="grid grid-cols-3 gap-2 sm:grid-cols-5">
        {previews.map((u, i) => (
          <div key={u} className="relative aspect-square overflow-hidden rounded-ctl border border-line">
            <img src={u} alt={`Pratinjau foto ${i + 1}`} className="h-full w-full object-cover" />
            <button type="button" onClick={() => onChange(value.filter((_, j) => j !== i))} aria-label={`Hapus foto ${i + 1}`} className="absolute right-1 top-1 rounded-full bg-ink/70 p-1 text-white hover:bg-ink"><X size={12} /></button>
          </div>
        ))}
        {value.length < LIMIT.photos && (
          <button type="button" onClick={() => input.current.click()} className="flex aspect-square flex-col items-center justify-center gap-1 rounded-ctl border border-dashed border-line bg-canvas text-xs font-semibold text-muted transition hover:border-brand hover:text-brand">
            <ImagePlus size={20} />Tambah foto
          </button>
        )}
      </div>
      <input ref={input} type="file" accept="image/jpeg,image/png" multiple hidden onChange={pick} aria-label="Pilih foto bukti" />
      {warn || error ? <span className="mt-1.5 block text-xs font-medium text-rose-600">{warn || error}</span>
        : <span className="mt-1.5 block text-xs text-muted">1 sampai {LIMIT.photos} foto, JPEG atau PNG, maksimal 5 MB per foto.</span>}
    </div>
  )
}

const SUBMITTED = {
  ai_validation: ['Laporan terkirim', 'Laporan Anda sudah diterima dan sedang diperiksa. Pantau perkembangannya di halaman detail.'],
  admin_review: ['Lolos validasi AI', 'Laporan Anda diterima dan menunggu ditinjau Admin.'],
  rejected: ['Validasi AI gagal', 'Laporan ditolak validasi otomatis. Lihat alasannya di halaman detail.'],
}

export function ReportForm() {
  const { id } = useParams()
  const { user, toast } = useApp()
  const nav = useNavigate()
  const [f, setF] = useState({ title: '', description: '', tags: [], photos: [] })
  const [err, setErr] = useState({})
  const [busy, setBusy] = useState(false)
  const [res, setRes] = useState(null)
  const load = useAsync(() => (id ? getReport(id, user.role) : Promise.resolve(null)), [id])

  useEffect(() => {
    const d = load.data
    if (d) setF({ title: d.title, description: d.description, tags: d.tags, photos: [] })
  }, [load.data])

  if (id && !load.data) return <Async state={load}>{() => null}</Async>
  if (id && !EDITABLE.includes(load.data.status)) return <Empty text="Laporan ini sudah diproses dan tidak bisa diedit lagi." />

  const set = k => e => setF({ ...f, [k]: e.target.value })
  const submit = async e => {
    e.preventDefault()
    const er = {}
    if (f.title.trim().length < 5) er.title = 'Judul minimal 5 karakter.'
    if (!f.description.trim()) er.description = 'Deskripsi wajib diisi.'
    if (!f.tags.length) er.tags = 'Tambahkan minimal 1 tag.'
    if (!id && !f.photos.length) er.photos = 'Lampirkan minimal 1 foto bukti.'
    setErr(er)
    if (Object.keys(er).length) return
    setBusy(true)
    try { setRes(await (id ? updateMyReport(id, f) : createReport(f, user))) }
    catch (x) {
      // Pesan validasi backend datang per field; photos.0 dan tags.1 digabung ke field induknya.
      const fields = {}
      Object.entries(x.fields || {}).forEach(([k, v]) => { fields[k.split('.')[0]] ??= v })
      if (Object.keys(fields).length) setErr(fields)
      toast(x.message, 'error')
    } finally { setBusy(false) }
  }
  const [doneTitle, doneText] = SUBMITTED[res?.status] || SUBMITTED.ai_validation

  return (
    <>
      <PageHeader title={id ? 'Edit laporan' : 'Buat laporan'} sub="Laporan akan divalidasi otomatis sebelum diteruskan ke Admin." />
      <Card>
        <div className="mb-5 rounded-xl border border-teal-100 bg-teal-50/70 p-4"><p className="text-xs font-bold text-teal-800">Lengkapi informasi laporan</p><p className="mt-1 text-xs leading-relaxed text-teal-700/80">Pastikan judul, deskripsi, tag, dan foto bukti menggambarkan kondisi dan lokasi kerusakan dengan jelas.</p></div>
        <form onSubmit={submit} className="space-y-4" noValidate>
          <Field required label="Judul laporan" maxLength={LIMIT.title} value={f.title} onChange={set('title')} error={err.title} />
          <Field required label="Deskripsi" as="textarea" rows={5} maxLength={LIMIT.description} placeholder="Jelaskan kerusakan dan lokasinya, misalnya nama jalan atau patokan terdekat." value={f.description} onChange={set('description')} error={err.description} hint={`${f.description.length}/${LIMIT.description} karakter`} />
          <TagInput value={f.tags} onChange={tags => setF(p => ({ ...p, tags }))} error={err.tags} />
          <PhotoInput required={!id} value={f.photos} onChange={photos => setF(p => ({ ...p, photos }))} error={err.photos} />
          <div className="flex justify-end gap-2">
            <Button type="button" variant="outline" onClick={() => nav(-1)}>Batal</Button>
            <Button type="submit" loading={busy}>{busy ? 'Mengirim laporan' : id ? 'Simpan perubahan' : 'Kirim laporan'}</Button>
          </div>
        </form>
      </Card>
      <Modal open={!!res} title={doneTitle} onClose={() => nav(`/user/reports/${res.id}`)}>
        <p className="mb-4 text-sm text-slate-600">{doneText}</p>
        <Button className="w-full" onClick={() => nav(`/user/reports/${res.id}`)}>Lihat laporan</Button>
      </Modal>
    </>
  )
}

export function MyReports() {
  const { user } = useApp()
  const [q, setQ] = useState('')
  const [st, setSt] = useState('')
  const [page, setPage] = useState(1)
  // Filter status dan halaman dikerjakan server; pencarian judul hanya menyaring halaman yang tampil.
  const s = useAsync(() => getMyReports(user, { status: st, page }), [st, page])
  return (
    <>
      <PageHeader title="Laporan saya" sub="Pantau status setiap laporan yang pernah Anda kirim." />
      <Filters q={q} setQ={setQ} status={st} setStatus={v => { setSt(v); setPage(1) }} />
      <Async state={s}>{({ items, meta }) => (
        <>
          <ReportList items={items.filter(r => r.title.toLowerCase().includes(q.toLowerCase()))} base="/user/reports" />
          <Pager meta={meta} onPage={setPage} />
        </>
      )}</Async>
    </>
  )
}

// Feed komunitas dan like (PBI-11, 12) belum ada endpoint-nya: halaman ini masih memakai data contoh.
export function Community() {
  const { user, toast } = useApp()
  const s = useAsync(() => getReports({ publicOnly: true }), [])
  const [q, setQ] = useState('')
  const [tag, setTag] = useState('')
  const [sort, setSort] = useState('new')
  const like = async id => { try { await toggleLike(id, user.id); s.reload() } catch (e) { toast(e.message, 'error') } }
  return (
    <>
      <PageHeader title="Komunitas" sub="Dukung laporan warga lain dengan memberi like agar lebih diprioritaskan." />
      <Filters q={q} setQ={setQ} />
      <Async state={s}>{list => {
        const tags = [...new Set(list.flatMap(r => r.tags))].sort()
        const items = list.filter(r => (!tag || r.tags.includes(tag)) && r.title.toLowerCase().includes(q.toLowerCase()))
          .sort((a, b) => (sort === 'like' ? b.likes.length - a.likes.length : b.id - a.id))
        return (
          <>
            <div className="mb-4 flex gap-2">
              <select value={tag} onChange={e => setTag(e.target.value)} aria-label="Saring menurut tag" className="rounded-ctl border border-line bg-white px-3 py-2.5 text-[13px] font-medium text-ink"><option value="">Semua tag</option>{tags.map(t => <option key={t} value={t}>#{t}</option>)}</select>
              <select value={sort} onChange={e => setSort(e.target.value)} aria-label="Urutkan" className="rounded-ctl border border-line bg-white px-3 py-2.5 text-[13px] font-medium text-ink"><option value="new">Terbaru</option><option value="like">Like terbanyak</option></select>
            </div>
            {!items.length ? <Empty text="Tidak ada laporan yang cocok dengan filter." /> : (
              <div className="grid gap-3 sm:grid-cols-2">{items.map(r => (
                <Card key={r.id}>
                  {r.photos[0] ? <img src={r.photos[0].url} alt="" className="mb-3 h-36 w-full rounded-lg object-cover" /> : <div className="mb-3 flex h-36 items-center justify-center rounded-lg bg-slate-100"><ImageOff className="h-6 w-6 text-slate-400" /></div>}
                  <div className="mb-1 flex items-start justify-between gap-2"><p className="font-medium">{r.title}</p><StatusBadge status={r.status} /></div>
                  <p className="mb-3 text-xs text-slate-500">{[r.tags.map(t => `#${t}`).join(' '), r.location, r.author && `oleh ${r.author}`].filter(Boolean).join(', ')}</p>
                  <button onClick={() => like(r.id)} aria-pressed={r.likes.includes(user.id)} className={`inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-sm ${r.likes.includes(user.id) ? 'border-brand bg-brand-50 text-brand' : 'border-line text-muted'}`}>
                    <ThumbsUp className="h-4 w-4" />{r.likes.length}
                  </button>
                </Card>
              ))}</div>
            )}
          </>
        )
      }}</Async>
    </>
  )
}
