import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { ArrowLeft, Download, Pencil, Send, XCircle, BadgeCheck, ImageOff, Tag, MapPin, CalendarDays, User, ShieldCheck, AlertTriangle } from 'lucide-react'
import { useApp } from '../context/AppContext'
import { useAsync, Async, Button, Card, Modal, SignaturePad, StatusBadge, StatusPipeline, fmtDate, fmtDateTime } from '../components/ui'
import { FEATURES, getReport, updateReport } from '../services'
import { CRITICALITY_LABEL, EDITABLE, PIPELINE, REASON_LABEL, STATUS_LABEL } from '../constants'

// Satu halaman detail untuk semua role. Aksi ditampilkan sesuai role & status.
// Pelapor membaca data dari API (PBI-7). Aksi Admin dan Pemerintah (verifikasi, teruskan, setujui)
// belum punya endpoint, jadi masih berjalan di data contoh lewat updateReport.
export default function ReportDetail() {
  const { id } = useParams()
  const { user, toast } = useApp()
  const nav = useNavigate()
  const role = user.role
  const s = useAsync(() => getReport(id, role), [id])
  const [modal, setModal] = useState(null)
  const [sig, setSig] = useState(null)
  const [busy, setBusy] = useState(false)
  const [photo, setPhoto] = useState(0)

  const act = async (patch, note, msg, actor) => {
    setBusy(true)
    try { await updateReport(id, patch, note, actor); toast(msg); setModal(null); s.reload() }
    catch (e) { toast(e.message, 'error') } finally { setBusy(false) }
  }

  return (
    <>
      <button onClick={() => nav(-1)} className="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-muted transition hover:text-brand print:hidden"><ArrowLeft className="h-4 w-4" />Kembali</button>
      <Async state={s}>{r => {
        const own = r.userId === user.id
        const editable = role === 'user' && own && EDITABLE.includes(r.status)
        const canEdit = editable && FEATURES.editReport
        const canCancel = editable && FEATURES.cancelReport
        const canApprove = role === 'admin' && r.status === 'admin_review'
        const canForward = role === 'admin' && r.status === 'approved'
        const canSign = role === 'government' && r.status === 'forwarded'
        const shown = r.photos[Math.min(photo, r.photos.length - 1)]
        return (
          <div className="space-y-5">
            <header className="flex flex-wrap items-start justify-between gap-4">
              <div className="min-w-0">
                <p className="text-sm font-medium text-muted">Laporan {r.code || `#${r.id}`}</p>
                <h1 className="mt-1 text-[22px] font-bold leading-snug tracking-tight text-ink sm:text-[28px]">{r.title}</h1>
                <p className="mt-2.5 flex flex-wrap gap-x-4 gap-y-1.5 text-[13px] text-muted">
                  {r.tags.length > 0 && <span className="inline-flex items-center gap-1.5"><Tag size={14} />{r.tags.map(t => `#${t}`).join(' ')}</span>}
                  {r.location && <span className="inline-flex items-center gap-1.5"><MapPin size={14} />{r.location}</span>}
                  <span className="inline-flex items-center gap-1.5"><CalendarDays size={14} />{fmtDate(r.date)}</span>
                  {r.author && <span className="inline-flex items-center gap-1.5"><User size={14} />{r.author}</span>}
                  {r.criticality && <span className="inline-flex items-center gap-1.5"><AlertTriangle size={14} />Tingkat kritis: {CRITICALITY_LABEL[r.criticality] || r.criticality}</span>}
                </p>
              </div>
              <Button variant="outline" className="print:hidden" onClick={() => window.print()}><Download size={16} />{role === 'admin' ? 'Export PDF' : 'Unduh PDF'}</Button>
            </header>
            <div className="grid gap-5 lg:grid-cols-[1fr_340px]">
              <div className="space-y-5">
                <Card>
                  {shown ? (
                    <div className="mb-4">
                      <img src={shown.url} alt="Foto bukti" className="max-h-96 w-full rounded-ctl bg-canvas object-contain" />
                      {r.photos.length > 1 && <div className="mt-2 flex gap-2 overflow-x-auto print:hidden">{r.photos.map((p, i) => (
                        <button key={p.id} onClick={() => setPhoto(i)} aria-label={`Lihat foto ${i + 1}`} aria-pressed={i === photo} className={`h-16 w-16 shrink-0 overflow-hidden rounded-ctl border-2 ${i === photo ? 'border-brand' : 'border-transparent opacity-70 hover:opacity-100'}`}><img src={p.url} alt="" className="h-full w-full object-cover" /></button>
                      ))}</div>}
                    </div>
                  ) : <div className="mb-4 flex h-24 items-center justify-center gap-2 rounded-ctl bg-canvas text-[13px] text-muted"><ImageOff size={16} />Tidak ada foto bukti</div>}
                  <h2 className="mb-1.5 text-[15px] font-semibold text-ink">Deskripsi</h2>
                  <p className="whitespace-pre-line text-sm leading-relaxed text-muted">{r.description}</p>
                </Card>
                {r.schedule && <Card title="Jadwal pengerjaan"><p className="text-sm"><b className="text-ink">{fmtDate(r.schedule.date)}</b><span className="text-muted">. {r.schedule.note}</span></p></Card>}
                {r.signature && <Card title="Tanda tangan digital Pemerintah"><img src={r.signature} alt="Tanda tangan" className="h-20" /></Card>}
                <Card title="Catatan proses">
                  {!r.history.length ? <p className="text-sm text-muted">Belum ada catatan.</p> : (
                    <ol className="space-y-4">{[...r.history].reverse().map((h, i) => (
                      <li key={i} className="flex gap-3 text-sm"><span className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand" />
                        <div>
                          <p className="font-semibold text-ink">{STATUS_LABEL[h.status] || h.status}</p>
                          {h.reason && <p className="text-muted">Alasan: {REASON_LABEL[h.reason] || h.reason}</p>}
                          <p className="text-muted">{h.note || (h.from ? `Status berubah dari ${STATUS_LABEL[h.from] || h.from}.` : 'Laporan dibuat.')}</p>
                          <p className="mt-0.5 text-xs text-muted/70">{fmtDateTime(h.at)}{h.actor && `, oleh ${h.actor}`}</p>
                        </div>
                      </li>
                    ))}</ol>
                  )}
                </Card>
              </div>
              <aside className="order-first space-y-5 lg:sticky lg:top-24 lg:order-none lg:self-start">
                <Card title="Posisi laporan">
                  <div className="mb-4"><StatusBadge status={r.status} /></div>
                  {PIPELINE.includes(r.status) ? <StatusPipeline report={r} /> : <p className="text-sm text-muted">Laporan berstatus {STATUS_LABEL[r.status] || r.status} dan tidak melanjutkan alur penanganan.</p>}
                </Card>
                {(canEdit || canCancel || canApprove || canForward || canSign) && (
                  <Card title="Tindakan" className="print:hidden"><div className="flex flex-col gap-2">
                    {canEdit && <Link to={`/user/reports/${r.id}/edit`} className="block"><Button variant="outline" className="w-full"><Pencil size={16} />Edit laporan</Button></Link>}
                    {canCancel && <Button variant="danger" className="w-full" onClick={() => setModal('cancel')}><XCircle size={16} />Batalkan laporan</Button>}
                    {canApprove && <Button className="w-full" onClick={() => setModal('verify')}><ShieldCheck size={16} />Setujui laporan</Button>}
                    {canForward && <Button className="w-full" onClick={() => setModal('send')}><Send size={16} />Teruskan ke Pemerintah</Button>}
                    {canSign && <Button className="w-full" onClick={() => setModal('approve')}><BadgeCheck size={16} />Setujui dan tanda tangani</Button>}
                  </div></Card>
                )}
              </aside>
            </div>

            <Modal open={modal === 'cancel'} title="Batalkan laporan?" onClose={() => setModal(null)}>
              <p className="mb-4 text-sm text-slate-600">Laporan akan ditandai Dibatalkan dan tidak diproses lebih lanjut.</p>
              <div className="flex justify-end gap-2"><Button variant="outline" onClick={() => setModal(null)}>Kembali</Button><Button variant="danger" loading={busy} onClick={() => act({ status: 'cancelled' }, 'Laporan dibatalkan oleh pelapor.', 'Laporan dibatalkan.')}>Ya, batalkan</Button></div>
            </Modal>
            <Modal open={modal === 'verify'} title="Setujui laporan?" onClose={() => setModal(null)}>
              <p className="mb-4 text-sm text-slate-600">Laporan dinyatakan valid dan siap diteruskan ke Pemerintah.</p>
              <div className="flex justify-end gap-2"><Button variant="outline" onClick={() => setModal(null)}>Kembali</Button><Button loading={busy} onClick={() => act({ status: 'approved' }, 'Laporan disetujui Admin.', 'Laporan disetujui.', user)}>Setujui</Button></div>
            </Modal>
            <Modal open={modal === 'send'} title="Teruskan ke Pemerintah?" onClose={() => setModal(null)}>
              <p className="mb-4 text-sm text-slate-600">Laporan ditandai Diteruskan dan muncul di dashboard Pemerintah.</p>
              <div className="flex justify-end gap-2"><Button variant="outline" onClick={() => setModal(null)}>Kembali</Button><Button loading={busy} onClick={() => act({ status: 'forwarded' }, 'Laporan diteruskan Admin ke Pemerintah.', 'Laporan terkirim ke Pemerintah.', user)}>Teruskan</Button></div>
            </Modal>
            <Modal open={modal === 'approve'} title="Setujui laporan" onClose={() => setModal(null)}>
              <p className="mb-3 text-sm text-slate-600">Bubuhkan tanda tangan digital pada kotak di bawah.</p>
              <SignaturePad onChange={setSig} />
              <div className="mt-4 flex justify-end gap-2"><Button variant="outline" onClick={() => setModal(null)}>Kembali</Button>
                <Button disabled={!sig} loading={busy} onClick={() => act({ status: 'gov_approved', signature: sig }, 'Laporan disetujui dengan tanda tangan digital.', 'Laporan disetujui.', user)}>Setujui</Button></div>
            </Modal>
          </div>
        )
      }}</Async>
    </>
  )
}
