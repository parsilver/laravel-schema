import { useState, useEffect, useCallback } from 'react'
import { api } from '../api/client'
import type { Table, TableDiff, Migration } from '../types'

interface UseTableResult {
  table: Table | null
  loading: boolean
  error: string | null
  refetch: () => Promise<void>
}

export function useTable(tableName: string | null): UseTableResult {
  const [table, setTable] = useState<Table | null>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const fetchTable = useCallback(async () => {
    if (!tableName) return

    setLoading(true)
    setError(null)
    try {
      const response = await api.getTable(tableName)
      setTable(response.data.table)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to fetch table')
    } finally {
      setLoading(false)
    }
  }, [tableName])

  useEffect(() => {
    fetchTable()
  }, [fetchTable])

  return { table, loading, error, refetch: fetchTable }
}

interface UseTableDiffResult {
  diff: TableDiff | null
  loading: boolean
  error: string | null
  refetch: () => Promise<void>
}

export function useTableDiff(tableName: string | null): UseTableDiffResult {
  const [diff, setDiff] = useState<TableDiff | null>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const fetchDiff = useCallback(async () => {
    if (!tableName) return

    setLoading(true)
    setError(null)
    try {
      const response = await api.getTableDiff(tableName)
      setDiff(response.data)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to fetch diff')
    } finally {
      setLoading(false)
    }
  }, [tableName])

  useEffect(() => {
    fetchDiff()
  }, [fetchDiff])

  return { diff, loading, error, refetch: fetchDiff }
}

interface UseMigrationsResult {
  migrations: Migration[]
  loading: boolean
  error: string | null
  refetch: () => Promise<void>
}

export function useMigrations(): UseMigrationsResult {
  const [migrations, setMigrations] = useState<Migration[]>([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const fetchMigrations = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const response = await api.getMigrations()
      setMigrations(response.data.migrations || [])
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to fetch migrations')
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    fetchMigrations()
  }, [fetchMigrations])

  return { migrations, loading, error, refetch: fetchMigrations }
}
