import React, { useState, useEffect } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { Layout } from '../../components/layout/Layout'
import { TopBar } from '../../components/layout/TopBar'
import { Card } from '../../components/ui/Card'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
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

  if (isLoading) return (
    <Layout>
      <div className="p-8 text-slate-500">Chargement...</div>
    </Layout>
  )
  if (!vehicule) return null

  return (
    <Layout>
      <TopBar
        title={`${vehicule.marque} ${vehicule.modele}`}
        subtitle={vehicule.immatriculation}
        actions={
          <div className="flex gap-3">
            <Button variant="ghost" onClick={() => navigate('/vehicules')}>← Retour</Button>
            {hasRole('GESTIONNAIRE') && (
              <Button variant="secondary" onClick={() => navigate(`/vehicules/${id}/modifier`)}>
                Modifier
              </Button>
            )}
            {hasRole('ADMIN') && (
              <Button variant="danger" onClick={handleDelete}>Supprimer</Button>
            )}
          </div>
        }
      />
      <div className="p-8 space-y-6 animate-slide-up">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <Card className="lg:col-span-2">
            <h3 className="font-display text-base font-semibold text-white mb-4">Informations générales</h3>
            <div className="grid grid-cols-2 gap-4">
              {[
                { label: 'Immatriculation', value: vehicule.immatriculation },
                { label: 'Marque / Modèle', value: `${vehicule.marque} ${vehicule.modele}` },
                { label: 'Année', value: vehicule.annee },
                { label: 'Kilométrage', value: `${vehicule.kilometrage?.toLocaleString('fr-FR')} km` },
                { label: 'Catégorie', value: vehicule.categorie?.libelle || '—' },
              ].map(({ label, value }) => (
                <div key={label} className="p-3 rounded-xl border border-white/5" style={{ background: 'rgba(255,255,255,0.03)' }}>
                  <p className="text-xs text-slate-500 mb-1">{label}</p>
                  <p className="text-sm font-medium text-white">{value}</p>
                </div>
              ))}
              <div className="p-3 rounded-xl border border-white/5" style={{ background: 'rgba(255,255,255,0.03)' }}>
                <p className="text-xs text-slate-500 mb-1">Statut</p>
                <Badge variant={vehicule.statut} />
              </div>
            </div>
          </Card>

          <Card>
            <h3 className="font-display text-base font-semibold text-white mb-4">Localisation</h3>
            {vehicule.adresse ? (
              <div className="space-y-2">
                <p className="text-sm text-slate-300">{vehicule.adresse}</p>
                {vehicule.latitude && vehicule.longitude && (
                  <p className="text-xs text-slate-500 font-mono">
                    {parseFloat(vehicule.latitude).toFixed(6)}, {parseFloat(vehicule.longitude).toFixed(6)}
                  </p>
                )}
              </div>
            ) : (
              <p className="text-sm text-slate-500">Aucune adresse renseignée</p>
            )}
          </Card>
        </div>
      </div>
    </Layout>
  )
}
