import type { Node, Edge } from '@xyflow/react'
import type { Table, TableDiff, SchemaDiff, ForeignKey } from '../types'

export type DiffStatus = 'added' | 'removed' | 'modified' | 'unchanged'

export interface TableNodeData {
  table: Table
  status: DiffStatus
  columnDiffs: Map<string, DiffStatus>
}

export interface RelationshipEdgeData {
  foreignKey: ForeignKey
  sourceColumn: string
  targetColumn: string
  status: DiffStatus
}

export type TableNode = Node<TableNodeData, 'tableNode'>
export type RelationshipEdge = Edge<RelationshipEdgeData>

function getTableStatus(tableName: string, diff: SchemaDiff | null): DiffStatus {
  if (!diff?.diff?.tables) return 'unchanged'

  const tableDiff = diff.diff.tables.find(t => t.name === tableName)
  if (!tableDiff) return 'unchanged'

  return tableDiff.status as DiffStatus
}

function getColumnDiffs(tableName: string, diff: SchemaDiff | null): Map<string, DiffStatus> {
  const columnDiffs = new Map<string, DiffStatus>()

  if (!diff?.diff?.tables) return columnDiffs

  const tableDiff = diff.diff.tables.find(t => t.name === tableName)
  if (!tableDiff) return columnDiffs

  const columns = tableDiff.columns || tableDiff.diff?.columns || []
  columns.forEach(col => {
    if (col.status !== 'unchanged') {
      columnDiffs.set(col.name, col.status as DiffStatus)
    }
  })

  return columnDiffs
}

function getForeignKeyStatus(
  tableName: string,
  fkName: string,
  diff: SchemaDiff | null
): DiffStatus {
  if (!diff?.diff?.tables) return 'unchanged'

  const tableDiff = diff.diff.tables.find(t => t.name === tableName)
  if (!tableDiff) return 'unchanged'

  const foreignKeys = tableDiff.foreignKeys || tableDiff.diff?.foreignKeys || []
  const fkDiff = foreignKeys.find(fk => fk.name === fkName)

  return fkDiff?.status as DiffStatus || 'unchanged'
}

export function tablesToNodes(tables: Table[], diff: SchemaDiff | null): TableNode[] {
  return tables.map((table) => ({
    id: table.name,
    type: 'tableNode',
    position: { x: 0, y: 0 },
    data: {
      table,
      status: getTableStatus(table.name, diff),
      columnDiffs: getColumnDiffs(table.name, diff),
    },
  }))
}

export function foreignKeysToEdges(tables: Table[], diff: SchemaDiff | null): RelationshipEdge[] {
  const edges: RelationshipEdge[] = []
  const tableNames = new Set(tables.map(t => t.name))

  tables.forEach((table) => {
    if (!table.foreignKeys) return

    table.foreignKeys.forEach((fk) => {
      if (!tableNames.has(fk.referencedTable)) return

      edges.push({
        id: `${table.name}-${fk.name}`,
        source: table.name,
        target: fk.referencedTable,
        type: 'relationship',
        data: {
          foreignKey: fk,
          sourceColumn: fk.columns[0] || '',
          targetColumn: fk.referencedColumns[0] || '',
          status: getForeignKeyStatus(table.name, fk.name, diff),
        },
      })
    })
  })

  return edges
}
