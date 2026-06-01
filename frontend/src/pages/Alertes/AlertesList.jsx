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
      <span className="font-mono font-bold text-violet-400">{row.vehicule?.immatriculation || '—'}</span>
    )},
    { key: 'type', label: 'Type', render: (row) => (
      <span className="text-slate-300">{TYPE_LABELS[row.type] || row.type}</span>
    )},
    { key: 'message', label: 'Message', render: (row) => (
      <span className="text-sm text-slate-400 max-w-xs truncate block">{row.message}</span>
    )},
    { key: 'dateEcheance', label: 'Échéance', render: (row) => (
      <span className={new Date(row.dateEcheance) < new Date() ? 'text-red-400 font-medium' : 'text-slate-400'}>
        {new Date(row.dateEcheance).toLocaleDateString('fr-FR')}
      </span>
    )},
    { key: 'statut', label: 'Statut', render: (row) => <Badge variant={row.statut} /> },
    ...(hasRole('GESTIONNAIRE') ? [{
      key: 'actions', label: '', render: (row) => row.statut !== 'resolue' ? (
        <Button variant="ghost" onClick={() => handleStatutChange(row.id, 'resolue')}>
          Résoudre
        </Button>
      ) : null
    }] : []),
  ]

  const FILTERS = [['', 'Toutes'], ['en_attente', 'En attente'], ['en_cours', 'En cours'], ['resolue', 'Résolues']]

  return (
    <Layout>
      <TopBar title="Alertes" subtitle="Suivi des alertes et échéances" />
      <div className="p-8">
        <div className="flex gap-2 mb-6">
          {FILTERS.map(([val, label]) => (
            <button
              key={val}
              onClick={() => setFilter(val)}
              className={`px-4 py-2 rounded-xl text-sm font-medium transition-all ${
                filter === val
                  ? 'text-white'
                  : 'text-slate-400 border border-white/5 hover:text-white hover:border-violet-500/30'
              }`}
              style={filter === val ? {
                background: 'linear-gradient(135deg, #6C63FF, #8B5CF6)',
                boxShadow: '0 4px 15px rgba(108,99,255,0.3)',
              } : {}}
            >
              {label}
            </button>
          ))}
        </div>
        <Card className="p-0">
          {isLoading ? <SkeletonTable /> : (
            <Table columns={columns} data={alertes} emptyMessage="Aucune alerte" />
          )}
        </Card>
      </div>
    </Layout>
  )
}
