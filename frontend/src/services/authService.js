import api from './api'

export const authService = {
  async login(email, motDePasse) {
    const response = await api.post('/auth/login', { email, motDePasse })
    const { token } = response.data
    localStorage.setItem('token', token)

    const meResponse = await api.get('/auth/me')
    const user = meResponse.data
    localStorage.setItem('user', JSON.stringify(user))

    return { token, user }
  },

  logout() {
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  },

  getCurrentUser() {
    const userStr = localStorage.getItem('user')
    return userStr ? JSON.parse(userStr) : null
  },

  isAuthenticated() {
    return !!localStorage.getItem('token')
  },
}
