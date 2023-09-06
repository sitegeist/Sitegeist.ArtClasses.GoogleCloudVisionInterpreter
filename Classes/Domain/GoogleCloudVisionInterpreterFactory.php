<?php

/*
 * This file is part of the Sitegeist.ArtClasses package.
 */

declare(strict_types=1);

namespace Sitegeist\ArtClasses\GoogleCloudVisionInterpreter\Domain;

use Neos\Flow\Annotations as Flow;

/**
 * The factory for creating Azure Computer Vision Interpreters from configuration
 */
#[Flow\Scope('singleton')]
final class GoogleCloudVisionInterpreterFactory
{
    public function create(
        string $credentialsFilePath,
        float $minimumScore
    ): GoogleCloudVisionInterpreter {
        return new GoogleCloudVisionInterpreter(
            $credentialsFilePath,
            $minimumScore
        );
    }
}
