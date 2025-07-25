<?php

namespace NAL\Dotenv\Parser;

use NAL\Dotenv\Exceptions\InvalidEnvKeyFormat;
use NAL\Dotenv\Exceptions\InvalidEnvLine;

class DotenvParser implements ParserInterface
{
    /**
     * @inheritDoc
     *
     * @throws InvalidEnvLine
     * @throws InvalidEnvKeyFormat
     */
    public function parse(string $content, string $filename, bool $override = false, array $previous = []): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $envs  = [];
        $groups = [];

        foreach ($lines as $index => $line) {
            if ($this->isSkippableLine($line)) {
                continue;
            }

            if (!str_contains($line, '=')) {
                throw new InvalidEnvLine($filename, $index);
            }

            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");

            if ((isset($envs[$key]) || isset($previous['envs'][$key])) && !$override) {
                continue;
            }

            if (!$this->validateKey($key)) {
                throw new InvalidEnvKeyFormat($key, $filename, $index);
            }

            $envs[$key] = $value;

            if ($separator = $this->isGroup($key)) {
                $name = strtok($key, $separator);

                $groups[$name][$key] = $value;
            }
        }

        return [$envs, $groups];
    }

    private function validateKey(string $key): bool
    {
        return (bool) preg_match("/^[a-zA-Z_][a-zA-Z_.]*$/", $key);
    }

    private function isSkippableLine(string $line): bool
    {
        return $line === '' || str_starts_with($line, '#');
    }

    private function isGroup(string $key): string
    {
        if (str_contains($key, "_")) {
            return "_";
        }

        if (str_contains($key, ".")) {
            return ".";
        }

        return false;
    }
}
