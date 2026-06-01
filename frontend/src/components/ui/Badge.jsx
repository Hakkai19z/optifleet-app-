import React from 'react'

const variants = {
  disponible: { bg: 'bg-emerald-500/15', text: 'text-emerald-400', dot: 'bg-emerald-400', label: 'Disponible' },
  en_mission: { bg: 'bg-blue-500/15', text: 'text-blue-400', dot: 'bg-blue-400', label: 'En mission' },
  maintenance: { bg: 'bg-amber-500/15', text: 'text-amber-400', dot: 'bg-amber-400', label: 'Maintenance' },
  inactif: { bg: 'bg-slate-500/15', text: 'text-slate-400', dot: 'bg-slate-400', label: 'Inactif' },
  alerte: { bg: 'bg-red-500/15', text: 'text-red-400', dot: 'bg-red-400', label: 'Alerte' },
  en_attente: { bg: 'bg-amber-500/15', text: 'text-amber-400', dot: 'bg-amber-400', label: 'En attente' },
  en_cours: { bg: 'bg-blue-500/15', text: 'text-blue-400', dot: 'bg-blue-400', label: 'En cours' },
  resolue: { bg: 'bg-emerald-500/15', text: 'text-emerald-400', dot: 'bg-emerald-400', label: 'Résolue' },
  ADMIN: { bg: 'bg-violet-500/15', text: 'text-violet-400', dot: 'bg-violet-400', label: 'Administrateur' },
  GESTIONNAIRE: { bg: 'bg-cyan-500/15', text: 'text-cyan-400', dot: 'bg-cyan-400', label: 'Gestionnaire' },
  CONDUCTEUR: { bg: 'bg-slate-500/15', text: 'text-slate-400', dot: 'bg-slate-400', label: 'Conducteur' },
}

export function Badge({ variant, label, className = '' }) {
  const v = variants[variant] || { bg: 'bg-slate-500/15', text: 'text-slate-400', dot: 'bg-slate-400', label: variant }
  return (
    <span className={`badge ${v.bg} ${v.text} ${className}`}>
      <span className={`w-1.5 h-1.5 rounded-full ${v.dot} animate-pulse`} />
      {label || v.label}
    </span>
  )
}
