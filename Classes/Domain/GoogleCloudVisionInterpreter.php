<?php

/*
 * This file is part of the Sitegeist.ArtClasses package.
 */

declare(strict_types=1);

namespace Sitegeist\ArtClasses\GoogleCloudVisionInterpreter\Domain;

use Google\Cloud\Vision\V1\BoundingPoly;
use Google\Cloud\Vision\V1\ColorInfo;
use Google\Cloud\Vision\V1\CropHint;
use Google\Cloud\Vision\V1\EntityAnnotation;
use Google\Cloud\Vision\V1\Feature\Type as FeatureType;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\LocalizedObjectAnnotation;
use Google\Cloud\Vision\V1\NormalizedVertex;
use Google\Cloud\Vision\V1\Page;
use Google\Cloud\Vision\V1\Vertex;
use Google\Cloud\Vision\V1\WebDetection\WebEntity;
use Google\Protobuf\Internal\RepeatedField;
use Neos\Flow\I18n\Locale;
use Neos\Media\Domain\Model\Image;
use Sitegeist\ArtClasses\Domain\Interpretation\InterpretedBoundingPolygon;
use Sitegeist\ArtClasses\Domain\Interpretation\ImageInterpreterInterface;
use Sitegeist\ArtClasses\Domain\Interpretation\ImageInterpretation;
use Sitegeist\ArtClasses\Domain\Interpretation\InterpretedDominantColor;
use Sitegeist\ArtClasses\Domain\Interpretation\InterpretedNormalizedVertex;
use Sitegeist\ArtClasses\Domain\Interpretation\InterpretedObject;
use Sitegeist\ArtClasses\Domain\Interpretation\InterpretedText;
use Sitegeist\ArtClasses\Domain\Interpretation\InterpretedVertex;

/**
 * An Azure Computer Vision based interpreter
 */
final class GoogleCloudVisionInterpreter implements ImageInterpreterInterface
{
    public function __construct(
        private readonly string $credentialsFilePath,
        private readonly float $minimumScore
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

        return new ImageInterpretation(
            new Locale('en'),
            null,
            array_map(
                fn (EntityAnnotation $annotation): string => $annotation->getDescription(),
                $this->filterAnnotations($annotation->getLabelAnnotations())
            ),
            array_map(
                fn (LocalizedObjectAnnotation $annotation): InterpretedObject => new InterpretedObject(
                    $annotation->getName(),
                    self::translateBoundingPolygon($annotation->getBoundingPoly())
                ),
                $this->filterAnnotations($annotation->getLocalizedObjectAnnotations())
            ),
            array_map(
                fn (EntityAnnotation $annotation): InterpretedText => new InterpretedText(
                    $annotation->getDescription(),
                    $annotation->getLocale() !== '' && $annotation->getLocale() !== 'und'
                        ? new Locale($annotation->getLocale())
                        : null,
                    self::translateBoundingPolygon($annotation->getBoundingPoly())
                ),
                $this->filterAnnotations($annotation->getTextAnnotations())
            ),
            array_map(
                fn (ColorInfo $colorInfo): InterpretedDominantColor => new InterpretedDominantColor(
                    $colorInfo->getColor()->getRed(),
                    $colorInfo->getColor()->getGreen(),
                    $colorInfo->getColor()->getBlue(),
                    $colorInfo->getColor()->getAlpha()->getValue()
                ),
                $this->filterAnnotations($annotation->getImagePropertiesAnnotation()->getDominantColors()->getColors())
            ),
            array_map(
                fn (CropHint $cropHint): InterpretedBoundingPolygon => self::translateBoundingPolygon($cropHint->getBoundingPoly()),
                $this->filterAnnotations($annotation->getCropHintsAnnotation()->getCropHints())
            )
        );
    }

    /**
     * @template T
     * @phpstan-param RepeatedField<T> $annotations
     * @phpstan-return array<T>
     */
    private function filterAnnotations(RepeatedField $annotations): array
    {
        return array_filter(
            iterator_to_array($annotations),
            fn (EntityAnnotation|LocalizedObjectAnnotation|Page|ColorInfo|CropHint|WebEntity $annotation): bool
                => match (get_class($annotation)) {
                    Page::class, CropHint::class => $annotation->getConfidence() > $this->minimumScore,
                    default => $annotation->getScore() > $this->minimumScore
            }
        );
    }

    private static function translateBoundingPolygon(BoundingPoly $boundingPolygon): InterpretedBoundingPolygon
    {
        return new InterpretedBoundingPolygon(
            array_map(
                fn (Vertex $vertex) => new InterpretedVertex($vertex->getX(), $vertex->getY()),
                iterator_to_array($boundingPolygon->getVertices())
            ),
            array_map(
                fn (NormalizedVertex $vertex) => new InterpretedNormalizedVertex($vertex->getX(), $vertex->getY()),
                iterator_to_array($boundingPolygon->getNormalizedVertices())
            )
        );
    }
}
