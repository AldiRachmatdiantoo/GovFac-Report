import { useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { Construction, Mail, Lock, Eye, EyeOff } from 'lucide-react'
import { Button, Field, STATUS_ICON } from '../components/ui'
import { useApp } from '../context/AppContext'
import { login, register, forgotPassword, resetPassword, USE_MOCK } from '../services'
import { HOME, PIPELINE, STEP_INFO, STATUS_LABEL } from '../constants'

function Shell({ title, sub, children }) {
  return (
    <div className="grid min-h-screen bg-canvas lg:grid-cols-2">
      <section className="hidden flex-col justify-between bg-ink p-12 text-white lg:flex xl:p-16">
        <div className="flex items-center gap-3"><div className="flex h-10 w-10 items-center justify-center rounded-ctl bg-brand-2 text-white"><Construction size={21} /></div><p className="text-lg font-bold tracking-tight">Lapor Infrastruktur</p></div>
        <div>
          <h2 className="max-w-md text-4xl font-bold leading-tight tracking-tight">Dari laporan warga sampai perbaikan terjadwal.</h2>
          <p className="mt-4 max-w-md text-sm leading-7 text-white/65">Setiap laporan melewati tahapan yang dapat Anda pantau kapan saja.</p>
          <ol className="mt-10 max-w-md">{PIPELINE.map((s, i) => { const I = STATUS_ICON[s]; return <li key={s} className="relative flex gap-4 pb-5 last:pb-0">{i < PIPELINE.length - 1 && <span className="absolute left-[17px] top-10 -bottom-1 w-px bg-white/20" />}<span className="z-10 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-2/20 text-brand-2 ring-1 ring-brand-2/40"><I size={16} /></span><div><p className="text-sm font-semibold">{STATUS_LABEL[s]}</p><p className="text-[13px] leading-relaxed text-white/60">{STEP_INFO[s]}</p></div></li> })}</ol>
        </div>
        <p className="text-xs text-white/45">Layanan pelaporan kerusakan infrastruktur publik</p>
      </section>
      <section className="flex items-center justify-center p-5 sm:p-10">
        <div className="w-full max-w-[400px]">
          <div className="mb-8 flex items-center gap-2.5 lg:hidden"><div className="flex h-10 w-10 items-center justify-center rounded-ctl bg-brand text-white"><Construction size={21} /></div><p className="font-bold tracking-tight text-ink">Lapor Infrastruktur</p></div>
          <h1 className="text-[28px] font-bold tracking-tight text-ink">{title}</h1>
          <p className="mb-7 mt-1.5 text-sm text-muted">{sub}</p>
          {children}
        </div>
      </section>
    </div>
  )
}
const emailOk = v => /\S+@\S+\.\S+/.test(v)

export function Login() {
  const { signIn, toast } = useApp()
  const nav = useNavigate()
  const [f, setF] = useState({ email: '', password: '' })
  const [err, setErr] = useState({})
  const [busy, setBusy] = useState(false)
  const [show, setShow] = useState(false)
  const set = k => e => setF({ ...f, [k]: e.target.value })
  const demo = USE_MOCK

  const submit = async e => {
    e.preventDefault()
    const er = {}
    if (!emailOk(f.email)) er.email = 'Format email tidak valid.'
    if (!f.password) er.password = 'Password wajib diisi.'
    setErr(er)
    if (Object.keys(er).length) return
    setBusy(true)
    try {
      const res = await login(f.email, f.password)
      signIn(res)
      toast(`Login berhasil. Selamat datang, ${res.user.name}.`)
      nav(HOME[res.user.role])
    } catch (x) { setErr({ form: x.message }) } finally { setBusy(false) }
  }

  return (
    <Shell title="Masuk" sub="Laporkan dan pantau kerusakan infrastruktur di sekitar Anda.">
      <form onSubmit={submit} className="space-y-4" noValidate>
        {err.form && <p role="alert" className="rounded-ctl bg-rose-50 p-3 text-sm font-medium text-rose-700">{err.form}</p>}
        <Field label="Email" icon={Mail} type="email" autoComplete="email" placeholder="nama@email.com" value={f.email} onChange={set('email')} error={err.email} />
        <Field label="Password" icon={Lock} type={show ? 'text' : 'password'} autoComplete="current-password" placeholder="Masukkan password" value={f.password} onChange={set('password')} error={err.password}
          right={<button type="button" onClick={() => setShow(!show)} aria-label={show ? 'Sembunyikan password' : 'Tampilkan password'} className="rounded-lg p-1.5 text-muted hover:text-ink">{show ? <EyeOff size={16} /> : <Eye size={16} />}</button>} />
        <div className="text-right text-[13px]"><Link to="/forgot-password" className="font-semibold text-brand hover:underline">Lupa password?</Link></div>
        <Button type="submit" loading={busy} className="w-full">Masuk</Button>
      </form>
      <p className="mt-5 text-center text-sm text-muted">Belum punya akun? <Link to="/register" className="font-semibold text-brand hover:underline">Daftar</Link></p>
      {demo && <div className="mt-8 border-t border-line pt-5"><p className="mb-2.5 text-xs font-semibold text-muted">Akun demo (klik untuk mengisi otomatis)</p><div className="flex flex-wrap gap-2">{[['Masyarakat', 'user@demo.com'], ['Admin', 'admin@demo.com'], ['Pemerintah', 'gov@demo.com']].map(([l, em]) => <button key={em} type="button" onClick={() => setF({ email: em, password: 'password123' })} className="rounded-full border border-line bg-white px-3.5 py-1.5 text-xs font-semibold text-ink transition hover:border-brand hover:text-brand">{l}</button>)}</div></div>}
    </Shell>
  )
}

export function Register() {
  const { signIn, toast } = useApp()
  const nav = useNavigate()
  const [f, setF] = useState({ name: '', email: '', password: '', confirm: '' })
  const [err, setErr] = useState({})
  const [busy, setBusy] = useState(false)
  const set = k => e => setF({ ...f, [k]: e.target.value })

  const submit = async e => {
    e.preventDefault()
    const er = {}
    if (f.name.trim().length < 3) er.name = 'Nama minimal 3 karakter.'
    if (!emailOk(f.email)) er.email = 'Format email tidak valid.'
    if (f.password.length < 8) er.password = 'Password minimal 8 karakter.'
    if (f.confirm !== f.password) er.confirm = 'Konfirmasi password tidak sama.'
    setErr(er)
    if (Object.keys(er).length) return
    setBusy(true)
    try {
      const res = await register(f)
      signIn(res)
      toast('Akun berhasil dibuat.')
      nav(HOME[res.user.role])
    } catch (x) {
      const taken = /taken|terdaftar/i.test(x.fields?.email || '')
      setErr(x.fields?.email || x.fields?.name || x.fields?.password
        ? { name: x.fields.name, email: taken ? 'Email sudah terdaftar.' : x.fields.email, password: x.fields.password }
        : { form: x.message })
    } finally { setBusy(false) }
  }

  return (
    <Shell title="Buat akun" sub="Daftar sebagai masyarakat pelapor">
      <form onSubmit={submit} className="space-y-4" noValidate>
        {err.form && <p className="rounded-ctl bg-rose-50 p-3 text-sm font-medium text-rose-700">{err.form}</p>}
        <Field label="Nama lengkap" value={f.name} onChange={set('name')} error={err.name} />
        <Field label="Email" type="email" value={f.email} onChange={set('email')} error={err.email} />
        <Field label="Password" type="password" value={f.password} onChange={set('password')} error={err.password} />
        <Field label="Konfirmasi password" type="password" value={f.confirm} onChange={set('confirm')} error={err.confirm} />
        <Button type="submit" loading={busy} className="w-full">Daftar</Button>
      </form>
      <p className="mt-4 text-center text-sm text-slate-600">Sudah punya akun? <Link to="/login" className="font-medium text-teal-700 hover:underline">Masuk</Link></p>
    </Shell>
  )
}

export function Forgot() {
  const [email, setEmail] = useState('')
  const [err, setErr] = useState('')
  const [sent, setSent] = useState(false)
  const [busy, setBusy] = useState(false)

  const submit = async e => {
    e.preventDefault()
    if (!emailOk(email)) return setErr('Format email tidak valid.')
    setErr(''); setBusy(true)
    try { await forgotPassword(email); setSent(true) } catch (x) { setErr(x.message) } finally { setBusy(false) }
  }

  return (
    <Shell title="Lupa password" sub="Kami kirim tautan pemulihan ke email Anda">
      {sent ? (
        <p className="rounded-ctl bg-emerald-50 p-3 text-sm font-medium text-emerald-700">Jika email terdaftar, tautan pemulihan password telah dikirim. Periksa kotak masuk Anda.</p>
      ) : (
        <form onSubmit={submit} className="space-y-4" noValidate>
          <Field label="Email" type="email" value={email} onChange={e => setEmail(e.target.value)} error={err} />
          <Button type="submit" loading={busy} className="w-full">Kirim tautan pemulihan</Button>
        </form>
      )}
      <p className="mt-4 text-center text-sm"><Link to="/login" className="text-teal-700 hover:underline">Kembali ke halaman masuk</Link></p>
    </Shell>
  )
}

// Halaman tujuan tautan di email reset password: /reset-password?token=...&email=...
export function Reset() {
  const [q] = useSearchParams()
  const { toast } = useApp()
  const nav = useNavigate()
  const token = q.get('token') || ''
  const email = q.get('email') || ''
  const [f, setF] = useState({ password: '', confirm: '' })
  const [err, setErr] = useState({})
  const [busy, setBusy] = useState(false)
  const set = k => e => setF({ ...f, [k]: e.target.value })

  const submit = async e => {
    e.preventDefault()
    const er = {}
    if (f.password.length < 8) er.password = 'Password minimal 8 karakter.'
    if (f.confirm !== f.password) er.confirm = 'Konfirmasi password tidak sama.'
    setErr(er)
    if (Object.keys(er).length) return
    setBusy(true)
    try {
      await resetPassword({ token, email, ...f })
      toast('Password berhasil diubah. Silakan masuk kembali.')
      nav('/login')
    } catch (x) { setErr({ form: x.status === 422 ? 'Tautan tidak valid atau sudah kedaluwarsa. Minta tautan baru.' : x.message }) } finally { setBusy(false) }
  }

  return (
    <Shell title="Atur ulang password" sub={email ? `Buat password baru untuk ${email}` : 'Buat password baru untuk akun Anda'}>
      {!token || !email ? (
        <p role="alert" className="rounded-ctl bg-rose-50 p-3 text-sm font-medium text-rose-700">Tautan tidak lengkap. Buka tautan dari email Anda, atau minta tautan baru.</p>
      ) : (
        <form onSubmit={submit} className="space-y-4" noValidate>
          {err.form && <p role="alert" className="rounded-ctl bg-rose-50 p-3 text-sm font-medium text-rose-700">{err.form}</p>}
          <Field label="Password baru" icon={Lock} type="password" autoComplete="new-password" value={f.password} onChange={set('password')} error={err.password} />
          <Field label="Konfirmasi password baru" icon={Lock} type="password" autoComplete="new-password" value={f.confirm} onChange={set('confirm')} error={err.confirm} />
          <Button type="submit" loading={busy} className="w-full">Simpan password baru</Button>
        </form>
      )}
      <p className="mt-4 text-center text-sm"><Link to="/forgot-password" className="font-semibold text-brand hover:underline">Minta tautan baru</Link> <span className="text-muted">atau</span> <Link to="/login" className="font-semibold text-brand hover:underline">kembali ke halaman masuk</Link></p>
    </Shell>
  )
}
