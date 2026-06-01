import React, { useState, useEffect } from 'react'
import { Layout } from '../../components/layout/Layout'
import { TopBar } from '../../components/layout/TopBar'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Badge } from '../../components/ui/Badge'
import { Table } from '../../components/ui/Table'
import { Modal } from '../../components/ui/Modal'
import { Input } from '../../components/ui/Input'
import { Select } from '../../components/ui/Select'
import { SkeletonTable } from '../../components/ui/Skeleton'
import { entretienService } from '../../services/entretienService'
import { vehiculeService } from '../../services/vehiculeService'
import { useToastStore } from '../../store/toastStore'
import { useAuth } from '../../hooks/useAuth'

const TYPE_LABELS = {
  revision: 'Révision', vidange: 'Vidange', CT: 'Contrôle technique',
  freins: 'Freins', pneus: 'Pneus', autre: 'Autre',
}

export default function EntretiensList() {
  const { hasRole } = useAuth()
  const { addToast } = useToastStore()
  const [entretiens, setEntretiens] = useState([])
  const [vehicules, setVehicules] = useState([])
  const [isLoading, setIsLoading] = useState(true)
  const [showModal, setShowModal] = useState(false)
  const [form, setForm] = useState({ vehicule: '', type: 'revision', dateRealise: '', dateProchaine: '', kmProchaine: '', cout: '', notes: '' })

  useEffect(() => {
    Promise.all([
      entretienService.getAll(),
      vehiculeService.getAll(),
    ]).then(([ent, veh]) => {
      setEntretiens(ent['hydra:member'] || ent)
      setVehicules(veh['hydra:member'] || veh)
    }).finally(() => setIsLoading(false))
  }, [])

  const handleCreate = async (e) => {
    e.preventDefault()
    try {
      const payload = { ...form }
      if (!payload.dateProchaine) delete payload.dateProchaine
      if (!payload.kmProchaine) delete payload.kmProchaine
      else payload.kmProchaine = parseInt(payload.kmProchaine)
      if (!payload.cout) delete payload.cout
      if (!payload.notes) delete payload.notes
      await entretienService.create(payload)
      addToast('Entretien ajouté')
      setShowModal(false)
      const data = await entretienService.getAll()
      setEntretiens(data['hydra:member'] || data)
    } catch {
      addToast('Erreur lors de la création', 'error')
    }
  }

  const columns = [
    { key: 'vehicule', label: 'Véhicule', render: (row) => (
      <span className="font-mono font-bold text-violet-400">{row.vehicule?.immatriculation || '—'}</span>
    )},
    { key: 'type', label: 'Type', render: (row) => (
      <span className="text-slate-300">{TYPE_LABELS[row.type] || row.type}</span>
    )},
    { key: 'dateRealise', label: 'Réalisé le', render: (row) => (
      <span className="text-slate-400">{new Date(row.dateRealise).toLocaleDateString('fr-FR')}</span>
    )},
    { key: 'dateProchaine', label: 'Prochain', render: (row) => row.dateProchaine ? (
      <span className={new Date(row.dateProchaine) < new Date() ? 'text-red-400 font-medium' : 'text-slate-400'}>
        {new Date(row.dateProchaine).toLocaleDateString('fr-FR')}
      </span>
    ) : <span className="text-slate-600">—</span> },
    { key: 'cout', label: 'Coût', render: (row) => row.cout ? (
      <span className="text-emerald-400 font-medium">{parseFloat(row.cout).toLocaleString('fr-FR')} €</span>
    ) : <span className="text-slate-600">—</span> },
  ]

  return (
    <Layout>
      <TopBar
        title="Entretiens"
        subtitle="Historique et planification"
        actions={hasRole('GESTIONNAIRE') && (
          <Button variant="primary" onClick={() => setShowModal(true)}>+ Ajouter un entretien</Button>
        )}
      />
      <div className="p-8">
        <Card className="p-0">
          {isLoading ? <SkeletonTable /> : (
            <Table columns={columns} data={entretiens} emptyMessage="Aucun entretien enregistré" />
          )}
        </Card>
      </div>

      <Modal isOpen={showModal} onClose={() => setShowModal(false)} title="Nouvel entretien">
        <form onSubmit={handleCreate} className="space-y-4">
          <Select label="Véhicule" value={form.vehicule} onChange={(e) => setForm({ ...form, vehicule: e.target.value })} required>
            <option value="">— Sélectionner —</option>
            {vehicules.map((v) => (
              <option key={v.id} value={`/api/vehicules/${v.id}`}>{v.immatriculation} — {v.marque} {v.modele}</option>
            ))}
          </Select>
          <Select label="Type" value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })}>
            {Object.entries(TYPE_LABELS).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
          </Select>
          <Input label="Date réalisée" type="date" value={form.dateRealise} onChange={(e) => setForm({ ...form, dateRealise: e.target.value })} required />
          <Input label="Prochaine date" type="date" value={form.dateProchaine} onChange={(e) => setForm({ ...form, dateProchaine: e.target.value })} />
          <Input label="Prochain kilométrage" type="number" value={form.kmProchaine} onChange={(e) => setForm({ ...form, kmProchaine: e.target.value })} placeholder="60000" />
          <Input label="Coût (€)" type="number" step="0.01" value={form.cout} onChange={(e) => setForm({ ...form, cout: e.target.value })} placeholder="120.00" />
          <div className="flex gap-3 pt-2">
            <Button type="submit" variant="primary">Enregistrer</Button>
            <Button type="button" variant="ghost" onClick={() => setShowModal(false)}>Annuler</Button>
          </div>
        </form>
      </Modal>
    </Layout>
  )
}
