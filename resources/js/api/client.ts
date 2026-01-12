import type {
  ApiResponse,
  TablesResponse,
  TableResponse,
  DiffResponse,
  MigrationsResponse,
  StatusResponse,
  TableDiff
} from '../types'

const getBaseUrl = (): string => {
  return window.LaravelSchema?.apiUrl || '/schema/api'
}

const handleResponse = async <T>(response: Response): Promise<ApiResponse<T>> => {
  if (!response.ok) {
    const error = await response.json().catch(() => ({}))
    throw new Error(error.data?.error || `HTTP error ${response.status}`)
  }
  return response.json()
}

export const api = {
  async getTables(): Promise<ApiResponse<TablesResponse>> {
    const response = await fetch(`${getBaseUrl()}/tables`)
    return handleResponse<TablesResponse>(response)
  },

  async getTable(name: string): Promise<ApiResponse<TableResponse>> {
    const response = await fetch(`${getBaseUrl()}/tables/${encodeURIComponent(name)}`)
    return handleResponse<TableResponse>(response)
  },

  async getDiff(): Promise<ApiResponse<DiffResponse>> {
    const response = await fetch(`${getBaseUrl()}/diff`)
    return handleResponse<DiffResponse>(response)
  },

  async getTableDiff(name: string): Promise<ApiResponse<TableDiff>> {
    const response = await fetch(`${getBaseUrl()}/diff/${encodeURIComponent(name)}`)
    return handleResponse<TableDiff>(response)
  },

  async getMigrations(): Promise<ApiResponse<MigrationsResponse>> {
    const response = await fetch(`${getBaseUrl()}/migrations`)
    return handleResponse<MigrationsResponse>(response)
  },

  async getStatus(): Promise<ApiResponse<StatusResponse>> {
    const response = await fetch(`${getBaseUrl()}/status`)
    return handleResponse<StatusResponse>(response)
  },

  async refresh(): Promise<ApiResponse<unknown>> {
    const response = await fetch(`${getBaseUrl()}/refresh`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    return handleResponse<unknown>(response)
  }
}
