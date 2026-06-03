import api from './api'

export const pleinService = {
  async getAll(params = {}) {
    const response = await api.get('/pleins', { params })
    return response.data
  },

  async getById(id) {
    const response = await api.get(`/pleins/${id}`)
    return response.data
  },

  async create(data) {
    const response = await api.post('/pleins', data)
    return response.data
  },

  async update(id, data) {
    const response = await api.put(`/pleins/${id}`, data)
    return response.data
  },

  async delete(id) {
    await api.delete(`/pleins/${id}`)
  },
}
