<?php

namespace NAL\Dotenv\Loader;

use NAL\Dotenv\Exceptions\UnableToOpenFileException;
use NAL\Dotenv\Exceptions\UnsupportedFileTypeException;
use NAL\Dotenv\Parser\DotenvParser;
use NAL\Dotenv\Parser\ParserInterface;

/**
 * Loader for `.env` files using the DotenvParser.
 *
 * DotenvLoader handles loading `.env` files, parsing them using a
 * `DotenvParser`, and populating environment variables.
 */
class DotenvLoader extends BaseLoader
{
    /**
     * Load and parse all configured `.env` files.
     *
     * This resolves all file paths, validates the file extensions,
     * reads and parses the contents using the configured parser, and
     * updates PHP environment variables.
     *
     * @throws UnsupportedFileTypeException If the file is not `.env`
     * @throws UnableToOpenFileException If the file cannot be opened
     * @return array{envs: array<string, mixed>, groups: array<string, array<string, mixed>>}
     */
    public function load(): array
    {
        $loaded = [
            'envs' => [],
            'groups' => [],
        ];

        foreach ($this->resolveFiles() as $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);

            if ($extension === 'json') {
                throw new UnsupportedFileTypeException(self::class, '.env',$file, $extension);
            }

            $handle = @fopen($file, 'r');

            if ($handle === false) {
                throw new UnableToOpenFileException($file); // @codeCoverageIgnore
            }

            $contents = fread($handle, filesize($file));
            fclose($handle);

            [$envs, $groups] = $this->parser()->parse(
                $contents,
                pathinfo($file, PATHINFO_FILENAME),
                $this->override,
                $loaded
            );

            $loaded['envs'] = array_merge($loaded['envs'], $envs);
            $loaded['groups'] = array_merge($loaded['groups'], $groups);
        }

        $this->save($loaded['envs']);

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
            $this->parser = new DotenvParser();
        }

        return parent::parser();
    }
}
