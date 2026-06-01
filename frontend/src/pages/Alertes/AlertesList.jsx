import React, { useState, useEffect } from 'react'
import { Layout } from '../../components/layout/Layout'
import { TopBar } from '../../components/layout/TopBar'
import { Card } from '../../components/ui/Card'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { SkeletonTable } from '../../components/ui/Skeleton'
import { alerteService } from '../../services/alerteService'
import { useToastStore } from '../../store/toastStore'
import { useAuth } from '../../hooks/useAuth'

const TYPE_LABELS = { assurance: 'Assurance', CT: 'Contrôle technique', revision: 'Révision', vidange: 'Vidange', autre: 'Autre' }

export default function AlertesList() {
  const { hasRole } = useAuth()
  const { addToast } = useToastStore()
  const [alertes, setAlertes] = useState([])
  const [isLoading, setIsLoading] = useState(true)
  const [filter, setFilter] = useState('')

  const fetchAlertes = () => {
    setIsLoading(true)
    alerteService.getAll(filter ? { statut: filter } : {})
      .then((data) => setAlertes(data['hydra:member'] || data))
      .finally(() => setIsLoading(false))
  }

  useEffect(() => { fetchAlertes() }, [filter])

  const handleStatutChange = async (id, statut) => {
    try {
      await alerteService.patch(id, { statut })
      addToast('Statut mis à jour')
      fetchAlertes()
    } catch {
      addToast('Erreur lors de la mise à jour', 'error')
    }
  }

  const columns = [
    { key: 'vehicule', label: 'Véhicule', render: (row) => (
      <span className="font-mono font-semibold">{row.vehicule?.immatriculation || '—'}</span>
    )},
    { key: 'type', label: 'Type', render: (row) => TYPE_LABELS[row.type] || row.type },
    { key: 'message', label: 'Message', render: (row) => (
      <span className="text-sm text-gray-600 max-w-xs truncate block">{row.message}</span>
    )},
    { key: 'dateEcheance', label: 'Échéance', render: (row) => (
      <span className={new Date(row.dateEcheance) < new Date() ? 'text-danger font-medium' : ''}>
        {new Date(row.dateEcheance).toLocaleDateString('fr-FR')}
      </span>
    )},
    { key: 'statut', label: 'Statut', render: (row) => <Badge variant={row.statut} /> },
    ...(hasRole('GESTIONNAIRE') ? [{
      key: 'actions', label: '', render: (row) => row.statut !== 'resolue' ? (
        <Button variant="ghost" size="sm" onClick={() => handleStatutChange(row.id, 'resolue')}>
          Résoudre
        </Button>
      ) : null
    }] : []),
  ]

  return (
    <Layout>
      <TopBar title="Alertes" subtitle="Suivi des alertes et échéances" />
      <div className="p-8">
        <div className="flex gap-3 mb-6">
          {[['', 'Toutes'], ['en_attente', 'En attente'], ['en_cours', 'En cours'], ['resolue', 'Résolues']].map(([val, label]) => (
            <button
              key={val}
              onClick={() => setFilter(val)}
              className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-colors ${
                filter === val ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
              }`}
            >
              {label}
            </button>
          ))}
        </div>
        <Card className="p-0">
          {isLoading ? <div className="p-6"><SkeletonTable /></div> : (
            <Table columns={columns} data={alertes} emptyMessage="Aucune alerte" />
          )}
        </Card>
      </div>
    </Layout>
  )
}
