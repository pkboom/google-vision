# Manage Google Vision for Laravel applications

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

### Detect Text

You can detect things from images.

```php
use Pkboom\GoogleVision\GoogleVisionFactory;

$googleVision = GoogleVisionFactory::create();

// Detect texts and handwriting
$result = $googleVision->text($imagePath);

// Detect logos
$result = $googleVision->logo($imagePath);

// Detect cropHints
$result = $googleVision->cropHints($imagePath);

// Detect document
$result = $googleVision->document($imagePath);

// Detect faces
$result = $googleVision->face($imagePath, $extension, $outputPath);

// Detect image properties
$result = $googleVision->imageProperty($imagePath);

// Detect image labels
$result = $googleVision->label($imagePath);

// Detect image landmarks
$result = $googleVision->landmark($imagePath);

// Detect images
$result = $googleVision->object($imagePath);

// Detect explicit content
$result = $googleVision->safeSearch($imagePath);

// Detect Web entities and pages
$result = $googleVision->web($imagePath);
```

### Detect pdf

You can detect pdf and store it as Json on Google Cloud Storage.

```php
use Pkboom\GoogleVision\GoogleVisionFactory;

$path = 'gs://your-bucket/file.pdf';
$output = 'gs://your-bucket/results/';

$googleVision = GoogleVisionFactory::create();

$googleStorage->pdf($path, $output);
```

## License

The MIT License (MIT). Please see [MIT license](http://opensource.org/licenses/MIT) for more information.
