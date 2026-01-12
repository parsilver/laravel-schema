<?php

declare(strict_types=1);

use Farzai\LaravelSchema\Schema\Enums\ColumnType;

describe('ColumnType', function () {
    it('has correct values for integer types', function () {
        expect(ColumnType::BigInteger->value)->toBe('bigInteger');
        expect(ColumnType::Integer->value)->toBe('integer');
        expect(ColumnType::SmallInteger->value)->toBe('smallInteger');
        expect(ColumnType::TinyInteger->value)->toBe('tinyInteger');
    });

    it('has correct values for string types', function () {
        expect(ColumnType::String->value)->toBe('string');
        expect(ColumnType::Text->value)->toBe('text');
        expect(ColumnType::Char->value)->toBe('char');
    });

    it('checks integer types correctly', function () {
        expect(ColumnType::Integer->isInteger())->toBeTrue();
        expect(ColumnType::BigInteger->isInteger())->toBeTrue();
        expect(ColumnType::String->isInteger())->toBeFalse();
    });

    it('checks string types correctly', function () {
        expect(ColumnType::String->isString())->toBeTrue();
        expect(ColumnType::Text->isString())->toBeTrue();
        expect(ColumnType::Integer->isString())->toBeFalse();
    });

    it('checks datetime types correctly', function () {
        expect(ColumnType::DateTime->isDateTime())->toBeTrue();
        expect(ColumnType::Timestamp->isDateTime())->toBeTrue();
        expect(ColumnType::String->isDateTime())->toBeFalse();
    });

    it('checks unsigned types correctly', function () {
        expect(ColumnType::UnsignedBigInteger->isUnsigned())->toBeTrue();
        expect(ColumnType::Id->isUnsigned())->toBeTrue();
        expect(ColumnType::Integer->isUnsigned())->toBeFalse();
    });

    it('checks auto increment types correctly', function () {
        expect(ColumnType::BigIncrements->isAutoIncrement())->toBeTrue();
        expect(ColumnType::Id->isAutoIncrement())->toBeTrue();
        expect(ColumnType::Integer->isAutoIncrement())->toBeFalse();
    });

    it('creates from string with tryFromString', function () {
        expect(ColumnType::tryFromString('string'))->toBe(ColumnType::String);
        expect(ColumnType::tryFromString('integer'))->toBe(ColumnType::Integer);
        expect(ColumnType::tryFromString('unknown_type'))->toBe(ColumnType::Unknown);
    });
});
