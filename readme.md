# Manage Google Vision for Laravel applications

[![Latest Stable Version](https://poser.pugx.org/pkboom/google-vision/v)](//packagist.org/packages/pkboom/google-vision)
This package makes working with Google Vision a breeze. Once it has been set up you can do these things:

```php
use Pkboom\GoogleVision\GoogleVisionFactory;

$googleVision = GoogleVisionFactory::create();

$result = $googleVision->text($imagePath);

// statically
use Pkboom\GoogleVision\Facades\GoogleVision;

GoogleVision::face($imagePath);
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

### Detect text or handwriting

```php
use Pkboom\GoogleVision\GoogleVisionFactory;

$googleVision = GoogleVisionFactory::create();

$result = $googleVision->text($imagePath);
```

### Detect logos

```php
use Pkboom\GoogleVision\GoogleVisionFactory;

$googleVision = GoogleVisionFactory::create();

$result = $googleVision->logo($imagePath);

// with extension
$result = $googleVision->logo($imagePath, $imageExtension);

// with file output
$result = $googleVision
    ->output($outputFilePath);
    ->logo($imagePath);
```

### Detect crop hints

```php
use Pkboom\GoogleVision\GoogleVisionFactory;

$googleVision = GoogleVisionFactory::create();

$result = $googleVision->cropHints($imagePath);
```

### Detect document

```php
use Pkboom\GoogleVision\GoogleVisionFactory;

$googleVision = GoogleVisionFactory::create();

$result = $googleVision->document($imagePath);
```

### Detect face

```php
use Pkboom\GoogleVision\GoogleVisionFactory;

$googleVision = GoogleVisionFactory::create();

$result = $googleVision->face($imagePath, $extension, $outputPath);

// with extension
$result = $googleVision->face($imagePath, $imageExtension);

// with file output
$result = $googleVision
    ->output($outputFilePath);
    ->face($imagePath);
```

### Detect image properties

```php
use Pkboom\GoogleVision\GoogleVisionFactory;

$googleVision = GoogleVisionFactory::create();

$result = $googleVision->imageProperty($imagePath);
```

### Detect image labels

```php
use Pkboom\GoogleVision\GoogleVisionFactory;

$googleVision = GoogleVisionFactory::create();

$result = $googleVision->label($imagePath);

```

### Detect landmarks

```php
use Pkboom\GoogleVision\GoogleVisionFactory;

$googleVision = GoogleVisionFactory::create();

$result = $googleVision->landmark($imagePath);

// with extension
$result = $googleVision->landmark($imagePath, $imageExtension);

// with file output
$result = $googleVision
    ->output($outputFilePath);
    ->landmark($imagePath);
```

### Detect objects

```php
use Pkboom\GoogleVision\GoogleVisionFactory;

$googleVision = GoogleVisionFactory::create();

$result = $googleVision->object($imagePath);
```

### Detect explicit content

```php
use Pkboom\GoogleVision\GoogleVisionFactory;

$googleVision = GoogleVisionFactory::create();

$result = $googleVision->safeSearch($imagePath);
```

### Detect Web entities and pages

```php
use Pkboom\GoogleVision\GoogleVisionFactory;

$googleVision = GoogleVisionFactory::create();

$result = $googleVision->web($imagePath);

// with geo results
$result = $googleVision
    ->includeGeoResult()
    ->web($imagePath);
```

### Detect pdf

You can detect pdf and store it as Json on Google Cloud Storage. Destination is set to `gs://your-bucket/results` by default.

```php
use Pkboom\GoogleVision\GoogleVisionFactory;

$path = 'gs://your-bucket/file.pdf';
$output = 'gs://your-bucket/any/';

$googleVision = GoogleVisionFactory::create();

$googleStorage->pdf($path, $output);

// with destination
$googleStorage
    ->to($output)
    ->pdf($path, $output);
```
