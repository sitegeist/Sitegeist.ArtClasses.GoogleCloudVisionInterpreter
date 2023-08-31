<?php

/*
 * This file is part of the Sitegeist.ArtClasses package.
 */

declare(strict_types=1);

namespace Sitegeist\ArtClasses\GoogleCloudVisionInterpreter\Domain;

use Google\Cloud\Vision\V1\Feature\Type as FeatureType;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Neos\Flow\I18n\Locale;
use Neos\Media\Domain\Model\Image;
use Sitegeist\ArtClasses\Domain\Interpretation\ImageInterpreterInterface;
use Sitegeist\ArtClasses\Domain\Interpretation\ImageInterpretation;

/**
 * An Azure Computer Vision based interpreter
 */
final class GoogleCloudVisionInterpreter implements ImageInterpreterInterface
{
    public function __construct(
        private readonly string $credentialsFilePath,
    ) {
    }

    public function interpretImage(Image $image, ?Locale $targetLocale): ImageInterpretation
    {
        $annotator = new ImageAnnotatorClient([
            'credentials' => $this->credentialsFilePath
        ]);
        $annotation = $annotator->annotateImage(
            $image->getResource()->getStream(),
            [
                FeatureType::FACE_DETECTION,
                FeatureType::LANDMARK_DETECTION,
                FeatureType::LOGO_DETECTION,
                FeatureType::LABEL_DETECTION,
                FeatureType::TEXT_DETECTION,
                FeatureType::DOCUMENT_TEXT_DETECTION,
                FeatureType::SAFE_SEARCH_DETECTION,
                FeatureType::IMAGE_PROPERTIES,
                FeatureType::CROP_HINTS,
                FeatureType::WEB_DETECTION,
                FeatureType::PRODUCT_SEARCH,
                FeatureType::OBJECT_LOCALIZATION,
            ]
        );

        return new ImageInterpretation(null, null);
    }
}
