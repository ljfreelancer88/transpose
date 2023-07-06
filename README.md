# Very short description of the package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ljfreelancer88/transpose.svg?style=flat-square)](https://packagist.org/packages/ljfreelancer88/transpose)
[![Total Downloads](https://img.shields.io/packagist/dt/ljfreelancer88/transpose.svg?style=flat-square)](https://packagist.org/packages/ljfreelancer88/transpose)
![GitHub Actions](https://github.com/ljfreelancer88/transpose/actions/workflows/main.yml/badge.svg)

Tranponse chords using PHP. It's been used at [Collideborate](https://collideborate.me)

Note: Under Development :)

## Installation

You can install the package via composer:

```bash
composer require ljfreelancer88/transpose
php composer.phar require ljfreelancer88/transpose
```

## Usage

```php
use Ljfreelancer88\Transpose\Transpose;

$transposer = new Transpose($model->content);
$transposer->setKey($key);
$transposer->loadSong();
$transposedSong = $transposer->transpose($model->key, $key);

# OR

$transposer = new Transpose();
$transposer->setKey($key);
$transposer->loadSong($model->content);
$transposedSong = $transposer->transpose($model->key, $key);
```

### Testing

```bash
composer test

php vendor/bin/phpunit tests
php vendor/bin/psalm
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email lj88@duck.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
