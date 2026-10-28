<?php

declare(strict_types=1);

namespace Pixelegg\PxeCompareslider\ViewHelpers;

use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Builds a srcset attribute value for a FAL image: one processed variant per requested width,
 * honouring the file reference's crop. Widths larger than the source collapse into the
 * largest available size (TYPO3 never upscales); duplicate widths are dropped.
 *
 * <cs:srcset image="{file}" widths="480,768,1024" />
 * → "/fileadmin/_processed_/…/img_480.jpg 480w, /fileadmin/_processed_/…/img_768.jpg 768w, …"
 */
final class SrcsetViewHelper extends AbstractViewHelper
{
    public function __construct(
        private readonly ImageService $imageService,
    ) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('image', FileInterface::class, 'FAL file or file reference', true);
        $this->registerArgument('widths', 'string', 'Comma-separated list of widths in pixels', true);
        $this->registerArgument('cropVariant', 'string', 'Crop variant to apply', false, 'default');
    }

    public function render(): string
    {
        /** @var FileInterface $image */
        $image = $this->arguments['image'];
        $widths = GeneralUtility::intExplode(',', (string)$this->arguments['widths'], true);

        $cropString = $image->hasProperty('crop') ? (string)$image->getProperty('crop') : '';
        $cropArea = CropVariantCollection::create($cropString)->getCropArea((string)$this->arguments['cropVariant']);
        $crop = $cropArea->isEmpty() ? null : $cropArea->makeAbsoluteBasedOnFile($image);

        $candidates = [];
        foreach ($widths as $width) {
            if ($width <= 0) {
                continue;
            }
            $processed = $this->imageService->applyProcessingInstructions($image, [
                'maxWidth' => $width,
                'crop' => $crop,
            ]);
            $actualWidth = (int)$processed->getProperty('width');
            if ($actualWidth <= 0 || isset($candidates[$actualWidth])) {
                continue;
            }
            $candidates[$actualWidth] = $this->imageService->getImageUri($processed) . ' ' . $actualWidth . 'w';
        }
        ksort($candidates);

        return implode(', ', $candidates);
    }
}
