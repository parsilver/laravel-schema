import { useState, useEffect } from 'react'
import { useSchema } from '../context/schema-context'
import type { Migration, SchemaDiff } from '../types'

interface SidebarProps {
  isOpen: boolean
  onClose: () => void
}

function extractTimestamp(filename: string): string {
  // Migration files are named like: 2024_01_15_123456_create_users_table.php
  const match = filename.match(/^(\d{4}_\d{2}_\d{2}_\d{6})/)
  if (match) {
    const [year, month, day] = match[1].split('_')
    return `${year}-${month}-${day}`
  }
  return ''
}

function extractMigrationName(filename: string): string {
  // Remove timestamp and .php extension
  return filename
    .replace(/^\d{4}_\d{2}_\d{2}_\d{6}_/, '')
    .replace(/\.php$/, '')
    .replace(/_/g, ' ')
}

function getTableStatus(tableName: string, diff: SchemaDiff | null): string | null {
  if (!diff?.tables) return null
  const tableDiff = diff.tables.find(t => t.name === tableName)
  return tableDiff?.status || null
}

function getStatusColor(status: string | null): string {
  switch (status) {
    case 'added':
      return 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300'
    case 'removed':
      return 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300'
    case 'modified':
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300'
    default:
      return ''
  }
}

interface SectionProps {
  title: string
  count: number
  defaultOpen?: boolean
  children: React.ReactNode
}

function Section({ title, count, defaultOpen = true, children }: SectionProps) {
  const [isOpen, setIsOpen] = useState(defaultOpen)

  return (
    <div className="border-b border-gray-200 dark:border-gray-700 last:border-b-0">
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
      >
        <span className="flex items-center gap-2">
          <span className="font-medium text-gray-900 dark:text-white">{title}</span>
          <span className="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
            {count}
          </span>
        </span>
        <svg
          className={`w-4 h-4 text-gray-500 transition-transform ${isOpen ? 'rotate-180' : ''}`}
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
        </svg>
      </button>
      {isOpen && (
        <div className="max-h-[300px] overflow-y-auto">
          {children}
        </div>
      )}
    </div>
  )
}

interface MigrationItemProps {
  migration: Migration
}

function MigrationItem({ migration }: MigrationItemProps) {
  const timestamp = extractTimestamp(migration.file)
  const name = extractMigrationName(migration.file)

  return (
    <div className="px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 border-l-2 border-transparent hover:border-indigo-500 transition-colors">
      <div className="text-sm text-gray-900 dark:text-gray-100 capitalize">
        {name}
      </div>
      <div className="text-xs text-gray-500 dark:text-gray-400 font-mono">
        {timestamp}
      </div>
    </div>
  )
}

interface TableItemProps {
  name: string
  status: string | null
}

function TableItem({ name, status }: TableItemProps) {
  const statusColor = getStatusColor(status)

  return (
    <div className="px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 border-l-2 border-transparent hover:border-indigo-500 transition-colors flex items-center justify-between">
      <span className="text-sm text-gray-900 dark:text-gray-100 font-mono">
        {name}
      </span>
      {status && status !== 'unchanged' && (
        <span className={`text-xs px-1.5 py-0.5 rounded ${statusColor}`}>
          {status}
        </span>
      )}
    </div>
  )
}

export default function Sidebar({ isOpen, onClose }: SidebarProps) {
  const { migrations, tables, diff } = useSchema()

  // Handle Escape key to close sidebar
  useEffect(() => {
    const handleEscape = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose()
    }
    if (isOpen) {
      document.addEventListener('keydown', handleEscape)
      return () => document.removeEventListener('keydown', handleEscape)
    }
  }, [isOpen, onClose])

  if (!isOpen) return null

  return (
    <>
      {/* Backdrop */}
      <div
        className="fixed inset-0 bg-black/20 dark:bg-black/40 z-40 lg:hidden"
        onClick={onClose}
      />

      {/* Sidebar */}
      <aside className="fixed left-0 top-0 h-full w-72 bg-white dark:bg-gray-800 shadow-lg z-50 flex flex-col border-r border-gray-200 dark:border-gray-700">
        {/* Header */}
        <div className="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Schema Explorer</h2>
          <button
            onClick={onClose}
            className="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400"
            aria-label="Close sidebar"
          >
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        {/* Content */}
        <div className="flex-1 overflow-y-auto">
          {/* Migrations Section */}
          <Section title="Migrations" count={migrations.length} defaultOpen={true}>
            {migrations.length === 0 ? (
              <div className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 italic">
                No migrations found
              </div>
            ) : (
              migrations.map((migration) => (
                <MigrationItem key={migration.file} migration={migration} />
              ))
            )}
          </Section>

          {/* Tables Section */}
          <Section title="Tables" count={tables.length} defaultOpen={true}>
            {tables.length === 0 ? (
              <div className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 italic">
                No tables found
              </div>
            ) : (
              tables.map((table) => (
                <TableItem
                  key={table.name}
                  name={table.name}
                  status={getTableStatus(table.name, diff)}
                />
              ))
            )}
          </Section>
        </div>
      </aside>
    </>
  )
}
