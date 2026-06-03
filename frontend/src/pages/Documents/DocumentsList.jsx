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
import { documentService } from '../../services/documentService'
import { vehiculeService } from '../../services/vehiculeService'
import { useToastStore } from '../../store/toastStore'
import { useAuth } from '../../hooks/useAuth'

const TYPE_LABELS = {
  assurance: 'Assurance', carte_grise: 'Carte grise', controle_technique: 'Contrôle technique',
  vignette: 'Vignette', permis: 'Permis', autre: 'Autre',
}

function EcheanceBadge({ jours, expire }) {
  let color, bg, label
  if (expire) {
    color = '#EF4444'; bg = 'rgba(239,68,68,0.15)'; label = `Expiré (${Math.abs(jours)} j)`
  } else if (jours <= 30) {
    color = '#F59E0B'; bg = 'rgba(245,158,11,0.15)'; label = `${jours} j restants`
  } else {
    color = '#10B981'; bg = 'rgba(16,185,129,0.15)'; label = `${jours} j restants`
  }
  return <span className="text-xs font-semibold px-2.5 py-1 rounded-full" style={{ color, background: bg }}>{label}</span>
}

export default function DocumentsList() {
  const { hasRole } = useAuth()
  const { addToast } = useToastStore()
  const [documents, setDocuments] = useState([])
  const [vehicules, setVehicules] = useState([])
  const [isLoading, setIsLoading] = useState(true)
  const [showModal, setShowModal] = useState(false)
  const [form, setForm] = useState({ vehicule: '', type: 'assurance', numero: '', dateDelivrance: '', dateExpiration: '', notes: '' })

  const reload = () => documentService.getAll().then((d) => setDocuments(d['hydra:member'] || d))

  useEffect(() => {
    Promise.all([
      documentService.getAll(),
      vehiculeService.getAll(),
    ]).then(([docs, veh]) => {
      setDocuments(docs['hydra:member'] || docs)
      setVehicules(veh['hydra:member'] || veh)
    }).finally(() => setIsLoading(false))
  }, [])

  const handleCreate = async (e) => {
    e.preventDefault()
    try {
      const payload = {
        vehicule: form.vehicule,
        type: form.type,
        dateExpiration: form.dateExpiration,
      }
      if (form.numero) payload.numero = form.numero
      if (form.dateDelivrance) payload.dateDelivrance = form.dateDelivrance
      if (form.notes) payload.notes = form.notes
      await documentService.create(payload)
      addToast('Document ajouté')
      setShowModal(false)
      setForm({ vehicule: '', type: 'assurance', numero: '', dateDelivrance: '', dateExpiration: '', notes: '' })
      await reload()
    } catch {
      addToast('Erreur lors de l\'ajout', 'error')
    }
  }

  const handleDelete = async (id) => {
    if (!window.confirm('Supprimer ce document ?')) return
    try {
      await documentService.delete(id)
      addToast('Document supprimé')
      await reload()
    } catch {
      addToast('Suppression impossible', 'error')
    }
  }

  // Tri : les plus urgents en premier
  const sorted = [...documents].sort((a, b) => (a.joursAvantExpiration ?? 9999) - (b.joursAvantExpiration ?? 9999))

  const columns = [
    { key: 'vehicule', label: 'Véhicule', render: (row) => (
      <span className="font-mono font-bold text-violet-400">{row.vehicule?.immatriculation || '—'}</span>
    )},
    { key: 'type', label: 'Type', render: (row) => (
      <span className="text-slate-300">{TYPE_LABELS[row.type] || row.type}</span>
    )},
    { key: 'numero', label: 'Numéro', render: (row) => (
      <span className="text-slate-400 font-mono text-sm">{row.numero || '—'}</span>
    )},
    { key: 'dateExpiration', label: 'Expiration', render: (row) => (
      <span className="text-slate-400">{new Date(row.dateExpiration).toLocaleDateString('fr-FR')}</span>
    )},
    { key: 'echeance', label: 'Échéance', render: (row) => (
      <EcheanceBadge jours={row.joursAvantExpiration} expire={row.isExpire} />
    )},
    ...(hasRole('GESTIONNAIRE') ? [{ key: 'actions', label: '', render: (row) => (
      <button onClick={() => handleDelete(row.id)} className="text-xs text-red-400 hover:text-red-300">Supprimer</button>
    )}] : []),
  ]

  return (
    <Layout>
      <TopBar
        title="Documents"
        subtitle="Assurances, contrôles techniques et échéances administratives"
        actions={hasRole('GESTIONNAIRE') && (
          <Button variant="primary" onClick={() => setShowModal(true)}>+ Ajouter un document</Button>
        )}
      />
      <div className="p-4 md:p-8">
        <Card className="p-0">
          {isLoading ? <SkeletonTable /> : (
            <Table columns={columns} data={sorted} emptyMessage="Aucun document enregistré" />
          )}
        </Card>
      </div>

      <Modal isOpen={showModal} onClose={() => setShowModal(false)} title="Nouveau document">
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
          <Input label="Numéro" value={form.numero} onChange={(e) => setForm({ ...form, numero: e.target.value })} placeholder="Police / référence" />
          <div className="grid grid-cols-2 gap-3">
            <Input label="Délivré le" type="date" value={form.dateDelivrance} onChange={(e) => setForm({ ...form, dateDelivrance: e.target.value })} />
            <Input label="Expire le" type="date" value={form.dateExpiration} onChange={(e) => setForm({ ...form, dateExpiration: e.target.value })} required />
          </div>
          <div className="flex gap-3 pt-2">
            <Button type="submit" variant="primary">Enregistrer</Button>
            <Button type="button" variant="ghost" onClick={() => setShowModal(false)}>Annuler</Button>
          </div>
        </form>
      </Modal>
    </Layout>
  )
}
