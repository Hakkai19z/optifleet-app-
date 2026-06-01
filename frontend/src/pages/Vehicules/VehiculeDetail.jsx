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

  if (isLoading) return <Layout><div className="p-8 text-gray-500">Chargement...</div></Layout>
  if (!vehicule) return null

  return (
    <Layout>
      <TopBar title={`${vehicule.marque} ${vehicule.modele}`} subtitle={vehicule.immatriculation} />
      <div className="p-8 space-y-6">
        <div className="flex justify-between items-center">
          <Button variant="ghost" onClick={() => navigate('/vehicules')}>← Retour</Button>
          {hasRole('GESTIONNAIRE') && (
            <div className="flex gap-3">
              <Button variant="secondary" onClick={() => navigate(`/vehicules/${id}/modifier`)}>
                Modifier
              </Button>
              {hasRole('ADMIN') && (
                <Button variant="danger" onClick={handleDelete}>Supprimer</Button>
              )}
            </div>
          )}
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <Card accent="primary" className="lg:col-span-2">
            <h3 className="text-base font-semibold text-dark mb-4">Informations générales</h3>
            <div className="grid grid-cols-2 gap-4">
              {[
                { label: 'Immatriculation', value: vehicule.immatriculation },
                { label: 'Marque / Modèle', value: `${vehicule.marque} ${vehicule.modele}` },
                { label: 'Année', value: vehicule.annee },
                { label: 'Kilométrage', value: `${vehicule.kilometrage?.toLocaleString('fr-FR')} km` },
                { label: 'Catégorie', value: vehicule.categorie?.libelle || '—' },
              ].map(({ label, value }) => (
                <div key={label}>
                  <p className="text-xs text-gray-500 mb-0.5">{label}</p>
                  <p className="text-sm font-medium text-dark">{value}</p>
                </div>
              ))}
              <div>
                <p className="text-xs text-gray-500 mb-0.5">Statut</p>
                <Badge variant={vehicule.statut} />
              </div>
            </div>
          </Card>

          <Card>
            <h3 className="text-base font-semibold text-dark mb-4">Localisation</h3>
            {vehicule.adresse ? (
              <div className="space-y-2">
                <p className="text-sm text-gray-600">{vehicule.adresse}</p>
                {vehicule.latitude && vehicule.longitude && (
                  <p className="text-xs text-gray-400 font-mono">
                    {parseFloat(vehicule.latitude).toFixed(6)}, {parseFloat(vehicule.longitude).toFixed(6)}
                  </p>
                )}
              </div>
            ) : (
              <p className="text-sm text-gray-400">Aucune adresse renseignée</p>
            )}
          </Card>
        </div>
      </div>
    </Layout>
  )
}
