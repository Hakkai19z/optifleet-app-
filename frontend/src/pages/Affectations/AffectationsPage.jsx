import React, { useState, useEffect, useCallback } from 'react'
import { Layout } from '../../components/layout/Layout'
import { TopBar } from '../../components/layout/TopBar'
import { Button } from '../../components/ui/Button'
import { Modal } from '../../components/ui/Modal'
import { Select } from '../../components/ui/Select'
import { Input } from '../../components/ui/Input'
import { SkeletonTable } from '../../components/ui/Skeleton'
import { gestionnaireService } from '../../services/gestionnaireService'
import { useToastStore } from '../../store/toastStore'

function ConducteurCard({ conducteur, vehiculesDisponibles, onAffecter, onDesaffecter }) {
  const [showModal, setShowModal] = useState(false)
  const [vehiculeId, setVehiculeId] = useState('')
  const [commentaire, setCommentaire] = useState('')
  const [loading, setLoading] = useState(false)

  const handleAffecter = async (e) => {
    e.preventDefault()
    if (!vehiculeId) return
    setLoading(true)
    try {
      await onAffecter(conducteur.id, parseInt(vehiculeId), commentaire)
      setShowModal(false)
      setVehiculeId('')
      setCommentaire('')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="glass-card p-5 flex flex-col sm:flex-row items-start sm:items-center gap-4 transition-all hover:border-violet-500/20">
      {/* Avatar */}
      <div className="w-12 h-12 rounded-2xl flex items-center justify-center text-sm font-bold text-white flex-shrink-0"
        style={{ background: 'linear-gradient(135deg, #6C63FF, #8B5CF6)' }}>
        {conducteur.prenom[0]}{conducteur.nom[0]}
      </div>

      {/* Info conducteur */}
      <div className="flex-1 min-w-0">
        <p className="font-semibold text-white">{conducteur.prenom} {conducteur.nom}</p>
        <p className="text-xs text-slate-500 truncate">{conducteur.email}</p>
      </div>

      {/* Véhicule actuel */}
      <div className="flex-1 min-w-0">
        {conducteur.vehiculeActuel ? (
          <div className="flex items-center gap-2">
            <div className="w-2 h-2 rounded-full bg-emerald-400 flex-shrink-0" />
            <div className="min-w-0">
              <p className="text-sm font-mono font-bold text-white">{conducteur.vehiculeActuel.immatriculation}</p>
              <p className="text-xs text-slate-500">{conducteur.vehiculeActuel.marque} {conducteur.vehiculeActuel.modele} · depuis {conducteur.vehiculeActuel.dateDebut}</p>
            </div>
          </div>
        ) : (
          <div className="flex items-center gap-2">
            <div className="w-2 h-2 rounded-full bg-slate-600 flex-shrink-0" />
            <p className="text-sm text-slate-500">Aucun véhicule assigné</p>
          </div>
        )}
      </div>

      {/* Actions */}
      <div className="flex gap-2 flex-shrink-0">
        {conducteur.vehiculeActuel ? (
          <Button
            variant="danger"
            size="sm"
            onClick={() => onDesaffecter(conducteur.vehiculeActuel.affectationId, conducteur)}
          >
            Libérer
          </Button>
        ) : (
          <Button
            variant="primary"
            size="sm"
            onClick={() => setShowModal(true)}
            disabled={vehiculesDisponibles.length === 0}
          >
            + Assigner
          </Button>
        )}
        {conducteur.vehiculeActuel && (
          <Button
            variant="secondary"
            size="sm"
            onClick={() => setShowModal(true)}
            disabled={vehiculesDisponibles.length === 0}
          >
            Changer
          </Button>
        )}
      </div>

      <Modal isOpen={showModal} onClose={() => setShowModal(false)} title={`Assigner un véhicule à ${conducteur.prenom} ${conducteur.nom}`}>
        <form onSubmit={handleAffecter} className="space-y-4">
          {vehiculesDisponibles.length === 0 ? (
            <div className="text-center py-8 text-slate-500">
              <p className="text-sm">Aucun véhicule disponible actuellement.</p>
              <p className="text-xs mt-1 text-slate-600">Libérez un véhicule ou changez son statut.</p>
            </div>
          ) : (
            <>
              <Select
                label="Véhicule disponible"
                value={vehiculeId}
                onChange={(e) => setVehiculeId(e.target.value)}
                required
              >
                <option value="">— Choisir un véhicule —</option>
                {vehiculesDisponibles.map((v) => (
                  <option key={v.id} value={v.id}>
                    {v.immatriculation} — {v.marque} {v.modele} ({v.kilometrage?.toLocaleString('fr-FR')} km)
                  </option>
                ))}
              </Select>
              <Input
                label="Commentaire (optionnel)"
                value={commentaire}
                onChange={(e) => setCommentaire(e.target.value)}
                placeholder="Mission, durée prévue..."
              />
              <div className="flex gap-3 pt-2">
                <Button type="submit" variant="primary" disabled={loading || !vehiculeId}>
                  {loading ? 'Affectation...' : 'Confirmer l\'affectation'}
                </Button>
                <Button type="button" variant="ghost" onClick={() => setShowModal(false)}>Annuler</Button>
              </div>
            </>
          )}
        </form>
      </Modal>
    </div>
  )
}

export default function AffectationsPage() {
  const { addToast } = useToastStore()
  const [conducteurs, setConducteurs] = useState([])
  const [vehiculesDisponibles, setVehiculesDisponibles] = useState([])
  const [isLoading, setIsLoading] = useState(true)
  const [search, setSearch] = useState('')

  const fetchData = useCallback(async () => {
    setIsLoading(true)
    try {
      const [c, v] = await Promise.all([
        gestionnaireService.getConducteurs(),
        gestionnaireService.getVehiculeDisponibles(),
      ])
      setConducteurs(c)
      setVehiculesDisponibles(v)
    } catch {
      addToast('Erreur lors du chargement', 'error')
    } finally {
      setIsLoading(false)
    }
  }, [addToast])

  useEffect(() => { fetchData() }, [fetchData])

  const handleAffecter = async (conducteurId, vehiculeId, commentaire) => {
    try {
      const res = await gestionnaireService.affecter(conducteurId, vehiculeId, commentaire)
      addToast(res.message)
      await fetchData()
    } catch (err) {
      addToast(err.response?.data?.message || 'Erreur lors de l\'affectation', 'error')
    }
  }

  const handleDesaffecter = async (affectationId, conducteur) => {
    if (!confirm(`Libérer le véhicule de ${conducteur.prenom} ${conducteur.nom} ?`)) return
    try {
      const res = await gestionnaireService.desaffecter(affectationId)
      addToast(res.message)
      await fetchData()
    } catch {
      addToast('Erreur lors de la libération', 'error')
    }
  }

  const filtered = conducteurs.filter(c =>
    `${c.prenom} ${c.nom} ${c.email}`.toLowerCase().includes(search.toLowerCase())
  )

  const avecVehicule = filtered.filter(c => c.vehiculeActuel)
  const sansVehicule = filtered.filter(c => !c.vehiculeActuel)

  const stats = {
    total: conducteurs.length,
    affectes: conducteurs.filter(c => c.vehiculeActuel).length,
    libres: conducteurs.filter(c => !c.vehiculeActuel).length,
    vehiculesDispos: vehiculesDisponibles.length,
  }

  return (
    <Layout>
      <TopBar
        title="Affectations"
        subtitle="Gérez l'attribution des véhicules aux conducteurs"
      />

      <div className="p-4 md:p-8 space-y-6 animate-slide-up">
        {/* Stats */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          {[
            { label: 'Conducteurs', value: stats.total, color: '#6C63FF' },
            { label: 'Avec véhicule', value: stats.affectes, color: '#10B981' },
            { label: 'Sans véhicule', value: stats.libres, color: '#F59E0B' },
            { label: 'Véhicules dispos', value: stats.vehiculesDispos, color: '#00D4FF' },
          ].map(({ label, value, color }) => (
            <div key={label} className="glass-card p-4 text-center">
              <p className="text-2xl font-display font-bold" style={{ color }}>{value}</p>
              <p className="text-xs text-slate-500 mt-0.5">{label}</p>
            </div>
          ))}
        </div>

        {/* Recherche */}
        <div className="relative">
          <svg className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            type="text"
            placeholder="Rechercher un conducteur..."
            value={search}
            onChange={e => setSearch(e.target.value)}
            className="form-input pl-9 max-w-md"
          />
        </div>

        {isLoading ? (
          <SkeletonTable rows={4} />
        ) : (
          <div className="space-y-6">
            {/* Conducteurs avec véhicule */}
            {avecVehicule.length > 0 && (
              <div>
                <div className="flex items-center gap-2 mb-3">
                  <div className="w-2 h-2 rounded-full bg-emerald-400" />
                  <h3 className="text-sm font-semibold text-white">En mission ({avecVehicule.length})</h3>
                </div>
                <div className="space-y-3">
                  {avecVehicule.map(c => (
                    <ConducteurCard
                      key={c.id}
                      conducteur={c}
                      vehiculesDisponibles={vehiculesDisponibles}
                      onAffecter={handleAffecter}
                      onDesaffecter={handleDesaffecter}
                    />
                  ))}
                </div>
              </div>
            )}

            {/* Conducteurs sans véhicule */}
            {sansVehicule.length > 0 && (
              <div>
                <div className="flex items-center gap-2 mb-3">
                  <div className="w-2 h-2 rounded-full bg-slate-500" />
                  <h3 className="text-sm font-semibold text-white">Sans véhicule ({sansVehicule.length})</h3>
                </div>
                <div className="space-y-3">
                  {sansVehicule.map(c => (
                    <ConducteurCard
                      key={c.id}
                      conducteur={c}
                      vehiculesDisponibles={vehiculesDisponibles}
                      onAffecter={handleAffecter}
                      onDesaffecter={handleDesaffecter}
                    />
                  ))}
                </div>
              </div>
            )}

            {filtered.length === 0 && (
              <div className="text-center py-16 text-slate-500">
                <p className="text-sm">Aucun conducteur trouvé</p>
              </div>
            )}
          </div>
        )}
      </div>
    </Layout>
  )
}
