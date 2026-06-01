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

const FILTER_LABELS = {
  '': 'Tous',
  disponible: 'Disponibles',
  en_mission: 'En mission',
  maintenance: 'Maintenance',
  inactif: 'Inactifs',
}

export default function VehiculesList() {
  const navigate = useNavigate()
  const { hasRole } = useAuth()
  const [filters, setFilters] = useState({})
  const { vehicules, isLoading, total } = useVehicules(filters)

  const columns = [
    { key: 'immatriculation', label: 'Immatriculation', render: (row) => (
      <span className="font-mono font-bold text-violet-400">{row.immatriculation}</span>
    )},
    { key: 'marque', label: 'Véhicule', render: (row) => (
      <div>
        <p className="font-medium text-white">{row.marque} {row.modele}</p>
        <p className="text-xs text-slate-500">{row.annee}</p>
      </div>
    )},
    { key: 'kilometrage', label: 'Kilométrage', render: (row) => (
      <span className="text-slate-300">{row.kilometrage.toLocaleString('fr-FR')} km</span>
    )},
    { key: 'categorie', label: 'Catégorie', render: (row) => (
      <span className="text-slate-400">{row.categorie?.libelle || '—'}</span>
    )},
    { key: 'statut', label: 'Statut', render: (row) => (
      <Badge variant={row.statut} />
    )},
    { key: 'actions', label: '', render: (row) => (
      <Button variant="ghost" onClick={(e) => { e.stopPropagation(); navigate(`/vehicules/${row.id}`) }}>
        Détails →
      </Button>
    )},
  ]

  return (
    <Layout>
      <TopBar
        title="Flotte"
        subtitle={`${total} véhicule${total > 1 ? 's' : ''} dans la flotte`}
        actions={hasRole('GESTIONNAIRE') && (
          <Button variant="primary" onClick={() => navigate('/vehicules/nouveau')}>
            + Nouveau véhicule
          </Button>
        )}
      />
      <div className="p-8">
        <div className="flex items-center gap-2 mb-6">
          {Object.entries(FILTER_LABELS).map(([statut, label]) => (
            <button
              key={statut}
              onClick={() => setFilters(statut ? { statut } : {})}
              className={`px-4 py-2 rounded-xl text-sm font-medium transition-all ${
                (filters.statut === statut) || (!filters.statut && !statut)
                  ? 'text-white'
                  : 'text-slate-400 border border-white/5 hover:text-white hover:border-violet-500/30'
              }`}
              style={(filters.statut === statut) || (!filters.statut && !statut) ? {
                background: 'linear-gradient(135deg, #6C63FF, #8B5CF6)',
                boxShadow: '0 4px 15px rgba(108,99,255,0.3)',
              } : {}}
            >
              {label}
            </button>
          ))}
        </div>

        <Card className="p-0">
          {isLoading ? (
            <SkeletonTable />
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
