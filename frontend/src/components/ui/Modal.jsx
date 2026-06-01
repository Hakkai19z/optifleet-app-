import React, { useEffect } from 'react'

export function Modal({ isOpen, onClose, title, children, size = 'md' }) {
  useEffect(() => {
    const handleEsc = (e) => { if (e.key === 'Escape') onClose() }
    if (isOpen) { document.addEventListener('keydown', handleEsc); document.body.style.overflow = 'hidden' }
    return () => { document.removeEventListener('keydown', handleEsc); document.body.style.overflow = '' }
  }, [isOpen, onClose])

  if (!isOpen) return null
  const sizes = { sm: 'max-w-md', md: 'max-w-lg', lg: 'max-w-2xl', xl: 'max-w-4xl' }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-black/70 backdrop-blur-sm" onClick={onClose} />
      <div className={`relative w-full ${sizes[size]} max-h-[90vh] overflow-y-auto rounded-2xl border border-white/10 bg-dark-800 shadow-2xl shadow-black/50 animate-slide-up`}
        style={{ background: '#111827' }}>
        <div className="flex items-center justify-between p-6 border-b border-white/5">
          <h2 className="font-display text-lg font-semibold text-white">{title}</h2>
          <button onClick={onClose} className="p-1.5 rounded-lg text-slate-400 hover:text-white transition-colors"
            style={{ ':hover': { background: 'rgba(255,255,255,0.08)' } }}>
            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div className="p-6">{children}</div>
      </div>
    </div>
  )
}
