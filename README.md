<div align="center">

# Simple Dotenv Library
![Status](https://img.shields.io/badge/test-pass-green)
![Status](https://img.shields.io/badge/coverage-100%25-green)
![License](https://img.shields.io/badge/license-MIT-blue.svg)

</div>

## About
The `NAL\Dotenv` class is a lightweight PHP package for managing environment variables.
It allows you to load environment variables from files, group variables, and access them in your PHP application.
The class supports loading variables from multiple files.

## Contributing
- This is an open-source library, and contributions are welcome.
- If you have any suggestions, bug reports, or feature requests, please open an issue or submit a pull request on the project repository.

## Requirement
- **PHP** version 8.0 or newer is required

## Installation & Setup

### Using Composer
> If Composer is not installed, follow the [official guide](https://getcomposer.org/download/).

1. Create a `composer.json` file at your project root directory (if you don't have one):
```json
{
  "require": {
    "naingaunglwin-dev/dotenv": "^1.0"
  }
}
```

- Run the following command in your terminal from the project's root directory:
```bash
composer install
```

If you already have `composer.json` file in your project, just run this command in your terminal,
```bash
composer require naingaunglwin-dev/dotenv
```

## Features

- ✅ Automatically uses the project root, but allows setting a custom path when initializing.
- ✅ Load from multiple .env files
- ✅ Customize file names and base path
- ✅ Support for grouped environment variables
- ✅ Auto-sync with `$_ENV`, `$_SERVER`, and `getenv()`
- ✅ Reload environment at runtime
- ✅ Check if a variable exists with `has()`
- ✅ Supports default fallback values

## Constructor Signature
```php
  public function __construct(
      array|string|null $files = null,
      string|null $envKey = null,
      bool $overwrite = true
  )
```
- `$files`: Optional string or array of .env file names. Defaults to standard files like `.env`, `.env.local`, etc.
- `$envKey`: Custom key for detecting the app environment (e.g., `APP_ENV`)
- `$overwrite`: Whether to overwrite existing variables (true by default)


- If no file is provided, the Dotenv class will search for the following default files in root directory:
  - .env
  - .env.local,
  - .env.development
  - .env.production
  - .env.testing
  - .env.dev
  - .env.prod
  - .env.test
  - .env.staging

## Usage

### Loading Environment Variables
- You can pass your environment file(s) with either a full path or just the file name.
- If you pass a full path, the file will be loaded directly from that path.
- If you pass just the file name, it will be resolved relative to the project root directory.

```php
<?php

use NAL\Dotenv\Dotenv;

// Load a file using its full path
$dotenv = new Dotenv('/home/user/project/config/.env.custom');

// Load a file from the project root by name
$dotenv = new Dotenv('.env.production');

// Load multiple files
$dotenv = new Dotenv(['.env.local', '/home/user/project/.env.override']);
```

### Accessing Environment Variables

```php
// Get a specific variable
$dotenv->load();

$host = $dotenv->get('DB_HOST');             // Get a variable
$debug = $dotenv->get('DEBUG', false);       // With fallback
$all = $dotenv->get();                       // Get all loaded variables
```

### Check Existence
```php
if ($dotenv->has('APP_SECRET')) {
    // APP_SECRET is defined
}
```

### Grouping Environment Variables
- You can access grouped environment variables with the group() method.
- This can be useful if you have variables structured by prefix (e.g., APP_NAME, APP_ENV).

```php
$grouped = $dotenv->group('APP');
// e.g., ['APP_NAME', 'APP_ENV', 'APP_KEY']
```

### Reloading Environment Variables
- You can reload environment variables by calling the `reload()` method. This method clears the previously loaded variables and reloads them from the specified files.
```php
$dotenv->reload(); // Clear and reload loaded files
```

## Exception Handling
- The Dotenv class throws the following exceptions:

  - **NAL\Dotenv\Exception\Missing**: Thrown when the specified environment file does not exist.
  - **NAL\Dotenv\Exception\UnMatch**: Thrown when an environment variable key does not match the expected format.

### Example
```php
<?php

use NAL\Dotenv\Dotenv;

try {
    $dotenv = new Dotenv(['.env']);
    $dotenv->load();

    $dbUser = $dotenv->get('DB_USER', 'root');

} catch (\Exception $e) {
    echo 'Dotenv Error: ' . $e->getMessage();
}
```

## Running Tests

- To run the test suite, execute the following command
```bash
vendor/bin/phpunit tests/DotenvTest.php
```
- This will run all test cases defined in the DotenvTest.php file.
