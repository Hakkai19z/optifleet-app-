import React, { useState, useEffect } from 'react'
import { Layout } from '../../components/layout/Layout'
import { TopBar } from '../../components/layout/TopBar'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Modal } from '../../components/ui/Modal'
import { Input } from '../../components/ui/Input'
import { Select } from '../../components/ui/Select'
import { SkeletonTable } from '../../components/ui/Skeleton'
import { reservationService } from '../../services/reservationService'
import { vehiculeService } from '../../services/vehiculeService'
import { utilisateurService } from '../../services/utilisateurService'
import { useToastStore } from '../../store/toastStore'
import { useAuth } from '../../hooks/useAuth'
import { ReservationCalendar } from './ReservationCalendar'

const STATUT_CONFIG = {
  en_attente: { label: 'En attente', color: '#F59E0B', bg: 'rgba(245,158,11,0.15)' },
  confirmee: { label: 'Confirmée', color: '#10B981', bg: 'rgba(16,185,129,0.15)' },
  annulee: { label: 'Annulée', color: '#EF4444', bg: 'rgba(239,68,68,0.15)' },
  terminee: { label: 'Terminée', color: '#6B7280', bg: 'rgba(107,114,128,0.15)' },
}

const fmt = (d) => new Date(d).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })

export default function ReservationsList() {
  const { hasRole, user } = useAuth()
  const { addToast } = useToastStore()
  const [reservations, setReservations] = useState([])
  const [vehicules, setVehicules] = useState([])
  const [conducteurs, setConducteurs] = useState([])
  const [isLoading, setIsLoading] = useState(true)
  const [showModal, setShowModal] = useState(false)
  const [form, setForm] = useState({ vehicule: '', conducteur: '', dateDebut: '', dateFin: '', motif: '' })
  const [view, setView] = useState('table')
  const [vehiculeFiltre, setVehiculeFiltre] = useState('')

  const reservationsFiltrees = vehiculeFiltre
    ? reservations.filter((r) => r.vehicule?.immatriculation === vehiculeFiltre)
    : reservations

  const reload = () => reservationService.getAll({ 'order[dateDebut]': 'asc' }).then((d) => setReservations(d['hydra:member'] || d))

  useEffect(() => {
    const requests = [
      reservationService.getAll({ 'order[dateDebut]': 'asc' }),
      vehiculeService.getAll(),
    ]
    // La liste des conducteurs n'est accessible qu'aux gestionnaires (ROLE_GESTIONNAIRE).
    // Un conducteur réserve uniquement pour lui-même, il n'en a donc pas besoin.
    if (hasRole('GESTIONNAIRE')) {
      requests.push(utilisateurService.getAll())
    }
    Promise.all(requests).then(([res, veh, users]) => {
      setReservations(res['hydra:member'] || res)
      setVehicules(veh['hydra:member'] || veh)
      if (users) {
        const u = users['hydra:member'] || users
        setConducteurs(u.filter((x) => x.role === 'CONDUCTEUR'))
      }
    }).finally(() => setIsLoading(false))
  }, [])

  const handleCreate = async (e) => {
    e.preventDefault()
    try {
      await reservationService.create({
        vehicule: form.vehicule,
        // Un conducteur réserve à son nom ; un gestionnaire choisit le conducteur.
        conducteur: hasRole('GESTIONNAIRE') ? form.conducteur : `/api/utilisateurs/${user.id}`,
        dateDebut: new Date(form.dateDebut).toISOString(),
        dateFin: new Date(form.dateFin).toISOString(),
        statut: 'en_attente',
        motif: form.motif || null,
      })
      addToast('Réservation créée')
      setShowModal(false)
      setForm({ vehicule: '', conducteur: '', dateDebut: '', dateFin: '', motif: '' })
      await reload()
    } catch (err) {
      // 422 = créneau déjà réservé (validateur CreneauDisponible)
      const violations = err?.response?.data?.violations
      if (err?.response?.status === 422 && violations?.length) {
        addToast(violations[0].message, 'error')
      } else {
        addToast('Erreur lors de la création', 'error')
      }
    }
  }

  const changeStatut = async (id, statut) => {
    try {
      await reservationService.update(id, { statut })
      addToast('Réservation mise à jour')
      await reload()
    } catch {
      addToast('Mise à jour impossible', 'error')
    }
  }

  const columns = [
    { key: 'vehicule', label: 'Véhicule', render: (row) => (
      <span className="font-mono font-bold text-violet-400">{row.vehicule?.immatriculation || '—'}</span>
    )},
    { key: 'conducteur', label: 'Conducteur', render: (row) => (
      <span className="text-slate-300">{row.conducteur ? `${row.conducteur.prenom} ${row.conducteur.nom}` : '—'}</span>
    )},
    { key: 'dateDebut', label: 'Début', render: (row) => <span className="text-slate-400">{fmt(row.dateDebut)}</span> },
    { key: 'dateFin', label: 'Fin', render: (row) => <span className="text-slate-400">{fmt(row.dateFin)}</span> },
    { key: 'statut', label: 'Statut', render: (row) => {
      const c = STATUT_CONFIG[row.statut] || STATUT_CONFIG.en_attente
      return <span className="text-xs font-semibold px-2.5 py-1 rounded-full" style={{ color: c.color, background: c.bg }}>{c.label}</span>
    }},
    { key: 'motif', label: 'Motif', render: (row) => <span className="text-slate-500 text-sm">{row.motif || '—'}</span> },
    ...(hasRole('GESTIONNAIRE') ? [{ key: 'actions', label: '', render: (row) => (
      <div className="flex gap-2 text-xs">
        {row.statut === 'en_attente' && (
          <button onClick={() => changeStatut(row.id, 'confirmee')} className="text-emerald-400 hover:text-emerald-300">Confirmer</button>
        )}
        {row.statut !== 'annulee' && row.statut !== 'terminee' && (
          <button onClick={() => changeStatut(row.id, 'annulee')} className="text-red-400 hover:text-red-300">Annuler</button>
        )}
      </div>
    )}] : []),
  ]

  return (
    <Layout>
      <TopBar
        title="Réservations"
        subtitle="Planification des véhicules de la flotte"
        actions={<Button variant="primary" onClick={() => setShowModal(true)}>+ Nouvelle réservation</Button>}
      />
      <div className="p-4 md:p-8 space-y-4">
        {/* Barre d'outils : bascule de vue + filtre véhicule (mode calendrier) */}
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div className="inline-flex rounded-xl border border-white/10 p-0.5 bg-white/5">
            {[
              { id: 'table', label: 'Tableau' },
              { id: 'calendar', label: 'Calendrier' },
            ].map((v) => (
              <button
                key={v.id}
                onClick={() => setView(v.id)}
                className={`px-3.5 py-1.5 rounded-lg text-sm font-medium transition-colors ${
                  view === v.id ? 'text-white' : 'text-slate-400 hover:text-slate-200'
                }`}
                style={view === v.id ? { background: 'linear-gradient(135deg, rgba(108,99,255,0.4), rgba(0,212,255,0.25))' } : undefined}
              >
                {v.label}
              </button>
            ))}
          </div>
          {view === 'calendar' && (
            <div className="w-full sm:w-64">
              <Select value={vehiculeFiltre} onChange={(e) => setVehiculeFiltre(e.target.value)}>
                <option value="">Tous les véhicules</option>
                {vehicules.map((v) => (
                  <option key={v.id} value={v.immatriculation}>{v.immatriculation} — {v.marque} {v.modele}</option>
                ))}
              </Select>
            </div>
          )}
        </div>

        <Card className={view === 'calendar' ? '' : 'p-0'}>
          {isLoading ? <SkeletonTable /> : view === 'calendar' ? (
            <ReservationCalendar reservations={reservationsFiltrees} />
          ) : (
            <Table columns={columns} data={reservations} emptyMessage="Aucune réservation" />
          )}
        </Card>
      </div>

      <Modal isOpen={showModal} onClose={() => setShowModal(false)} title="Nouvelle réservation">
        <form onSubmit={handleCreate} className="space-y-4">
          <Select label="Véhicule" value={form.vehicule} onChange={(e) => setForm({ ...form, vehicule: e.target.value })} required>
            <option value="">— Sélectionner —</option>
            {vehicules.map((v) => (
              <option key={v.id} value={`/api/vehicules/${v.id}`}>{v.immatriculation} — {v.marque} {v.modele}</option>
            ))}
          </Select>
          {hasRole('GESTIONNAIRE') ? (
            <Select label="Conducteur" value={form.conducteur} onChange={(e) => setForm({ ...form, conducteur: e.target.value })} required>
              <option value="">— Sélectionner —</option>
              {conducteurs.map((c) => (
                <option key={c.id} value={`/api/utilisateurs/${c.id}`}>{c.prenom} {c.nom}</option>
              ))}
            </Select>
          ) : (
            <div>
              <label className="block text-sm font-medium text-slate-400 mb-1.5">Conducteur</label>
              <p className="px-4 py-2.5 rounded-xl text-sm text-slate-300 border border-white/5 bg-white/5">
                {user?.prenom} {user?.nom} <span className="text-slate-500">(vous)</span>
              </p>
            </div>
          )}
          <div className="grid grid-cols-2 gap-3">
            <Input label="Début" type="datetime-local" value={form.dateDebut} onChange={(e) => setForm({ ...form, dateDebut: e.target.value })} required />
            <Input label="Fin" type="datetime-local" value={form.dateFin} onChange={(e) => setForm({ ...form, dateFin: e.target.value })} required />
          </div>
          <Input label="Motif" value={form.motif} onChange={(e) => setForm({ ...form, motif: e.target.value })} placeholder="Mission commerciale…" />
          <div className="flex gap-3 pt-2">
            <Button type="submit" variant="primary">Réserver</Button>
            <Button type="button" variant="ghost" onClick={() => setShowModal(false)}>Annuler</Button>
          </div>
        </form>
      </Modal>
    </Layout>
  )
}
