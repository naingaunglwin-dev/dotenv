<?php

namespace NAL\Dotenv\Exceptions;

class InvalidJson extends EnvRuntimeException
{
    public function __construct(string $file)
    {
        parent::__construct("Invalid JSON in '$file'");
    }
}
