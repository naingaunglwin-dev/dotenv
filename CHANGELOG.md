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
