<?php

namespace NAL\Dotenv\Parser;

interface ParserInterface
{
    /**
     * Parse raw environment content into env variables and groups.
     *
     * @param string $content  Raw content from the environment file.
     * @param string $filename The filename (used for error reporting or context).
     * @param bool   $override Whether to override existing variables.
     * @param array  $previous Previously loaded environment data to merge or reference.
     *
     * @return array{
     *     envs: array<string, string>,  // Flat key-value environment variables
     *     groups: array<string, string> // Grouped environment variables
     * }
     */
    public function parse(string $content, string $filename, bool $override = false, array $previous = []):array;
}
