import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { ArrowLeft, Download, Pencil, Send, XCircle, BadgeCheck, ImageOff, Tag, MapPin, CalendarDays, User } from 'lucide-react'
import { useApp } from '../context/AppContext'
import { useAsync, Async, Button, Card, Modal, SignaturePad, StatusBadge, StatusPipeline, fmtDate } from '../components/ui'
import { getReport, updateReport } from '../services'
import { EDITABLE, PIPELINE } from '../constants'

// Satu halaman detail untuk semua role (SCRUM-13, 14, 15, 16, 21, 22, 23, 26). Aksi ditampilkan sesuai role & status.
export default function ReportDetail() {
  const { id } = useParams()
  const { user, toast } = useApp()
  const nav = useNavigate()
  const s = useAsync(() => getReport(id), [id])
  const [modal, setModal] = useState(null)
  const [sig, setSig] = useState(null)
  const [busy, setBusy] = useState(false)
  const role = user.role

  // SCRUM-22: status otomatis berpindah saat Admin membuka laporan baru (pemicu final menunggu klarifikasi)
  useEffect(() => {
    if (role === 'admin' && s.data?.status === 'Menunggu') {
      updateReport(id, { status: 'Diproses' }, 'Laporan dibuka Admin. Status berubah otomatis menjadi Diproses.')
        .then(() => { toast('Status otomatis berubah menjadi Diproses.', 'info'); s.reload() })
    }
  }, [s.data?.status])

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
        const canEdit = role === 'user' && own && EDITABLE.includes(r.status)
        return (
          <div className="space-y-5">
            <header className="flex flex-wrap items-start justify-between gap-4">
              <div className="min-w-0">
                <p className="text-sm font-medium text-muted">Laporan #{r.id}</p>
                <h1 className="mt-1 text-[22px] font-bold leading-snug tracking-tight text-ink sm:text-[28px]">{r.title}</h1>
                <p className="mt-2.5 flex flex-wrap gap-x-4 gap-y-1.5 text-[13px] text-muted"><span className="inline-flex items-center gap-1.5"><Tag size={14} />{r.category}</span><span className="inline-flex items-center gap-1.5"><MapPin size={14} />{r.location}</span><span className="inline-flex items-center gap-1.5"><CalendarDays size={14} />{fmtDate(r.date)}</span><span className="inline-flex items-center gap-1.5"><User size={14} />{r.author}</span></p>
              </div>
              <Button variant="outline" className="print:hidden" onClick={() => window.print()}><Download size={16} />{role === 'admin' ? 'Export PDF' : 'Unduh PDF'}</Button>
            </header>
            <div className="grid gap-5 lg:grid-cols-[1fr_340px]">
              <div className="space-y-5">
                <Card>
                  {r.photo ? <img src={r.photo} alt="Foto bukti" className="mb-4 max-h-80 w-full rounded-ctl object-cover" /> : <div className="mb-4 flex h-24 items-center justify-center gap-2 rounded-ctl bg-canvas text-[13px] text-muted"><ImageOff size={16} />Tidak ada foto bukti</div>}
                  <h2 className="mb-1.5 text-[15px] font-semibold text-ink">Deskripsi</h2>
                  <p className="text-sm leading-relaxed text-muted">{r.description}</p>
                </Card>
                {r.schedule && <Card title="Jadwal pengerjaan"><p className="text-sm"><b className="text-ink">{fmtDate(r.schedule.date)}</b><span className="text-muted">. {r.schedule.note}</span></p></Card>}
                {r.signature && <Card title="Tanda tangan digital Pemerintah"><img src={r.signature} alt="Tanda tangan" className="h-20" /></Card>}
                <Card title="Catatan proses">
                  <ol className="space-y-4">{[...r.history].reverse().map((h, i) => <li key={i} className="flex gap-3 text-sm"><span className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand" /><div><p className="font-semibold text-ink">{h.status}</p><p className="text-muted">{h.note}</p><p className="mt-0.5 text-xs text-muted/70">{h.at}</p></div></li>)}</ol>
                </Card>
              </div>
              <aside className="order-first space-y-5 lg:sticky lg:top-24 lg:order-none lg:self-start">
                <Card title="Posisi laporan">
                  <div className="mb-4"><StatusBadge status={r.status} /></div>
                  {PIPELINE.includes(r.status) ? <StatusPipeline report={r} /> : <p className="text-sm text-muted">Laporan berstatus {r.status} dan tidak melanjutkan alur penanganan.</p>}
                </Card>
                {(canEdit || (role === 'admin' && r.status === 'Diproses') || (role === 'government' && r.status === 'Terverifikasi')) && (
                  <Card title="Tindakan" className="print:hidden"><div className="flex flex-col gap-2">
                    {canEdit && <Link to={`/user/reports/${r.id}/edit`} className="block"><Button variant="outline" className="w-full"><Pencil size={16} />Edit laporan</Button></Link>}
                    {canEdit && <Button variant="danger" className="w-full" onClick={() => setModal('cancel')}><XCircle size={16} />Batalkan laporan</Button>}
                    {role === 'admin' && r.status === 'Diproses' && <Button className="w-full" onClick={() => setModal('send')}><Send size={16} />Kirim ke Pemerintah</Button>}
                    {role === 'government' && r.status === 'Terverifikasi' && <Button className="w-full" onClick={() => setModal('approve')}><BadgeCheck size={16} />Setujui dan tanda tangani</Button>}
                  </div></Card>
                )}
              </aside>
            </div>

            <Modal open={modal === 'cancel'} title="Batalkan laporan?" onClose={() => setModal(null)}>
              <p className="mb-4 text-sm text-slate-600">Laporan akan ditandai Dibatalkan dan tidak diproses lebih lanjut.</p>
              <div className="flex justify-end gap-2"><Button variant="outline" onClick={() => setModal(null)}>Kembali</Button><Button variant="danger" loading={busy} onClick={() => act({ status: 'Dibatalkan' }, 'Laporan dibatalkan oleh pelapor.', 'Laporan dibatalkan.')}>Ya, batalkan</Button></div>
            </Modal>
            <Modal open={modal === 'send'} title="Kirim ke Pemerintah?" onClose={() => setModal(null)}>
              <p className="mb-4 text-sm text-slate-600">Laporan ditandai Terverifikasi dan muncul di dashboard Pemerintah.</p>
              <div className="flex justify-end gap-2"><Button variant="outline" onClick={() => setModal(null)}>Kembali</Button><Button loading={busy} onClick={() => act({ status: 'Terverifikasi' }, 'Laporan diverifikasi Admin dan dikirim ke Pemerintah.', 'Laporan terkirim ke Pemerintah.')}>Kirim</Button></div>
            </Modal>
            <Modal open={modal === 'approve'} title="Setujui laporan" onClose={() => setModal(null)}>
              <p className="mb-3 text-sm text-slate-600">Bubuhkan tanda tangan digital pada kotak di bawah.</p>
              <SignaturePad onChange={setSig} />
              <div className="mt-4 flex justify-end gap-2"><Button variant="outline" onClick={() => setModal(null)}>Kembali</Button>
                <Button disabled={!sig} loading={busy} onClick={() => act({ status: 'Disetujui', signature: sig }, 'Laporan disetujui dengan tanda tangan digital.', 'Laporan disetujui.', user)}>Setujui</Button></div>
            </Modal>
          </div>
        )
      }}</Async>
    </>
  )
}
