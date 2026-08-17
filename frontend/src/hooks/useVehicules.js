import { useState, useEffect, useCallback } from 'react'
import { vehiculeService } from '../services/vehiculeService'

export function useVehicules(params = {}) {
  const [vehicules, setVehicules] = useState([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState(null)
  const [total, setTotal] = useState(0)

  const fetchVehicules = useCallback(async () => {
    setIsLoading(true)
    setError(null)
    try {
      const data = await vehiculeService.getAll(params)
      const items = data['hydra:member'] || data
      setVehicules(items)
      setTotal(data['hydra:totalItems'] || items.length)
    } catch (err) {
      setError(err.message)
    } finally {
      setIsLoading(false)
    }
  }, [JSON.stringify(params)]) // eslint-disable-line react-hooks/exhaustive-deps -- comparing params by value, not reference

  useEffect(() => {
    fetchVehicules()
  }, [fetchVehicules])

  return { vehicules, isLoading, error, total, refetch: fetchVehicules }
}
