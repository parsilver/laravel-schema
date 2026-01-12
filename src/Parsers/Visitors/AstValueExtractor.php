<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Parsers\Visitors;

use PhpParser\Node;
use PhpParser\Node\Scalar\String_;

/**
 * Utility class for extracting PHP values from AST nodes.
 *
 * This class provides methods to extract typed values (strings, integers,
 * booleans, arrays) from PhpParser AST argument nodes.
 */
final class AstValueExtractor
{
    /**
     * Extract table name from argument (alias for extractString).
     */
    public function extractTableName(?Node\Arg $arg): ?string
    {
        return $this->extractString($arg);
    }

    /**
     * Extract string value from argument.
     */
    public function extractString(?Node\Arg $arg): ?string
    {
        if ($arg === null) {
            return null;
        }

        if ($arg->value instanceof String_) {
            return $arg->value->value;
        }

        return null;
    }

    /**
     * Extract integer value from argument.
     */
    public function extractInt(?Node\Arg $arg): ?int
    {
        if ($arg === null) {
            return null;
        }

        if ($arg->value instanceof Node\Scalar\LNumber) {
            return $arg->value->value;
        }

        return null;
    }

    /**
     * Extract boolean value from argument.
     */
    public function extractBool(?Node\Arg $arg): ?bool
    {
        if ($arg === null) {
            return null;
        }

        if ($arg->value instanceof Node\Expr\ConstFetch) {
            $name = strtolower($arg->value->name->toString());

            return match ($name) {
                'true' => true,
                'false' => false,
                default => null,
            };
        }

        return null;
    }

    /**
     * Extract array value from argument.
     *
     * @return array<int, string>|null
     */
    public function extractArray(?Node\Arg $arg): ?array
    {
        if ($arg === null) {
            return null;
        }

        if ($arg->value instanceof Node\Expr\Array_) {
            $values = [];
            foreach ($arg->value->items as $item) {
                // @phpstan-ignore notIdentical.alwaysTrue (Array items can be null for spread elements)
                if ($item !== null && $item->value instanceof String_) {
                    $values[] = $item->value->value;
                }
            }

            return $values;
        }

        return null;
    }

    /**
     * Extract any value from argument.
     */
    public function extractValue(?Node\Arg $arg): mixed
    {
        if ($arg === null) {
            return null;
        }

        if ($arg->value instanceof String_) {
            return $arg->value->value;
        }

        if ($arg->value instanceof Node\Scalar\LNumber) {
            return $arg->value->value;
        }

        if ($arg->value instanceof Node\Scalar\DNumber) {
            return $arg->value->value;
        }

        if ($arg->value instanceof Node\Expr\ConstFetch) {
            $name = strtolower($arg->value->name->toString());

            return match ($name) {
                'true' => true,
                'false' => false,
                'null' => null,
                default => $name,
            };
        }

        if ($arg->value instanceof Node\Expr\Array_) {
            return $this->extractArray($arg);
        }

        return null;
    }
}
