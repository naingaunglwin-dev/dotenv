<?php

namespace NAL\Dotenv\Parser;

use NAL\Dotenv\Exceptions\InvalidEnvKeyFormat;
use NAL\Dotenv\Exceptions\InvalidJson;

class JsonParser implements ParserInterface
{
    /**
     * @inheritDoc
     *
     * @throws InvalidJson
     */
    public function parse(string $content, string $filename, bool $override = false, array $previous = []): array
    {
        $decoded = json_decode($content, true);
        $envs    = [];
        $groups  = [];

        if (!is_array($decoded)) {
            throw new InvalidJson($filename);
        }

        $this->flatten($filename, $decoded, $envs, $groups, $previous);

        return [$envs, $groups];
    }

    /**
     * Recursively flatten nested array into ENV_KEY format.
     *
     * @throws InvalidEnvKeyFormat
     */
    private function flatten(
        string $filename,
        array $decoded,
        array &$envs,
        array &$groups,
        array $previous,
        string $prefix = ''
    ): void {
        foreach ($decoded as $key => $value) {
            $fullKey = strtoupper($prefix . $key);

            if (!$this->validateKey($fullKey)) {
                throw new InvalidEnvKeyFormat($fullKey, $filename);
            }

            if (is_array($value)) {
                $this->flatten($filename, $value, $envs, $groups, $previous, $fullKey . '_');
                continue;
            }

            // Override condition is unnecessary: JSON decoding keeps only the last occurrence of duplicate keys.
            $envs[$fullKey] = $value;

            if ($separator = $this->isGroup($fullKey)) {
                $group = strtok($fullKey, $separator);
                $groups[$group][$fullKey] = $value;
            }
        }
    }

    private function validateKey(string $key): bool
    {
        return (bool) preg_match("/^[A-Z_][A-Z0-9_]*$/", $key);
    }

    private function isGroup(string $key): string|false
    {
        if (str_contains($key, "_")) return "_";
        return false;
    }
}