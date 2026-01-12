<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Parsers;

use Farzai\LaravelSchema\Parsers\Visitors\AstValueExtractor;
use Farzai\LaravelSchema\Parsers\Visitors\BlueprintCallProcessor;
use Farzai\LaravelSchema\Parsers\Visitors\ClosureAnalyzer;
use Farzai\LaravelSchema\Parsers\Visitors\ColumnDefinitionParser;
use Farzai\LaravelSchema\Parsers\Visitors\IndexAndConstraintParser;
use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\NodeVisitorAbstract;

/**
 * AST visitor for extracting schema operations from Laravel migration files.
 *
 * This visitor traverses the AST of a migration file and extracts schema
 * operations (create, table, drop, rename) along with their column,
 * index, and foreign key definitions.
 */
final class BlueprintVisitor extends NodeVisitorAbstract
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $operations = [];

    private ?string $currentTable = null;

    /**
     * @var array<string, mixed>
     */
    private array $currentOperation = [];

    /**
     * Track whether we're inside the up() method.
     */
    private bool $inUpMethod = false;

    private AstValueExtractor $extractor;

    private ClosureAnalyzer $closureAnalyzer;

    /**
     * Create a new BlueprintVisitor instance.
     *
     * Dependencies can be injected for testing, or defaults will be created.
     */
    public function __construct(
        ?AstValueExtractor $extractor = null,
        ?ClosureAnalyzer $closureAnalyzer = null,
    ) {
        $this->extractor = $extractor ?? new AstValueExtractor;

        if ($closureAnalyzer !== null) {
            $this->closureAnalyzer = $closureAnalyzer;
        } else {
            // Create the dependency chain with default instances
            $columnParser = new ColumnDefinitionParser($this->extractor);
            $indexParser = new IndexAndConstraintParser($this->extractor);
            $processor = new BlueprintCallProcessor($this->extractor, $columnParser, $indexParser);
            $this->closureAnalyzer = new ClosureAnalyzer($processor);
        }
    }

    /**
     * Enter a node.
     */
    public function enterNode(Node $node): ?Node
    {
        // Track when we enter the up() method
        if ($node instanceof Node\Stmt\ClassMethod && $node->name->toString() === 'up') {
            $this->inUpMethod = true;
        }

        // Only process Schema calls inside the up() method
        if ($this->inUpMethod && $node instanceof StaticCall && $this->isSchemaCall($node)) {
            $this->processSchemaCall($node);
        }

        return null;
    }

    /**
     * Leave a node.
     */
    public function leaveNode(Node $node): ?Node
    {
        // Track when we leave the up() method
        if ($node instanceof Node\Stmt\ClassMethod && $node->name->toString() === 'up') {
            $this->inUpMethod = false;
        }

        return null;
    }

    /**
     * Check if this is a Schema facade call.
     */
    private function isSchemaCall(StaticCall $node): bool
    {
        if ($node->class instanceof Node\Name) {
            $className = $node->class->toString();

            return $className === 'Schema' || str_ends_with($className, '\Schema');
        }

        return false;
    }

    /**
     * Process a Schema:: call.
     */
    private function processSchemaCall(StaticCall $node): void
    {
        if (! $node->name instanceof Node\Identifier) {
            return;
        }

        $method = $node->name->toString();

        if (! in_array($method, ['create', 'table', 'drop', 'dropIfExists', 'rename', 'dropColumns'], true)) {
            return;
        }

        $tableName = $this->extractor->extractTableName($node->args[0] ?? null);

        if ($tableName === null) {
            return;
        }

        if ($method === 'rename') {
            $newName = $this->extractor->extractTableName($node->args[1] ?? null);
            $this->operations[] = [
                'type' => 'rename',
                'table' => $tableName,
                'newName' => $newName,
            ];

            return;
        }

        if (in_array($method, ['drop', 'dropIfExists'], true)) {
            $this->operations[] = [
                'type' => $method,
                'table' => $tableName,
            ];

            return;
        }

        // For create/table, analyze the closure
        $this->currentTable = $tableName;
        $this->currentOperation = [
            'type' => $method,
            'table' => $tableName,
            'columns' => [],
            'indexes' => [],
            'foreignKeys' => [],
            'drops' => [],
        ];

        // Find and analyze the closure argument
        foreach ($node->args as $arg) {
            if ($arg->value instanceof Closure) {
                $this->closureAnalyzer->analyzeClosure(
                    $arg->value,
                    $this->currentTable,
                    $this->currentOperation
                );
                break;
            }
        }

        $this->operations[] = $this->currentOperation;
        $this->currentTable = null;
        $this->currentOperation = [];
    }

    /**
     * Get the extracted operations.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * Reset the visitor state.
     */
    public function reset(): void
    {
        $this->operations = [];
        $this->currentTable = null;
        $this->currentOperation = [];
        $this->inUpMethod = false;
    }
}
