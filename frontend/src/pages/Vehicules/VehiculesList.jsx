import React, { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Layout } from '../../components/layout/Layout'
import { TopBar } from '../../components/layout/TopBar'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Badge } from '../../components/ui/Badge'
import { Table } from '../../components/ui/Table'
import { SkeletonTable } from '../../components/ui/Skeleton'
import { useVehicules } from '../../hooks/useVehicules'
import { useAuth } from '../../hooks/useAuth'

export default function VehiculesList() {
  const navigate = useNavigate()
  const { hasRole } = useAuth()
  const [filters, setFilters] = useState({})
  const { vehicules, isLoading, total } = useVehicules(filters)

  const columns = [
    { key: 'immatriculation', label: 'Immatriculation', render: (row) => (
      <span className="font-mono font-semibold text-dark">{row.immatriculation}</span>
    )},
    { key: 'marque', label: 'Véhicule', render: (row) => (
      <div>
        <p className="font-medium text-dark">{row.marque} {row.modele}</p>
        <p className="text-xs text-gray-500">{row.annee}</p>
      </div>
    )},
    { key: 'kilometrage', label: 'Kilométrage', render: (row) => (
      <span>{row.kilometrage.toLocaleString('fr-FR')} km</span>
    )},
    { key: 'categorie', label: 'Catégorie', render: (row) => (
      <span>{row.categorie?.libelle || '—'}</span>
    )},
    { key: 'statut', label: 'Statut', render: (row) => (
      <Badge variant={row.statut} />
    )},
    { key: 'actions', label: '', render: (row) => (
      <Button variant="ghost" size="sm" onClick={(e) => { e.stopPropagation(); navigate(`/vehicules/${row.id}`) }}>
        Détails →
      </Button>
    )},
  ]

  return (
    <Layout>
      <TopBar title="Véhicules" subtitle={`${total} véhicule${total > 1 ? 's' : ''} dans la flotte`} />
      <div className="p-8">
        <div className="flex items-center justify-between mb-6">
          <div className="flex gap-3">
            {['', 'disponible', 'en_mission', 'maintenance', 'inactif'].map((statut) => (
              <button
                key={statut}
                onClick={() => setFilters(statut ? { statut } : {})}
                className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-colors ${
                  filters.statut === statut || (!filters.statut && !statut)
                    ? 'bg-primary text-white'
                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                }`}
              >
                {statut === '' ? 'Tous' : statut === 'disponible' ? 'Disponibles' : statut === 'en_mission' ? 'En mission' : statut === 'maintenance' ? 'Maintenance' : 'Inactifs'}
              </button>
            ))}
          </div>
          {hasRole('GESTIONNAIRE') && (
            <Button variant="primary" onClick={() => navigate('/vehicules/nouveau')}>
              + Nouveau véhicule
            </Button>
          )}
        </div>

        <Card className="p-0">
          {isLoading ? (
            <div className="p-6"><SkeletonTable /></div>
          ) : (
            <Table
              columns={columns}
              data={vehicules}
              onRowClick={(row) => navigate(`/vehicules/${row.id}`)}
              emptyMessage="Aucun véhicule trouvé"
            />
          )}
        </Card>
      </div>
    </Layout>
  )
}
