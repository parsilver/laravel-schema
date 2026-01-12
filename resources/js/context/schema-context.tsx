import { createContext, useContext, useState, useEffect, useCallback, type ReactNode } from 'react'
import { api } from '../api/client'
import type { Table, SchemaStatus, SchemaDiff, SchemaContextValue, Toast } from '../types'

const SchemaContext = createContext<SchemaContextValue | null>(null)

interface SchemaProviderProps {
  children: ReactNode
}

export function SchemaProvider({ children }: SchemaProviderProps) {
  const [tables, setTables] = useState<Table[]>([])
  const [status, setStatus] = useState<SchemaStatus | null>(null)
  const [diff, setDiff] = useState<SchemaDiff | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [lastRefreshed, setLastRefreshed] = useState<Date | null>(null)
  const [toast, setToast] = useState<Toast | null>(null)

  const clearToast = useCallback(() => {
    setToast(null)
  }, [])

  const fetchTables = useCallback(async () => {
    try {
      const response = await api.getTables()
      setTables(response.data.tables || [])
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to fetch tables')
    }
  }, [])

  const fetchStatus = useCallback(async () => {
    try {
      const response = await api.getStatus()
      setStatus(response.data)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to fetch status')
    }
  }, [])

  const fetchDiff = useCallback(async () => {
    try {
      const response = await api.getDiff()
      setDiff(response.data)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to fetch diff')
    }
  }, [])

  const refresh = useCallback(async () => {
    setLoading(true)
    setError(null)
    setToast(null)
    try {
      await api.refresh()
      await Promise.all([fetchTables(), fetchStatus(), fetchDiff()])
      setLastRefreshed(new Date())
      setToast({
        id: Date.now().toString(),
        message: 'Schema refreshed successfully',
        type: 'success'
      })
      // Auto-clear toast after 3 seconds
      setTimeout(() => setToast(null), 3000)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to refresh')
      setToast({
        id: Date.now().toString(),
        message: err instanceof Error ? err.message : 'Failed to refresh',
        type: 'error'
      })
    } finally {
      setLoading(false)
    }
  }, [fetchTables, fetchStatus, fetchDiff])

  const initialize = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      await Promise.all([fetchTables(), fetchStatus(), fetchDiff()])
      setLastRefreshed(new Date())
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to initialize')
    } finally {
      setLoading(false)
    }
  }, [fetchTables, fetchStatus, fetchDiff])

  useEffect(() => {
    initialize()
  }, [initialize])

  const value: SchemaContextValue = {
    tables,
    status,
    diff,
    loading,
    error,
    lastRefreshed,
    toast,
    refresh,
    fetchTables,
    fetchStatus,
    fetchDiff,
    clearToast
  }

  return (
    <SchemaContext.Provider value={value}>
      {children}
    </SchemaContext.Provider>
  )
}

export function useSchema(): SchemaContextValue {
  const context = useContext(SchemaContext)
  if (!context) {
    throw new Error('useSchema must be used within a SchemaProvider')
  }
  return context
}
