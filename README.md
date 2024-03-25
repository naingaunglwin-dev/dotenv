<div align="center">

# Simple Dotenv Library
![Status](https://img.shields.io/badge/test-pass-green)
![Status](https://img.shields.io/badge/coverage-100%25-green)
![License](https://img.shields.io/badge/license-MIT-blue.svg)

</div>

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

## Usage
- Create `.env` file in your project (preferably at the project root directory);
- Add environment variables to that `.env` file.
```dotenv
BASE_URL="https://example.com/my_url"
```

- In your php file,
```php
<?php

require_once "vendor/autoload.php";

use NAL\Dotenv\Dotenv;

$dotenv = new Dotenv('path/to/your/file/.env');

// Set env variable dynamically,
$dotenv->set('ENVIRONMENT', 'DEVELOPMENT');

// Get env variable value,
$dotenv->get('ENVIRONMENT'); //DEVELOPMENT
$dotenv->get('BASE_URL'); //"https://example.com/my_url"

// Restart your dotenv process,
// you can pass default values with restart process,
$dotenv->restart(['LOCALE' => 'en']);
```

- **Available Only In Version 1.0.1 (Review CHANGELOG.md for more changes)**

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
