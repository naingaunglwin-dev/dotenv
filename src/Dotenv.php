<?php

declare(strict_types=1);

namespace NAL\Dotenv;

class Dotenv
{
    /**
     * The array of environment files to be loaded.
     *
     * @var array
     */
    private array $files;

    /**
     * Base path of the project
     *
     * @var string
     */
    private string $basepath = '';

    /**
     * The local environment variables loaded from files.
     *
     * @var array
     */
    private array $local = [];

    /**
     * The global environment variables.
     *
     * @var array
     */
    private array $global = [];

    /**
     * Whether the environment variables have been loaded.
     *
     * @var bool
     */
    private bool $loaded = false;

    /**
     * Grouped environment variables.
     *
     * @var array
     */
    private array $group = [];

    /**
     * The keys of all loaded environment variables.
     *
     * @var array
     */
    private array $keys = [];

    /**
     * The default environment file names to look for.
     *
     * @var array
     */
    private array $defaults = [
        '.env', '.env.local',
        '.env.development', '.env.production',
        '.env.dev', '.env.prod'
    ];

    /**
     * Server key that hold for all env keys
     *
     * @var string
     */
    private string $key = "__NAL_ENV_KEYS";

    /**
     * Dotenv constructor.
     *
     * Initializes the Dotenv instance by loading environment variables
     * from specified files or default files if none are provided.
     *
     * @param string|array<string>|null $file The environment file(s) to load.
     */
    public function __construct(string|array $file = null, string $basepath = '')
    {
        $this->basepath = empty($basepath) ? dirname(__DIR__, 4) : $basepath;

        if (!str_ends_with("/", $this->basepath) && !str_ends_with("\\", $this->basepath)) {
            $this->basepath .= "/";
        }

        if (!$file) {
            $file = $this->find();
        } else {
            $this->validate(file: $file);
        }

        $this->files = $file;

        $this->load(file: $this->files);
    }

    /**
     * Get an environment variable value.
     *
     * Returns the value of the specified environment variable key.
     * If no key is specified, returns all loaded environment variables.
     *
     * @param string|null $key The environment variable key.
     * @param mixed $default The default value if the key is not found.
     * @return mixed The value of the environment variable or the default value.
     */
    public function get(string $key = null, mixed $default = null): mixed
    {
        if (!$this->loaded) $this->load(file: $this->files);

        if (is_null($key)) {
            return $this->global;
        }

        return $this->global[$key] ?? $default;
    }

    /**
     * Get a group of environment variables.
     *
     * Returns all environment variables that belong to the specified group.
     *
     *       [
     *         'APP' => [
     *           'APP_NAME'   => 'Dotenv'
     *           'APP_ENV'    => 'development',
     *           'APP_LOCALE' => 'en'
     *         ]
     *       ]
     *
     * @param string $group The group name.
     * @param mixed $default The default value if the group is not found.
     * @return mixed The group of environment variables or the default value.
     */
    public function group(string $group, mixed $default = []): mixed
    {
        if (!$this->loaded) $this->load(file: $this->files);

        return $this->group[$group] ?? $default;
    }

    /**
     * Find available environment files.
     *
     * Searches the default environment files in the project root directory.
     *
     * @return array The list of available environment files.
     */
    private function find(): array
    {
        $available = [];

        foreach ($this->defaults as $default) {
            if (file_exists($this->basepath . $default)) {
                $available[] = $default;
            }
        }

        return $available;
    }

    /**
     * Load environment variables from files.
     *
     * Loads environment variables from the specified file(s) and stores them
     * in the local and global arrays. If variables are already loaded, this method returns early.
     *
     * @param string|array|null $file The environment file(s) to load.
     * @return Dotenv Returns the current Dotenv instance.
     */
    public function load(string|array $file = null): Dotenv
    {
        if ($this->loaded) {
            return $this;
        }

        if (!empty($file)) {
            $this->validate(file: $file);
        }

        if (is_string($file)) {
            $file = [$file];
        }

        $file = array_merge($this->files, $file);

        $read = [];

        foreach ($file as $f) {

            $f = $this->basepath . $f;

            if (!file_exists($f)) {
                continue;
            }

            foreach (file($f) as $content) {
                $content = $this->trim(string: $content);

                if (str_contains($content, '#')) {
                    continue;
                }

                $exploded = explode('=', $content, 2);

                if (count($exploded) === 2) {
                    $key   = str_replace(
                        ['"', "'"],
                        '',
                        $this->trim(string: $exploded[0])
                    );

                    $value = str_replace(
                        ['"', "'"],
                        '',
                        $this->trim(string: $exploded[1])
                    );

                    $read[$key] = $value;

                    preg_match('/^[A-Za-z]+(?=_)/', $key, $matches);

                    if ($matches) {
                        $this->group[$matches[0]] = [$key => $value];
                    }
                }
            }
        }

        $read['REQUEST_SCHEME'] = $_SERVER['REQUEST_SCHEME'] ?? 'http';

        $this->store(envs: $read);

        $this->loaded = true;

        $this->update();

        return $this;
    }

    /**
     * Reloads environment variables from the specified files.
     *
     * Resets the local environment variables, reloads the environment
     * variables from the files, and updates the global environment variables. If a
     * default set of variables is provided, they are merged with the reloaded
     * variables.
     *
     * @param array|null $default An optional array of default environment variables to merge after reloading.
     * @return Dotenv.
     */
    public function reload(array $default = null): Dotenv
    {
        $this->local = [];

        $this->update();

        $this->load(file: $this->files);

        $this->update(default: $default);

        return $this;
    }

    /**
     * Update the environment variables.
     *
     * Updates the local and global environment variables and synchronizes them with
     * the PHP `$_SERVER` and `$_ENV` superglobals.
     *
     * @param array|null $default The default environment variables to merge.
     * @return void
     */
    private function update(array $default = null): void
    {
        $previousKeys = isset($_SERVER[$this->key])
            ? explode(',', $_SERVER[$this->key])
            : $this->keys;

        if (!empty($default)) {
            $this->local = array_merge($this->local, $default);
        }

        if (!empty($this->local)) {
            $this->match($this->local);

            $keys = [];

            foreach ($this->local as $key => $value) {
                putenv(sprintf('%s=%s', $key, $value));
                $keys[] = $key;
            }

            $this->keys = $keys;

            $diff = array_diff_key($previousKeys, $this->keys);

            if (!empty($diff)) {
                foreach ($diff as $key => $value) {
                    $this->unset(key: $key);
                }
            }

            $this->global = $_ENV = $this->local;

            $_SERVER += $this->global;
            $_SERVER[$this->key] = implode(',', $this->keys);
        }
    }

    /**
     * Unset an environment variable.
     *
     * Removes the specified environment variable from `$_SERVER`, `$_ENV`, and the process environment.
     *
     * @param string $key The environment variable key to unset.
     * @return void
     */
    private function unset(string $key): void
    {
        if (isset($_SERVER[$key])) unset($_SERVER[$key]);
        putenv($key);
        if (isset($_ENV[$key])) unset($_ENV[$key]);
    }

    /**
     * Store environment variables.
     *
     * Stores the environment variables in the local array after matching the keys against a pattern.
     *
     * @param array $envs The environment variables to store.
     * @return void
     */
    private function store(array $envs): void
    {
        foreach ($envs as $key => $value) {
            $this->match(vars: [$key => $value]);
            $this->local[$key] = $value;
        }
    }

    /**
     * Validate environment variable keys.
     *
     * Ensures that all environment variable keys match the allowed pattern.
     *
     * @param array $vars The array of environment variables to validate.
     * @return void
     * @throws \InvalidArgumentException If a variable key has an invalid format.
     */
    private function match(array $vars): void
    {
        foreach ($vars as $key => $value) {
            if (!preg_match('/^[a-zA-Z_][a-zA-Z_.]*$/', $key)) throw new \InvalidArgumentException("Not allowed variable key format is used");
        }
    }

    /**
     * Trim whitespace and unwanted characters from a string.
     *
     * Removes leading and trailing whitespace and reduces internal whitespace to a single space.
     *
     * @param string $string The string to trim.
     * @return string The trimmed string.
     */
    private function trim(string $string): string
    {
        return preg_replace('/\s+/', '', trim($string));
    }

    /**
     * Validate th file
     *
     * @param array<string>|string $file
     * @return void
     */
    private function validate(array|string $file): void
    {
        if (is_string($file)) {
            $file = [$file];
        }

        foreach ($file as $f) {
            $check = $this->basepath . $f;
            if (!file_exists($check)) throw new \InvalidArgumentException("$check doesn't exist");
        }
    }
}
