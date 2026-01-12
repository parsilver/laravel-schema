<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Parsers\Visitors;

use PhpParser\Node;

/**
 * Parser for index and foreign key definitions in Laravel migrations.
 *
 * Handles primary, unique, index, fulltext, spatial indexes and foreign keys.
 */
final class IndexAndConstraintParser
{
    /**
     * Index method names.
     *
     * @var array<int, string>
     */
    private const INDEX_METHODS = [
        'primary',
        'unique',
        'index',
        'fulltext',
        'spatialIndex',
    ];

    public function __construct(
        private AstValueExtractor $extractor,
    ) {}

    /**
     * Check if method is an index definition.
     */
    public function isIndexMethod(string $method): bool
    {
        return in_array($method, self::INDEX_METHODS, true);
    }

    /**
     * Check if method is a foreign key definition.
     */
    public function isForeignKeyMethod(string $method): bool
    {
        return $method === 'foreign';
    }

    /**
     * Process index calls.
     *
     * @param  array<int, Node\Arg>  $args
     * @param  array<string, mixed>  $currentOperation  Reference to operation
     */
    public function processIndexCall(string $method, array $args, string $currentTable, array &$currentOperation): void
    {
        $columns = $this->extractor->extractValue($args[0] ?? null);
        $columns = is_array($columns) ? $columns : [$columns];
        $name = $this->extractor->extractString($args[1] ?? null);

        if (empty($columns)) {
            return;
        }

        $name = $name ?? $currentTable.'_'.implode('_', $columns).'_'.$method;

        $currentOperation['indexes'][$name] = [
            'name' => $name,
            'type' => $method,
            'columns' => $columns,
        ];
    }

    /**
     * Process foreign key calls.
     *
     * @param  array<int, Node\Arg>  $args
     * @param  array<int, array{method: string, args: array<int, Node\Arg>}>  $modifiers
     * @param  array<string, mixed>  $currentOperation  Reference to operation
     */
    public function processForeignKeyCall(array $args, array $modifiers, string $currentTable, array &$currentOperation): void
    {
        $columns = $this->extractor->extractValue($args[0] ?? null);
        $columns = is_array($columns) ? $columns : [$columns];

        $fkInfo = [
            'name' => $currentTable.'_'.implode('_', $columns).'_foreign',
            'columns' => $columns,
            'referencedTable' => null,
            'referencedColumns' => [],
            'onUpdate' => 'NO ACTION',
            'onDelete' => 'NO ACTION',
        ];

        foreach ($modifiers as $modifier) {
            switch ($modifier['method']) {
                case 'references':
                    $refs = $this->extractor->extractValue($modifier['args'][0] ?? null);
                    $fkInfo['referencedColumns'] = is_array($refs) ? $refs : [$refs];
                    break;
                case 'on':
                    $fkInfo['referencedTable'] = $this->extractor->extractString($modifier['args'][0] ?? null);
                    break;
                case 'onUpdate':
                    $fkInfo['onUpdate'] = $this->extractor->extractString($modifier['args'][0] ?? null) ?? 'NO ACTION';
                    break;
                case 'onDelete':
                    $fkInfo['onDelete'] = $this->extractor->extractString($modifier['args'][0] ?? null) ?? 'NO ACTION';
                    break;
            }
        }

        if ($fkInfo['referencedTable'] !== null) {
            $currentOperation['foreignKeys'][$fkInfo['name']] = $fkInfo;
        }
    }

    /**
     * Check if modifiers contain constrained().
     *
     * @param  array<int, array{method: string, args: array<int, Node\Arg>}>  $modifiers
     */
    public function hasConstrainedModifier(array $modifiers): bool
    {
        foreach ($modifiers as $modifier) {
            if ($modifier['method'] === 'constrained') {
                return true;
            }
        }

        return false;
    }

    /**
     * Add foreign key from constrained() modifier.
     *
     * @param  array<string, mixed>  $columnInfo
     * @param  array<int, array{method: string, args: array<int, Node\Arg>}>  $modifiers
     * @param  array<string, mixed>  $currentOperation  Reference to operation
     */
    public function addForeignKeyFromConstraint(array $columnInfo, array $modifiers, string $currentTable, array &$currentOperation): void
    {
        $fkInfo = [
            'name' => $currentTable.'_'.$columnInfo['name'].'_foreign',
            'columns' => [$columnInfo['name']],
            'referencedTable' => null,
            'referencedColumns' => ['id'],
            'onUpdate' => 'NO ACTION',
            'onDelete' => 'NO ACTION',
        ];

        foreach ($modifiers as $modifier) {
            switch ($modifier['method']) {
                case 'constrained':
                    $table = $this->extractor->extractString($modifier['args'][0] ?? null);
                    if ($table === null) {
                        // Derive table name from column name (e.g., user_id -> users)
                        $table = str_replace('_id', '', $columnInfo['name']).'s';
                    }
                    $fkInfo['referencedTable'] = $table;
                    break;
                case 'onUpdate':
                    $fkInfo['onUpdate'] = $this->extractor->extractString($modifier['args'][0] ?? null) ?? 'NO ACTION';
                    break;
                case 'onDelete':
                    $fkInfo['onDelete'] = $this->extractor->extractString($modifier['args'][0] ?? null) ?? 'NO ACTION';
                    break;
                case 'cascadeOnUpdate':
                    $fkInfo['onUpdate'] = 'CASCADE';
                    break;
                case 'cascadeOnDelete':
                    $fkInfo['onDelete'] = 'CASCADE';
                    break;
                case 'nullOnDelete':
                    $fkInfo['onDelete'] = 'SET NULL';
                    break;
            }
        }

        if ($fkInfo['referencedTable'] !== null) {
            $currentOperation['foreignKeys'][$fkInfo['name']] = $fkInfo;
        }
    }
}
