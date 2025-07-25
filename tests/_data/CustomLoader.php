<?php

namespace NALDotenvTests\_data;

use NAL\Dotenv\Loader\LoaderInterface;

class CustomLoader implements LoaderInterface
{
    public function load(): array
    {
        return [];
    }
}
