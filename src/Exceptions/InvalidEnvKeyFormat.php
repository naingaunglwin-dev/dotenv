<?php

namespace NAL\Dotenv\Exceptions;

class InvalidEnvKeyFormat extends EnvRuntimeException
{
    public function __construct(string $key, string $file, string $line = '')
    {
        parent::__construct("Invalid env key: $key in $file" . ($line !== '' ? "($line)" : ''));
    }
}
