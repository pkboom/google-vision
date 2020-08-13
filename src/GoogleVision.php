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

    public function __construct(ImageAnnotatorClient $imageAnnotator)
    {
        $this->imageAnnotator = $imageAnnotator;
    }

    public function text($path)
    {
        $response = $this->imageAnnotator->textDetection(file_get_contents($path));
        $texts = $response->getTextAnnotations();

        $results = [];

        foreach ($texts as $text) {
            $results[] = $text->getDescription();
        }

        return $results;
    }

    public function logo($path, $extension = null)
    {
        $response = $this->imageAnnotator->logoDetection(file_get_contents($path));
        $logos = $response->getLogoAnnotations();

        $results = [];

        foreach ($logos as $logo) {
            $vertices = $logo->getBoundingPoly()->getVertices();
            $bounds = [];

            foreach ($vertices as $vertex) {
                $bounds[] = [$vertex->getX(), $vertex->getY()];
            }

            $results[] = ['logo' => $logo->getDescription()] + [
                'bounds' => $bounds,
            ];
        }

        if ($logos || $this->output) {
            $this->drawBounds($results, $path, $extension);
        }

        return $results;
    }

    public function cropHints($path)
    {
        $response = $this->imageAnnotator->cropHintsDetection(file_get_contents($path));
        $annotations = $response->getCropHintsAnnotation();

        $bounds = [];

        if ($annotations) {
            foreach ($annotations->getCropHints() as $hint) {
                $vertices = $hint->getBoundingPoly()->getVertices();

                foreach ($vertices as $vertex) {
                    $bounds[] = [$vertex->getX(), $vertex->getY()];
                }
            }
        }

        return $bounds;
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

        $results = [];

        foreach ($faces as $face) {
            $vertices = $face->getBoundingPoly()->getVertices();
            $bounds = [];

            foreach ($vertices as $vertex) {
                $bounds[] = [$vertex->getX(), $vertex->getY()];
            }

            $results[] = $this->getLikelihood($face) + [
                'bounds' => $bounds,
            ];
        }

        if ($faces || $this->output) {
            $this->drawBounds($results, $path, $extension);
        }

        return $results;
    }

    public function getLikelihood($face)
    {
        $likelihoodName = [
            'UNKNOWN',
            'VERY_UNLIKELY',
            'UNLIKELY',
            'POSSIBLE',
            'LIKELY',
            'VERY_LIKELY',
        ];

        return [
            'anger' => $likelihoodName[$face->getAngerLikelihood()],
            'joy' => $likelihoodName[$face->getJoyLikelihood()],
            'surprise' => $likelihoodName[$face->getSurpriseLikelihood()],
            'sorrow' => $likelihoodName[$face->getSorrowLikelihood()],
            'under_exposed' => $likelihoodName[$face->getUnderExposedLikelihood()],
            'blurred' => $likelihoodName[$face->getBlurredLikelihood()],
            'headwear' => $likelihoodName[$face->getHeadwearLikelihood()],
        ];
    }

    public function drawBounds($results, $path, $extension)
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
    }

    public function imageProperty($path)
    {
        $response = $this->imageAnnotator->imagePropertiesDetection(file_get_contents($path));
        $props = $response->getImagePropertiesAnnotation();

        $results = [];

        foreach ($props->getDominantColors()->getColors() as $colorInfo) {
            $color = $colorInfo->getColor();

            $results[] = [
                'Fraction' => $colorInfo->getPixelFraction(),
                'Red' => $color->getRed(),
                'Green' => $color->getGreen(),
                'Blue' => $color->getBlue(),
            ];
        }

        return $results;
    }

    public function label($path)
    {
        $response = $this->imageAnnotator->labelDetection(file_get_contents($path));
        $labels = $response->getLabelAnnotations();

        $results = [];

        if ($labels) {
            foreach ($labels as $label) {
                $results[] = $label->getDescription();
            }
        }

        return $results;
    }

    public function landmark($path)
    {
        $response = $this->imageAnnotator->landmarkDetection(file_get_contents($path));
        $landmarks = $response->getLandmarkAnnotations();

        $results = [];

        foreach ($landmarks as $landmark) {
            $results[] = $landmark->getDescription();
        }

        return $results;
    }

    public function object($path)
    {
        $response = $this->imageAnnotator->objectLocalization(file_get_contents($path));
        $objects = $response->getLocalizedObjectAnnotations();

        $results = [];

        foreach ($objects as $object) {
            $vertices = $object->getBoundingPoly()->getNormalizedVertices();
            $bounds = [];

            foreach ($vertices as $vertex) {
                $bounds[] = [$vertex->getX(), $vertex->getY()];
            }

            $results[] = [
                'name' => $object->getName(),
                'score' => $object->getScore(),
                'bounds' => $bounds,
            ];
        }

        return $results;
    }

    public function safeSearch($path)
    {
        $response = $this->imageAnnotator->safeSearchDetection(file_get_contents($path));
        $safe = $response->getSafeSearchAnnotation();

        $likelihoodName = ['UNKNOWN', 'VERY_UNLIKELY', 'UNLIKELY', 'POSSIBLE', 'LIKELY', 'VERY_LIKELY'];

        return [
            'adult' => $likelihoodName[$safe->getAdult()],
            'medical' => $likelihoodName[$safe->getMedical()],
            'spoof' => $likelihoodName[$safe->getSpoof()],
            'violence' => $likelihoodName[$safe->getViolence()],
            'racy' => $likelihoodName[$safe->getRacy()],
        ];
    }

    public function web($path, $includeGeoResult = false)
    {
        $response = $this->imageAnnotator->webDetection(file_get_contents($path), $this->imageContext($includeGeoResult));
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

    public function imageContext($include)
    {
        $imageContext = null;

        if ($include) {
            $params = new WebDetectionParams();
            $params->setIncludeGeoResults(true);

            $imageContext = new ImageContext();
            $imageContext->setWebDetectionParams($params);
        }

        return ['imageContext' => $imageContext];
    }

    public function pdf($path, $output)
    {
        $feature = (new Feature())->setType(Type::DOCUMENT_TEXT_DETECTION);

        $gcsSource = (new GcsSource())->setUri($path);

        $mimeType = 'application/pdf';

        $inputConfig = (new InputConfig())
            ->setGcsSource($gcsSource)
            ->setMimeType($mimeType);

        $gcsDestination = (new GcsDestination())->setUri($output);

        $batchSize = 2;

        $outputConfig = (new OutputConfig())
            ->setGcsDestination($gcsDestination)
            ->setBatchSize($batchSize);

        $request = (new AsyncAnnotateFileRequest())
            ->setFeatures([$feature])
            ->setInputConfig($inputConfig)
            ->setOutputConfig($outputConfig);

        $requests = [$request];

        $operation = $this->imageAnnotator->asyncBatchAnnotateFiles($requests);

        $operation->pollUntilComplete();
    }

    public function output($path)
    {
        $this->output = $path;

        return $this;
    }
}
