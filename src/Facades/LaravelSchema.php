<?php

namespace Farzai\LaravelSchema\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Farzai\LaravelSchema\LaravelSchema
 */
class LaravelSchema extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Farzai\LaravelSchema\LaravelSchema::class;
    }
}
