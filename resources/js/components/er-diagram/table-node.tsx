import { memo, useState, useMemo } from 'react'
import { Handle, Position } from '@xyflow/react'
import type { NodeProps } from '@xyflow/react'
import type { TableNodeData } from '../../utils/transform'

const COLUMN_THRESHOLD = 10 // Show expand button when columns exceed this

const statusStyles = {
  added: {
    border: 'border-green-500 border-dashed', // Dashed border for added
    bg: 'bg-green-50 dark:bg-green-900/20',
    badge: 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
  },
  removed: {
    border: 'border-red-500 border-dotted', // Dotted border for removed
    bg: 'bg-red-50 dark:bg-red-900/20',
    badge: 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
  },
  modified: {
    border: 'border-yellow-500 border-double', // Double border for modified
    bg: 'bg-yellow-50 dark:bg-yellow-900/20',
    badge: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100',
  },
  unchanged: {
    border: 'border-gray-300 dark:border-gray-600 border-solid', // Solid border for unchanged
    bg: 'bg-white dark:bg-gray-800',
    badge: '',
  },
}

const columnStatusIndicators = {
  added: { symbol: '+', color: 'text-green-600 dark:text-green-400' },
  removed: { symbol: '-', color: 'text-red-600 dark:text-red-400' },
  modified: { symbol: '~', color: 'text-yellow-600 dark:text-yellow-400' },
}

function TableNode({ data }: NodeProps<TableNodeData>) {
  const { table, status, columnDiffs } = data
  const style = statusStyles[status]
  const [isExpanded, setIsExpanded] = useState(false)

  const primaryKeyColumns = table.indexes
    ?.find(idx => idx.type === 'primary')
    ?.columns || []

  const columnCount = table.columns?.length || 0
  const showExpandButton = columnCount > COLUMN_THRESHOLD

  const displayedColumns = useMemo(() => {
    if (!table.columns) return []
    if (isExpanded || !showExpandButton) return table.columns
    return table.columns.slice(0, COLUMN_THRESHOLD)
  }, [table.columns, isExpanded, showExpandButton])

  const hiddenCount = columnCount - COLUMN_THRESHOLD

  return (
    <div className={`rounded-lg border-2 shadow-lg min-w-[260px] ${style.border} ${style.bg}`}>
      <Handle
        type="target"
        position={Position.Left}
        className="!w-3 !h-3 !bg-gray-400 dark:!bg-gray-500"
      />

      <div className="px-3 py-2 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between gap-2">
        <div className="flex items-center gap-2">
          <svg className="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7c-2 0-3 1-3 3z" />
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 12h16" />
          </svg>
          <span className="font-semibold text-gray-900 dark:text-gray-100 text-sm">
            {table.name}
          </span>
        </div>
        {status !== 'unchanged' && (
          <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${style.badge}`}>
            {status}
          </span>
        )}
      </div>

      <div className={isExpanded ? 'max-h-96 overflow-y-auto' : ''}>
        {displayedColumns.map((column) => {
          const columnStatus = columnDiffs.get(column.name)
          const indicator = columnStatus ? columnStatusIndicators[columnStatus] : null
          const isPrimaryKey = primaryKeyColumns.includes(column.name)

          return (
            <div
              key={column.name}
              className="px-3 py-1.5 flex items-center gap-2 text-xs border-b border-gray-100 dark:border-gray-700 last:border-b-0 hover:bg-gray-50 dark:hover:bg-gray-700/50"
            >
              <span className="flex-1 font-mono text-gray-800 dark:text-gray-200 truncate" title={column.name}>
                {column.name}
              </span>
              <span className="text-gray-500 dark:text-gray-400 font-mono" title={`${column.type}${column.length ? `(${column.length})` : ''}`}>
                {column.type}
                {column.length ? `(${column.length})` : ''}
              </span>
              {isPrimaryKey && (
                <span className="text-yellow-600 dark:text-yellow-400 font-bold" title="Primary Key">
                  PK
                </span>
              )}
              {column.nullable && (
                <span className="text-gray-400 dark:text-gray-500" title="Nullable">
                  ?
                </span>
              )}
              {indicator && (
                <span className={`font-bold ${indicator.color}`} title={columnStatus}>
                  {indicator.symbol}
                </span>
              )}
            </div>
          )
        })}

        {showExpandButton && (
          <button
            onClick={() => setIsExpanded(!isExpanded)}
            className="w-full px-3 py-1.5 text-xs text-center text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 border-t border-gray-200 dark:border-gray-700 font-medium"
            aria-expanded={isExpanded}
            aria-label={isExpanded ? 'Show fewer columns' : `Show ${hiddenCount} more columns`}
          >
            {isExpanded ? (
              <span className="flex items-center justify-center gap-1">
                <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 15l7-7 7 7" />
                </svg>
                Show less
              </span>
            ) : (
              <span className="flex items-center justify-center gap-1">
                <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                </svg>
                +{hiddenCount} more columns
              </span>
            )}
          </button>
        )}
      </div>

      {table.foreignKeys && table.foreignKeys.length > 0 && (
        <div className="px-3 py-1.5 border-t border-gray-200 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
          {table.foreignKeys.length} foreign key{table.foreignKeys.length > 1 ? 's' : ''}
        </div>
      )}

      <Handle
        type="source"
        position={Position.Right}
        className="!w-3 !h-3 !bg-gray-400 dark:!bg-gray-500"
      />
    </div>
  )
}

export default memo(TableNode)
