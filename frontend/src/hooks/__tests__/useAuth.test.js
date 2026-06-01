import { describe, it, expect, beforeEach, vi } from 'vitest'
import { renderHook, act } from '@testing-library/react'
import { useAuthStore } from '../../store/authStore'

vi.mock('../../services/authService', () => ({
  authService: {
    login: vi.fn(),
    logout: vi.fn(),
    getCurrentUser: vi.fn(() => null),
    isAuthenticated: vi.fn(() => false),
  },
}))

describe('useAuthStore', () => {
  beforeEach(() => {
    useAuthStore.setState({ user: null, isAuthenticated: false, error: null, isLoading: false })
  })

  it('initial state is unauthenticated', () => {
    const { result } = renderHook(() => useAuthStore())
    expect(result.current.isAuthenticated).toBe(false)
    expect(result.current.user).toBeNull()
  })

  it('clears error', () => {
    useAuthStore.setState({ error: 'Some error' })
    const { result } = renderHook(() => useAuthStore())
    act(() => { result.current.clearError() })
    expect(result.current.error).toBeNull()
  })

  it('logout clears user and auth state', () => {
    useAuthStore.setState({ user: { id: 1, nom: 'Test' }, isAuthenticated: true })
    const { result } = renderHook(() => useAuthStore())
    act(() => { result.current.logout() })
    expect(result.current.user).toBeNull()
    expect(result.current.isAuthenticated).toBe(false)
  })
})
