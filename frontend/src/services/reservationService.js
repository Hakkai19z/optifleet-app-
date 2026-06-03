import api from './api'

export const reservationService = {
  async getAll(params = {}) {
    const response = await api.get('/reservations', { params })
    return response.data
  },

  async getById(id) {
    const response = await api.get(`/reservations/${id}`)
    return response.data
  },

  async create(data) {
    const response = await api.post('/reservations', data)
    return response.data
  },

  async update(id, data) {
    const response = await api.patch(`/reservations/${id}`, data, {
      headers: { 'Content-Type': 'application/merge-patch+json' },
    })
    return response.data
  },

  async delete(id) {
    await api.delete(`/reservations/${id}`)
  },
}
