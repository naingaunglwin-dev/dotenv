# Changelog

## v1.0.1

### Added

- **Load**
  - Dotenv no longer autoload the `.env` file on creating a new instance by default.
  - To load the `.env` file on creating new instance, you now need to pass the second argument as `true` when instantiating the class.
  - You can now manually load the `.env` file by calling the `load()` method.
  - `load()` method return Dotenv class.
  - Add new method `getInGroup(string $group)`,
```php
// if you set your environment variables in group like this,
APP_URL='https://example.com/'
APP_KEY=1234567

// You can use `getInGroup` method to retrieve all of that same group variables,
$dotenv->getInGroup('APP');

// Output
array(2) {
    [0] =>
    string(7) "APP_URL"
    [1] =>
    string(7) "APP_KEY"
}
```

- **Remove deleted variables**
  - This includes removing deleted environment variables from `$_SERVER`, `$_ENV`, and `getenv()`.

## v1.0.2

### Changes
- Remove env variables manually set process.
- Dotenv now accept 2 parameters, `array|string` **$file** & `string` **$path**
- Dotenv will load default defined `env` files if user pass nothing to $file
```php
// Change method name

// from
$dotenv->getInGroup('APP');
// to
$dotenv->group('APP');

// from
$dotenv->restart();
// to
$dotenv->reload(); // Reload no longer accept default values
```

## v1.0.3

### Changes
- replace `file()` with `fopen()` for improved performance when reading env files
- The constructor now accepts three parameters:
  - `array|string|null` **$file**
  - `string|null` **$envKey**
  - `bool` **$overwrite**
- improved `load` method:
  - On production mode, env files are loaded only once (for performance)

### Added
- New method `has()` to check for the existence of an env variable:
```php
$dotenv->has('APP_LOCALE');
```
- You can now control whether to overwrite existing environment variables when loading multiple env files, via the $overwrite parameter.

## v2.0.0

### 🚀 Added
- **JSON File Support** — Load environment variables from structured `.json` files.
- **Custom Loader Registry** — Add support for user-defined loaders (e.g., YAML, XML).
- **Static Access** — Access env values directly using `Env::get()` or `Env::group()`.
- **Safe Loading** — Use `safeLoad()` to avoid exceptions on missing or malformed files.
- **Multiple File Support** — Load multiple `.env` and `.json` files in defined order.
- **Caching** — Enable in-memory caching for performance using `cache: true`.
- **LoaderRegistry** — Convention-based class resolution for parsers like `JsonParser`.

### 🛠️ Changed
- `Env::create()` no longer loads `.env` by default — use `->load()` explicitly.
- Parser resolution now follows a naming convention (`JsonParser`, `YamlParser`, etc.).
- Parser must implement `ParserInterface` and be registered for custom loaders.

### ⚠️ Breaking Changes
- The legacy `$dotenv = new Dotenv(...)->load()` style is replaced by `Env::create(...)->load()`.
- Parser resolution and loader registration are now required for custom file formats.

### ✅ Internal Improvements
- Improved performance by using `fopen()` instead of `file()` for reading files.
- Cleaner architecture with `BaseLoader`, `LoaderInterface`, and `LoaderRegistry`.
