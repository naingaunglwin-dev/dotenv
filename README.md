<div align="center">

# Simple Dotenv Library
![Status](https://img.shields.io/badge/status-development-blue)
![License](https://img.shields.io/badge/license-MIT-green.svg)

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
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/naingaunglwin-dev/dotenv"
    }
  ],
  "require": {
    "naingaunglwin-dev/dotenv": "dev-master"
  }
}
```
- Run the following command in your terminal from the project's root directory:
```bash
composer install
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

require_once "vendor/autoload.php";;

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
