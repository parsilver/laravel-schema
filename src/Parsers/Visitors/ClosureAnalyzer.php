<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Parsers\Visitors;

use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;

/**
 * Analyzer for Blueprint closures in Laravel migrations.
 *
 * Traverses closure statements and delegates method calls to the processor.
 */
final class ClosureAnalyzer
{
    public function __construct(
        private BlueprintCallProcessor $processor,
    ) {}

    /**
     * Analyze a closure containing Blueprint calls.
     *
     * @param  array<string, mixed>  $currentOperation  Reference to operation
     */
    public function analyzeClosure(Closure $closure, string $currentTable, array &$currentOperation): void
    {
        foreach ($closure->stmts ?? [] as $stmt) {
            $this->analyzeStatement($stmt, $currentTable, $currentOperation);
        }
    }

    /**
     * Analyze a statement within the closure.
     *
     * @param  array<string, mixed>  $currentOperation  Reference to operation
     */
    public function analyzeStatement(Node $stmt, string $currentTable, array &$currentOperation): void
    {
        // Handle expression statements
        if ($stmt instanceof Node\Stmt\Expression) {
            $stmt = $stmt->expr;
        }

        // Process method calls on the Blueprint ($table->xxx())
        if ($stmt instanceof MethodCall) {
            $this->processor->processMethodChain($stmt, $currentTable, $currentOperation);
        }
    }
}
