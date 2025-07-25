<?php

namespace NAL\Dotenv\Exceptions;

class UnsupportedFileTypeException extends EnvRuntimeException
{
    public function __construct(string $class, string $support, string $extension, string $file)
    {
        parent::__construct(sprintf(
            "%s only supports loading %s files. Attempted to load a .%s file: '%s'",
            $class,
            $support,
            $extension,
            $file
        ));
    }
}
