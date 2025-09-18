<?php

namespace NAL\Dotenv;

class PathResolver
{
    const DS = DIRECTORY_SEPARATOR;

    /**
     * PathResolver
     *
     * @param string $basepath
     */
    public function __construct(private string $basepath = '',)
    {
        $this->basepath = $basepath ?: dirname(__DIR__, 5);
    }

    /**
     * @param string $path path to resolve
     *
     * @return string
     */
    public function resolve(string $path): string
    {
        $path = self::normalize($path);

        if (file_exists($path)) {
            return $path;
        }

        $newPath = self::join($this->basepath, $path);

        if (!file_exists($newPath)) {
            throw new \RuntimeException("Unable to locate $path");
        }

        return $newPath;
    }

    /**
     * Join multiple path segments into one normalized path.
     *
     * Example:
     * ```
     * Path::join('/var', 'www', 'html'); // returns "/var/www/html"
     * Path::join('C:\\', 'Users', 'Public'); // returns "C:\Users\Public" on Windows
     * ```
     *
     * @param string ...$paths One or more path segments.
     *
     * @return string The joined, normalized path.
     */
    public static function join(string ...$paths): string
    {
        if (empty($paths)) {
            return ''; // @codeCoverageIgnore
        }

        $first = array_shift($paths);
        $first = rtrim($first, self::DS);

        $segments = [$first];

        foreach ($paths as $path) {
            $segments[] = static::normalize($path);
        }

        $joined = implode(self::DS, $segments);

        if (empty($joined)) {
            return $joined; // @codeCoverageIgnore
        }

        return static::isAbsolute($first) ? $joined : self::DS . ltrim($joined, self::DS);
    }

    /**
     * Check if a given path is absolute.
     *
     * Supports both Unix-like absolute paths (starting with "/")
     * and Windows absolute paths (e.g., "C:\").
     *
     * @param string $path The path to check.
     * @return bool True if path is absolute, false otherwise.
     */
    public static function isAbsolute(string $path): bool
    {
        $isAbsolute = false;

        // Normalize first segment and detect if it's absolute
        $path = str_replace(['/', '\\'], self::DS, $path);
        if (str_starts_with($path, self::DS) || preg_match('/^[A-Za-z]:\\\\/', $path)) {
            $isAbsolute = true;
        }

        return $isAbsolute;
    }

    /**
     * Normalize a path segment by replacing slashes with directory separator
     * and trimming leading and trailing directory separators.
     *
     * @param string $path The path segment to normalize.
     *
     * @return string Normalized path segment without leading/trailing slashes.
     */
    public static function normalize(string $path): string
    {
        if (self::isAbsolute($path)) {
            return str_replace(['/', '\\'], self::DS, $path);
        }

        return trim(str_replace(['/', '\\'], self::DS, $path), self::DS);
    }
}
