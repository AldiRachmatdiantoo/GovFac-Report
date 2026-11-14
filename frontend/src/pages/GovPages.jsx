import { useState } from 'react'
import { Link } from 'react-router-dom'
import { BadgeCheck, CalendarCheck, ShieldCheck } from 'lucide-react'
import { useApp } from '../context/AppContext'
import { useAsync, Async, Button, Card, Empty, Field, Filters, Modal, PageHeader, ReportList, StatCard, StatusBadge, StatusDonut, fmtDate } from '../components/ui'
import { getAudit, getReports, updateReport } from '../services'
import { GOV_STATUSES } from '../constants'

export function GovDashboard() {
  const s = useAsync(() => getReports({ statuses: GOV_STATUSES }), [])
  return (
    <>
      <PageHeader title="Dashboard Pemerintah" sub="Laporan yang sudah diverifikasi Admin dan menunggu tindak lanjut." />
      <Async state={s}>{list => {
        const by = {}
        list.forEach(r => { by[r.status] = (by[r.status] || 0) + 1 })
        const queue = list.filter(r => r.status === 'forwarded')
        const upcoming = list.filter(r => r.schedule).sort((a, b) => a.schedule.date.localeCompare(b.schedule.date))
        return (
          <div className="space-y-5">
            <div className="grid grid-cols-3 gap-3">
              <StatCard icon={ShieldCheck} label="Menunggu persetujuan" value={by.forwarded || 0} alert={!!by.forwarded} />
              <StatCard icon={BadgeCheck} label="Disetujui" value={by.gov_approved || 0} />
              <StatCard icon={CalendarCheck} label="Terjadwal" value={by.scheduled || 0} />
            </div>
            <div className="grid gap-5 lg:grid-cols-[1fr_340px]">
              <div className="space-y-5">
                <section>
                  <h2 className="mb-3 text-base font-semibold text-ink">Menunggu persetujuan</h2>
                  {queue.length ? <ReportList items={queue} base="/government/reports" showAuthor /> : <Empty text="Tidak ada laporan yang menunggu persetujuan." />}
                </section>
                <section>
                  <h2 className="mb-3 text-base font-semibold text-ink">Jadwal pekerjaan</h2>
                  {upcoming.length ? <div className="divide-y divide-line overflow-hidden rounded-card border border-line bg-white shadow-card">{upcoming.map(r => {
                    const d = new Date(r.schedule.date + 'T00:00:00')
                    return <Link key={r.id} to={`/government/reports/${r.id}`} className="flex items-center gap-4 p-4 transition hover:bg-brand-50/60">
                      <div className="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-ctl bg-brand-50 text-brand"><span className="text-base font-bold leading-none">{d.getDate()}</span><span className="text-[11px] font-semibold">{d.toLocaleDateString('id-ID', { month: 'short' })}</span></div>
                      <div className="min-w-0"><p className="truncate text-sm font-semibold text-ink">{r.title}</p><p className="truncate text-xs text-muted">{r.schedule.note}</p></div>
                    </Link>
                  })}</div> : <Empty text="Belum ada pekerjaan yang dijadwalkan." />}
                </section>
              </div>
              <Card title="Distribusi status"><StatusDonut by={by} /></Card>
            </div>
          </div>
        )
      }}</Async>
    </>
  )
}

export function GovVerified() {
  const s = useAsync(() => getReports({ statuses: ['forwarded'] }), [])
  const [q, setQ] = useState('')
  return (
    <>
      <PageHeader title="Laporan terverifikasi" sub="Buka laporan untuk menyetujuinya dengan tanda tangan digital." />
      <Filters q={q} setQ={setQ} />
      <Async state={s}>{list => <ReportList showAuthor base="/government/reports" items={list.filter(r => r.title.toLowerCase().includes(q.toLowerCase()))} />}</Async>
    </>
  )
}

export function Schedules() {
  const { user, toast } = useApp()
  const s = useAsync(() => getReports({ statuses: ['gov_approved', 'scheduled'] }), [])
  const [m, setM] = useState(null)
  const [date, setDate] = useState('')
  const [note, setNote] = useState('')
  const [busy, setBusy] = useState(false)
  const open = r => { setM(r); setDate(r.schedule?.date || ''); setNote('') }

  const save = async () => {
    if (!date || date < new Date().toISOString().slice(0, 10)) return toast('Pilih tanggal yang valid (tidak di masa lalu).', 'error')
    if (!note.trim()) return toast(m.schedule ? 'Alasan perubahan wajib diisi.' : 'Catatan pekerjaan wajib diisi.', 'error')
    setBusy(true)
    try {
      await updateReport(m.id, { status: 'scheduled', schedule: { date, note } },
        m.schedule ? `Jadwal diubah ke ${date}. Alasan: ${note}` : `Pekerjaan dijadwalkan pada ${date}. Catatan: ${note}`, user)
      toast('Jadwal tersimpan.'); setM(null); s.reload()
    } catch (e) { toast(e.message, 'error') } finally { setBusy(false) }
  }

  return (
    <>
      <PageHeader title="Penjadwalan pekerjaan" sub="Jadwalkan laporan yang disetujui. Setiap perubahan jadwal tercatat di audit log." />
      <Async state={s}>{list => !list.length ? <Empty text="Belum ada laporan yang disetujui." /> : (
        <div className="divide-y divide-line overflow-hidden rounded-card border border-line bg-white shadow-card">
          {list.map(r => (
            <div key={r.id} className="flex flex-wrap items-center justify-between gap-3 p-4">
              <div className="min-w-0"><p className="font-medium">{r.title}</p>
                <p className="text-xs text-slate-500">{r.schedule ? `Jadwal: ${fmtDate(r.schedule.date)}. ${r.schedule.note}` : 'Belum dijadwalkan'}</p></div>
              <div className="flex items-center gap-2"><StatusBadge status={r.status} />
                <Button variant="outline" onClick={() => open(r)}>{r.schedule ? 'Ubah jadwal' : 'Jadwalkan'}</Button></div>
            </div>
          ))}
        </div>
      )}</Async>
      <Modal open={!!m} title={m?.schedule ? 'Ubah jadwal' : 'Jadwalkan pekerjaan'} onClose={() => setM(null)}>
        <div className="space-y-3">
          <Field label="Tanggal pengerjaan" type="date" value={date} onChange={e => setDate(e.target.value)} />
          <Field label={m?.schedule ? 'Alasan perubahan' : 'Catatan pekerjaan'} as="textarea" rows={3} value={note} onChange={e => setNote(e.target.value)} />
          <div className="flex justify-end gap-2"><Button variant="outline" onClick={() => setM(null)}>Batal</Button><Button loading={busy} onClick={save}>Simpan jadwal</Button></div>
        </div>
      </Modal>
    </>
  )
}

export function AuditLog() {
  const s = useAsync(() => getAudit(), [])
  return (
    <>
      <PageHeader title="Audit log" sub="Riwayat tindakan Pemerintah. Log bersifat permanen dan tidak dapat dihapus." />
      <Async state={s}>{list => !list.length ? <Empty text="Belum ada aktivitas tercatat. Setujui atau jadwalkan laporan untuk melihat log di sini." /> : (
        <Card className="overflow-x-auto !p-0">
          <table className="w-full text-left text-sm">
            <thead className="bg-slate-50 text-slate-600"><tr><th className="p-3">Waktu</th><th className="p-3">Petugas</th><th className="p-3">Laporan</th><th className="p-3">Tindakan</th></tr></thead>
            <tbody className="divide-y divide-slate-100">{list.map(a => (
              <tr key={a.id}><td className="whitespace-nowrap p-3">{a.at}</td><td className="p-3">{a.actor}</td><td className="p-3">#{a.reportId} {a.title}</td><td className="p-3">{a.action}</td></tr>
            ))}</tbody>
          </table>
        </Card>
      )}</Async>
    </>
  )
}
