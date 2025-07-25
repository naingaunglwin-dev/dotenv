<div align="center">

# Simple Dotenv Library
![Status](https://img.shields.io/badge/test-pass-green)
![Status](https://img.shields.io/badge/coverage-100%25-green)
![License](https://img.shields.io/badge/license-MIT-blue.svg)

</div>

## About
`NAL\Dotenv` is a flexible and lightweight PHP environment loader designed for modern applications.
It supports loading environment variables from `.env` and `.json` files by default, with the ability to register custom loaders (e.g., YAML, XML) effortlessly.

**This package provides:**

- Grouped access to related environment keys
- Support for multiple environment files
- Static or instance-based access
- Optional caching for performance
- Convention-based parser resolution
- Seamless integration with custom loaders and parsers

It's ideal for both small-scale projects and larger applications where managing structured and multi-source environment data is essential.

## Contributing
- This is an open-source library, and contributions are welcome.
- If you have any suggestions, bug reports, or feature requests, please open an issue or submit a pull request on the project repository.

## Requirement
- **PHP** version 8.2 or newer is required

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
✅ **Load .env files easily**
- Supports standard .env key-value files for environment configuration.

✅ **Support for .json config files**
- Load structured environment data from JSON files — great for frontend-backend monorepo setups.

✅ **Nested key flattening**
- Nested JSON keys are automatically flattened into uppercase ENV_STYLE_KEYS.

✅ **Environment grouping support**
- Automatically organizes keys into groups based on naming conventions like APP_ENV → APP group.

✅ **Custom Loader Registry**
- Register your own loaders to support formats like YAML, TOML, etc.

✅ **Optional override behavior**
- Configure whether later files can override previously loaded keys.

✅ **Safe loading**
- Use `safeLoad()` to prevent exceptions when loading optional or missing files.

✅ **Supercharged access methods**
- Access environment values using `Env::get('KEY')` or grouped access with `Env::group('APP')`.

✅ **Supports multiple files**
- Pass an array of files to load them in order — useful for layering config.

## Usage

```php
<?php

use NAL\Dotenv\Env;

$env = Env::create()->load();

// Access values
$envs = $env->get('APP_ENV');           // "production"
$group = $env->group('DATABASE');      // ['DATABASE_HOST' => '127.0.0.1', ...]
```

#### Static Access
You can also use static calls without manually creating an instance:
```php
<?php

use NAL\Dotenv\Env;

$value = Env::get('APP_ENV');
$group = Env::group('APP');
```
> If no instance was created yet, Env::get() and Env::group() will auto-initialize using default behavior `(.env, no cache)`. If an instance was previously created via Env::create(), the static calls will reuse that instance instead.

#### Safe Load (No Exceptions)
```php
<?php

use NAL\Dotenv\Env;

$env = Env::create()->safeLoad(); // Returns ['envs' => [], 'groups' => []] on failure
```

#### Load JSON Configuration
```php
<?php

use NAL\Dotenv\Env;

$env = Env::create(name: 'env.json')->load();
```

#### Load Multiple Files
```php
<?php

use NAL\Dotenv\Env;

Env::create(name: ['.env', '.env.testing'])->load();
```

#### Enable Caching
```php
<?php

use NAL\Dotenv\Env;

Env::create(name: '.env', cache: true)->load(); // Loads once, reuses from memory
```

#### Register Custom Loader (e.g., YAML)
```php
<?php

use NAL\Dotenv\Loader\LoaderRegistry;

$registry = new LoaderRegistry();

$registry->register('yaml', fn ($file, $override, $resolver, $parser) => new YamlLoader(
    $file, $override, $resolver, $parser
), '\\Namespace\\For\\Parser');

Env::create(name: 'config.yaml', registry: $registry)->load();
```

**Notes for Custom Loader Development**
- The registered callback must accept exactly 4 arguments in this order:
```php
($file, $override, $resolver, $parser)
```
- Your custom loader **must**:

  - Implement `NAL\Dotenv\Loader\LoaderInterface`

    **or**

  - Extend `NAL\Dotenv\Loader\BaseLoader` for convenient utilities like `resolveFiles()` and `save()`.

- Every custom loader must include a corresponding parser, whose class name:

  - **Starts with the capitalized extension name**, followed by Parser
    **e.g., for .yaml, the parser must be YamlParser**

  - **Must implement** `NAL\Dotenv\Parser\ParserInterface`

> Parsers are resolved by convention from the namespace `\NAL\Dotenv\Parser\`, or a custom namespace if provided.

## Running Tests

- To run the test suite, execute the following command
```bash
vendor/bin/phpunit tests/EnvTest.php
```
- This will run all test cases defined in the EnvTest.php file.
