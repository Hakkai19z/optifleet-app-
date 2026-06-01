import api from './api'

export const utilisateurService = {
  async getAll() {
    const response = await api.get('/utilisateurs')
    return response.data
  },

  async getById(id) {
    const response = await api.get(`/utilisateurs/${id}`)
    return response.data
  },

  async create(data) {
    const response = await api.post('/utilisateurs', data)
    return response.data
  },

  async update(id, data) {
    const response = await api.put(`/utilisateurs/${id}`, data)
    return response.data
  },

  async delete(id) {
    await api.delete(`/utilisateurs/${id}`)
  },
}
