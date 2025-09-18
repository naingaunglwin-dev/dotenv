<?php

namespace NAL\Dotenv\Loader;

use NAL\Dotenv\Exceptions\UnableToOpenFileException;
use NAL\Dotenv\Exceptions\UnsupportedFileTypeException;
use NAL\Dotenv\Parser\JsonParser;
use NAL\Dotenv\Parser\ParserInterface;

/**
 * Loader for `.json` environment files using the JsonParser.
 *
 * `JsonLoader` loads JSON files, parsing them using a
 * `JsonParser`, and populating environment variables.
 */
class JsonLoader extends BaseLoader
{
    /**
     * Loads and parses JSON environment files.
     *
     * Validates file extensions, reads contents, and parses them into `envs`
     * and `groups` using the associated parser. If the file cannot be opened
     * or is not a `.json` file, it throws a RuntimeException.
     *
     * @return array{
     *     envs: array<string, mixed>,
     *     groups: array<string, array<string, mixed>>,
     *}
     *
     * @throws UnsupportedFileTypeException If a non-JSON file is passed
     * @throws UnableToOpenFileException If a file cannot be opened.
     */
    public function load(): array
    {
        $loaded = [
            'envs' => [],
            'groups' => []
        ];

        foreach ($this->resolveFiles() as $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if ($extension !== 'json') {
                throw new UnsupportedFileTypeException(self::class, '.json', $file, $extension);
            }

            $handle = @fopen($file, 'r');

            if ($handle === false) {
                throw new UnableToOpenFileException($file); // @codeCoverageIgnore
            }

            $content = fread($handle, filesize($file));
            fclose($handle);

            [$envs, $groups] = $this->parser()->parse(
                $content,
                pathinfo($file, PATHINFO_FILENAME),
                $this->override,
                $loaded
            );

            $loaded['envs'] = array_merge($loaded['envs'], $envs);
            $loaded['groups'] = array_merge($loaded['groups'], $groups);
        }

        $this->save($loaded);

        return $loaded;
    }

    /**
     * Get the parser instance, lazily initializing if not provided.
     *
     * @return ParserInterface The dotenv parser instance.
     */
    protected function parser(): ParserInterface
    {
        if ($this->parser === null) {
            $this->parser = new JsonParser();
        }

        return parent::parser();
    }
}
