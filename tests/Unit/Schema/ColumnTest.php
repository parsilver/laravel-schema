<?php

declare(strict_types=1);

use Farzai\LaravelSchema\Schema\Column;
use Farzai\LaravelSchema\Schema\Enums\ColumnType;

describe('Column', function () {
    it('creates a column with required properties', function () {
        $column = new Column(
            name: 'id',
            type: ColumnType::BigInteger,
        );

        expect($column->name)->toBe('id');
        expect($column->type)->toBe(ColumnType::BigInteger);
        expect($column->nullable)->toBeFalse();
        expect($column->default)->toBeNull();
    });

    it('creates a column with all properties', function () {
        $column = new Column(
            name: 'email',
            type: ColumnType::String,
            nullable: true,
            default: 'test@example.com',
            length: 255,
            comment: 'User email address',
        );

        expect($column->name)->toBe('email');
        expect($column->type)->toBe(ColumnType::String);
        expect($column->nullable)->toBeTrue();
        expect($column->default)->toBe('test@example.com');
        expect($column->length)->toBe(255);
        expect($column->comment)->toBe('User email address');
    });

    it('checks equality between columns', function () {
        $column1 = new Column(
            name: 'name',
            type: ColumnType::String,
            nullable: false,
            length: 100,
        );

        $column2 = new Column(
            name: 'name',
            type: ColumnType::String,
            nullable: false,
            length: 100,
        );

        $column3 = new Column(
            name: 'name',
            type: ColumnType::String,
            nullable: true,
            length: 100,
        );

        expect($column1->equals($column2))->toBeTrue();
        expect($column1->equals($column3))->toBeFalse();
    });

    it('converts to array', function () {
        $column = new Column(
            name: 'status',
            type: ColumnType::Enum,
            nullable: false,
            allowedValues: ['active', 'inactive'],
        );

        $array = $column->toArray();

        expect($array['name'])->toBe('status');
        expect($array['type'])->toBe('enum');
        expect($array['nullable'])->toBeFalse();
        expect($array['allowedValues'])->toBe(['active', 'inactive']);
    });

    it('creates from array', function () {
        $data = [
            'name' => 'price',
            'type' => 'decimal',
            'nullable' => false,
            'precision' => 10,
            'scale' => 2,
        ];

        $column = Column::fromArray($data);

        expect($column->name)->toBe('price');
        expect($column->type)->toBe(ColumnType::Decimal);
        expect($column->nullable)->toBeFalse();
        expect($column->precision)->toBe(10);
        expect($column->scale)->toBe(2);
    });
});
