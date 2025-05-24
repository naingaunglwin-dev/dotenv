<?php

namespace NAL\Dotenv\Exception;

class UnMatch extends \OutOfBoundsException
{
    /**
     * Unmatched var key format exception
     *
     * @param string $key
     * @param string $format
     * @param string $file
     * @return static
     */
    public static function varNameFormat(string $key, string $format, string $file): static
    {
        return new static(
            sprintf(
                "'%s' format is unmatched with allowed format %s in %s",
                $key,
                $format,
                $file
            )
        );
    }
}
