import api from './api'

export const alerteService = {
  async getAll(params = {}) {
    const response = await api.get('/alertes', { params })
    return response.data
  },

  async patch(id, data) {
    const response = await api.patch(`/alertes/${id}`, data, {
      headers: { 'Content-Type': 'application/merge-patch+json' }
    })
    return response.data
  },
}
