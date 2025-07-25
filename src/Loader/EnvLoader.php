<?php

namespace NAL\Dotenv\Loader;

use NAL\Dotenv\Parser\ParserInterface;
use NAL\Dotenv\PathResolver;

/**
 * Flexible environment loader that resolves appropriate loader classes using a registry.
 *
 * `EnvLoader` allows loading environment variables from multiple file formats by using
 * a `LoaderRegistry` to resolve the correct loader (e.g., DotenvLoader, JsonLoader)
 * for each file based on its extension.
 *
 * @package NAL\Dotenv\Loader
 */
class EnvLoader extends BaseLoader
{
    /**
     * EnvLoader constructor
     *
     * @param LoaderRegistry       $registry  The loader registry instance used to resolve specific loaders.
     * @param string|array|null    $files     The path(s) to the env files to be loaded.
     * @param bool                 $override  Whether to override existing environment variables.
     * @param PathResolver|null    $resolver  Optional path resolver for resolving relative paths.
     * @param ParserInterface|null $parser    Optional parser to be passed down to resolved loaders.
     */
    public function __construct(
        private LoaderRegistry $registry,
        protected null|string|array $files = null,
        protected bool $override = false,
        protected ?PathResolver $resolver = null,
        protected ?ParserInterface $parser = null,
    ) {
        parent::__construct($files, $this->override, $resolver, $parser);
    }

    /**
     * Load environment variables using dynamically resolved loaders from the registry.
     *
     * For each file, the appropriate loader is resolved via the registry and its `load()` method
     * is invoked. All resulting variables are merged into a single result.
     *
     * @return array{envs: array<string, mixed>, groups: array<string, array<string, mixed>>}
     */
    public function load(): array
    {
        $loaded = [
            'envs' => [],
            'groups' => []
        ];

        foreach ($this->resolveFiles() as $file) {
            $loader = $this->registry->resolve($file, $this->override);

            $envs = $loader->load();

            $loaded['envs'] = array_merge($loaded['envs'], $envs['envs']);
            $loaded['groups'] = array_merge($loaded['groups'], $envs['groups']);
        }

        return $loaded;
    }
}
