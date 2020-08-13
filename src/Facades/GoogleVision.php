<?php

namespace Pkboom\GoogleVision\Facades;

use Illuminate\Support\Facades\Facade;

class GoogleVision extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-google-vision';
    }
}
