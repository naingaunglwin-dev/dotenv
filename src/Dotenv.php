<?php

declare(strict_types=1);

namespace NAL\Dotenv;

use NAL\Dotenv\Exception\Missing;
use NAL\Dotenv\Exception\UnMatch;

class Dotenv
{
    /**
     * @var array<string, string> Loaded environment variables.
     */
    private array $envs = [];

    /**
     * @var bool Whether the env files have already been loaded.
     */
    private bool $loaded = false;

    /**
     * @var array<string, array<string, string>> Grouped environment variables (e.g. DB_HOST, DB_USER under 'DB').
     */
    private array $group = [];

    /**
     * @var array<int, string> Keys of loaded environment variables.
     */
    private array $keys = [];

    /**
     * @var array<int, string> Default file names to look for when no specific .env file is given.
     */
    private array $defaultEnvFiles = [
        '.env', '.env.local',
        '.env.development', '.env.production', '.env.testing',
        '.env.dev', '.env.prod', '.env.test', '.env.staging'
    ];

    /**
     * @var string Key used to store environment keys in $_SERVER.
     */
    private const KEY = "__NAL_ENV_KEYS";

    /**
     * Env constructor.
     *
     * @param string|array|null $files     Optional path(s) to specific .env files.
     * @param string|null       $envKey    Your project env key to detect whether your application is in production environment or other, eg. (APP_ENV, APP_ENVIRONMENT)
     * @param bool              $overwrite Whether to allow overwriting of previously set environment variables.
     */
    public function __construct(
        private string|array|null    $files = null,
        private readonly string|null $envKey = null,
        private readonly bool        $overwrite = true
    ) {
        if (empty($files)) {
            $this->files = $this->findAvailableEnvFilesFromDefaultFiles();
        } else {
            $this->files = $this->resolvePaths($files);
        }
    }

    /**
     * Get a specific environment variable or all of them.
     *
     * @param string|null $key     Variable name.
     * @param mixed       $default Default value if key is not found.
     * @return mixed
     */
    public function get(?string $key = null, mixed $default = null): mixed
    {
        if (!$this->loaded) {
            $this->load();
        }

        return is_null($key) ? $this->envs : ($this->envs[$key] ?? $default);
    }

    /**
     * Get grouped environment variables (e.g. all DB_*).
     *
     * @param string|null $key     Group key (e.g. 'DB').
     * @param mixed       $default Default value if group is not found.
     * @return mixed
     */
    public function group(?string $key = null, mixed $default = null): mixed
    {
        if (!$this->loaded) {
            $this->load();
        }

        return is_null($key) ? $this->group : ($this->group[$key] ?? $default);
    }

    /**
     * Check if a specific environment variable exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->envs);
    }

    /**
     * Load environment variables from the defined files.
     *
     * @return $this
     */
    public function load(): Dotenv
    {
        if ($this->loaded && $this->isAppInProduction()) {
            return $this;
        }

        return $this->reload();
    }

    /**
     * Reload environment variables, clearing previously loaded ones.
     *
     * @return $this
     */
    public function reload(): Dotenv
    {
        $this->restore();

        return $this->doLoad();
    }

    /**
     * Restore the internal state, removing all loaded environment data.
     *
     * @return void
     */
    private function restore(): void
    {
        $this->envs  = [];
        $this->group = [];
        $this->keys  = [];
    }

    /**
     * Load and parse the environment files.
     *
     * @return $this
     */
    private function doLoad(): Dotenv
    {
        foreach ($this->files as $file) {
            $handle = @fopen($file, "r");

            if (!$handle) {
                continue;
            }

            while (($line = fgets($handle)) !== false) {
                $line = trim($line);

                if ($this->isSkippableLines($line)) {
                    continue;
                }

                if (!str_contains($line, '=')) {
                    throw new \RuntimeException("Env var must contain '='");
                }

                [$key, $value] = explode("=", $line, 2);

                $key = $this->trim($key);
                $value = $this->trim($value);

                $this->ensureValidEnvKeyFormat($key, $file);

                if (!$this->overwrite && array_key_exists($key, $this->envs)) {
                    continue;
                }

                $this->envs[$key] = $value;

                if ($this->isGroupEnv($key)) {
                    $name = strtok($key, '_');
                    $this->group[$name][$key] = $value;
                }
            }

            fclose($handle);
        }

        $this->loaded = true;
        $this->saveOnServer();

        return $this;
    }

    /**
     * Save environment variables into $_SERVER and putenv(), and store the keys.
     *
     * @return void
     */
    private function saveOnServer(): void
    {
        $oldKeys = isset($_SERVER[self::KEY])
            ? explode(',', $_SERVER[self::KEY])
            : $this->keys;

        if (empty($this->envs)) {
            return;
        }

        $keyDiff = array_diff_key(array_flip($oldKeys), $this->envs);

        if (!empty($keyDiff)) {
            foreach ($keyDiff as $key => $_) {
                $this->remove($key);
            }
        }

        foreach ($this->envs as $key => $var) {
            $_SERVER[$key] = $var;
            putenv("$key=$var");
        }

        $this->keys = array_keys($this->envs);
        $_SERVER[self::KEY] = implode(',', $this->keys);
    }

    /**
     * Remove environment variable from $_SERVER, $_ENV, and system environment.
     *
     * @param string $key
     * @return void
     */
    private function remove(string $key): void
    {
        unset($_SERVER[$key], $_ENV[$key]);
        putenv($key);
    }

    /**
     * Check if the key is a grouped environment variable (contains underscore).
     *
     * @param string $key
     * @return bool
     */
    private function isGroupEnv(string $key): bool
    {
        return str_contains($key, '_');
    }

    /**
     * Normalize and trim quotes and whitespace from string.
     *
     * @param string $value
     * @return string
     */
    private function trim(string $value): string
    {
        return preg_replace('/\s+/', '', trim($value, "\"'"));
    }

    /**
     * Determine if the line should be skipped (empty or comment).
     *
     * @param string $line
     * @return bool
     */
    private function isSkippableLines(string $line): bool
    {
        return $line === '' || str_starts_with($line, '#');
    }

    /**
     * Find all available default .env files in DIR_ROOT.
     *
     * @return array<int, string>
     */
    private function findAvailableEnvFilesFromDefaultFiles(): array
    {
        $basePath = dirname(__DIR__, 4) . '/';

        return array_map(
            fn ($file) => $basePath . $file,
            array_filter($this->defaultEnvFiles, fn ($file) => file_exists($basePath . $file))
        );
    }

    /**
     * Validate the format of an environment variable key.
     *
     * @param string $key
     * @param string $file
     * @return void
     *
     * @throws UnMatch
     */
    private function ensureValidEnvKeyFormat(string $key, string $file): void
    {
        $pattern = "/^[a-zA-Z_][a-zA-Z_.]*$/";
        if (preg_match($pattern, $key)) {
            return;
        }

        throw UnMatch::varNameFormat($key, $pattern, $file);
    }

    /**
     * Resolve and normalize paths provided to the constructor.
     *
     * @param array|string $path
     * @return array<int, string>
     *
     * @throws Missing
     */
    private function resolvePaths(array|string $path): array
    {
        $paths = is_string($path) ? [$path] : $path;

        $resolved = [];

        foreach ($paths as $path) {

            if (file_exists($path)) {
                $resolved[] = $path;
                continue;
            }

            $path = $this->normalizePath($path);
            $path = dirname(__DIR__, 4) . '/' . $path;

            if (!file_exists($path)) {
                throw Missing::file($path);
            }

            $resolved[] = $path;
        }

        return $resolved;
    }

    /**
     * Normalize a file path to use system-specific directory separators.
     *
     * @param string $path
     * @return string
     */
    private function normalizePath(string $path): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Check if the current environment is production.
     *
     * @return bool
     */
    private function isAppInProduction(): bool
    {
        $possible = array_change_key_case($this->envs, CASE_LOWER);

        foreach ([
             strtolower($this->envKey ?? 'APP_ENV'),
             'app_environment',
             'application_env',
             'environment',
             'app_mode',
             'app_stage',
             'env',
        ] as $key) {
            if (isset($possible[$key])) {
                $key = array_search($possible[$key], $this->envs);
                break;
            }
        }

        $env = strtolower($this->envs[$key]);

        return in_array(strtolower($env), ['production', 'prod'], true);
    }
}
