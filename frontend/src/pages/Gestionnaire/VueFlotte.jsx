import React, { useState, useEffect, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import { Layout } from '../../components/layout/Layout'
import { TopBar } from '../../components/layout/TopBar'
import { Button } from '../../components/ui/Button'
import { Badge } from '../../components/ui/Badge'
import { KmQuotaBar } from '../../components/ui/KmQuotaBar'
import { SkeletonCard } from '../../components/ui/Skeleton'
import { gestionnaireService } from '../../services/gestionnaireService'
import { useToastStore } from '../../store/toastStore'

const STATUT_OPTIONS = ['disponible', 'en_mission', 'maintenance', 'inactif']
const STATUT_LABELS = { disponible: 'Disponible', en_mission: 'En mission', maintenance: 'Maintenance', inactif: 'Inactif' }

function VehiculeFlotteCard({ vehicule, onChangerStatut }) {
  const navigate = useNavigate()
  const [changingStatut, setChangingStatut] = useState(false)

  const handleStatut = async (statut) => {
    setChangingStatut(true)
    try {
      await onChangerStatut(vehicule.id, statut)
    } finally {
      setChangingStatut(false)
    }
  }

  return (
    <div className="glass-card p-5 space-y-4 hover:border-violet-500/20 transition-all">
      {/* Header */}
      <div className="flex items-start justify-between gap-3">
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2 mb-1 flex-wrap">
            <span className="font-mono font-bold text-white">{vehicule.immatriculation}</span>
            <Badge variant={vehicule.statut} />
            {vehicule.pourcentageQuota >= 90 && (
              <span className="text-xs px-2 py-0.5 rounded-full font-semibold"
                style={{ background: 'rgba(239,68,68,0.15)', color: '#F87171', border: '1px solid rgba(239,68,68,0.3)' }}>
                ⚠ Quota {vehicule.pourcentageQuota >= 100 ? 'dépassé' : 'critique'}
              </span>
            )}
          </div>
          <p className="text-sm text-slate-400">{vehicule.marque} {vehicule.modele} · {vehicule.annee}</p>
          {vehicule.categorie && <p className="text-xs text-slate-600 mt-0.5">{vehicule.categorie}</p>}
        </div>
        <Button variant="ghost" size="sm" onClick={() => navigate(`/vehicules/${vehicule.id}`)}>
          Fiche →
        </Button>
      </div>

      {/* Conducteur */}
      <div className="p-3 rounded-xl border border-white/5" style={{ background: 'rgba(255,255,255,0.03)' }}>
        {vehicule.conducteur ? (
          <div className="flex items-center gap-3">
            <div className="w-8 h-8 rounded-xl flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
              style={{ background: 'linear-gradient(135deg, #6C63FF, #8B5CF6)' }}>
              {vehicule.conducteur.prenom[0]}{vehicule.conducteur.nom[0]}
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-white">{vehicule.conducteur.prenom} {vehicule.conducteur.nom}</p>
              <p className="text-xs text-slate-500">Depuis le {vehicule.conducteur.dateDebut}</p>
            </div>
          </div>
        ) : (
          <div className="flex items-center gap-2 text-slate-500">
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <p className="text-sm">Aucun conducteur assigné</p>
          </div>
        )}
      </div>

      {/* Quota */}
      <KmQuotaBar kilometrage={vehicule.kilometrage} quotaKmAnnuel={vehicule.quotaKmAnnuel} />

      {/* Changer statut */}
      <div className="flex gap-2 flex-wrap">
        {STATUT_OPTIONS.filter(s => s !== vehicule.statut).map(s => (
          <button
            key={s}
            onClick={() => handleStatut(s)}
            disabled={changingStatut}
            className="text-xs px-3 py-1.5 rounded-lg border border-white/10 text-slate-400 hover:text-white hover:border-violet-500/40 transition-all disabled:opacity-40"
          >
            → {STATUT_LABELS[s]}
          </button>
        ))}
      </div>
    </div>
  )
}

export default function VueFlotte() {
  const { addToast } = useToastStore()
  const [flotte, setFlotte] = useState([])
  const [isLoading, setIsLoading] = useState(true)
  const [filter, setFilter] = useState('tous')

  const fetchFlotte = useCallback(async () => {
    setIsLoading(true)
    try {
      const data = await gestionnaireService.getVueFlotte()
      setFlotte(data)
    } catch {
      addToast('Erreur lors du chargement', 'error')
    } finally {
      setIsLoading(false)
    }
  }, [addToast])

  useEffect(() => { fetchFlotte() }, [fetchFlotte])

  const handleChangerStatut = async (vehiculeId, statut) => {
    try {
      const res = await gestionnaireService.changerStatut(vehiculeId, statut)
      addToast(res.message)
      await fetchFlotte()
    } catch {
      addToast('Erreur lors du changement de statut', 'error')
    }
  }

  const filteredFlotte = filter === 'tous'
    ? flotte
    : filter === 'sans_conducteur'
    ? flotte.filter(v => !v.conducteur)
    : flotte.filter(v => v.statut === filter)

  const stats = {
    total: flotte.length,
    enMission: flotte.filter(v => v.statut === 'en_mission').length,
    disponibles: flotte.filter(v => v.statut === 'disponible').length,
    maintenance: flotte.filter(v => v.statut === 'maintenance').length,
    sansConducteur: flotte.filter(v => !v.conducteur).length,
  }

  const filters = [
    { key: 'tous', label: `Tous (${stats.total})` },
    { key: 'disponible', label: `Disponibles (${stats.disponibles})` },
    { key: 'en_mission', label: `En mission (${stats.enMission})` },
    { key: 'maintenance', label: `Maintenance (${stats.maintenance})` },
    { key: 'sans_conducteur', label: `Sans conducteur (${stats.sansConducteur})` },
  ]

  return (
    <Layout>
      <TopBar title="Vue flotte" subtitle="État en temps réel de tous les véhicules" />

      <div className="p-4 md:p-8 space-y-6 animate-slide-up">
        {/* Filtres */}
        <div className="flex gap-2 flex-wrap">
          {filters.map(f => (
            <button
              key={f.key}
              onClick={() => setFilter(f.key)}
              className={`px-3 py-1.5 rounded-xl text-sm font-medium transition-all ${
                filter === f.key ? 'text-white' : 'text-slate-400 border border-white/5 hover:text-white hover:border-violet-500/30'
              }`}
              style={filter === f.key ? {
                background: 'linear-gradient(135deg, #6C63FF, #8B5CF6)',
                boxShadow: '0 4px 15px rgba(108,99,255,0.3)',
              } : {}}
            >
              {f.label}
            </button>
          ))}
        </div>

        {isLoading ? (
          <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            {[...Array(5)].map((_, i) => <SkeletonCard key={i} />)}
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            {filteredFlotte.map(v => (
              <VehiculeFlotteCard
                key={v.id}
                vehicule={v}
                onChangerStatut={handleChangerStatut}
              />
            ))}
            {filteredFlotte.length === 0 && (
              <div className="col-span-3 text-center py-16 text-slate-500 text-sm">
                Aucun véhicule dans cette catégorie
              </div>
            )}
          </div>
        )}
      </div>
    </Layout>
  )
}
