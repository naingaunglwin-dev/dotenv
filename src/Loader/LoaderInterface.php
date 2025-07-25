<?php

namespace NAL\Dotenv\Loader;

interface LoaderInterface
{
    /**
     * Load environment variables and groups from file(s).
     *
     * @return array{
     *     envs: array<string, mixed>,
     *     groups: array<string, array<string, mixed>>
     * }
     *     An associative array containing:
     *     - `envs`: Flattened key-value environment variables.
     *     - `groups`: Grouped environment variables, if applicable.
     */
    public function load(): array;
}
