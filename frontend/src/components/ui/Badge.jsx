import React from 'react'

const variantStyles = {
  disponible: 'bg-teal-100 text-teal-800 border border-teal-200',
  en_mission: 'bg-blue-100 text-blue-800 border border-blue-200',
  maintenance: 'bg-amber-100 text-amber-800 border border-amber-200',
  inactif: 'bg-gray-100 text-gray-600 border border-gray-200',
  alerte: 'bg-red-100 text-red-800 border border-red-200',
  en_attente: 'bg-amber-100 text-amber-800 border border-amber-200',
  en_cours: 'bg-blue-100 text-blue-800 border border-blue-200',
  resolue: 'bg-teal-100 text-teal-800 border border-teal-200',
  ADMIN: 'bg-purple-100 text-purple-800 border border-purple-200',
  GESTIONNAIRE: 'bg-blue-100 text-blue-800 border border-blue-200',
  CONDUCTEUR: 'bg-gray-100 text-gray-700 border border-gray-200',
}

const labels = {
  disponible: 'Disponible',
  en_mission: 'En mission',
  maintenance: 'Maintenance',
  inactif: 'Inactif',
  alerte: 'Alerte',
  en_attente: 'En attente',
  en_cours: 'En cours',
  resolue: 'Résolue',
  ADMIN: 'Administrateur',
  GESTIONNAIRE: 'Gestionnaire',
  CONDUCTEUR: 'Conducteur',
}

export function Badge({ variant, label, className = '' }) {
  const style = variantStyles[variant] || 'bg-gray-100 text-gray-600 border border-gray-200'
  const displayLabel = label || labels[variant] || variant

  return (
    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${style} ${className}`}>
      {displayLabel}
    </span>
  )
}
