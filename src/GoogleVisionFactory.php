<?php

namespace Pkboom\GoogleVision;

use Google\Cloud\Vision\V1\ImageAnnotatorClient;

class GoogleVisionFactory
{
    public static function create()
    {
        $credentials = [
            'credentials' => config('google-vision.service_account_credentials_json'),
        ];

        $imageAnnotator = new ImageAnnotatorClient($credentials);

        return new GoogleVision($imageAnnotator);
    }
}
