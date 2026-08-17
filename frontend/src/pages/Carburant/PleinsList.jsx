import React, { useState, useEffect, useMemo } from 'react'
import { Layout } from '../../components/layout/Layout'
import { TopBar } from '../../components/layout/TopBar'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Modal } from '../../components/ui/Modal'
import { Input } from '../../components/ui/Input'
import { Select } from '../../components/ui/Select'
import { SkeletonTable } from '../../components/ui/Skeleton'
import { pleinService } from '../../services/pleinService'
import { vehiculeService } from '../../services/vehiculeService'
import { conducteurService } from '../../services/conducteurService'
import { useToastStore } from '../../store/toastStore'
import { useAuth } from '../../hooks/useAuth'

const CARBURANT_LABELS = {
  diesel: 'Diesel', essence: 'Essence', gpl: 'GPL', electrique: 'Électrique',
}

function StatBox({ label, value, accent }) {
  return (
    <Card className="p-5">
      <p className="text-xs text-slate-500 uppercase tracking-widest mb-1">{label}</p>
      <p className="text-2xl font-display font-bold" style={{ color: accent }}>{value}</p>
    </Card>
  )
}

export default function PleinsList() {
  const { hasRole } = useAuth()
  const { addToast } = useToastStore()
  const [pleins, setPleins] = useState([])
  const [vehicules, setVehicules] = useState([])
  const [isLoading, setIsLoading] = useState(true)
  const [showModal, setShowModal] = useState(false)
  const [form, setForm] = useState({ vehicule: '', date: '', litres: '', prixLitre: '', kilometrage: '', typeCarburant: 'diesel', notes: '' })

  const reload = () => pleinService.getAll({ 'order[date]': 'desc' }).then((d) => setPleins(d['hydra:member'] || d))

  const isGestionnaire = hasRole('GESTIONNAIRE')

  useEffect(() => {
    const vehiculesPromise = isGestionnaire
      ? vehiculeService.getAll().then((veh) => veh['hydra:member'] || veh)
      : conducteurService.getMonVehicule().then((d) => (d.vehicule ? [d.vehicule] : []))

    Promise.all([
      pleinService.getAll({ 'order[date]': 'desc' }),
      vehiculesPromise,
    ]).then(([pl, veh]) => {
      setPleins(pl['hydra:member'] || pl)
      setVehicules(veh)
    }).finally(() => setIsLoading(false))
  }, [isGestionnaire])

  const totaux = useMemo(() => {
    const litres = pleins.reduce((s, p) => s + parseFloat(p.litres || 0), 0)
    const cout = pleins.reduce((s, p) => s + (p.montant || parseFloat(p.litres || 0) * parseFloat(p.prixLitre || 0)), 0)
    const prixMoyen = litres > 0 ? cout / litres : 0
    return { litres, cout, prixMoyen }
  }, [pleins])

  const handleCreate = async (e) => {
    e.preventDefault()
    try {
      const payload = {
        vehicule: form.vehicule,
        date: form.date,
        litres: String(form.litres),
        prixLitre: String(form.prixLitre),
        kilometrage: parseInt(form.kilometrage),
        typeCarburant: form.typeCarburant,
      }
      if (form.notes) payload.notes = form.notes
      await pleinService.create(payload)
      addToast('Plein enregistré')
      setShowModal(false)
      setForm({ vehicule: '', date: '', litres: '', prixLitre: '', kilometrage: '', typeCarburant: 'diesel', notes: '' })
      await reload()
    } catch {
      addToast('Erreur lors de l\'enregistrement', 'error')
    }
  }

  const handleDelete = async (id) => {
    if (!window.confirm('Supprimer ce plein ?')) return
    try {
      await pleinService.delete(id)
      addToast('Plein supprimé')
      await reload()
    } catch {
      addToast('Suppression impossible', 'error')
    }
  }

  const columns = [
    { key: 'date', label: 'Date', render: (row) => (
      <span className="text-slate-400">{new Date(row.date).toLocaleDateString('fr-FR')}</span>
    )},
    { key: 'vehicule', label: 'Véhicule', render: (row) => (
      <span className="font-mono font-bold text-violet-400">{row.vehicule?.immatriculation || '—'}</span>
    )},
    { key: 'typeCarburant', label: 'Carburant', render: (row) => (
      <span className="text-slate-300">{CARBURANT_LABELS[row.typeCarburant] || row.typeCarburant}</span>
    )},
    { key: 'litres', label: 'Volume', render: (row) => (
      <span className="text-slate-300">{parseFloat(row.litres).toLocaleString('fr-FR')} L</span>
    )},
    { key: 'prixLitre', label: 'Prix/L', render: (row) => (
      <span className="text-slate-400">{parseFloat(row.prixLitre).toLocaleString('fr-FR', { minimumFractionDigits: 3 })} €</span>
    )},
    { key: 'montant', label: 'Total', render: (row) => (
      <span className="text-emerald-400 font-medium">{(row.montant ?? 0).toLocaleString('fr-FR')} €</span>
    )},
    { key: 'kilometrage', label: 'Km', render: (row) => (
      <span className="text-slate-400">{row.kilometrage?.toLocaleString('fr-FR')}</span>
    )},
    ...(hasRole('GESTIONNAIRE') ? [{ key: 'actions', label: '', render: (row) => (
      <button onClick={() => handleDelete(row.id)} className="text-xs text-red-400 hover:text-red-300">Supprimer</button>
    )}] : []),
  ]

  return (
    <Layout>
      <TopBar
        title="Carburant"
        subtitle="Suivi des pleins et de la consommation"
        actions={<Button variant="primary" onClick={() => {
          if (!isGestionnaire && vehicules.length === 1) {
            setForm((f) => ({ ...f, vehicule: `/api/vehicules/${vehicules[0].id}` }))
          }
          setShowModal(true)
        }}>+ Ajouter un plein</Button>}
      />
      <div className="p-4 md:p-8 space-y-6">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <StatBox label="Total dépensé" value={`${totaux.cout.toLocaleString('fr-FR', { maximumFractionDigits: 0 })} €`} accent="#10B981" />
          <StatBox label="Volume total" value={`${totaux.litres.toLocaleString('fr-FR', { maximumFractionDigits: 0 })} L`} accent="#00D4FF" />
          <StatBox label="Prix moyen / L" value={`${totaux.prixMoyen.toLocaleString('fr-FR', { minimumFractionDigits: 3, maximumFractionDigits: 3 })} €`} accent="#6C63FF" />
        </div>

        <Card className="p-0">
          {isLoading ? <SkeletonTable /> : (
            <Table columns={columns} data={pleins} emptyMessage="Aucun plein enregistré" />
          )}
        </Card>
      </div>

      <Modal isOpen={showModal} onClose={() => setShowModal(false)} title="Nouveau plein">
        <form onSubmit={handleCreate} className="space-y-4">
          <Select label="Véhicule" value={form.vehicule} onChange={(e) => setForm({ ...form, vehicule: e.target.value })} required>
            <option value="">— Sélectionner —</option>
            {vehicules.map((v) => (
              <option key={v.id} value={`/api/vehicules/${v.id}`}>{v.immatriculation} — {v.marque} {v.modele}</option>
            ))}
          </Select>
          <Input label="Date" type="date" value={form.date} onChange={(e) => setForm({ ...form, date: e.target.value })} required />
          <div className="grid grid-cols-2 gap-3">
            <Input label="Volume (L)" type="number" step="0.01" value={form.litres} onChange={(e) => setForm({ ...form, litres: e.target.value })} placeholder="45.0" required />
            <Input label="Prix / L (€)" type="number" step="0.001" value={form.prixLitre} onChange={(e) => setForm({ ...form, prixLitre: e.target.value })} placeholder="1.850" required />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <Input label="Kilométrage" type="number" value={form.kilometrage} onChange={(e) => setForm({ ...form, kilometrage: e.target.value })} placeholder="47000" required />
            <Select label="Carburant" value={form.typeCarburant} onChange={(e) => setForm({ ...form, typeCarburant: e.target.value })}>
              {Object.entries(CARBURANT_LABELS).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
            </Select>
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
