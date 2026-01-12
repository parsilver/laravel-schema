import dagre from '@dagrejs/dagre'
import type { Node, Edge } from '@xyflow/react'

const NODE_WIDTH = 280
const NODE_HEADER_HEIGHT = 44
const NODE_COLUMN_HEIGHT = 28
const NODE_PADDING = 16

export function calculateNodeHeight(columnCount: number): number {
  return NODE_HEADER_HEIGHT + (columnCount * NODE_COLUMN_HEIGHT) + NODE_PADDING
}

export function calculateLayout<N extends Node, E extends Edge>(
  nodes: N[],
  edges: E[]
): { nodes: N[]; edges: E[] } {
  const dagreGraph = new dagre.graphlib.Graph()

  dagreGraph.setDefaultEdgeLabel(() => ({}))
  dagreGraph.setGraph({
    rankdir: 'LR',
    nodesep: 80,
    ranksep: 150,
    marginx: 50,
    marginy: 50,
  })

  nodes.forEach((node) => {
    const columnCount = (node.data as { table?: { columns?: unknown[] } })?.table?.columns?.length || 5
    const height = calculateNodeHeight(columnCount)
    dagreGraph.setNode(node.id, { width: NODE_WIDTH, height })
  })

  edges.forEach((edge) => {
    dagreGraph.setEdge(edge.source, edge.target)
  })

  dagre.layout(dagreGraph)

  const layoutedNodes = nodes.map((node) => {
    const nodeWithPosition = dagreGraph.node(node.id)
    const columnCount = (node.data as { table?: { columns?: unknown[] } })?.table?.columns?.length || 5
    const height = calculateNodeHeight(columnCount)

    return {
      ...node,
      position: {
        x: nodeWithPosition.x - NODE_WIDTH / 2,
        y: nodeWithPosition.y - height / 2,
      },
    }
  })

  return { nodes: layoutedNodes, edges }
}
