import api from './api'

export const categorieService = {
  async getAll() {
    const response = await api.get('/categories')
    return response.data
  },

  async create(data) {
    const response = await api.post('/categories', data)
    return response.data
  },

  async update(id, data) {
    const response = await api.put(`/categories/${id}`, data)
    return response.data
  },

  async delete(id) {
    await api.delete(`/categories/${id}`)
  },
}
