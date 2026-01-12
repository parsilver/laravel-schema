<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Parsers;

use Farzai\LaravelSchema\Exceptions\MigrationParseException;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;

/**
 * Analyzer for extracting schema operations from Laravel migration source code.
 */
final class BlueprintAnalyzer
{
    private Parser $parser;

    private NodeTraverser $traverser;

    private BlueprintVisitor $visitor;

    /**
     * Create a new BlueprintAnalyzer instance.
     *
     * Dependencies can be injected for testing, or defaults will be created.
     */
    public function __construct(
        ?Parser $parser = null,
        ?BlueprintVisitor $visitor = null,
    ) {
        $this->parser = $parser ?? (new ParserFactory)->createForNewestSupportedVersion();
        $this->visitor = $visitor ?? new BlueprintVisitor;
        $this->traverser = new NodeTraverser;
        $this->traverser->addVisitor($this->visitor);
    }

    /**
     * Analyze migration source code and extract schema operations.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws MigrationParseException
     */
    public function analyze(string $source): array
    {
        $this->visitor->reset();

        try {
            $ast = $this->parser->parse($source);

            if ($ast === null) {
                return [];
            }

            $this->traverser->traverse($ast);

            return $this->visitor->getOperations();
        } catch (\Throwable $e) {
            throw new MigrationParseException('inline', $e->getMessage());
        }
    }

    /**
     * Analyze a migration file and extract schema operations.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws MigrationParseException
     */
    public function analyzeFile(string $filePath): array
    {
        if (! file_exists($filePath)) {
            throw new MigrationParseException($filePath, 'File does not exist');
        }

        $source = file_get_contents($filePath);

        if ($source === false) {
            throw new MigrationParseException($filePath, 'Could not read file');
        }

        $this->visitor->reset();

        try {
            $ast = $this->parser->parse($source);

            if ($ast === null) {
                return [];
            }

            $this->traverser->traverse($ast);

            return $this->visitor->getOperations();
        } catch (\Throwable $e) {
            throw new MigrationParseException($filePath, $e->getMessage());
        }
    }
}
