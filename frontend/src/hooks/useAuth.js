import { useCallback } from 'react'
import { useAuthStore } from '../store/authStore'

export function useAuth() {
  const { user, isAuthenticated, isLoading, error, login, register, logout, clearError } = useAuthStore()

  const hasRole = useCallback((role) => {
    if (!user) return false
    const hierarchy = { ADMIN: 3, GESTIONNAIRE: 2, CONDUCTEUR: 1 }
    const userLevel = hierarchy[user.role] || 0
    const requiredLevel = hierarchy[role] || 0
    return userLevel >= requiredLevel
  }, [user])

  return { user, isAuthenticated, isLoading, error, login, register, logout, clearError, hasRole }
}
