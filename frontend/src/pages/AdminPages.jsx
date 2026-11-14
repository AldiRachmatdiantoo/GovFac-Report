import { useState } from 'react'
import { FileText, Clock, Loader, ShieldCheck } from 'lucide-react'
import { useAsync, Async, Card, Empty, Filters, PageHeader, ReportList, StatCard, StatusDonut, TagBars } from '../components/ui'
import { getReports, getStats } from '../services'

export function AdminDashboard() {
  const s = useAsync(() => Promise.all([getStats(), getReports()]), [])
  return (
    <>
      <PageHeader title="Dashboard Admin" sub="Antrean tinjauan dan ringkasan seluruh laporan masyarakat." />
      <Async state={s}>{([st, all]) => {
        const queue = all.filter(r => r.status === 'admin_review')
        return (
          <div className="space-y-5">
            <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
              <StatCard icon={FileText} label="Total laporan" value={st.total} />
              <StatCard icon={Clock} label="Perlu ditinjau" value={st.by.admin_review || 0} alert={!!st.by.admin_review} />
              <StatCard icon={Loader} label="Disetujui Admin" value={st.by.approved || 0} />
              <StatCard icon={ShieldCheck} label="Diteruskan" value={st.by.forwarded || 0} />
            </div>
            <div className="grid gap-5 lg:grid-cols-[1fr_340px]">
              <section>
                <h2 className="mb-3 text-base font-semibold text-ink">Perlu ditinjau</h2>
                {queue.length ? <ReportList items={queue} base="/admin/reports" showAuthor /> : <Empty text="Semua laporan baru sudah ditinjau." />}
              </section>
              <div className="space-y-5">
                <Card title="Distribusi status"><StatusDonut by={st.by} /></Card>
                <Card title="Laporan per tag"><TagBars items={all} /></Card>
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
      <PageHeader title="Laporan masuk" sub="Buka laporan untuk meninjau detailnya, menyetujuinya, lalu meneruskannya ke Pemerintah." />
      <Filters q={q} setQ={setQ} status={st} setStatus={setSt} />
      <Async state={s}>{list => <ReportList showAuthor base="/admin/reports" items={list.filter(r => (!st || r.status === st) && r.title.toLowerCase().includes(q.toLowerCase()))} />}</Async>
    </>
  )
}
