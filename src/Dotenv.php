<?php

namespace NAL\Dotenv;

use Nal\Dotenv\Exception\NotAllowVarnameFormat;
use Nal\Dotenv\Exception\PathNotFoundException;

class Dotenv
{
    /**
     * Env File
     *
     * @var string
     */
    private string $file;

    /**
     * Local env data
     *
     * @var array
     */
    private array $local = [];

    /**
     * $_ENV
     *
     * @var array
     */
    private array $global = [];

    /**
     * Constructor for the Dotenv class.
     *
     * @param string $file The path to the .env file.
     */
    public function __construct(string $file)
    {
        $this->file = $file;

        $this->load($this->file);

        $this->updateGlobalEnv();
    }

    /**
     * Retrieve the value of the specified environment variable by key.
     *
     * @param string $key The key of the environment variable.
     * @return mixed The value of the specified environment variable if it exists, otherwise null.
     */
    public function get(string $key): mixed
    {
        if (isset($this->global[$key])) {
            return $this->global[$key];
        }

        return null;
    }

    /**
     * Set a new environment variable or update an existing one.
     *
     * @param string $key The key (variable name) to set or update.
     * @param mixed $value The value to assign to the variable.
     * @param bool|null $overwrite If true, the variable will be updated if it exists, otherwise it won't be updated.
     *
     * @return void
     */
    public function set(string $key, mixed $value, bool $overwrite = null): void
    {
        if ($overwrite === null) {
            $overwrite = false;
        }

        $this->store([$key => $value], $overwrite);

        $this->updateGlobalEnv();
    }

    /**
     * Restart the environment variables with given defaults.
     *
     * @param array|null $default An array of default variables.
     * @return void
     */
    public function restart(array $default = null)
    {
        $this->local = [];

        $this->updateGlobalEnv($default);

        $this->load($this->file);

        $this->updateGlobalEnv($default);
    }

    /**
     * Load the variables from the .env file.
     *
     * @param  string $file The path to the .env file.
     * @throws PathNotFoundException If the specified file does not exist.
     * @throws NotAllowVarnameFormat If a variable name does not match the allowed pattern.
     * @return void
     */
    private function load(string $file): void
    {
        if (!file_exists($file)) {
            throw new PathNotFoundException("File not found : $file");
        }

        if (!is_file($file)) {
            throw new PathNotFoundException("Not a valid file: $file");
        }

        $array = file($file);
        $contents = [];

        foreach ($array as $content) {

            $content = trim($content);

            if (str_contains($content, '#')) {
                continue;
            }

            if (empty($content)) {
                continue;
            }

            $e = explode("=", $content);

            if (count($e) === 2) {
                $key   = trim($e[0]);
                $value = trim($e[1]);

                $key   = $this->removeQuotes($key);
                $value = $this->removeQuotes($value);

                $contents[$key] = $value;
            }
        }

        $this->store($contents);
    }

    /**
     * Store and update the environment variables.
     *
     * @param array $vars An array of variables to store.
     * @param bool|null $overwrite
     * @return void
     */
    private function store(array $vars, bool $overwrite = null): void
    {
        if ($overwrite === null) {
            $overwrite = false;
        }

        foreach ($vars as $name => $value) {
            if ($this->match($name)) {
                if ($overwrite) {
                    $this->local[$name] = $value;
                } else {
                    if (!isset($this->local[$name])) {
                        $this->local[$name] = $value;
                    }
                }
            }
        }
    }

    /**
     * Matches the variable name(s) against the allowed pattern.
     *
     * @param string|array $vars The variable name(s) to match against the pattern.
     *
     * @throws NotAllowVarnameFormat If any variable name doesn't match the allowed pattern.
     *
     * @return bool True if the variable name(s) match the pattern or if an empty array is passed, otherwise false.
     */
    private function match(string|array $vars): bool
    {
        if (is_array($vars)) {
            if (empty($vars)) {
                return true;
            }
        }

        $array = [];

        if (is_string($vars)) {
            $array[$vars] = "";
        }

        foreach ($array as $name => $value) {
            if (!preg_match('/^[a-zA-Z_][a-zA-Z_.]*$/', $name)) {
                throw new NotAllowVarnameFormat("Var name $name doesn't match with allowed Var name pattern");
            }
        }

        return true;
    }

    /**
     * Update the global environment variables.
     *
     * @param array|null $default An array of default variables.
     * @return void
     */
    private function updateGlobalEnv(array $default = null): void
    {
        if (!empty($default)) {
            $this->local = array_merge($this->local, $default);
        }

        if ($this->match($this->local)) {
            $_ENV = $this->local;
            $this->global = $_ENV;

            $_SERVER += $this->global;
        }
    }

    /**
     * Remove Double Quote & Single Quote from string
     *
     * @param $value
     * @return string
     */
    private function removeQuotes($value): string
    {
        return str_replace(['"', "'"], '', $value);
    }
}
