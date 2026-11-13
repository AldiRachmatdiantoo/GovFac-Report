import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { FilePlus, FileText, Clock, ShieldCheck, BadgeCheck, ThumbsUp, ImageOff } from 'lucide-react'
import { useApp } from '../context/AppContext'
import { useAsync, Async, Button, Card, Empty, Field, Filters, Modal, PageHeader, ReportList, StatCard, StatusBadge, StatusDonut } from '../components/ui'
import { getReport, getReports, getStats, saveReport, toggleLike } from '../services'
import { CATEGORIES, EDITABLE } from '../constants'

export function UserDashboard() {
  const { user } = useApp()
  const s = useAsync(() => Promise.all([getStats(user.id), getReports({ userId: user.id })]), [])
  return (
    <>
      <PageHeader title={`Halo, ${user.name.split(' ')[0]}`} sub="Ringkasan laporan Anda dan perkembangannya."
        action={<Link to="/user/reports/create"><Button><FilePlus size={16} />Buat laporan</Button></Link>} />
      <Async state={s}>{([st, list]) => (
        <div className="space-y-5">
          <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <StatCard icon={FileText} label="Total laporan" value={st.total} />
            <StatCard icon={Clock} label="Menunggu" value={st.by['Menunggu'] || 0} alert={!!st.by['Menunggu']} />
            <StatCard icon={ShieldCheck} label="Dalam proses" value={(st.by['Diproses'] || 0) + (st.by['Terverifikasi'] || 0)} />
            <StatCard icon={BadgeCheck} label="Disetujui / terjadwal" value={(st.by['Disetujui'] || 0) + (st.by['Terjadwal'] || 0)} />
          </div>
          <div className="grid gap-5 lg:grid-cols-[1fr_340px]">
            <section>
              <div className="mb-3 flex items-center justify-between"><h2 className="text-base font-semibold text-ink">Laporan terbaru</h2><Link to="/user/reports" className="text-[13px] font-semibold text-brand hover:underline">Lihat semua</Link></div>
              <ReportList items={list.slice(0, 5)} base="/user/reports" />
            </section>
            <Card title="Distribusi status"><StatusDonut by={st.by} /></Card>
          </div>
        </div>
      )}</Async>
    </>
  )
}

export function ReportForm() {
  const { id } = useParams()
  const { user, toast } = useApp()
  const nav = useNavigate()
  const [f, setF] = useState({ title: '', category: CATEGORIES[0], location: '', description: '', photo: null })
  const [err, setErr] = useState({})
  const [busy, setBusy] = useState(false)
  const [res, setRes] = useState(null)
  const load = useAsync(() => (id ? getReport(id) : Promise.resolve(null)), [id])

  useEffect(() => {
    const d = load.data
    if (d) setF({ title: d.title, category: d.category, location: d.location, description: d.description, photo: d.photo })
  }, [load.data])

  if (id && !load.data) return <Async state={load}>{() => null}</Async>
  if (id && !EDITABLE.includes(load.data.status)) return <Empty text="Laporan ini sudah diproses dan tidak bisa diedit lagi." />

  const set = k => e => setF({ ...f, [k]: e.target.value })
  const pick = e => {
    const file = e.target.files[0]
    if (!file) return
    if (!file.type.startsWith('image/') || file.size > 2e6) return setErr(p => ({ ...p, photo: 'Gunakan file gambar maksimal 2 MB.' }))
    const r = new FileReader()
    r.onload = () => { setF(p => ({ ...p, photo: r.result })); setErr(p => ({ ...p, photo: null })) }
    r.readAsDataURL(file)
  }
  const submit = async e => {
    e.preventDefault()
    const er = {}
    if (f.title.trim().length < 5) er.title = 'Judul minimal 5 karakter.'
    if (!f.location.trim()) er.location = 'Lokasi wajib diisi.'
    if (!f.description.trim()) er.description = 'Deskripsi wajib diisi.'
    setErr(er)
    if (Object.keys(er).length) return
    setBusy(true)
    try { setRes(await saveReport(f, user, id)) } catch (x) { toast(x.message, 'error') } finally { setBusy(false) }
  }

  return (
    <>
      <PageHeader title={id ? 'Edit laporan' : 'Buat laporan'} sub="Laporan akan divalidasi otomatis oleh AI sebelum diteruskan." />
      <Card>
        <div className="mb-5 rounded-xl border border-teal-100 bg-teal-50/70 p-4"><p className="text-xs font-bold text-teal-800">Lengkapi informasi laporan</p><p className="mt-1 text-xs leading-relaxed text-teal-700/80">Pastikan judul, lokasi, deskripsi, dan foto bukti menggambarkan kondisi kerusakan dengan jelas.</p></div>
        <form onSubmit={submit} className="space-y-4" noValidate>
          <Field required label="Judul laporan" value={f.title} onChange={set('title')} error={err.title} />
          <Field label="Kategori" as="select" value={f.category} onChange={set('category')}>{CATEGORIES.map(c => <option key={c}>{c}</option>)}</Field>
          <Field required label="Lokasi kerusakan" value={f.location} onChange={set('location')} error={err.location} />
          <Field required label="Deskripsi" as="textarea" rows={4} value={f.description} onChange={set('description')} error={err.description} />
          <div>
            <Field label="Foto bukti" type="file" accept="image/*" onChange={pick} error={err.photo} />
            {f.photo && <img src={f.photo} alt="Pratinjau foto" className="mt-2 h-40 rounded-lg object-cover" />}
          </div>
          <div className="flex justify-end gap-2">
            <Button type="button" variant="outline" onClick={() => nav(-1)}>Batal</Button>
            <Button type="submit" loading={busy}>{busy ? 'AI sedang memvalidasi' : id ? 'Simpan perubahan' : 'Kirim laporan'}</Button>
          </div>
        </form>
      </Card>
      <Modal open={!!res} title={res?.ai.ok ? 'Lolos validasi AI' : 'Validasi AI gagal'} onClose={() => {}}>
        <p className="mb-4 text-sm text-slate-600">{res?.ai.ok ? 'Laporan Anda diterima dan menunggu ditinjau Admin.' : `${res?.ai.reason} Perbaiki laporan melalui tombol Edit di halaman detail.`}</p>
        <Button className="w-full" onClick={() => nav(`/user/reports/${res.report.id}`)}>Lihat laporan</Button>
      </Modal>
    </>
  )
}

export function MyReports() {
  const { user } = useApp()
  const s = useAsync(() => getReports({ userId: user.id }), [])
  const [q, setQ] = useState('')
  const [st, setSt] = useState('')
  return (
    <>
      <PageHeader title="Laporan saya" sub="Pantau status setiap laporan yang pernah Anda kirim." />
      <Filters q={q} setQ={setQ} status={st} setStatus={setSt} />
      <Async state={s}>{list => <ReportList items={list.filter(r => (!st || r.status === st) && r.title.toLowerCase().includes(q.toLowerCase()))} base="/user/reports" />}</Async>
    </>
  )
}

export function Community() {
  const { user, toast } = useApp()
  const s = useAsync(() => getReports({ publicOnly: true }), [])
  const [q, setQ] = useState('')
  const [cat, setCat] = useState('')
  const [sort, setSort] = useState('new')
  const like = async id => { try { await toggleLike(id, user.id); s.reload() } catch (e) { toast(e.message, 'error') } }
  return (
    <>
      <PageHeader title="Komunitas" sub="Dukung laporan warga lain dengan memberi like agar lebih diprioritaskan." />
      <Filters q={q} setQ={setQ} />
      <div className="mb-4 flex gap-2">
        <select value={cat} onChange={e => setCat(e.target.value)} className="rounded-ctl border border-line bg-white px-3 py-2.5 text-[13px] font-medium text-ink"><option value="">Semua kategori</option>{CATEGORIES.map(c => <option key={c}>{c}</option>)}</select>
        <select value={sort} onChange={e => setSort(e.target.value)} className="rounded-ctl border border-line bg-white px-3 py-2.5 text-[13px] font-medium text-ink"><option value="new">Terbaru</option><option value="like">Like terbanyak</option></select>
      </div>
      <Async state={s}>{list => {
        const items = list.filter(r => (!cat || r.category === cat) && r.title.toLowerCase().includes(q.toLowerCase()))
          .sort((a, b) => (sort === 'like' ? b.likes.length - a.likes.length : b.id - a.id))
        if (!items.length) return <Empty text="Tidak ada laporan yang cocok dengan filter." />
        return (
          <div className="grid gap-3 sm:grid-cols-2">{items.map(r => (
            <Card key={r.id}>
              {r.photo ? <img src={r.photo} alt="" className="mb-3 h-36 w-full rounded-lg object-cover" /> : <div className="mb-3 flex h-36 items-center justify-center rounded-lg bg-slate-100"><ImageOff className="h-6 w-6 text-slate-400" /></div>}
              <div className="mb-1 flex items-start justify-between gap-2"><p className="font-medium">{r.title}</p><StatusBadge status={r.status} /></div>
              <p className="mb-3 text-xs text-slate-500">{r.category}, {r.location}, oleh {r.author}</p>
              <button onClick={() => like(r.id)} aria-pressed={r.likes.includes(user.id)} className={`inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-sm ${r.likes.includes(user.id) ? 'border-brand bg-brand-50 text-brand' : 'border-line text-muted'}`}>
                <ThumbsUp className="h-4 w-4" />{r.likes.length}
              </button>
            </Card>
          ))}</div>
        )
      }}</Async>
    </>
  )
}
