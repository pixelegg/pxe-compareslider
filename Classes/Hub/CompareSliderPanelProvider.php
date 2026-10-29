<?php

declare(strict_types=1);

namespace Pixelegg\PxeCompareslider\Hub;

use Pixelegg\PxeCompareslider\Service\CompareSliderUsageService;
use Pixelegg\PxeHub\Panel\PanelAction;
use Pixelegg\PxeHub\Panel\PanelProvider;
use Pixelegg\PxeHub\Panel\PanelStatus;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Gives Compare Slider a card in pxe_hub (icon + usage metric + link to its module).
 *
 * Registered conditionally (only when pxe_hub is installed) via Configuration/Services.php,
 * so pxe_compareslider does not depend on pxe_hub.
 */
final readonly class CompareSliderPanelProvider implements PanelProvider
{
    public function __construct(
        private CompareSliderUsageService $usageService,
        private UriBuilder $uriBuilder,
    ) {}

    public function getExtensionKey(): string
    {
        return 'pxe_compareslider';
    }

    public function getLabel(): string
    {
        return $this->translate('module.title');
    }

    public function getIcon(): string
    {
        return 'content-pxe-compareslider';
    }

    public function getModuleLink(): ?string
    {
        return (string)$this->uriBuilder->buildUriFromRoute('pxe_compareslider');
    }

    public function getStatus(): PanelStatus
    {
        $count = count($this->usageService->getUsages());
        if ($count === 0) {
            return PanelStatus::unknown($this->translate('hub.status.unused'));
        }
        return PanelStatus::ok(sprintf(
            $this->translate($count === 1 ? 'hub.status.count.one' : 'hub.status.count.other'),
            number_format($count, 0, '', $this->translate('module.number.thousandsSeparator'))
        ));
    }

    /** @return array<int,\Pixelegg\PxeHub\Panel\Metric> */
    public function getMetrics(): array
    {
        // Count already shown inline in the status text ("N elements"), so no separate chip.
        return [];
    }

    /** @return PanelAction[] */
    public function getActions(): array
    {
        return [
            new PanelAction($this->translate('hub.action.open'), (string)$this->uriBuilder->buildUriFromRoute('pxe_compareslider')),
        ];
    }

    private function translate(string $key): string
    {
        $languageService = $GLOBALS['LANG'] ?? null;
        $label = $languageService instanceof LanguageService
            ? $languageService->sL('LLL:EXT:pxe_compareslider/Resources/Private/Language/locallang_mod.xlf:' . $key)
            : '';
        return $label !== '' ? $label : $key;
    }
}
