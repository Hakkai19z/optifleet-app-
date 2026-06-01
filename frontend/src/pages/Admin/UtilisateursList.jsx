import React, { useState, useEffect } from 'react'
import { Layout } from '../../components/layout/Layout'
import { TopBar } from '../../components/layout/TopBar'
import { Card } from '../../components/ui/Card'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Table } from '../../components/ui/Table'
import { Modal } from '../../components/ui/Modal'
import { Input } from '../../components/ui/Input'
import { Select } from '../../components/ui/Select'
import { SkeletonTable } from '../../components/ui/Skeleton'
import { utilisateurService } from '../../services/utilisateurService'
import { useToastStore } from '../../store/toastStore'

export default function UtilisateursList() {
  const { addToast } = useToastStore()
  const [users, setUsers] = useState([])
  const [isLoading, setIsLoading] = useState(true)
  const [showModal, setShowModal] = useState(false)
  const [form, setForm] = useState({ nom: '', prenom: '', email: '', motDePasse: '', role: 'CONDUCTEUR' })

  const fetchUsers = () => {
    utilisateurService.getAll()
      .then((data) => setUsers(data['hydra:member'] || data))
      .finally(() => setIsLoading(false))
  }

  useEffect(() => { fetchUsers() }, [])

  const handleCreate = async (e) => {
    e.preventDefault()
    try {
      await utilisateurService.create(form)
      addToast('Utilisateur créé')
      setShowModal(false)
      setForm({ nom: '', prenom: '', email: '', motDePasse: '', role: 'CONDUCTEUR' })
      fetchUsers()
    } catch {
      addToast('Erreur lors de la création', 'error')
    }
  }

  const handleDelete = async (id) => {
    if (!confirm('Supprimer cet utilisateur ?')) return
    try {
      await utilisateurService.delete(id)
      addToast('Utilisateur supprimé')
      fetchUsers()
    } catch {
      addToast('Impossible de supprimer', 'error')
    }
  }

  const columns = [
    { key: 'nom', label: 'Nom', render: (row) => `${row.prenom} ${row.nom}` },
    { key: 'email', label: 'Email' },
    { key: 'role', label: 'Rôle', render: (row) => <Badge variant={row.role} /> },
    { key: 'createdAt', label: 'Créé le', render: (row) => row.createdAt ? new Date(row.createdAt).toLocaleDateString('fr-FR') : '—' },
    { key: 'actions', label: '', render: (row) => (
      <Button variant="danger" size="sm" onClick={() => handleDelete(row.id)}>Supprimer</Button>
    )},
  ]

  return (
    <Layout>
      <TopBar title="Utilisateurs" subtitle="Gestion des comptes" />
      <div className="p-8">
        <div className="flex justify-end mb-6">
          <Button variant="primary" onClick={() => setShowModal(true)}>+ Nouvel utilisateur</Button>
        </div>
        <Card className="p-0">
          {isLoading ? <div className="p-6"><SkeletonTable /></div> : (
            <Table columns={columns} data={users} emptyMessage="Aucun utilisateur" />
          )}
        </Card>
      </div>

      <Modal isOpen={showModal} onClose={() => setShowModal(false)} title="Nouvel utilisateur">
        <form onSubmit={handleCreate} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <Input label="Nom" value={form.nom} onChange={(e) => setForm({ ...form, nom: e.target.value })} required />
            <Input label="Prénom" value={form.prenom} onChange={(e) => setForm({ ...form, prenom: e.target.value })} required />
          </div>
          <Input label="Email" type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} required />
          <Input label="Mot de passe" type="password" value={form.motDePasse} onChange={(e) => setForm({ ...form, motDePasse: e.target.value })} required />
          <Select label="Rôle" value={form.role} onChange={(e) => setForm({ ...form, role: e.target.value })}>
            <option value="CONDUCTEUR">Conducteur</option>
            <option value="GESTIONNAIRE">Gestionnaire</option>
            <option value="ADMIN">Administrateur</option>
          </Select>
          <div className="flex gap-3">
            <Button type="submit" variant="primary">Créer</Button>
            <Button type="button" variant="ghost" onClick={() => setShowModal(false)}>Annuler</Button>
          </div>
        </form>
      </Modal>
    </Layout>
  )
}
