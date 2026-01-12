<?php

declare(strict_types=1);

use Farzai\LaravelSchema\Diff\SchemaDiffer;
use Farzai\LaravelSchema\Schema\Column;
use Farzai\LaravelSchema\Schema\DatabaseSchema;
use Farzai\LaravelSchema\Schema\Enums\ColumnType;
use Farzai\LaravelSchema\Schema\Enums\DiffStatus;
use Farzai\LaravelSchema\Schema\Table;

describe('SchemaDiffer', function () {
    it('returns empty diff for identical schemas', function () {
        $schema = new DatabaseSchema(tables: [
            'users' => new Table(
                name: 'users',
                columns: [
                    'id' => new Column(name: 'id', type: ColumnType::BigIncrements),
                    'name' => new Column(name: 'name', type: ColumnType::String),
                ],
            ),
        ]);

        $differ = new SchemaDiffer;
        $diff = $differ->diff($schema, $schema);

        expect($diff->hasDifferences)->toBeFalse();
        expect($diff->tables)->toHaveCount(1);
        expect($diff->getTable('users')->status)->toBe(DiffStatus::Unchanged);
    });

    it('detects added tables', function () {
        $expected = new DatabaseSchema(tables: []);
        $actual = new DatabaseSchema(tables: [
            'users' => new Table(name: 'users'),
        ]);

        $differ = new SchemaDiffer;
        $diff = $differ->diff($expected, $actual);

        expect($diff->hasDifferences)->toBeTrue();
        expect($diff->getAddedTables())->toHaveCount(1);
        expect($diff->getTable('users')->status)->toBe(DiffStatus::Added);
    });

    it('detects removed tables', function () {
        $expected = new DatabaseSchema(tables: [
            'users' => new Table(name: 'users'),
        ]);
        $actual = new DatabaseSchema(tables: []);

        $differ = new SchemaDiffer;
        $diff = $differ->diff($expected, $actual);

        expect($diff->hasDifferences)->toBeTrue();
        expect($diff->getRemovedTables())->toHaveCount(1);
        expect($diff->getTable('users')->status)->toBe(DiffStatus::Removed);
    });

    it('detects added columns', function () {
        $expected = new DatabaseSchema(tables: [
            'users' => new Table(
                name: 'users',
                columns: [
                    'id' => new Column(name: 'id', type: ColumnType::BigIncrements),
                ],
            ),
        ]);

        $actual = new DatabaseSchema(tables: [
            'users' => new Table(
                name: 'users',
                columns: [
                    'id' => new Column(name: 'id', type: ColumnType::BigIncrements),
                    'email' => new Column(name: 'email', type: ColumnType::String),
                ],
            ),
        ]);

        $differ = new SchemaDiffer;
        $diff = $differ->diff($expected, $actual);

        expect($diff->hasDifferences)->toBeTrue();
        expect($diff->getTable('users')->status)->toBe(DiffStatus::Modified);
        expect($diff->getTable('users')->getAddedColumns())->toHaveCount(1);
        expect($diff->getTable('users')->columns['email']->status)->toBe(DiffStatus::Added);
    });

    it('detects removed columns', function () {
        $expected = new DatabaseSchema(tables: [
            'users' => new Table(
                name: 'users',
                columns: [
                    'id' => new Column(name: 'id', type: ColumnType::BigIncrements),
                    'email' => new Column(name: 'email', type: ColumnType::String),
                ],
            ),
        ]);

        $actual = new DatabaseSchema(tables: [
            'users' => new Table(
                name: 'users',
                columns: [
                    'id' => new Column(name: 'id', type: ColumnType::BigIncrements),
                ],
            ),
        ]);

        $differ = new SchemaDiffer;
        $diff = $differ->diff($expected, $actual);

        expect($diff->hasDifferences)->toBeTrue();
        expect($diff->getTable('users')->getRemovedColumns())->toHaveCount(1);
        expect($diff->getTable('users')->columns['email']->status)->toBe(DiffStatus::Removed);
    });

    it('detects modified columns', function () {
        $expected = new DatabaseSchema(tables: [
            'users' => new Table(
                name: 'users',
                columns: [
                    'status' => new Column(name: 'status', type: ColumnType::String, nullable: false),
                ],
            ),
        ]);

        $actual = new DatabaseSchema(tables: [
            'users' => new Table(
                name: 'users',
                columns: [
                    'status' => new Column(name: 'status', type: ColumnType::String, nullable: true),
                ],
            ),
        ]);

        $differ = new SchemaDiffer;
        $diff = $differ->diff($expected, $actual);

        expect($diff->hasDifferences)->toBeTrue();
        expect($diff->getTable('users')->getModifiedColumns())->toHaveCount(1);
        expect($diff->getTable('users')->columns['status']->status)->toBe(DiffStatus::Modified);
        expect($diff->getTable('users')->columns['status']->changes)->toHaveKey('nullable');
    });

    it('generates correct summary', function () {
        $expected = new DatabaseSchema(tables: [
            'users' => new Table(
                name: 'users',
                columns: [
                    'id' => new Column(name: 'id', type: ColumnType::BigIncrements),
                    'old_column' => new Column(name: 'old_column', type: ColumnType::String),
                ],
            ),
            'removed_table' => new Table(name: 'removed_table'),
        ]);

        $actual = new DatabaseSchema(tables: [
            'users' => new Table(
                name: 'users',
                columns: [
                    'id' => new Column(name: 'id', type: ColumnType::BigIncrements),
                    'new_column' => new Column(name: 'new_column', type: ColumnType::String),
                ],
            ),
            'added_table' => new Table(name: 'added_table'),
        ]);

        $differ = new SchemaDiffer;
        $diff = $differ->diff($expected, $actual);

        $summary = $diff->getSummary();

        expect($summary['total_tables'])->toBe(3);
        expect($summary['added_tables'])->toBe(1);
        expect($summary['removed_tables'])->toBe(1);
        expect($summary['modified_tables'])->toBe(1);
    });
});
