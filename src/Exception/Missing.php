<?php

namespace NAL\Dotenv\Exception;

class Missing extends \InvalidArgumentException
{
    /**
     * File not found exception
     *
     * @param string $file
     * @return static
     */
    public static function file(string $file)
    {
        return new static(
            sprintf(
                "Missing %s file, does not exist or not a valid file",
                $file
            )
        );
    }
}
