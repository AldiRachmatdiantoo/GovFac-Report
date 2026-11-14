import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [react(), tailwindcss()],
  // Port dikunci: backend hanya mengizinkan origin FRONTEND_URL (bawaan http://localhost:5173) untuk CORS.
  server: { port: 5173, strictPort: true },
})
