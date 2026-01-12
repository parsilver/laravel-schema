<?php

declare(strict_types=1);

use Farzai\LaravelSchema\Schema\Enums\DiffStatus;

describe('DiffStatus', function () {
    it('has correct values', function () {
        expect(DiffStatus::Added->value)->toBe('added');
        expect(DiffStatus::Removed->value)->toBe('removed');
        expect(DiffStatus::Modified->value)->toBe('modified');
        expect(DiffStatus::Unchanged->value)->toBe('unchanged');
    });

    it('correctly identifies differences', function () {
        expect(DiffStatus::Added->hasDifference())->toBeTrue();
        expect(DiffStatus::Removed->hasDifference())->toBeTrue();
        expect(DiffStatus::Modified->hasDifference())->toBeTrue();
        expect(DiffStatus::Unchanged->hasDifference())->toBeFalse();
    });

    it('returns correct labels', function () {
        expect(DiffStatus::Added->label())->toBe('Added');
        expect(DiffStatus::Removed->label())->toBe('Removed');
        expect(DiffStatus::Modified->label())->toBe('Modified');
        expect(DiffStatus::Unchanged->label())->toBe('Unchanged');
    });

    it('returns correct colors', function () {
        expect(DiffStatus::Added->color())->toBe('green');
        expect(DiffStatus::Removed->color())->toBe('red');
        expect(DiffStatus::Modified->color())->toBe('yellow');
        expect(DiffStatus::Unchanged->color())->toBe('gray');
    });
});
