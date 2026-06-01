import React, { useState, useEffect } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { Layout } from '../../components/layout/Layout'
import { TopBar } from '../../components/layout/TopBar'
import { Card } from '../../components/ui/Card'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { MapWidget } from '../../components/ui/MapWidget'
import { KmQuotaBar } from '../../components/ui/KmQuotaBar'
import { vehiculeService } from '../../services/vehiculeService'
import { useAuth } from '../../hooks/useAuth'
import { useToastStore } from '../../store/toastStore'

export default function VehiculeDetail() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { hasRole } = useAuth()
  const { addToast } = useToastStore()
  const [vehicule, setVehicule] = useState(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    vehiculeService.getById(id)
      .then(setVehicule)
      .catch(() => { addToast('Véhicule introuvable', 'error'); navigate('/vehicules') })
      .finally(() => setIsLoading(false))
  }, [id])

  const handleDelete = async () => {
    if (!confirm(`Supprimer le véhicule ${vehicule?.immatriculation} ?`)) return
    try {
      await vehiculeService.delete(id)
      addToast('Véhicule supprimé avec succès')
      navigate('/vehicules')
    } catch {
      addToast('Impossible de supprimer ce véhicule', 'error')
    }
  }

  if (isLoading) {
    return (
      <Layout>
        <div className="p-4 md:p-8 text-slate-500 text-sm">Chargement...</div>
      </Layout>
    )
  }
  if (!vehicule) return null

  const pct = vehicule.quotaKmAnnuel
    ? Math.round((vehicule.kilometrage / vehicule.quotaKmAnnuel) * 100)
    : null

  return (
    <Layout>
      <TopBar
        title={`${vehicule.marque} ${vehicule.modele}`}
        subtitle={vehicule.immatriculation}
        actions={
          hasRole('GESTIONNAIRE') && (
            <div className="flex gap-2">
              <Button variant="secondary" onClick={() => navigate(`/vehicules/${id}/modifier`)}>
                Modifier
              </Button>
              {hasRole('ADMIN') && (
                <Button variant="danger" onClick={handleDelete}>Supprimer</Button>
              )}
            </div>
          )
        }
      />

      <div className="p-4 md:p-8 space-y-6 animate-slide-up">
        <Button variant="ghost" onClick={() => navigate('/vehicules')} className="mb-2">← Retour</Button>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Main info */}
          <div className="lg:col-span-2 space-y-6">
            {/* Hero card */}
            <div className="relative overflow-hidden rounded-2xl p-6"
              style={{ background: 'linear-gradient(135deg, #0F1628 0%, #1a1040 60%, #0d1a2e 100%)', border: '1px solid rgba(108,99,255,0.2)' }}>
              <div className="absolute top-0 right-0 w-64 h-64 rounded-full blur-3xl opacity-15 pointer-events-none"
                style={{ background: 'radial-gradient(circle, #6C63FF, transparent)' }} />
              <div className="relative z-10 flex flex-col sm:flex-row items-start sm:items-center gap-4">
                <div className="w-14 h-14 rounded-2xl flex items-center justify-center flex-shrink-0"
                  style={{ background: 'linear-gradient(135deg, rgba(108,99,255,0.3), rgba(0,212,255,0.2))', border: '1px solid rgba(108,99,255,0.3)' }}>
                  <svg className="w-7 h-7 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l1 1h1m8-1V7a1 1 0 011-1h2l3 4v5l-1 1h-1m-6 0h6" />
                  </svg>
                </div>
                <div className="flex-1">
                  <div className="flex flex-wrap items-center gap-2 mb-1">
                    <Badge variant={vehicule.statut} />
                    {vehicule.categorie?.libelle && (
                      <span className="text-xs text-slate-500 px-2 py-0.5 rounded-full bg-white/5">{vehicule.categorie.libelle}</span>
                    )}
                    {pct !== null && pct >= 90 && (
                      <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-500/15 text-red-400 border border-red-500/20">
                        ⚠ Quota {pct >= 100 ? 'dépassé' : 'critique'}
                      </span>
                    )}
                  </div>
                  <h2 className="font-display text-2xl font-bold text-white">
                    {vehicule.marque} {vehicule.modele} <span className="text-slate-500 font-normal text-lg">({vehicule.annee})</span>
                  </h2>
                  <p className="font-mono text-lg font-bold text-gradient mt-0.5">{vehicule.immatriculation}</p>
                </div>
              </div>
            </div>

            {/* Info grid */}
            <Card>
              <h3 className="font-display font-semibold text-white mb-4">Informations générales</h3>
              <div className="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-4">
                {[
                  { label: 'Marque', value: vehicule.marque },
                  { label: 'Modèle', value: vehicule.modele },
                  { label: 'Année', value: vehicule.annee },
                  { label: 'Catégorie', value: vehicule.categorie?.libelle || '—' },
                  { label: 'Statut', value: <Badge variant={vehicule.statut} /> },
                  { label: 'Ajouté le', value: vehicule.createdAt ? new Date(vehicule.createdAt).toLocaleDateString('fr-FR') : '—' },
                ].map(({ label, value }) => (
                  <div key={label} className="p-3 rounded-xl bg-white/3 border border-white/5">
                    <p className="text-xs text-slate-500 mb-1">{label}</p>
                    <div className="text-sm font-semibold text-white">{value}</div>
                  </div>
                ))}
              </div>

              {/* Km Quota section */}
              <div className="border-t border-white/5 pt-4">
                <h4 className="text-sm font-semibold text-white mb-3">Kilométrage & Quota</h4>
                <div className="grid grid-cols-2 gap-4 mb-3">
                  <div className="p-3 rounded-xl bg-white/3 border border-white/5">
                    <p className="text-xs text-slate-500 mb-1">Kilométrage actuel</p>
                    <p className="text-xl font-display font-bold text-white">
                      {vehicule.kilometrage.toLocaleString('fr-FR')} <span className="text-sm text-slate-400 font-normal">km</span>
                    </p>
                  </div>
                  <div className="p-3 rounded-xl bg-white/3 border border-white/5">
                    <p className="text-xs text-slate-500 mb-1">Quota annuel</p>
                    {vehicule.quotaKmAnnuel ? (
                      <p className="text-xl font-display font-bold text-white">
                        {vehicule.quotaKmAnnuel.toLocaleString('fr-FR')} <span className="text-sm text-slate-400 font-normal">km</span>
                      </p>
                    ) : (
                      <p className="text-sm text-slate-500">Non défini</p>
                    )}
                  </div>
                </div>
                <KmQuotaBar
                  kilometrage={vehicule.kilometrage}
                  quotaKmAnnuel={vehicule.quotaKmAnnuel}
                />
              </div>
            </Card>
          </div>

          {/* Right column — Map */}
          <div className="space-y-6">
            <Card>
              <h3 className="font-display font-semibold text-white mb-4">
                <span className="flex items-center gap-2">
                  <svg className="w-4 h-4 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  Localisation
                </span>
              </h3>
              <MapWidget
                latitude={vehicule.latitude}
                longitude={vehicule.longitude}
                adresse={vehicule.adresse}
              />
            </Card>
          </div>
        </div>
      </div>
    </Layout>
  )
}
