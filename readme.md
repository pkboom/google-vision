# Manage Google Vision

[![Latest Stable Version](https://poser.pugx.org/pkboom/google-vision/v/stable)](https://packagist.org/packages/pkboom/google-vision)
[![Build Status](https://travis-ci.com/pkboom/google-vision.svg?branch=master)](https://travis-ci.com/pkboom/google-vision)

This package makes working with Google Vision a breeze. Once it has been set up you can do these things:

```php
use Pkboom\GoogleVision\GoogleVisionFactory;

$googleVision = GoogleVisionFactory::create();

$result = $googleVision->text($imagePath);
```

## Installation

You can install the package via composer:

```bash
composer require pkboom/google-vision
```

You must publish the configuration with this command:

```bash
php artisan vendor:publish --provider="Pkboom\GoogleVision\GoogleVisionServiceProvider"
```

This will publish a file called google-vision.php in your config-directory with these contents:

```php
return [
    /*
     * Path to the json file containing the credentials.
     */
    'service_account_credentials_json' => storage_path('app/service-account/credentials.json'),
];
```

## How to obtain the credentials to communicate with Google Vision

[how to obtain the credentials to communicate with google calendar](https://github.com/spatie/laravel-google-calendar#how-to-obtain-the-credentials-to-communicate-with-google-calendar)

[google vision set up](https://cloud.google.com/vision/docs/setup)

## Usage

```php
$skeleton = new pkboom\GoogleVision();
echo $skeleton->echoPhrase('Hello, pkboom!');
```

## Requirements

### Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [MIT license](http://opensource.org/licenses/MIT) for more information.
