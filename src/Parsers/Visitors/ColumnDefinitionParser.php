<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Parsers\Visitors;

use PhpParser\Node;

/**
 * Parser for column definition method calls in Laravel migrations.
 *
 * Handles column type detection, property extraction, and modifier application.
 */
final class ColumnDefinitionParser
{
    /**
     * Methods that create multiple columns at once.
     *
     * @var array<int, string>
     */
    private const MULTI_COLUMN_METHODS = [
        'timestamps',
        'timestampsTz',
        'softDeletes',
        'softDeletesTz',
        'morphs',
        'nullableMorphs',
        'uuidMorphs',
        'nullableUuidMorphs',
        'ulidMorphs',
        'nullableUlidMorphs',
        'rememberToken',
        'nullableTimestamps',
    ];

    public function __construct(
        private AstValueExtractor $extractor,
    ) {}

    /**
     * Check if method creates multiple columns.
     */
    public function isMultiColumnMethod(string $method): bool
    {
        return in_array($method, self::MULTI_COLUMN_METHODS, true);
    }

    /**
     * Parse a column definition call.
     *
     * @param  array<int, Node\Arg>  $args
     * @return array<string, mixed>|null
     */
    public function parseColumnCall(string $method, array $args): ?array
    {
        $columnName = $this->extractor->extractString($args[0] ?? null);

        // Handle id() which is special
        if ($method === 'id') {
            return [
                'name' => $columnName ?? 'id',
                'type' => 'id',
                'nullable' => false,
                'autoIncrement' => true,
                'unsigned' => true,
            ];
        }

        if ($columnName === null) {
            return null;
        }

        return $this->buildColumnInfo($method, $columnName, $args);
    }

    /**
     * Build column info from method and arguments.
     *
     * @param  array<int, Node\Arg>  $args
     * @return array<string, mixed>
     */
    public function buildColumnInfo(string $method, string $name, array $args): array
    {
        $info = [
            'name' => $name,
            'type' => $method,
            'nullable' => false,
            'default' => null,
            'autoIncrement' => false,
            'unsigned' => false,
            'length' => null,
            'precision' => null,
            'scale' => null,
        ];

        // Extract length/precision from arguments
        switch ($method) {
            case 'string':
            case 'char':
                $info['length'] = $this->extractor->extractInt($args[1] ?? null) ?? 255;
                break;
            case 'decimal':
            case 'float':
            case 'double':
                $info['precision'] = $this->extractor->extractInt($args[1] ?? null) ?? 8;
                $info['scale'] = $this->extractor->extractInt($args[2] ?? null) ?? 2;
                break;
            case 'enum':
            case 'set':
                $info['allowedValues'] = $this->extractor->extractArray($args[1] ?? null);
                break;
            case 'foreignId':
            case 'foreignIdFor':
                $info['unsigned'] = true;
                $info['type'] = 'unsignedBigInteger';
                break;
            case 'bigIncrements':
            case 'increments':
            case 'mediumIncrements':
            case 'smallIncrements':
            case 'tinyIncrements':
                $info['autoIncrement'] = true;
                $info['unsigned'] = true;
                break;
            case 'unsignedBigInteger':
            case 'unsignedInteger':
            case 'unsignedMediumInteger':
            case 'unsignedSmallInteger':
            case 'unsignedTinyInteger':
                $info['unsigned'] = true;
                break;
        }

        return $info;
    }

    /**
     * Apply a modifier to column info.
     *
     * @param  array<string, mixed>  $info
     * @param  array<int, Node\Arg>  $args
     * @param  array<string, mixed>  $currentOperation  Reference to operation for index creation
     * @return array<string, mixed>
     */
    public function applyModifier(array $info, string $method, array $args, array &$currentOperation): array
    {
        switch ($method) {
            case 'nullable':
                $info['nullable'] = $this->extractor->extractBool($args[0] ?? null) ?? true;
                break;
            case 'default':
                $info['default'] = $this->extractor->extractValue($args[0] ?? null);
                break;
            case 'unsigned':
                $info['unsigned'] = true;
                break;
            case 'autoIncrement':
                $info['autoIncrement'] = true;
                break;
            case 'comment':
                $info['comment'] = $this->extractor->extractString($args[0] ?? null);
                break;
            case 'charset':
                $info['charset'] = $this->extractor->extractString($args[0] ?? null);
                break;
            case 'collation':
                $info['collation'] = $this->extractor->extractString($args[0] ?? null);
                break;
            case 'after':
                $info['after'] = $this->extractor->extractString($args[0] ?? null);
                break;
            case 'first':
                $info['first'] = true;
                break;
            case 'unique':
                $currentOperation['indexes'][$info['name'].'_unique'] = [
                    'name' => $info['name'].'_unique',
                    'type' => 'unique',
                    'columns' => [$info['name']],
                ];
                break;
            case 'primary':
                $currentOperation['indexes']['primary'] = [
                    'name' => 'primary',
                    'type' => 'primary',
                    'columns' => [$info['name']],
                ];
                break;
            case 'index':
                $indexName = $this->extractor->extractString($args[0] ?? null) ?? $info['name'].'_index';
                $currentOperation['indexes'][$indexName] = [
                    'name' => $indexName,
                    'type' => 'index',
                    'columns' => [$info['name']],
                ];
                break;
        }

        return $info;
    }

    /**
     * Process multi-column methods like timestamps().
     *
     * @param  array<int, Node\Arg>  $args
     * @param  array<string, mixed>  $currentOperation  Reference to operation
     */
    public function processMultiColumnMethod(string $method, array $args, array &$currentOperation): void
    {
        switch ($method) {
            case 'timestamps':
            case 'nullableTimestamps':
                $nullable = $method === 'nullableTimestamps';
                $currentOperation['columns']['created_at'] = [
                    'name' => 'created_at',
                    'type' => 'timestamp',
                    'nullable' => $nullable,
                ];
                $currentOperation['columns']['updated_at'] = [
                    'name' => 'updated_at',
                    'type' => 'timestamp',
                    'nullable' => $nullable,
                ];
                break;
            case 'timestampsTz':
                $currentOperation['columns']['created_at'] = [
                    'name' => 'created_at',
                    'type' => 'timestampTz',
                    'nullable' => true,
                ];
                $currentOperation['columns']['updated_at'] = [
                    'name' => 'updated_at',
                    'type' => 'timestampTz',
                    'nullable' => true,
                ];
                break;
            case 'softDeletes':
                $column = $this->extractor->extractString($args[0] ?? null) ?? 'deleted_at';
                $currentOperation['columns'][$column] = [
                    'name' => $column,
                    'type' => 'timestamp',
                    'nullable' => true,
                ];
                break;
            case 'softDeletesTz':
                $column = $this->extractor->extractString($args[0] ?? null) ?? 'deleted_at';
                $currentOperation['columns'][$column] = [
                    'name' => $column,
                    'type' => 'timestampTz',
                    'nullable' => true,
                ];
                break;
            case 'rememberToken':
                $currentOperation['columns']['remember_token'] = [
                    'name' => 'remember_token',
                    'type' => 'string',
                    'length' => 100,
                    'nullable' => true,
                ];
                break;
            case 'morphs':
            case 'nullableMorphs':
                $name = $this->extractor->extractString($args[0] ?? null) ?? 'taggable';
                $nullable = $method === 'nullableMorphs';
                $currentOperation['columns'][$name.'_type'] = [
                    'name' => $name.'_type',
                    'type' => 'string',
                    'nullable' => $nullable,
                ];
                $currentOperation['columns'][$name.'_id'] = [
                    'name' => $name.'_id',
                    'type' => 'unsignedBigInteger',
                    'nullable' => $nullable,
                    'unsigned' => true,
                ];
                break;
            case 'uuidMorphs':
            case 'nullableUuidMorphs':
                $name = $this->extractor->extractString($args[0] ?? null) ?? 'taggable';
                $nullable = $method === 'nullableUuidMorphs';
                $currentOperation['columns'][$name.'_type'] = [
                    'name' => $name.'_type',
                    'type' => 'string',
                    'nullable' => $nullable,
                ];
                $currentOperation['columns'][$name.'_id'] = [
                    'name' => $name.'_id',
                    'type' => 'uuid',
                    'nullable' => $nullable,
                ];
                break;
            case 'ulidMorphs':
            case 'nullableUlidMorphs':
                $name = $this->extractor->extractString($args[0] ?? null) ?? 'taggable';
                $nullable = $method === 'nullableUlidMorphs';
                $currentOperation['columns'][$name.'_type'] = [
                    'name' => $name.'_type',
                    'type' => 'string',
                    'nullable' => $nullable,
                ];
                $currentOperation['columns'][$name.'_id'] = [
                    'name' => $name.'_id',
                    'type' => 'ulid',
                    'nullable' => $nullable,
                ];
                break;
        }
    }
}
