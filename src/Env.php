<?php

namespace NAL\Dotenv;

use NAL\Dotenv\Loader\DotenvLoader;
use NAL\Dotenv\Loader\EnvLoader;
use NAL\Dotenv\Loader\JsonLoader;
use NAL\Dotenv\Loader\LoaderInterface;
use NAL\Dotenv\Loader\LoaderRegistry;

/**
 *
 *  Usage example:
 *  ```
 *  $env   = Env::create(name: '.env');
 *
 *  $value = $env->get('APP_ENV', 'production');
 *  $group = $env->group('APP', []);
 *
 *  $value = Env::get('APP_ENV', 'production');
 *  $group = Env::group('APP');
 *  ```
 *
 * @method static mixed get(string $key, mixed $default = null)
 * @method static mixed group(string $key, mixed $default = null)
 * @method mixed get(string $key, mixed $default = null)
 * @method mixed group(string $key, mixed $default = null)
 */
class Env
{
    /**
     * Cached loaded data
     *
     * @var array|null
     */
    private ?array $cached;

    /**
     * singleton instance used for static access
     *
     * @var Env|null
     */
    private static ?Env $instance;

    /**
     * Env constructor
     *
     * @param LoaderInterface $loader  The loader instance responsible for reading env files.
     * @param bool            $cache   Whether to cache the loaded values during runtime.
     */
    public function __construct(
        private LoaderInterface $loader,
        private bool $cache = false,
    )
    {
    }

    /**
     * Create a new Env instance
     *
     * If no loader is given, it will guess based on file name and register default loaders.
     *
     * @param LoaderInterface|null $loader    env loader implementation.
     * @param string|array|null    $name      The file(s) to load from.
     * @param LoaderRegistry|null  $registry  Optional loader registry to use for auto-loading.
     * @param bool                 $override  Whether later env vars override earlier ones.
     * @param bool                 $cache     Whether to enable result caching.
     * @return Env
     */
    public static function create(?LoaderInterface $loader = null, null|string|array $name = null, ?LoaderRegistry $registry = null, bool $override = false, bool $cache = false): Env
    {
        if ($loader === null) {
            $loader = new EnvLoader(
                $registry ?? self::createDefaultLoaderRegistry(),
                $name,
                $override
            );
        }

        self::$instance = new self($loader, $cache);

        return self::$instance;
    }

    /**
     * Load environment variables from the defined source.
     *
     * @return array{
     *     envs: array,
     *     groups: array,
     * }
     */
    public function load(): array
    {
        if ($this->cache) {
            if (!isset($this->cached)) {
                $this->cached = $this->loader->load();
            }

            return $this->cached;
        }

        return $this->loader->load();
    }

    /**
     * Safe load version that catches exceptions and returns empty result.
     *
     * @return array{envs: array, groups: array}
     */
    public function safeLoad(): array
    {
        try {
            return $this->load();
        } catch (\Throwable) {
            return ['envs' => [], 'groups' => []];
        }
    }

    /**
     * Load environment variables and return them with processing time (in seconds).
     *
     * @return array{envs: array, groups: array, process: float}
     */
    public function dump(): array
    {
        $start = microtime(true);

        $result = $this->load();

        $result['process'] = microtime(true) - $start;;

        return $result;
    }

    /**
     * Create a default loader registry with support for `.env` and `.json`.
     *
     * @return LoaderRegistry
     */
    private static function createDefaultLoaderRegistry(): LoaderRegistry
    {
        $registry = new LoaderRegistry();

        $registry->register('dotenv', fn (...$arg) => new DotenvLoader(...$arg));
        $registry->register('json', fn (...$arg) => new JsonLoader(...$arg));

        return $registry;
    }

    public function __call(string $name, array $arguments): mixed
    {
        if (in_array($name, ['get', 'group'])) {
            return $this->call($this, $arguments, $name === 'get' ? 'envs' : 'groups');
        }

        throw new \BadMethodCallException("Call to undefined method: {$name}()");
    }

    public static function __callStatic(string $name, array $arguments): mixed
    {
        $instance = self::$instance ?? self::create();

        if (in_array($name, ['get', 'group'])) {
            return $instance->call($instance, $arguments, $name === 'get' ? 'envs' : 'groups');
        }

        throw new \BadMethodCallException("Call to undefined method: {$name}()");
    }

    /**
     * @param Env    $instance
     * @param array  $arguments
     * @param string $from
     *
     * @return mixed
     */
    private function call(Env $instance, array $arguments, string $from): mixed
    {
        $key = $arguments[0];
        $default = $arguments[1] ?? null;
        return $instance->load()[$from][$key] ?? $default;
    }
}
