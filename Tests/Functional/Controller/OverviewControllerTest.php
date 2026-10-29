<?php

declare(strict_types=1);

namespace Pixelegg\PxeCompareslider\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use Pixelegg\PxeCompareslider\Controller\Backend\OverviewController;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Module\ModuleRegistry;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Renders the backend module end-to-end (ModuleTemplate, core "Module" layout, own template)
 * and checks that the core frame (docheader, module body) is there: an own "Module" layout
 * used to replace it, which left the module without docheader and padding.
 */
final class OverviewControllerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['fluid_styled_content'];

    protected array $testExtensionsToLoad = ['pixelegg/pxe-compareslider'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tt_content.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_reference.csv');
    }

    #[Test]
    public function moduleIsRenderedInsideTheCoreModuleFrame(): void
    {
        $html = $this->renderModule(1);

        self::assertStringContainsString('module-docheader', $html);
        self::assertMatchesRegularExpression('/<div class="module[^"]*" data-module-id="[^"]*" data-module-name="pxe_compareslider"/', $html);
        self::assertMatchesRegularExpression('/<div class="module-body t3js-module-body">.*<div class="pxecs">/s', $html);
        // Bookmark button in the docheader (TYPO3 13: ShortcutButton, 14: shortcut context).
        self::assertMatchesRegularExpression('/TYPO3\\.ShortcutMenu\\.createShortcut|<typo3-backend-bookmark-button route="pxe_compareslider"/', $html);
        self::assertStringContainsString('<h1>Compare slider</h1>', $html);
        self::assertStringContainsString('pxe_compareslider <span aria-hidden="true">·</span> <a href="https://www.pixelegg.de"', $html);
    }

    #[Test]
    public function countersUsePluralLabels(): void
    {
        $html = $this->renderModule(1);

        self::assertMatchesRegularExpression('#<div class="h2 mb-0">3</div>\s*<div class="text-body-secondary">Elements</div>#', $html);
        self::assertMatchesRegularExpression('#<div class="h2 mb-0">2</div>\s*<div class="text-body-secondary">Pages</div>#', $html);
        self::assertStringContainsString('Missing after image', $html);
    }

    #[Test]
    public function countersUseSingularLabelsForOne(): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tt_content');
        $connection->delete('tt_content', ['uid' => 2]);
        $connection->delete('tt_content', ['uid' => 3]);

        $html = $this->renderModule(1);

        self::assertMatchesRegularExpression('#<div class="h2 mb-0">1</div>\s*<div class="text-body-secondary">Element</div>#', $html);
        self::assertMatchesRegularExpression('#<div class="h2 mb-0">1</div>\s*<div class="text-body-secondary">Page</div>#', $html);
    }

    #[Test]
    public function moduleIsTranslated(): void
    {
        $html = $this->renderModule(2);

        self::assertStringContainsString('<h1>Vergleichsslider</h1>', $html);
        self::assertMatchesRegularExpression('#<div class="h2 mb-0">3</div>\s*<div class="text-body-secondary">Elemente</div>#', $html);
        self::assertMatchesRegularExpression('#<div class="h2 mb-0">2</div>\s*<div class="text-body-secondary">Seiten</div>#', $html);
    }

    #[Test]
    public function moduleSitsAfterThePageModuleGroup(): void
    {
        $topLevel = [];
        foreach ($this->get(ModuleRegistry::class)->getModules() as $identifier => $module) {
            if (!$module->hasParentModule()) {
                $topLevel[] = $identifier;
            }
        }
        // "web" in TYPO3 13, "content" in TYPO3 14
        $pageGroup = array_search('content', $topLevel, true);
        if ($pageGroup === false) {
            $pageGroup = array_search('web', $topLevel, true);
        }
        $self = array_search('pxe_compareslider', $topLevel, true);
        $site = array_search('site', $topLevel, true);

        self::assertIsInt($pageGroup);
        self::assertIsInt($self);
        self::assertIsInt($site);
        self::assertGreaterThan($pageGroup, $self);
        self::assertLessThan($site, $self);
    }

    private function renderModule(int $backendUserUid): string
    {
        $backendUser = $this->setUpBackendUser($backendUserUid);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $module = $this->get(ModuleProvider::class)->getModule('pxe_compareslider', $backendUser);
        self::assertNotNull($module);
        $request = (new ServerRequest('http://localhost/typo3/module/pxe-compareslider', 'GET'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', $this->get(Router::class)->getRoute('pxe_compareslider'))
            ->withAttribute('module', $module);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $response = $this->get(OverviewController::class)->indexAction($request);
        self::assertSame(200, $response->getStatusCode());

        return (string)$response->getBody();
    }
}
