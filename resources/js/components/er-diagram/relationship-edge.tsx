import { memo } from 'react'
import { BaseEdge, EdgeLabelRenderer, getBezierPath } from '@xyflow/react'
import type { EdgeProps } from '@xyflow/react'
import type { RelationshipEdgeData } from '../../utils/transform'

const statusStyles = {
  added: {
    stroke: '#22c55e',
    label: 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
    dasharray: '8,4', // Dashed pattern for added
  },
  removed: {
    stroke: '#ef4444',
    label: 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
    dasharray: '2,2', // Dotted pattern for removed
  },
  modified: {
    stroke: '#eab308',
    label: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100',
    dasharray: '12,4,4,4', // Dash-dot pattern for modified
  },
  unchanged: {
    stroke: '#9ca3af',
    label: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
    dasharray: undefined, // Solid line for unchanged
  },
}

function RelationshipEdge({
  id,
  sourceX,
  sourceY,
  targetX,
  targetY,
  sourcePosition,
  targetPosition,
  data,
  selected,
}: EdgeProps<RelationshipEdgeData>) {
  const status = data?.status || 'unchanged'
  const style = statusStyles[status]

  const [edgePath, labelX, labelY] = getBezierPath({
    sourceX,
    sourceY,
    sourcePosition,
    targetX,
    targetY,
    targetPosition,
  })

  const strokeWidth = selected ? 3 : 2

  return (
    <>
      <BaseEdge
        id={id}
        path={edgePath}
        style={{
          stroke: style.stroke,
          strokeWidth,
          strokeDasharray: style.dasharray,
        }}
        markerEnd={`url(#arrow-${status})`}
      />
      {(selected || status !== 'unchanged') && data?.foreignKey && (
        <EdgeLabelRenderer>
          <div
            className={`absolute px-2 py-1 rounded text-xs font-medium pointer-events-none ${style.label}`}
            style={{
              transform: `translate(-50%, -50%) translate(${labelX}px, ${labelY}px)`,
            }}
          >
            {data.sourceColumn} → {data.targetColumn}
          </div>
        </EdgeLabelRenderer>
      )}
    </>
  )
}

export function EdgeMarkers() {
  return (
    <svg style={{ position: 'absolute', width: 0, height: 0 }}>
      <defs>
        <marker
          id="arrow-unchanged"
          viewBox="0 0 10 10"
          refX="8"
          refY="5"
          markerWidth="6"
          markerHeight="6"
          orient="auto-start-reverse"
        >
          <path d="M 0 0 L 10 5 L 0 10 z" fill="#9ca3af" />
        </marker>
        <marker
          id="arrow-added"
          viewBox="0 0 10 10"
          refX="8"
          refY="5"
          markerWidth="6"
          markerHeight="6"
          orient="auto-start-reverse"
        >
          <path d="M 0 0 L 10 5 L 0 10 z" fill="#22c55e" />
        </marker>
        <marker
          id="arrow-removed"
          viewBox="0 0 10 10"
          refX="8"
          refY="5"
          markerWidth="6"
          markerHeight="6"
          orient="auto-start-reverse"
        >
          <path d="M 0 0 L 10 5 L 0 10 z" fill="#ef4444" />
        </marker>
        <marker
          id="arrow-modified"
          viewBox="0 0 10 10"
          refX="8"
          refY="5"
          markerWidth="6"
          markerHeight="6"
          orient="auto-start-reverse"
        >
          <path d="M 0 0 L 10 5 L 0 10 z" fill="#eab308" />
        </marker>
      </defs>
    </svg>
  )
}

export default memo(RelationshipEdge)
