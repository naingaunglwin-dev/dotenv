<?php

namespace NAL\Dotenv\Loader;

use NAL\Dotenv\Exceptions\MissingLoader;
use NAL\Dotenv\Exceptions\MissingParser;
use NAL\Dotenv\Parser\DotenvParser;
use NAL\Dotenv\PathResolver;

/**
 * Class LoaderRegistry
 *
 * Manages the registration and resolution of file loaders for environment configurations.
 * Supports resolving file loaders dynamically based on file extensions, and injects
 * appropriate parser instances per file type.
 */
class LoaderRegistry
{
    /**
     * Maps file extensions (e.g., 'json', 'dotenv') to their corresponding loader factories.
     *
     * @var array<string, callable>|null
     */
    private ?array $loader;

    /**
     * Parser's namespace
     *
     * @var array|null
     */
    private ?array $parserNamespace;

    /**
     * Parser default namespace
     *
     * @var string
     */
    private string $namespace = "\\NAL\\Dotenv\\Parser\\";

    /**
     * LoaderRegistry
     */
    public function __construct()
    {
    }

    /**
     * Register a file loader for a given extension.
     *
     * @param string      $extension File extension (e.g., 'json', 'dotenv').
     * @param callable    $loader    A closure or callable that returns a LoaderInterface instance.
     * @param string|null $namespace namespace for finding parser related to loader
     */
    public function register(string $extension, callable $loader, ?string $namespace = null): void
    {
        if (!isset($this->loader)) {
            $this->loader = [];
        }

        if (!isset($this->parserNamespace)) {
            $this->parserNamespace = [];
        }

        $this->loader[$extension] = $loader;
        $this->parserNamespace[$extension] = $namespace ?? $this->namespace;
    }

    /**
     * Resolve and return the appropriate loader for a given file.
     *
     * @param string $file     The path to the file to load.
     * @param bool   $override Whether environment values should be overridden.
     *
     * @return LoaderInterface The resolved loader instance.
     *
     * @throws MissingLoader If no loader is registered for the file extension.
     * @throws MissingParser If the parser class is missing for the given extension.
     */
    public function resolve(string $file, bool $override): LoaderInterface
    {
        $basename = basename($file);
        $extension = pathinfo($basename, PATHINFO_EXTENSION);

        if (str_starts_with($basename, '.env') && !in_array($extension, array_keys($this->loader))) {
            $extension = 'dotenv';
            $parser = DotenvParser::class;
        } else {
            $extension = pathinfo($basename, PATHINFO_EXTENSION);
            $parser = rtrim($this->parserNamespace[$extension] ?? $this->namespace, "\\") . "\\" . ucfirst($extension) . "Parser";
        }

        if (!isset($this->loader[$extension])) {
            throw new MissingLoader($extension);
        }

        if (!class_exists($parser)) {
            throw new MissingParser($parser, $extension);
        }

        return $this->loader[$extension]($file, $override, new PathResolver(),new $parser);
    }
}
