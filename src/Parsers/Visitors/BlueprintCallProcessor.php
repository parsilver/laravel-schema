<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Parsers\Visitors;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;

/**
 * Processor for Blueprint method calls in Laravel migrations.
 *
 * Routes method calls to appropriate parsers and handles method chains.
 */
final class BlueprintCallProcessor
{
    /**
     * Drop method names.
     *
     * @var array<int, string>
     */
    private const DROP_METHODS = [
        'dropColumn',
        'dropColumns',
        'dropPrimary',
        'dropUnique',
        'dropIndex',
        'dropForeign',
        'dropSoftDeletes',
        'dropTimestamps',
        'dropRememberToken',
    ];

    public function __construct(
        private AstValueExtractor $extractor,
        private ColumnDefinitionParser $columnParser,
        private IndexAndConstraintParser $indexParser,
    ) {}

    /**
     * Process a method call chain.
     *
     * @param  array<string, mixed>  $currentOperation  Reference to operation
     */
    public function processMethodChain(MethodCall $call, string $currentTable, array &$currentOperation): void
    {
        // Collect the chain of method calls
        $chain = [];
        $current = $call;

        while ($current instanceof MethodCall) {
            if ($current->name instanceof Node\Identifier) {
                $chain[] = [
                    'method' => $current->name->toString(),
                    'args' => $current->args,
                ];
            }
            $current = $current->var;
        }

        // Reverse to get the chain in order
        $chain = array_reverse($chain);

        if (empty($chain)) {
            return;
        }

        // The first call determines the column type
        $firstCall = $chain[0];
        $this->processBlueprintCall(
            $firstCall['method'],
            $firstCall['args'],
            array_slice($chain, 1),
            $currentTable,
            $currentOperation
        );
    }

    /**
     * Process a Blueprint method call.
     *
     * @param  array<int, Node\Arg>  $args
     * @param  array<int, array{method: string, args: array<int, Node\Arg>}>  $modifiers
     * @param  array<string, mixed>  $currentOperation  Reference to operation
     */
    public function processBlueprintCall(
        string $method,
        array $args,
        array $modifiers,
        string $currentTable,
        array &$currentOperation
    ): void {
        // Handle drop methods
        if ($this->isDropMethod($method)) {
            $this->processDropCall($method, $args, $currentOperation);

            return;
        }

        // Handle index methods
        if ($this->indexParser->isIndexMethod($method)) {
            $this->indexParser->processIndexCall($method, $args, $currentTable, $currentOperation);

            return;
        }

        // Handle foreign key methods
        if ($this->indexParser->isForeignKeyMethod($method)) {
            $this->indexParser->processForeignKeyCall($args, $modifiers, $currentTable, $currentOperation);

            return;
        }

        // Handle multi-column methods
        if ($this->columnParser->isMultiColumnMethod($method)) {
            $this->columnParser->processMultiColumnMethod($method, $args, $currentOperation);

            return;
        }

        // Handle column definitions
        $columnInfo = $this->columnParser->parseColumnCall($method, $args);
        if ($columnInfo !== null) {
            // Apply modifiers
            foreach ($modifiers as $modifier) {
                $columnInfo = $this->columnParser->applyModifier(
                    $columnInfo,
                    $modifier['method'],
                    $modifier['args'],
                    $currentOperation
                );
            }

            // Handle foreignId with constrained
            if ($this->indexParser->hasConstrainedModifier($modifiers)) {
                $this->indexParser->addForeignKeyFromConstraint($columnInfo, $modifiers, $currentTable, $currentOperation);
            }

            $currentOperation['columns'][$columnInfo['name']] = $columnInfo;
        }
    }

    /**
     * Check if method is a drop method.
     */
    private function isDropMethod(string $method): bool
    {
        return in_array($method, self::DROP_METHODS, true);
    }

    /**
     * Process drop calls.
     *
     * @param  array<int, Node\Arg>  $args
     * @param  array<string, mixed>  $currentOperation  Reference to operation
     */
    private function processDropCall(string $method, array $args, array &$currentOperation): void
    {
        if ($method === 'dropColumn' || $method === 'dropColumns') {
            $columns = [];
            foreach ($args as $arg) {
                $value = $this->extractor->extractValue($arg);
                if (is_array($value)) {
                    $columns = array_merge($columns, $value);
                } elseif (is_string($value)) {
                    $columns[] = $value;
                }
            }
            $currentOperation['drops']['columns'] = array_merge(
                $currentOperation['drops']['columns'] ?? [],
                $columns
            );
        }
    }
}
