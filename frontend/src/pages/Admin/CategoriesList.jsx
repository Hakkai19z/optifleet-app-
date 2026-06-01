import React, { useState, useEffect } from 'react'
import { Layout } from '../../components/layout/Layout'
import { TopBar } from '../../components/layout/TopBar'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Modal } from '../../components/ui/Modal'
import { Input } from '../../components/ui/Input'
import { categorieService } from '../../services/categorieService'
import { useToastStore } from '../../store/toastStore'

export default function CategoriesList() {
  const { addToast } = useToastStore()
  const [categories, setCategories] = useState([])
  const [showModal, setShowModal] = useState(false)
  const [form, setForm] = useState({ libelle: '', description: '' })

  const fetchCategories = () => {
    categorieService.getAll().then((data) => setCategories(data['hydra:member'] || data))
  }

  useEffect(() => { fetchCategories() }, [])

  const handleCreate = async (e) => {
    e.preventDefault()
    try {
      await categorieService.create(form)
      addToast('Catégorie créée')
      setShowModal(false)
      setForm({ libelle: '', description: '' })
      fetchCategories()
    } catch {
      addToast('Erreur lors de la création', 'error')
    }
  }

  const handleDelete = async (id) => {
    if (!confirm('Supprimer cette catégorie ?')) return
    try {
      await categorieService.delete(id)
      addToast('Catégorie supprimée')
      fetchCategories()
    } catch {
      addToast('Impossible de supprimer (véhicules rattachés ?)', 'error')
    }
  }

  const columns = [
    { key: 'libelle', label: 'Libellé', render: (row) => (
      <span className="font-medium text-white">{row.libelle}</span>
    )},
    { key: 'description', label: 'Description', render: (row) => (
      <span className="text-slate-400">{row.description || '—'}</span>
    )},
    { key: 'actions', label: '', render: (row) => (
      <Button variant="danger" onClick={() => handleDelete(row.id)}>Supprimer</Button>
    )},
  ]

  return (
    <Layout>
      <TopBar
        title="Catégories"
        subtitle="Catégories de véhicules"
        actions={<Button variant="primary" onClick={() => setShowModal(true)}>+ Nouvelle catégorie</Button>}
      />
      <div className="p-4 md:p-8">
        <Card className="p-0">
          <Table columns={columns} data={categories} emptyMessage="Aucune catégorie" />
        </Card>
      </div>

      <Modal isOpen={showModal} onClose={() => setShowModal(false)} title="Nouvelle catégorie">
        <form onSubmit={handleCreate} className="space-y-4">
          <Input label="Libellé" value={form.libelle} onChange={(e) => setForm({ ...form, libelle: e.target.value })} required />
          <Input label="Description" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} />
          <div className="flex gap-3">
            <Button type="submit" variant="primary">Créer</Button>
            <Button type="button" variant="ghost" onClick={() => setShowModal(false)}>Annuler</Button>
          </div>
        </form>
      </Modal>
    </Layout>
  )
}
