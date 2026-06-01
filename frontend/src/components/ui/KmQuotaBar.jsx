import React from 'react'

export function KmQuotaBar({ kilometrage, quotaKmAnnuel, showLabel = true, className = '' }) {
  if (!quotaKmAnnuel) return null

  const pct = Math.min((kilometrage / quotaKmAnnuel) * 100, 100)
  const isExceeded = kilometrage >= quotaKmAnnuel
  const isWarning = pct >= 80 && !isExceeded
  const isGood = pct < 80

  const barColor = isExceeded
    ? 'linear-gradient(90deg, #EF4444, #DC2626)'
    : isWarning
    ? 'linear-gradient(90deg, #F59E0B, #D97706)'
    : 'linear-gradient(90deg, #10B981, #059669)'

  const textColor = isExceeded ? 'text-red-400' : isWarning ? 'text-amber-400' : 'text-emerald-400'

  return (
    <div className={`space-y-1.5 ${className}`}>
      {showLabel && (
        <div className="flex items-center justify-between text-xs">
          <span className="text-slate-500">Quota annuel</span>
          <span className={`font-semibold ${textColor}`}>
            {isExceeded ? '⚠ Dépassé' : `${pct.toFixed(0)}%`}
          </span>
        </div>
      )}
      <div className="h-2 rounded-full bg-white/5 overflow-hidden">
        <div
          className="h-full rounded-full transition-all duration-700"
          style={{ width: `${pct}%`, background: barColor }}
        />
      </div>
      {showLabel && (
        <div className="flex justify-between text-xs text-slate-600">
          <span>{kilometrage.toLocaleString('fr-FR')} km</span>
          <span>{quotaKmAnnuel.toLocaleString('fr-FR')} km</span>
        </div>
      )}
    </div>
  )
}
