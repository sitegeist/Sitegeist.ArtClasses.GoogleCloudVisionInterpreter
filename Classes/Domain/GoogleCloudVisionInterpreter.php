<?php

/*
 * This file is part of the Sitegeist.ArtClasses package.
 */

declare(strict_types=1);

namespace Sitegeist\ArtClasses\GoogleCloudVisionInterpreter\Domain;

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
    }
}
