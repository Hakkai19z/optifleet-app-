import { useState, useEffect, useCallback } from 'react'
import { alerteService } from '../services/alerteService'

export function useAlertes(params = {}) {
  const [alertes, setAlertes] = useState([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState(null)

  const fetchAlertes = useCallback(async () => {
    setIsLoading(true)
    setError(null)
    try {
      const data = await alerteService.getAll(params)
      const items = data['hydra:member'] || data
      setAlertes(items)
    } catch (err) {
      setError(err.message)
    } finally {
      setIsLoading(false)
    }
  }, [JSON.stringify(params)]) // eslint-disable-line react-hooks/exhaustive-deps -- comparing params by value, not reference

  useEffect(() => {
    fetchAlertes()
  }, [fetchAlertes])

  return { alertes, isLoading, error, refetch: fetchAlertes }
}
