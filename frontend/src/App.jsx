import { Navigate, Outlet, Route, Routes } from 'react-router-dom'
import { Loader2 } from 'lucide-react'
import { useApp } from './context/AppContext'
import Layout from './layouts/Layout'
import { Login, Register, Forgot, Reset } from './pages/Auth'
import { UserDashboard, ReportForm, MyReports, Community } from './pages/UserPages'
import ReportDetail from './pages/ReportDetail'
import { AdminDashboard, AdminReports } from './pages/AdminPages'
import { GovDashboard, GovVerified, Schedules, AuditLog } from './pages/GovPages'
import { HOME } from './constants'
import { FEATURES } from './services'

// Penjaga route: wajib login dan sesuai role
const Guard = ({ role }) => {
  const { user } = useApp()
  if (!user) return <Navigate to="/login" replace />
  if (user.role !== role) return <Navigate to={HOME[user.role]} replace />
  return <Layout />
}
const Public = () => {
  const { user } = useApp()
  return user ? <Navigate to={HOME[user.role]} replace /> : <Outlet />
}

export default function App() {
  const { user, ready } = useApp()
  // Tunggu pengecekan sesi selesai supaya pengguna yang masih login tidak sempat dilempar ke /login.
  if (!ready) return <div className="flex min-h-screen items-center justify-center bg-canvas" role="status" aria-label="Memuat"><Loader2 className="h-7 w-7 animate-spin text-brand" /></div>
  return (
    <Routes>
      <Route element={<Public />}>
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route path="/forgot-password" element={<Forgot />} />
      </Route>
      {/* Tautan dari email reset password; tetap bisa dibuka walau sedang login di perangkat ini. */}
      <Route path="/reset-password" element={<Reset />} />

      <Route path="/user" element={<Guard role="user" />}>
        <Route index element={<Navigate to="dashboard" replace />} />
        <Route path="dashboard" element={<UserDashboard />} />
        <Route path="reports" element={<MyReports />} />
        <Route path="reports/create" element={<ReportForm />} />
        <Route path="reports/:id" element={<ReportDetail />} />
        {FEATURES.editReport && <Route path="reports/:id/edit" element={<ReportForm />} />}
        <Route path="community" element={<Community />} />
      </Route>

      <Route path="/admin" element={<Guard role="admin" />}>
        <Route index element={<Navigate to="dashboard" replace />} />
        <Route path="dashboard" element={<AdminDashboard />} />
        <Route path="reports" element={<AdminReports />} />
        <Route path="reports/:id" element={<ReportDetail />} />
      </Route>

      <Route path="/government" element={<Guard role="government" />}>
        <Route index element={<Navigate to="dashboard" replace />} />
        <Route path="dashboard" element={<GovDashboard />} />
        <Route path="verified-reports" element={<GovVerified />} />
        <Route path="reports/:id" element={<ReportDetail />} />
        <Route path="schedules" element={<Schedules />} />
        <Route path="audit-log" element={<AuditLog />} />
      </Route>

      <Route path="*" element={<Navigate to={user ? HOME[user.role] : '/login'} replace />} />
    </Routes>
  )
}
