import api from './api'

export const statistiqueService = {
  async getGlobales() {
    const response = await api.get('/statistiques')
    return response.data
  },
}
