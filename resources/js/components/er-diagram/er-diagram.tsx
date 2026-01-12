import { useEffect, useMemo, useCallback, useRef } from 'react'
import {
  ReactFlow,
  Background,
  Controls,
  useNodesState,
  useEdgesState,
  useReactFlow,
} from '@xyflow/react'
import '@xyflow/react/dist/style.css'

import { useSchema } from '../../context/schema-context'
import { calculateLayout } from '../../utils/layout'
import { tablesToNodes, foreignKeysToEdges } from '../../utils/transform'
import type { TableNode, RelationshipEdge } from '../../utils/transform'
import TableNodeComponent from './table-node'
import RelationshipEdgeComponent, { EdgeMarkers } from './relationship-edge'
import Legend from './legend'

const nodeTypes = {
  tableNode: TableNodeComponent,
}

const edgeTypes = {
  relationship: RelationshipEdgeComponent,
}

const PAN_STEP = 50
const ZOOM_STEP = 0.2

export default function ERDiagram() {
  const { tables, diff, loading, error, refresh } = useSchema()
  const [nodes, setNodes, onNodesChange] = useNodesState<TableNode>([])
  const [edges, setEdges, onEdgesChange] = useEdgesState<RelationshipEdge>([])
  const { fitView, zoomIn, zoomOut, getViewport, setViewport } = useReactFlow()
  const containerRef = useRef<HTMLDivElement>(null)

  const hasDifferences = useMemo(() => {
    return diff?.hasDifferences || false
  }, [diff])

  const summary = useMemo(() => {
    return diff?.summary || { added_tables: 0, removed_tables: 0, modified_tables: 0 }
  }, [diff])

  useEffect(() => {
    if (tables.length === 0) return

    const rawNodes = tablesToNodes(tables, diff)
    const rawEdges = foreignKeysToEdges(tables, diff)
    const { nodes: layoutedNodes, edges: layoutedEdges } = calculateLayout(rawNodes, rawEdges)

    setNodes(layoutedNodes)
    setEdges(layoutedEdges)

    setTimeout(() => {
      fitView({ padding: 0.2 })
    }, 50)
  }, [tables, diff, setNodes, setEdges, fitView])

  const handleFitView = useCallback(() => {
    fitView({ padding: 0.2, duration: 300 })
  }, [fitView])

  const handleKeyDown = useCallback((event: React.KeyboardEvent) => {
    // Don't handle if user is typing in an input
    if (event.target instanceof HTMLInputElement || event.target instanceof HTMLTextAreaElement) {
      return
    }

    const viewport = getViewport()

    switch (event.key) {
      case 'ArrowUp':
        event.preventDefault()
        setViewport({ ...viewport, y: viewport.y + PAN_STEP }, { duration: 100 })
        break
      case 'ArrowDown':
        event.preventDefault()
        setViewport({ ...viewport, y: viewport.y - PAN_STEP }, { duration: 100 })
        break
      case 'ArrowLeft':
        event.preventDefault()
        setViewport({ ...viewport, x: viewport.x + PAN_STEP }, { duration: 100 })
        break
      case 'ArrowRight':
        event.preventDefault()
        setViewport({ ...viewport, x: viewport.x - PAN_STEP }, { duration: 100 })
        break
      case '+':
      case '=':
        event.preventDefault()
        zoomIn({ duration: 100 })
        break
      case '-':
      case '_':
        event.preventDefault()
        zoomOut({ duration: 100 })
        break
      case '0':
      case 'Home':
        event.preventDefault()
        handleFitView()
        break
      case 'Escape':
        event.preventDefault()
        // Deselect all nodes
        setNodes((nds) => nds.map((n) => ({ ...n, selected: false })))
        setEdges((eds) => eds.map((e) => ({ ...e, selected: false })))
        break
    }
  }, [getViewport, setViewport, zoomIn, zoomOut, handleFitView, setNodes, setEdges])

  if (loading) {
    return (
      <div className="h-full w-full flex items-center justify-center bg-gray-50 dark:bg-gray-900">
        <div className="flex flex-col items-center gap-4">
          <div className="w-12 h-12 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin" />
          <p className="text-gray-600 dark:text-gray-400">Loading schema...</p>
        </div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="h-full w-full flex items-center justify-center bg-gray-50 dark:bg-gray-900" role="alert">
        <div className="text-center">
          <div className="text-red-500 mb-2">
            <svg className="w-12 h-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <p className="text-gray-600 dark:text-gray-400 mb-4">{error}</p>
          <button
            onClick={refresh}
            className="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 transition-colors"
            aria-label="Retry loading schema"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Retry
          </button>
        </div>
      </div>
    )
  }

  if (tables.length === 0) {
    return (
      <div className="h-full w-full flex items-center justify-center bg-gray-50 dark:bg-gray-900">
        <div className="text-center">
          <div className="text-gray-400 mb-2">
            <svg className="w-12 h-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7c-2 0-3 1-3 3z" />
            </svg>
          </div>
          <p className="text-gray-600 dark:text-gray-400">No tables found</p>
        </div>
      </div>
    )
  }

  return (
    <div
      ref={containerRef}
      className="h-full w-full relative outline-none"
      tabIndex={0}
      onKeyDown={handleKeyDown}
      role="application"
      aria-label="Database schema diagram. Use arrow keys to pan, plus/minus to zoom, Escape to deselect, 0 to fit view."
    >
      <EdgeMarkers />

      {hasDifferences && (
        <div className="absolute top-4 left-4 z-10 flex items-center gap-4 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 px-4 py-2">
          <div className="flex items-center gap-2 text-yellow-600 dark:text-yellow-400">
            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span className="font-medium text-sm">Schema has differences</span>
          </div>
          <div className="flex items-center gap-3 text-xs">
            {summary.added_tables > 0 && (
              <span className="text-green-600 dark:text-green-400">
                +{summary.added_tables} added
              </span>
            )}
            {summary.removed_tables > 0 && (
              <span className="text-red-600 dark:text-red-400">
                -{summary.removed_tables} removed
              </span>
            )}
            {summary.modified_tables > 0 && (
              <span className="text-yellow-600 dark:text-yellow-400">
                ~{summary.modified_tables} modified
              </span>
            )}
          </div>
        </div>
      )}

      {!hasDifferences && (
        <div className="absolute top-4 left-4 z-10 flex items-center gap-2 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 px-4 py-2 text-green-600 dark:text-green-400">
          <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
          </svg>
          <span className="font-medium text-sm">Schema is in sync</span>
        </div>
      )}

      <ReactFlow
        nodes={nodes}
        edges={edges}
        onNodesChange={onNodesChange}
        onEdgesChange={onEdgesChange}
        nodeTypes={nodeTypes}
        edgeTypes={edgeTypes}
        nodesDraggable={true}
        nodesConnectable={false}
        elementsSelectable={true}
        fitView
        minZoom={0.1}
        maxZoom={2}
        defaultEdgeOptions={{
          type: 'relationship',
        }}
        proOptions={{ hideAttribution: true }}
      >
        <Background color="#e5e7eb" gap={16} />
        <Controls
          showInteractive={false}
          className="!bg-white dark:!bg-gray-800 !border-gray-200 dark:!border-gray-700 !shadow-lg"
        />
      </ReactFlow>

      <Legend />

      <button
        onClick={handleFitView}
        className="absolute bottom-4 left-4 z-10 p-2 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400"
        title="Fit to view"
        aria-label="Fit diagram to view"
      >
        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
        </svg>
      </button>
    </div>
  )
}
