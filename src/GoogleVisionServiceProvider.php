<?php

namespace Pkboom\GoogleVision;

use Illuminate\Support\ServiceProvider;
use Pkboom\GoogleVision\Facades\GoogleVision;
use Pkboom\GoogleVision\Exceptions\InvalidConfiguration;

class GoogleVisionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(GoogleVision::class, function () {
            $config = config('google-vision');

            $this->guardAgainstInvalidConfiguration($config);

            return GoogleVisionFactory::create();
        });

        $this->app->alias(GoogleVision::class, 'laravel-google-vision');

        $this->mergeConfigFrom(__DIR__.'/../config/google-vision.php', 'google-vision');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/google-vision.php' => config_path('google-vision.php'),
        ]);
    }

    protected function guardAgainstInvalidConfiguration(array $config = null)
    {
        $credentials = $config['service_account_credentials_json'];

        if (!is_array($credentials) && !is_string($credentials)) {
            throw InvalidConfiguration::credentialsTypeWrong($credentials);
        }

        if (is_string($credentials) && !file_exists($credentials)) {
            throw InvalidConfiguration::credentialsJsonDoesNotExist($credentials);
        }
    }
}
