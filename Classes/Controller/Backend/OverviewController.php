<?php

declare(strict_types=1);

namespace Pixelegg\PxeCompareslider\Controller\Backend;

use Pixelegg\PxeCompareslider\Service\CompareSliderUsageService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\DocHeaderComponent;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ResourceFactory;

final readonly class OverviewController
{
    public function __construct(
        private ModuleTemplateFactory $moduleTemplateFactory,
        private CompareSliderUsageService $usageService,
        private UriBuilder $uriBuilder,
        private ResourceFactory $resourceFactory,
        private PageRenderer $pageRenderer,
    ) {}

    public function indexAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->pageRenderer->addCssFile('EXT:pxe_compareslider/Resources/Public/Css/Backend/module.css');

        $usages = $this->usageService->getUsages();
        $returnUrl = (string)($request->getAttribute('normalizedParams')?->getRequestUri() ?? '');

        $rows = [];
        foreach ($usages as $usage) {
            $usage['editUrl'] = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => ['tt_content' => [$usage['contentUid'] => 'edit']],
                'returnUrl' => $returnUrl,
            ]);
            $usage['pageUrl'] = (string)$this->uriBuilder->buildUriFromRoute('web_layout', [
                'id' => $usage['pageUid'],
            ]);
            $usage['beforeThumb'] = $this->thumbnail($usage['beforeReferenceUid']);
            $usage['afterThumb'] = $this->thumbnail($usage['afterReferenceUid']);
            $rows[] = $usage;
        }

        $title = $this->getLanguageService()->sL('LLL:EXT:pxe_compareslider/Resources/Private/Language/locallang_mod.xlf:module.title');
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->setTitle($title);
        $this->addShortcutButton($moduleTemplate, $title);
        $moduleTemplate->assignMultiple([
            'usages' => $rows,
            'stats' => $this->usageService->getStatistics($usages),
        ]);

        return $moduleTemplate->renderResponse('Overview');
    }

    /**
     * Bookmark button in the docheader. TYPO3 14 adds it from a shortcut context
     * (manual ShortcutButtons are deprecated there); TYPO3 13 needs the button itself.
     */
    private function addShortcutButton(ModuleTemplate $moduleTemplate, string $title): void
    {
        $docHeader = $moduleTemplate->getDocHeaderComponent();
        if (method_exists(DocHeaderComponent::class, 'setShortcutContext')) { // @phpstan-ignore function.alreadyNarrowedType
            $docHeader->setShortcutContext(routeIdentifier: 'pxe_compareslider', displayName: $title); // @phpstan-ignore method.notFound
            return;
        }
        $buttonBar = $docHeader->getButtonBar();
        $buttonBar->addButton(
            $buttonBar->makeShortcutButton()->setRouteIdentifier('pxe_compareslider')->setDisplayName($title),
            ButtonBar::BUTTON_POSITION_RIGHT
        );
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    private function thumbnail(?int $referenceUid): ?FileReference
    {
        if ($referenceUid === null) {
            return null;
        }
        try {
            return $this->resourceFactory->getFileReferenceObject($referenceUid);
        } catch (ResourceDoesNotExistException) {
            return null;
        }
    }
}
