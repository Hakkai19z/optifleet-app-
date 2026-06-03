import api from './api'

export const documentService = {
  async getAll(params = {}) {
    const response = await api.get('/documents', { params })
    return response.data
  },

  async getById(id) {
    const response = await api.get(`/documents/${id}`)
    return response.data
  },

  async create(data) {
    const response = await api.post('/documents', data)
    return response.data
  },

  async update(id, data) {
    const response = await api.put(`/documents/${id}`, data)
    return response.data
  },

  async delete(id) {
    await api.delete(`/documents/${id}`)
  },
}
