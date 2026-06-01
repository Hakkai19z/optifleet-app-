import React from 'react'
import { useToastStore } from '../../store/toastStore'

function ToastItem({ toast }) {
  const { removeToast } = useToastStore()
  const styles = {
    success: { border: 'border-emerald-500/30', icon: '✓', iconBg: 'bg-emerald-500/20 text-emerald-400' },
    error: { border: 'border-red-500/30', icon: '✕', iconBg: 'bg-red-500/20 text-red-400' },
    warning: { border: 'border-amber-500/30', icon: '!', iconBg: 'bg-amber-500/20 text-amber-400' },
  }
  const s = styles[toast.type] || styles.success
  return (
    <div className={`flex items-center gap-3 px-4 py-3 rounded-2xl border ${s.border} shadow-2xl min-w-72 animate-slide-up`}
      style={{ background: 'rgba(17,24,39,0.95)', backdropFilter: 'blur(20px)' }}>
      <div className={`w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 ${s.iconBg}`}>{s.icon}</div>
      <p className="text-sm text-slate-200 flex-1">{toast.message}</p>
      <button onClick={() => removeToast(toast.id)} className="text-slate-500 hover:text-white text-lg leading-none">×</button>
    </div>
  )
}

export function ToastContainer() {
  const { toasts } = useToastStore()
  return (
    <div className="fixed bottom-6 right-6 z-50 flex flex-col gap-2">
      {toasts.map((t) => <ToastItem key={t.id} toast={t} />)}
    </div>
  )
}
