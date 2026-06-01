import React from 'react'

export function TopBar({ title, subtitle, actions }) {
  return (
    <header className="px-8 py-5 border-b border-white/5 flex items-center justify-between"
      style={{ background: 'rgba(11,15,26,0.8)', backdropFilter: 'blur(20px)' }}>
      <div>
        <h1 className="font-display text-xl font-bold text-white">{title}</h1>
        {subtitle && <p className="text-sm text-slate-500 mt-0.5">{subtitle}</p>}
      </div>
      {actions && <div className="flex items-center gap-3">{actions}</div>}
    </header>
  )
}
