import api from './api'

export const conducteurService = {
  async getMonVehicule() {
    const r = await api.get('/conducteur/mon-vehicule')
    return r.data
  },

  async signalerProbleme(message) {
    const r = await api.post('/conducteur/signaler-probleme', { message })
    return r.data
  },

  async majKilometrage(kilometrage) {
    const r = await api.patch('/conducteur/kilometrage', { kilometrage })
    return r.data
  },
}
