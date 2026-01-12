// Global window types
declare global {
  interface Window {
    LaravelSchema?: {
      apiUrl?: string
    }
  }
}

// Column types
export interface Column {
  name: string
  type: string
  length?: number
  precision?: number
  scale?: number
  nullable: boolean
  default: string | number | boolean | null
}

export interface ColumnDiff {
  name: string
  status: 'added' | 'removed' | 'modified' | 'unchanged'
  changes?: Record<string, { from: unknown; to: unknown }>
}

// Index types
export interface Index {
  name: string
  type: 'primary' | 'unique' | 'index'
  columns: string[]
}

export interface IndexDiff {
  name: string
  status: 'added' | 'removed' | 'modified' | 'unchanged'
}

// Foreign key types
export interface ForeignKey {
  name: string
  columns: string[]
  referencedTable: string
  referencedColumns: string[]
  onUpdate?: string
  onDelete?: string
}

export interface ForeignKeyDiff {
  name: string
  status: 'added' | 'removed' | 'modified' | 'unchanged'
}

// Table types
export interface Table {
  name: string
  columns?: Column[]
  indexes?: Index[]
  foreignKeys?: ForeignKey[]
  engine?: string
  charset?: string
  collation?: string
}

export interface TableDiff {
  name: string
  status: 'added' | 'removed' | 'modified' | 'unchanged'
  columns?: ColumnDiff[]
  indexes?: IndexDiff[]
  foreignKeys?: ForeignKeyDiff[]
  diff?: {
    columns?: ColumnDiff[]
    indexes?: IndexDiff[]
    foreignKeys?: ForeignKeyDiff[]
  }
}

// Schema status types
export interface SchemaStatus {
  synced: boolean
  summary?: SchemaSummary
  addedTables?: number
  removedTables?: number
  modifiedTables?: number
}

export interface SchemaSummary {
  total_tables?: number
  added_tables?: number
  removed_tables?: number
  modified_tables?: number
}

// Diff types
export interface SchemaDiff {
  hasDifferences: boolean
  summary?: SchemaSummary
  diff?: {
    tables?: TableDiff[]
  }
}

// API Response types
export interface ApiResponse<T> {
  data: T
}

export interface TablesResponse {
  tables: Table[]
}

export interface TableResponse {
  table: Table
}

export interface StatusResponse extends SchemaStatus {}

export interface DiffResponse extends SchemaDiff {}

export interface Migration {
  file: string
  path: string
}

export interface MigrationsResponse {
  migrations: Migration[]
  count: number
  path: string
}

export type ViewMode = 'diff' | 'database' | 'migrations'

// Toast types
export interface Toast {
  id: string
  message: string
  type: 'success' | 'error' | 'info'
}

// Schema Context types
export interface SchemaContextValue {
  tables: Table[]
  migrations: Migration[]
  status: SchemaStatus | null
  diff: SchemaDiff | null
  viewMode: ViewMode
  loading: boolean
  error: string | null
  lastRefreshed: Date | null
  toast: Toast | null
  refresh: () => Promise<void>
  fetchTables: () => Promise<void>
  fetchStatus: () => Promise<void>
  fetchDiff: () => Promise<void>
  fetchMigrations: () => Promise<void>
  setViewMode: (mode: ViewMode) => void
  clearToast: () => void
}
