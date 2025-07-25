<?php

namespace NAL\Dotenv\Exceptions;

class MissingParser extends EnvRuntimeException
{
    public function __construct(string $parser, string $extension)
    {
        parent::__construct("Missing parser: '$parser' for extension $extension");
    }
}
