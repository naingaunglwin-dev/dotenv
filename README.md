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
- You can just download the code from repo and use or download using composer.

### Download Using Composer
- If you don't have composer, install [composer](https://getcomposer.org/download/) first.
- create file `composer.json` at your project root directory.
- Add this to `composer.json`
```php
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
- **Base Path Handling**: The base path of the project is automatically set to the root level of the project, but you can provide a custom base path when initializing the class.
- **Environment Groups**: Group environment variables based on their prefix.
- **File Validation**: Ensures that the specified .env file(s) exist in the project directory.
- **Environment Update**: Synchronizes environment variables with the PHP $_SERVER and $_ENV superglobals.

## Configuration
- You can customize the base path for locating environment files by passing it to the constructor:
```php
$dotenv = new Dotenv(null, '/custom/path/');
```

- If no file is provided, the Dotenv class will search for the following default files in the specified or root directory:

    - .env
    - .env.local
    - .env.development
    - .env.production
    - .env.dev
    - .env.prod

## Usage

### Loading Environment Variables
- To load environment variables, simply initialize the Dotenv class.
- By default, it looks for common .env files (such as .env, .env.local, .env.production, etc.) in the project root directory.

```php
<?php

require_once "vendor/autoload.php";

use NAL\Dotenv\Dotenv;

// Initialize Dotenv and load environment variables
$dotenv = new Dotenv();
```

- You can also specify custom environment files to load by passing the file name(s) to the constructor:
```php
$dotenv = new Dotenv('.env.testing');
```

- Or load multiple files:
```php
$dotenv = new Dotenv(['.env.dev', '.env.prod']);
```

### Accessing Environment Variables
- Once the environment files are loaded, you can retrieve environment variables using the get() method. If no key is provided, it will return all variables.

```php
// Get a specific variable
$appName = $dotenv->get('APP_NAME');

// Get all variables
$envVariables = $dotenv->get();
```

- You can also provide a default value if the key is not found:
```php
$debugMode = $dotenv->get('DEBUG_MODE', false);
```

### Grouping Environment Variables
- You can access grouped environment variables with the group() method.
- This can be useful if you have variables structured by prefix (e.g., APP_NAME, APP_ENV).

```php
$appGroup = $dotenv->group('APP');
```

### Reloading Environment Variables
- You can reload environment variables by calling the `reload()` method. This method clears the previously loaded variables and reloads them from the specified files.
```php
$dotenv->reload();
```

## Exception Handling
- The Dotenv class throws the following exceptions:

  - **NAL\Dotenv\Exception\Missing**: Thrown when the specified environment file does not exist.
  - **NAL\Dotenv\Exception\UnMatch**: Thrown when an environment variable key does not match the expected format.

### Example
```php
use NAL\Dotenv\Dotenv;

try {
    // Initialize dotenv
    $dotenv = new Dotenv();

    // Load variables from a specific .env file
    $dotenv->load('.env');

    // Get environment variables
    $dbHost = $dotenv->get('DB_HOST', 'localhost');

    // Get all environment variables
    $allEnv = $dotenv->get();

} catch (\Exception $e) {
    echo 'Error loading environment variables: ' . $e->getMessage();
}
```
