import api from './api'

export const entretienService = {
  async getAll(params = {}) {
    const response = await api.get('/entretiens', { params })
    return response.data
  },

  async getById(id) {
    const response = await api.get(`/entretiens/${id}`)
    return response.data
  },

  async create(data) {
    const response = await api.post('/entretiens', data)
    return response.data
  },

  async update(id, data) {
    const response = await api.put(`/entretiens/${id}`, data)
    return response.data
  },

  async delete(id) {
    await api.delete(`/entretiens/${id}`)
  },
}
