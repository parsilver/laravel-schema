<?php

declare(strict_types=1);

use Farzai\LaravelSchema\Schema\Column;
use Farzai\LaravelSchema\Schema\Enums\ColumnType;
use Farzai\LaravelSchema\Schema\Enums\IndexType;
use Farzai\LaravelSchema\Schema\ForeignKey;
use Farzai\LaravelSchema\Schema\Index;
use Farzai\LaravelSchema\Schema\Table;

describe('Table', function () {
    it('creates a table with name only', function () {
        $table = new Table(name: 'users');

        expect($table->name)->toBe('users');
        expect($table->columns)->toBeEmpty();
        expect($table->indexes)->toBeEmpty();
        expect($table->foreignKeys)->toBeEmpty();
    });

    it('creates a table with columns', function () {
        $columns = [
            'id' => new Column(name: 'id', type: ColumnType::BigIncrements),
            'name' => new Column(name: 'name', type: ColumnType::String),
        ];

        $table = new Table(name: 'users', columns: $columns);

        expect($table->columns)->toHaveCount(2);
        expect($table->hasColumn('id'))->toBeTrue();
        expect($table->hasColumn('name'))->toBeTrue();
        expect($table->hasColumn('email'))->toBeFalse();
    });

    it('gets column by name', function () {
        $column = new Column(name: 'id', type: ColumnType::BigIncrements);
        $table = new Table(name: 'users', columns: ['id' => $column]);

        expect($table->getColumn('id'))->toBe($column);
        expect($table->getColumn('nonexistent'))->toBeNull();
    });

    it('creates a table with indexes', function () {
        $indexes = [
            'PRIMARY' => new Index(
                name: 'PRIMARY',
                type: IndexType::Primary,
                columns: ['id'],
            ),
            'users_email_unique' => new Index(
                name: 'users_email_unique',
                type: IndexType::Unique,
                columns: ['email'],
            ),
        ];

        $table = new Table(name: 'users', indexes: $indexes);

        expect($table->hasIndex('PRIMARY'))->toBeTrue();
        expect($table->hasIndex('users_email_unique'))->toBeTrue();
        expect($table->getIndex('PRIMARY')->type)->toBe(IndexType::Primary);
    });

    it('creates a table with foreign keys', function () {
        $foreignKeys = [
            'posts_user_id_foreign' => new ForeignKey(
                name: 'posts_user_id_foreign',
                columns: ['user_id'],
                referencedTable: 'users',
                referencedColumns: ['id'],
            ),
        ];

        $table = new Table(name: 'posts', foreignKeys: $foreignKeys);

        expect($table->hasForeignKey('posts_user_id_foreign'))->toBeTrue();
        expect($table->getForeignKey('posts_user_id_foreign')->referencedTable)->toBe('users');
    });

    it('converts to array', function () {
        $table = new Table(
            name: 'users',
            columns: [
                'id' => new Column(name: 'id', type: ColumnType::BigIncrements),
            ],
            engine: 'InnoDB',
            charset: 'utf8mb4',
        );

        $array = $table->toArray();

        expect($array['name'])->toBe('users');
        expect($array['columns'])->toHaveCount(1);
        expect($array['engine'])->toBe('InnoDB');
        expect($array['charset'])->toBe('utf8mb4');
    });

    it('creates from array', function () {
        $data = [
            'name' => 'posts',
            'columns' => [
                'id' => ['name' => 'id', 'type' => 'bigIncrements'],
                'title' => ['name' => 'title', 'type' => 'string'],
            ],
            'indexes' => [
                'PRIMARY' => ['name' => 'PRIMARY', 'type' => 'primary', 'columns' => ['id']],
            ],
        ];

        $table = Table::fromArray($data);

        expect($table->name)->toBe('posts');
        expect($table->columns)->toHaveCount(2);
        expect($table->hasColumn('id'))->toBeTrue();
        expect($table->hasIndex('PRIMARY'))->toBeTrue();
    });
});
