import { useState } from 'react'
import { FileText, Clock, Loader, ShieldCheck } from 'lucide-react'
import { useAsync, Async, Card, Empty, Filters, PageHeader, ReportList, StatCard, StatusDonut, CategoryBars } from '../components/ui'
import { getReports, getStats } from '../services'

export function AdminDashboard() {
  const s = useAsync(() => Promise.all([getStats(), getReports()]), [])
  return (
    <>
      <PageHeader title="Dashboard Admin" sub="Antrean tinjauan dan ringkasan seluruh laporan masyarakat." />
      <Async state={s}>{([st, all]) => {
        const queue = all.filter(r => r.status === 'Menunggu')
        return (
          <div className="space-y-5">
            <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
              <StatCard icon={FileText} label="Total laporan" value={st.total} />
              <StatCard icon={Clock} label="Perlu ditinjau" value={st.by['Menunggu'] || 0} alert={!!st.by['Menunggu']} />
              <StatCard icon={Loader} label="Diproses" value={st.by['Diproses'] || 0} />
              <StatCard icon={ShieldCheck} label="Terverifikasi" value={st.by['Terverifikasi'] || 0} />
            </div>
            <div className="grid gap-5 lg:grid-cols-[1fr_340px]">
              <section>
                <h2 className="mb-3 text-base font-semibold text-ink">Perlu ditinjau</h2>
                {queue.length ? <ReportList items={queue} base="/admin/reports" showAuthor /> : <Empty text="Semua laporan baru sudah ditinjau." />}
              </section>
              <div className="space-y-5">
                <Card title="Distribusi status"><StatusDonut by={st.by} /></Card>
                <Card title="Laporan per kategori"><CategoryBars items={all} /></Card>
              </div>
            </div>
          </div>
        )
      }}</Async>
    </>
  )
}

export function AdminReports() {
  const s = useAsync(() => getReports(), [])
  const [q, setQ] = useState('')
  const [st, setSt] = useState('')
  return (
    <>
      <PageHeader title="Laporan masuk" sub="Buka laporan untuk meninjau detailnya. Laporan baru otomatis berstatus Diproses saat dibuka." />
      <Filters q={q} setQ={setQ} status={st} setStatus={setSt} />
      <Async state={s}>{list => <ReportList showAuthor base="/admin/reports" items={list.filter(r => (!st || r.status === st) && r.title.toLowerCase().includes(q.toLowerCase()))} />}</Async>
    </>
  )
}
