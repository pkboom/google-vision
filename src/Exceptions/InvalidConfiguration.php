<?php

namespace Pkboom\GoogleVision\Exceptions;

use Exception;

class InvalidConfiguration extends Exception
{
    public static function credentialsJsonDoesNotExist(string $path)
    {
        return new static("Could not find a credentials file at `{$path}`.");
    }

    public static function credentialsTypeWrong($credentials)
    {
        return new static(sprintf('Credentials should be an array or the path of json file. "%s was given.', gettype($credentials)));
    }
}
