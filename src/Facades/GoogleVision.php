<?php

namespace Illuminate\Support\Facades;

use Pkboom\GoogleVision\GoogleVision;

class Queue extends Facade
{
    protected static function getFacadeAccessor()
    {
        return GoogleVision::class;
    }
}
