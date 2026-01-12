import { useState, useEffect, useCallback } from 'react'

const LEGEND_COLLAPSED_KEY = 'laravel-schema:legend-collapsed'

const legendItems = [
  { status: 'added', label: 'Added', color: 'bg-green-500', symbol: '+', pattern: 'border-2 border-dashed border-green-600' },
  { status: 'removed', label: 'Removed', color: 'bg-red-500', symbol: '-', pattern: 'border-2 border-dotted border-red-600' },
  { status: 'modified', label: 'Modified', color: 'bg-yellow-500', symbol: '~', pattern: 'border-2 border-double border-yellow-600' },
  { status: 'unchanged', label: 'Unchanged', color: 'bg-gray-400', symbol: '', pattern: 'border-2 border-solid border-gray-500' },
]

export default function Legend() {
  const [collapsed, setCollapsed] = useState(() => {
    if (typeof window !== 'undefined') {
      const stored = localStorage.getItem(LEGEND_COLLAPSED_KEY)
      return stored === 'true'
    }
    return false
  })

  useEffect(() => {
    localStorage.setItem(LEGEND_COLLAPSED_KEY, String(collapsed))
  }, [collapsed])

  const handleToggle = useCallback(() => {
    setCollapsed(prev => !prev)
  }, [])

  return (
    <div className="absolute bottom-4 right-4 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-10">
      <button
        onClick={handleToggle}
        className="w-full px-3 py-2 flex items-center justify-between text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-t-lg"
        aria-expanded={!collapsed}
        aria-label={collapsed ? 'Expand legend' : 'Collapse legend'}
      >
        <span>Legend</span>
        <svg
          className={`w-4 h-4 transition-transform ${collapsed ? 'rotate-180' : ''}`}
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          aria-hidden="true"
        >
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
        </svg>
      </button>

      {!collapsed && (
        <div className="px-3 pb-3 space-y-2">
          {legendItems.map((item) => (
            <div key={item.status} className="flex items-center gap-2 text-xs">
              <div className="flex items-center gap-1">
                <div className={`w-3 h-3 rounded ${item.color}`} aria-hidden="true" />
                <div className={`w-4 h-3 rounded ${item.pattern} bg-transparent`} aria-hidden="true" />
              </div>
              <span className="text-gray-600 dark:text-gray-400">{item.label}</span>
              {item.symbol && (
                <span className="text-gray-500 dark:text-gray-500 font-mono" aria-label={`Symbol: ${item.symbol}`}>({item.symbol})</span>
              )}
            </div>
          ))}
          <div className="pt-2 border-t border-gray-200 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
            <div className="flex items-center gap-1">
              <span className="font-mono" aria-hidden="true">PK</span>
              <span>= Primary Key</span>
            </div>
            <div className="flex items-center gap-1">
              <span className="font-mono" aria-hidden="true">?</span>
              <span>= Nullable</span>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
