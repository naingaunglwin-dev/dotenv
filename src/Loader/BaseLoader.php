<?php

namespace NAL\Dotenv\Loader;

use NAL\Dotenv\Parser\ParserInterface;
use NAL\Dotenv\PathResolver;

abstract class BaseLoader implements LoaderInterface
{
    /**
     * The default filename to load if no file is specified.
     *
     * @var string
     */
    private string $defaultFile = '.env';

    /**
     * BaseLoader constructor
     *
     * @param string|array|null    $files    The file or files to load.
     * @param bool                 $override Whether to override existing env values.
     * @param PathResolver|null    $resolver Optional path resolver.
     * @param ParserInterface|null $parser   Optional parser to parse file contents.
     */
    public function __construct(
        protected null|string|array $files = null,
        protected bool $override = false,
        protected ?PathResolver $resolver = null,
        protected ?ParserInterface $parser = null,
    )
    {
        $this->resolver = $resolver ?? new PathResolver();
        $this->files = $files ?? $this->getDefaultFile();

        if (!is_array($this->files)) {
            $this->files = [$this->files];
        }
    }

    /**
     * Get the default filename to load.
     *
     * @return string
     */
    protected function getDefaultFile(): string
    {
        return $this->defaultFile;
    }

    /**
     * Resolves all configured file paths to absolute paths.
     *
     * @return string[] Array of resolved file paths.
     */
    protected function resolveFiles(): array
    {
        return array_map(fn ($file) => $this->resolver->resolve($file), $this->files);
    }

    /**
     * Get the parser instance.
     *
     * @return ParserInterface|null
     */
    protected function parser(): ?ParserInterface
    {
        return $this->parser;
    }

    /**
     * Sets environment variables into PHP's superglobals and environment.
     *
     * @param array $envs Array with an 'envs' key holding key-value env pairs.
     */
    protected function save(array $envs): void
    {
        $envs = $envs['envs'] ?? [];

        if (!empty($envs)) {
            foreach ($envs as $key => $value) {
                putenv("$key=$value");
                $_ENV[$key]    = $value;
                $_SERVER[$key] = $value;
            }

            $this->syncServer($envs);
        }
    }

    /**
     * Syncs environment variables in $_SERVER and unsets removed ones.
     *
     * @param array $new Newly set environment variables.
     */
    protected function syncServer(array $new): void
    {
        $previous = $_SERVER['_nubo_dotenv_keys_'] ?? null;
        $keys = array_keys($new);

        if (null !== $previous) {
            $previous = explode(",", $previous);

            $diff = array_diff($new, $previous);

            if (!empty($diff)) {
                foreach ($diff as $key => $value) { // remove env vars if there is diff
                    putenv($key);
                    unset($_ENV[$key]);
                    unset($_SERVER[$key]);
                }
            }
        }

        $_SERVER['_nubo_dotenv_keys_'] = implode(",", $keys);
    }
}
