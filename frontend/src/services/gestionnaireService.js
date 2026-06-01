import api from './api'

export const gestionnaireService = {
  async getConducteurs() {
    const r = await api.get('/gestionnaire/conducteurs')
    return r.data
  },

  async getVehiculeDisponibles() {
    const r = await api.get('/gestionnaire/vehicules-disponibles')
    return r.data
  },

  async getVueFlotte() {
    const r = await api.get('/gestionnaire/vue-flotte')
    return r.data
  },

  async affecter(conducteurId, vehiculeId, commentaire = '') {
    const r = await api.post('/gestionnaire/affecter', { conducteurId, vehiculeId, commentaire })
    return r.data
  },

  async desaffecter(affectationId) {
    const r = await api.delete(`/gestionnaire/desaffecter/${affectationId}`)
    return r.data
  },

  async changerStatut(vehiculeId, statut) {
    const r = await api.patch(`/gestionnaire/changer-statut/${vehiculeId}`, { statut })
    return r.data
  },
}
