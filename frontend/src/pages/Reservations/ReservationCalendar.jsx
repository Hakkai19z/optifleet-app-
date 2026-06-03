import React, { useMemo, useState } from 'react'
import { Modal } from '../../components/ui/Modal'

const STATUT = {
  en_attente: { label: 'En attente', color: '#F59E0B', bg: 'rgba(245,158,11,0.18)', border: 'rgba(245,158,11,0.4)' },
  confirmee: { label: 'Confirmée', color: '#10B981', bg: 'rgba(16,185,129,0.18)', border: 'rgba(16,185,129,0.4)' },
  annulee: { label: 'Annulée', color: '#EF4444', bg: 'rgba(239,68,68,0.15)', border: 'rgba(239,68,68,0.35)' },
  terminee: { label: 'Terminée', color: '#6B7280', bg: 'rgba(107,114,128,0.15)', border: 'rgba(107,114,128,0.35)' },
}

const JOURS = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']
const MOIS = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']

const startOfDay = (d) => new Date(d.getFullYear(), d.getMonth(), d.getDate())
const sameDay = (a, b) => a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate()
const fmtHeure = (d) => new Date(d).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })

export function ReservationCalendar({ reservations }) {
  const today = startOfDay(new Date())
  const [cursor, setCursor] = useState(new Date(today.getFullYear(), today.getMonth(), 1))
  const [dayDetail, setDayDetail] = useState(null)

  const year = cursor.getFullYear()
  const month = cursor.getMonth()

  // Grille de 42 cases démarrant au lundi de la semaine du 1er du mois
  const days = useMemo(() => {
    const first = new Date(year, month, 1)
    const offset = (first.getDay() + 6) % 7 // Lundi = 0
    const gridStart = new Date(year, month, 1 - offset)
    return [...Array(42)].map((_, i) => new Date(gridStart.getFullYear(), gridStart.getMonth(), gridStart.getDate() + i))
  }, [year, month])

  // Pré-calcule, pour chaque jour, les réservations qui le chevauchent
  const resaParJour = useMemo(() => {
    return days.map((day) => {
      const dayStart = startOfDay(day).getTime()
      const dayEnd = dayStart + 86400000 - 1
      return reservations.filter((r) => {
        const debut = new Date(r.dateDebut).getTime()
        const fin = new Date(r.dateFin).getTime()
        return debut <= dayEnd && fin >= dayStart
      })
    })
  }, [days, reservations])

  const goPrev = () => setCursor(new Date(year, month - 1, 1))
  const goNext = () => setCursor(new Date(year, month + 1, 1))
  const goToday = () => setCursor(new Date(today.getFullYear(), today.getMonth(), 1))

  return (
    <div className="space-y-4">
      {/* Barre de navigation */}
      <div className="flex items-center justify-between">
        <h3 className="font-display text-lg font-semibold text-white capitalize">
          {MOIS[month]} {year}
        </h3>
        <div className="flex items-center gap-2">
          <button onClick={goToday} className="px-3 py-1.5 rounded-lg text-sm text-slate-300 border border-white/10 hover:bg-white/5 transition-colors">
            Aujourd'hui
          </button>
          <button onClick={goPrev} aria-label="Mois précédent" className="w-8 h-8 rounded-lg flex items-center justify-center text-slate-300 border border-white/10 hover:bg-white/5 transition-colors">
            ‹
          </button>
          <button onClick={goNext} aria-label="Mois suivant" className="w-8 h-8 rounded-lg flex items-center justify-center text-slate-300 border border-white/10 hover:bg-white/5 transition-colors">
            ›
          </button>
        </div>
      </div>

      {/* En-têtes jours */}
      <div className="grid grid-cols-7 gap-px">
        {JOURS.map((j) => (
          <div key={j} className="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider py-2">{j}</div>
        ))}
      </div>

      {/* Grille */}
      <div className="grid grid-cols-7 gap-px rounded-xl overflow-hidden" style={{ background: 'rgba(255,255,255,0.05)' }}>
        {days.map((day, i) => {
          const inMonth = day.getMonth() === month
          const isToday = sameDay(day, today)
          const resas = resaParJour[i]
          const visibles = resas.slice(0, 3)
          const reste = resas.length - visibles.length
          return (
            <div
              key={i}
              onClick={() => resas.length && setDayDetail({ day, resas })}
              className={`min-h-24 p-1.5 flex flex-col gap-1 transition-colors ${resas.length ? 'cursor-pointer hover:bg-white/5' : ''}`}
              style={{ background: inMonth ? 'rgba(15,22,40,0.6)' : 'rgba(10,14,26,0.6)' }}
            >
              <span className={`text-xs font-medium self-end w-6 h-6 flex items-center justify-center rounded-full ${
                isToday ? 'text-white' : inMonth ? 'text-slate-400' : 'text-slate-700'
              }`} style={isToday ? { background: 'linear-gradient(135deg, #6C63FF, #00D4FF)' } : undefined}>
                {day.getDate()}
              </span>
              <div className="flex flex-col gap-1">
                {visibles.map((r) => {
                  const s = STATUT[r.statut] || STATUT.en_attente
                  return (
                    <div
                      key={r.id}
                      title={`${r.vehicule?.immatriculation || '—'} · ${r.conducteur ? r.conducteur.prenom + ' ' + r.conducteur.nom : ''}\n${fmtHeure(r.dateDebut)} → ${fmtHeure(r.dateFin)}${r.motif ? '\n' + r.motif : ''}`}
                      className="text-[10px] leading-tight font-medium truncate px-1.5 py-0.5 rounded"
                      style={{ background: s.bg, color: s.color, border: `1px solid ${s.border}` }}
                    >
                      {r.vehicule?.immatriculation || 'Réservation'}
                    </div>
                  )
                })}
                {reste > 0 && <span className="text-[10px] text-slate-500 pl-1">+{reste} autre{reste > 1 ? 's' : ''}</span>}
              </div>
            </div>
          )
        })}
      </div>

      {/* Légende */}
      <div className="flex flex-wrap gap-4 pt-1">
        {Object.entries(STATUT).map(([k, s]) => (
          <div key={k} className="flex items-center gap-1.5">
            <span className="w-3 h-3 rounded" style={{ background: s.bg, border: `1px solid ${s.border}` }} />
            <span className="text-xs text-slate-400">{s.label}</span>
          </div>
        ))}
      </div>

      {/* Détail d'un jour */}
      <Modal
        isOpen={!!dayDetail}
        onClose={() => setDayDetail(null)}
        title={dayDetail ? `Réservations du ${dayDetail.day.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' })}` : ''}
      >
        <div className="space-y-3">
          {dayDetail?.resas.map((r) => {
            const s = STATUT[r.statut] || STATUT.en_attente
            return (
              <div key={r.id} className="p-3 rounded-xl border border-white/5" style={{ background: 'rgba(255,255,255,0.03)' }}>
                <div className="flex items-center justify-between mb-1">
                  <span className="font-mono font-bold text-violet-400">{r.vehicule?.immatriculation || '—'}</span>
                  <span className="text-xs font-semibold px-2 py-0.5 rounded-full" style={{ color: s.color, background: s.bg }}>{s.label}</span>
                </div>
                <p className="text-sm text-slate-300">
                  {r.vehicule ? `${r.vehicule.marque} ${r.vehicule.modele}` : ''}
                  {r.conducteur && <span className="text-slate-500"> · {r.conducteur.prenom} {r.conducteur.nom}</span>}
                </p>
                <p className="text-xs text-slate-500 mt-0.5">{fmtHeure(r.dateDebut)} → {fmtHeure(r.dateFin)}</p>
                {r.motif && <p className="text-xs text-slate-500 mt-1 italic">{r.motif}</p>}
              </div>
            )
          })}
        </div>
      </Modal>
    </div>
  )
}
