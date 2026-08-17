import React, { useState, useEffect } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { Layout } from '../../components/layout/Layout'
import { TopBar } from '../../components/layout/TopBar'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Select } from '../../components/ui/Select'
import { vehiculeService } from '../../services/vehiculeService'
import { categorieService } from '../../services/categorieService'
import { useToastStore } from '../../store/toastStore'

export default function VehiculeForm() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { addToast } = useToastStore()
  const isEdit = !!id

  const [categories, setCategories] = useState([])
  const [isLoading, setIsLoading] = useState(false)
  const [errors, setErrors] = useState({})
  const [form, setForm] = useState({
    immatriculation: '',
    marque: '',
    modele: '',
    annee: new Date().getFullYear(),
    kilometrage: 0,
    quotaKmAnnuel: '',
    statut: 'disponible',
    categorie: '',
    adresse: '',
  })

  useEffect(() => {
    categorieService.getAll().then((data) => {
      setCategories(data['hydra:member'] || data)
    })
    if (isEdit) {
      vehiculeService.getById(id).then((data) => {
        setForm({
          immatriculation: data.immatriculation,
          marque: data.marque,
          modele: data.modele,
          annee: data.annee,
          kilometrage: data.kilometrage,
          quotaKmAnnuel: data.quotaKmAnnuel ?? '',
          statut: data.statut,
          categorie: data.categorie?.['@id'] || '',
          adresse: data.adresse || '',
        })
      })
    }
  }, [id, isEdit])

  const validate = () => {
    const newErrors = {}
    if (!form.immatriculation.match(/^[A-Z]{2}-[0-9]{3}-[A-Z]{2}$/)) {
      newErrors.immatriculation = 'Format requis: AA-000-AA'
    }
    if (!form.marque.trim()) newErrors.marque = 'Marque requise'
    if (!form.modele.trim()) newErrors.modele = 'Modèle requis'
    if (form.annee < 1900 || form.annee > 2030) newErrors.annee = 'Année invalide'
    if (form.kilometrage < 0) newErrors.kilometrage = 'Kilométrage invalide'
    setErrors(newErrors)
    return Object.keys(newErrors).length === 0
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    if (!validate()) return
    setIsLoading(true)
    try {
      const payload = {
        ...form,
        annee: parseInt(form.annee),
        kilometrage: parseInt(form.kilometrage),
        quotaKmAnnuel: form.quotaKmAnnuel !== '' ? parseInt(form.quotaKmAnnuel) : null,
      }
      if (!payload.categorie) delete payload.categorie
      if (payload.quotaKmAnnuel === null) delete payload.quotaKmAnnuel
      if (isEdit) {
        await vehiculeService.update(id, payload)
        addToast('Véhicule modifié avec succès')
      } else {
        await vehiculeService.create(payload)
        addToast('Véhicule créé avec succès')
      }
      navigate('/vehicules')
    } catch (err) {
      addToast(err.response?.data?.['hydra:description'] || 'Erreur lors de la sauvegarde', 'error')
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <Layout>
      <TopBar
        title={isEdit ? 'Modifier le véhicule' : 'Nouveau véhicule'}
        actions={<Button variant="ghost" onClick={() => navigate(-1)}>← Retour</Button>}
      />
      <div className="p-4 md:p-8 max-w-2xl animate-slide-up">
        <Card>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <Input
                label="Immatriculation"
                value={form.immatriculation}
                onChange={(e) => setForm({ ...form, immatriculation: e.target.value.toUpperCase() })}
                error={errors.immatriculation}
                placeholder="AB-123-CD"
                required
              />
              <Select
                label="Statut"
                value={form.statut}
                onChange={(e) => setForm({ ...form, statut: e.target.value })}
              >
                {['disponible', 'en_mission', 'maintenance', 'inactif'].map((s) => (
                  <option key={s} value={s}>{s.replace('_', ' ')}</option>
                ))}
              </Select>
              <Input label="Marque" value={form.marque} onChange={(e) => setForm({ ...form, marque: e.target.value })} error={errors.marque} required />
              <Input label="Modèle" value={form.modele} onChange={(e) => setForm({ ...form, modele: e.target.value })} error={errors.modele} required />
              <Input label="Année" type="number" value={form.annee} onChange={(e) => setForm({ ...form, annee: e.target.value })} error={errors.annee} required />
              <Input label="Kilométrage (km)" type="number" value={form.kilometrage} onChange={(e) => setForm({ ...form, kilometrage: e.target.value })} error={errors.kilometrage} />
              <Input label="Quota annuel (km)" type="number" value={form.quotaKmAnnuel} onChange={(e) => setForm({ ...form, quotaKmAnnuel: e.target.value })} placeholder="ex: 30000" />
              <Select label="Catégorie" value={form.categorie} onChange={(e) => setForm({ ...form, categorie: e.target.value })}>
                <option value="">— Sélectionner —</option>
                {categories.map((c) => (
                  <option key={c.id} value={`/api/categories/${c.id}`}>{c.libelle}</option>
                ))}
              </Select>
              <Input label="Adresse" value={form.adresse} onChange={(e) => setForm({ ...form, adresse: e.target.value })} placeholder="123 rue de la Paix, Paris" />
            </div>
            <div className="flex gap-3 pt-2">
              <Button type="submit" variant="primary" disabled={isLoading}>
                {isLoading ? 'Enregistrement...' : isEdit ? 'Enregistrer' : 'Créer le véhicule'}
              </Button>
              <Button type="button" variant="ghost" onClick={() => navigate(-1)}>Annuler</Button>
            </div>
          </form>
        </Card>
      </div>
    </Layout>
  )
}
