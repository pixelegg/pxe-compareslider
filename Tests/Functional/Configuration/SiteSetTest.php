<?php

declare(strict_types=1);

namespace Pixelegg\PxeCompareslider\Tests\Functional\Configuration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Installation path A: the Site Set alone (no static template) must render the element.
 */
final class SiteSetTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['fluid_styled_content'];

    protected array $testExtensionsToLoad = [
        'pixelegg/pxe-compareslider',
        // Provides the PAGE object through a set that depends on pixelegg/compareslider.
        'typo3conf/ext/pxe_compareslider/Tests/Functional/Fixtures/Extensions/test_site',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3conf/ext/pxe_compareslider/Tests/Functional/Fixtures/Frontend/fileadmin/' => 'fileadmin/',
    ];

    #[Test]
    public function setIsRegisteredWithDependencyAndSettings(): void
    {
        $registry = $this->get(SetRegistry::class);

        self::assertTrue($registry->hasSet('pixelegg/compareslider'), 'invalid sets: ' . json_encode($registry->getInvalidSets()));
        $set = $registry->getSet('pixelegg/compareslider');
        self::assertNotNull($set);
        self::assertContains('typo3/fluid-styled-content', $set->dependencies);
        foreach ([
            'plugin.tx_pxecompareslider.view.templateRootPath',
            'plugin.tx_pxecompareslider.settings.includeAssetsGlobally',
            'plugin.tx_pxecompareslider.settings.image.maxWidth',
            'plugin.tx_pxecompareslider.settings.image.srcsetWidths',
            'plugin.tx_pxecompareslider.settings.image.sizes',
        ] as $setting) {
            self::assertContains($setting, array_map(static fn($definition) => $definition->key, $set->settingsDefinitions));
        }
    }

    #[Test]
    public function siteSetRendersTheElementWithoutAStaticTemplate(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Frontend/records.csv');
        $siteDir = Environment::getConfigPath() . '/sites/main';
        GeneralUtility::mkdir_deep($siteDir);
        file_put_contents(
            $siteDir . '/config.yaml',
            "rootPageId: 1\nbase: 'http://localhost/'\ndependencies:\n  - pixelegg/test-site\n"
            . "languages:\n  - languageId: 0\n    title: English\n    locale: en_US.UTF-8\n    base: /\n"
        );
        // Deliberately no sys_template record: TYPO3 13/14 only use the sets' TypoScript when the
        // root page has no template record. test-site -> compareslider -> fluid-styled-content.

        $html = (string)$this->executeFrontendSubRequest(new InternalRequest('http://localhost/'))->getBody();

        self::assertStringContainsString('frame-type-pxe_compareslider', $html);
        self::assertStringContainsString('aria-label="Kitchen"', $html);
        self::assertMatchesRegularExpression('#<link[^>]+compareslider\.css#', $html);
        self::assertMatchesRegularExpression('#<img[^>]+width="1540"#', $html, 'constants from settings.definitions.yaml apply');
    }
}
