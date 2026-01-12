<?php

declare(strict_types=1);

use Farzai\LaravelSchema\Schema\Enums\IndexType;

describe('IndexType', function () {
    it('has correct values', function () {
        expect(IndexType::Primary->value)->toBe('primary');
        expect(IndexType::Unique->value)->toBe('unique');
        expect(IndexType::Index->value)->toBe('index');
        expect(IndexType::Fulltext->value)->toBe('fulltext');
        expect(IndexType::Spatial->value)->toBe('spatial');
    });

    it('correctly identifies unique indexes', function () {
        expect(IndexType::Primary->isUnique())->toBeTrue();
        expect(IndexType::Unique->isUnique())->toBeTrue();
        expect(IndexType::Index->isUnique())->toBeFalse();
        expect(IndexType::Fulltext->isUnique())->toBeFalse();
    });

    it('returns correct labels', function () {
        expect(IndexType::Primary->label())->toBe('Primary Key');
        expect(IndexType::Unique->label())->toBe('Unique');
        expect(IndexType::Index->label())->toBe('Index');
    });

    it('creates from string with tryFromString', function () {
        expect(IndexType::tryFromString('primary'))->toBe(IndexType::Primary);
        expect(IndexType::tryFromString('PRIMARY'))->toBe(IndexType::Primary);
        expect(IndexType::tryFromString('pri'))->toBe(IndexType::Primary);
        expect(IndexType::tryFromString('unique'))->toBe(IndexType::Unique);
        expect(IndexType::tryFromString('mul'))->toBe(IndexType::Index);
        expect(IndexType::tryFromString('unknown'))->toBe(IndexType::Index);
    });
});
