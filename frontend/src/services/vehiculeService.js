import api from './api'

export const vehiculeService = {
  async getAll(params = {}) {
    const response = await api.get('/vehicules', { params })
    return response.data
  },

  async getById(id) {
    const response = await api.get(`/vehicules/${id}`)
    return response.data
  },

  async create(data) {
    const response = await api.post('/vehicules', data)
    return response.data
  },

  async update(id, data) {
    const response = await api.put(`/vehicules/${id}`, data)
    return response.data
  },

  async patch(id, data) {
    const response = await api.patch(`/vehicules/${id}`, data, {
      headers: { 'Content-Type': 'application/merge-patch+json' }
    })
    return response.data
  },

  async delete(id) {
    await api.delete(`/vehicules/${id}`)
  },
}
