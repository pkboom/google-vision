<?php

namespace Pkboom\GoogleVision;

use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\GcsSource;
use Google\Cloud\Vision\V1\InputConfig;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\ImageContext;
use Google\Cloud\Vision\V1\OutputConfig;
use Google\Cloud\Vision\V1\GcsDestination;
use Google\Cloud\Vision\V1\WebDetectionParams;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\AsyncAnnotateFileRequest;

class GoogleVision
{
    public $imageAnnotator;

    public $storage;

    public $output;

    public static $likelihood = [
            'UNKNOWN',
            'VERY_UNLIKELY',
            'UNLIKELY',
            'POSSIBLE',
            'LIKELY',
            'VERY_LIKELY',
    ];

    public $includeGeo = false;

    public function __construct(ImageAnnotatorClient $imageAnnotator)
    {
        $this->imageAnnotator = $imageAnnotator;
    }

    public function text($path)
    {
        $response = $this->imageAnnotator->textDetection(file_get_contents($path));
        $texts = $response->getTextAnnotations();

        return collect($texts)->map(fn($text) => $text->getDescription());
    }

    public function logo($path, $extension = null)
    {
        $response = $this->imageAnnotator->logoDetection(file_get_contents($path));
        $logos = $response->getLogoAnnotations();

        return collect($logos)->map(fn($logo) => [
            'logo' => $logo->getDescription(),
            'bounds' => $this->getBounds($logo),
        ])->when($this->output, fn($collection) => $this->drawBounds($collection, $path, $extension));
    }

    public function cropHints($path)
    {
        $response = $this->imageAnnotator->cropHintsDetection(file_get_contents($path));
        $annotation = $response->getCropHintsAnnotation();

        return  collect(optional($annotation)->getCropHints())
            ->map(fn($hint) => $this->getBounds($hint));
    }

    public function document($path)
    {
        $response = $this->imageAnnotator->documentTextDetection(file_get_contents($path));
        $annotation = $response->getFullTextAnnotation();

        $results = [];

        if ($annotation) {
            foreach ($annotation->getPages() as $page) {
                foreach ($page->getBlocks() as $block) {
                    $block_text = '';
                    foreach ($block->getParagraphs() as $paragraph) {
                        foreach ($paragraph->getWords() as $word) {
                            foreach ($word->getSymbols() as $symbol) {
                                $block_text .= $symbol->getText();
                            }
                            $block_text .= ' ';
                        }
                    }
                    $results[] = [
                        'Block content' => $block_text,
                        'Block confidence' => $block->getConfidence(),
                    ];
                }
            }
        }

        return $results;
    }

    public function face($path, $extension = null)
    {
        $response = $this->imageAnnotator->faceDetection(file_get_contents($path));
        $faces = $response->getFaceAnnotations();

        return collect($faces)
            ->map(fn($face) => $this->getFaceLikelihood($face) + ['bounds' => $this->getBounds($face)])
            ->when($this->output, fn($collection) => $this->drawBounds($collection, $path, $extension));
    }

    protected function getFaceLikelihood($face)
    {
        return [
            'anger' => static::$likelihood[$face->getAngerLikelihood()],
            'joy' => static::$likelihood[$face->getJoyLikelihood()],
            'surprise' => static::$likelihood[$face->getSurpriseLikelihood()],
            'sorrow' => static::$likelihood[$face->getSorrowLikelihood()],
            'under_exposed' => static::$likelihood[$face->getUnderExposedLikelihood()],
            'blurred' => static::$likelihood[$face->getBlurredLikelihood()],
            'headwear' => static::$likelihood[$face->getHeadwearLikelihood()],
        ];
    }

    public function imageProperty($path)
    {
        $response = $this->imageAnnotator->imagePropertiesDetection(file_get_contents($path));
        $props = $response->getImagePropertiesAnnotation();

        return collect($props->getDominantColors()->getColors())
            ->map(fn($colorInfo) => $this->getProperties($colorInfo));
    }

    protected function getProperties($colorInfo)
    {
        return [
            'Fraction' => $colorInfo->getPixelFraction(),
            'Red' => $colorInfo->getColor()->getRed(),
            'Green' => $colorInfo->getColor()->getGreen(),
            'Blue' => $colorInfo->getColor()->getBlue(),
        ];
    }

    public function label($path)
    {
        $response = $this->imageAnnotator->labelDetection(file_get_contents($path));
        $labels = $response->getLabelAnnotations();

        return collect($labels)->map(fn($label) => $label->getDescription());
    }

    public function landmark($path, $extension = null)
    {
        $response = $this->imageAnnotator->landmarkDetection(file_get_contents($path));
        $landmarks = $response->getLandmarkAnnotations();

        return collect($landmarks)
            ->map(fn($landmark) => [
                'landmark' => $landmark->getDescription(),
                'bounds' => $this->getBounds($landmark),
            ])
            ->when($this->output, fn($collection) => $this->drawBounds($collection, $path, $extension));
    }

    public function object($path)
    {
        $response = $this->imageAnnotator->objectLocalization(file_get_contents($path));
        $objects = $response->getLocalizedObjectAnnotations();

        return collect($objects)->map(function($object) {
            $vertices = $object->getBoundingPoly()->getNormalizedVertices();
            
            return [
                'name' => $object->getName(),
                'score' => $object->getScore(),
                'bounds' => collect($vertices)->map(fn($vertex) => [$vertex->getX(), $vertex->getY()]),
            ];
        });
    }

    public function safeSearch($path)
    {
        $response = $this->imageAnnotator->safeSearchDetection(file_get_contents($path));
        $safe = $response->getSafeSearchAnnotation();

        return [
            'adult' => static::$likelihood[$safe->getAdult()],
            'medical' => static::$likelihood[$safe->getMedical()],
            'spoof' => static::$likelihood[$safe->getSpoof()],
            'violence' => static::$likelihood[$safe->getViolence()],
            'racy' => static::$likelihood[$safe->getRacy()],
        ];
    }

    public function web($path)
    {
        $response = $this->imageAnnotator->webDetection(file_get_contents($path), $this->imageContext());
        $web = $response->getWebDetection();

        $results = [];

        foreach ($web->getBestGuessLabels() as $label) {
            $results['best_guess_label'][] = $label->getLabel();
        }

        foreach ($web->getPagesWithMatchingImages() as $page) {
            $results['pages_with_matching_images'][] = $page->getUrl();
        }

        foreach ($web->getFullMatchingImages() as $fullMatchingImage) {
            $results['full_matching_images'][] = $fullMatchingImage->getUrl();
        }

        foreach ($web->getPartialMatchingImages() as $partialMatchingImage) {
            $results['partial_matching_images'][] = $partialMatchingImage->getUrl();
        }

        foreach ($web->getVisuallySimilarImages() as $visuallySimilarImage) {
            $results['visually_similar_images'][] = $visuallySimilarImage->getUrl();
        }

        foreach ($web->getWebEntities() as $entity) {
            $results['web_entities'][] = [
                'Description:' => $entity->getDescription(),
                'Score' => $entity->getScore(),
            ];
        }

        return $results;
    }

    public function includeGeoResult()
    {
        $this->includeGeo = true;

        return $this;
    }

    protected function imageContext()
    {
        if (! $this->includeGeo) {
            return []; 
        }

        $params = new WebDetectionParams();
        $params->setIncludeGeoResults(true);

        $imageContext = new ImageContext();
        $imageContext->setWebDetectionParams($params);

        return ['imageContext' => $imageContext];
    }

    public function pdf($path)
    {
        $feature = (new Feature())->setType(Type::DOCUMENT_TEXT_DETECTION);

        $gcsSource = (new GcsSource())->setUri($path);

        $mimeType = 'application/pdf';

        $inputConfig = (new InputConfig())
            ->setGcsSource($gcsSource)
            ->setMimeType($mimeType);

        $gcsDestination = (new GcsDestination())->setUri($this->pdfDestination($path));

        $batchSize = 2;

        $outputConfig = (new OutputConfig())
            ->setGcsDestination($gcsDestination)
            ->setBatchSize($batchSize);

        $request = (new AsyncAnnotateFileRequest())
            ->setFeatures([$feature])
            ->setInputConfig($inputConfig)
            ->setOutputConfig($outputConfig);

        $operation = $this->imageAnnotator->asyncBatchAnnotateFiles([$request]);

        $operation->pollUntilComplete();
    }

    protected function pdfDestination($path)
    {
        preg_match('/^gs:\/\/([a-zA-Z0-9\._\-]+)\/?(\S+)?$/', $path, $match);

        $bucket = $match[1];
        
        return $this->output ?? "gs://{$bucket}/results/";
    }

    public function output($path)
    {
        $this->output = $path;

        return $this;
    }

    protected function drawBounds($results, $path, $extension)
    {
        $imageCreateFunc = [
            'png' => 'imagecreatefrompng',
            'gd' => 'imagecreatefromgd',
            'gif' => 'imagecreatefromgif',
            'jpg' => 'imagecreatefromjpeg',
            'jpeg' => 'imagecreatefromjpeg',
        ];

        $imageWriteFunc = [
            'png' => 'imagepng',
            'gd' => 'imagegd',
            'gif' => 'imagegif',
            'jpg' => 'imagejpeg',
            'jpeg' => 'imagejpeg',
        ];

        $extension ??= pathinfo($path, PATHINFO_EXTENSION);

        if (!array_key_exists($extension, $imageCreateFunc)) {
            throw new \Exception('Unsupported image extension');
        }

        copy($path, $this->output);

        $outputImage = call_user_func($imageCreateFunc[$extension], $this->output);

        collect($results)->map(fn($result) => $result['bounds'])
            ->each(function($bound) use($outputImage){
                imagerectangle($outputImage, $bound[0][0], $bound[0][1], $bound[2][0], $bound[2][1], 0x00ff00);
            });

        call_user_func($imageWriteFunc[$extension], $outputImage, $this->output);

        return $results;
    }

	protected function getBounds($object)
	{
        return collect($object->getBoundingPoly()->getVertices())
            ->map(function($vertex) {
                return [$vertex->getX(), $vertex->getY()];
            });
	}
}
