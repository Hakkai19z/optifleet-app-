import { create } from 'zustand'
import { authService } from '../services/authService'

export const useAuthStore = create((set) => ({
  user: authService.getCurrentUser(),
  isAuthenticated: authService.isAuthenticated(),
  isLoading: false,
  error: null,

  login: async (email, motDePasse) => {
    set({ isLoading: true, error: null })
    try {
      const { user } = await authService.login(email, motDePasse)
      set({ user, isAuthenticated: true, isLoading: false })
      return user
    } catch (err) {
      const message = err.response?.data?.message || 'Identifiants incorrects'
      set({ error: message, isLoading: false })
      throw err
    }
  },

  register: async (nom, prenom, email, motDePasse) => {
    set({ isLoading: true, error: null })
    try {
      const { user } = await authService.register(nom, prenom, email, motDePasse)
      set({ user, isAuthenticated: true, isLoading: false })
      return user
    } catch (err) {
      const message = err.response?.data?.message || "Erreur lors de l'inscription"
      set({ error: message, isLoading: false })
      throw err
    }
  },

  logout: () => {
    authService.logout()
    set({ user: null, isAuthenticated: false })
  },

  clearError: () => set({ error: null }),
}))
