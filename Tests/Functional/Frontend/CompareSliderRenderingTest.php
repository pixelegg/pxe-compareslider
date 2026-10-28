<?php

declare(strict_types=1);

namespace Pixelegg\PxeCompareslider\Tests\Functional\Frontend;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Renders the fixture page through the real frontend (fluid_styled_content + this extension's
 * TypoScript) and checks the markup contract the CSS/JS rely on.
 *
 * Fixture elements (records.csv):
 *  1 header "Kitchen" + subheader, vertical, start 30, labels Old/New, png/png
 *  2 no images, legacy FlexForm title/subtitle, empty header  -> caption only
 *  3 no header, no legacy title, png/png                      -> translated fallback name
 *  4 no header, legacy title "Legacy name", load_eager, png/webp
 *  5 header "Header wins" + legacy title                       -> header wins
 */
final class CompareSliderRenderingTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['fluid_styled_content'];

    protected array $testExtensionsToLoad = ['pixelegg/pxe-compareslider'];

    protected array $pathsToProvideInTestInstance = [
        'typo3conf/ext/pxe_compareslider/Tests/Functional/Fixtures/Frontend/fileadmin/' => 'fileadmin/',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Frontend/records.csv');
        $siteDir = Environment::getConfigPath() . '/sites/main';
        GeneralUtility::mkdir_deep($siteDir);
        file_put_contents(
            $siteDir . '/config.yaml',
            "rootPageId: 1\nbase: 'http://localhost/'\nlanguages:\n  - languageId: 0\n    title: English\n    locale: en_US.UTF-8\n    base: /\n"
        );
    }

    /**
     * @param list<string> $additionalConstantFiles imported after the extension's constants
     */
    private function setUpTypoScript(array $additionalConstantFiles = []): void
    {
        $this->setUpFrontendRootPage(
            1,
            [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:pxe_compareslider/Configuration/TypoScript/constants.typoscript',
                    ...$additionalConstantFiles,
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:pxe_compareslider/Configuration/TypoScript/setup.typoscript',
                    'EXT:pxe_compareslider/Tests/Functional/Fixtures/Frontend/page.typoscript',
                ],
            ],
        );
    }

    private function render(string $path = '/'): string
    {
        return (string)$this->executeFrontendSubRequest(new InternalRequest('http://localhost' . $path))->getBody();
    }

    /**
     * Markup of one content element (the fluid_styled_content frame "c{uid}").
     */
    private function element(string $html, int $uid): string
    {
        preg_match('#<div id="c' . $uid . '" class="frame[^"]*">(.*?)(?=<div id="c\d+" class="frame|</body>)#s', $html, $m);
        self::assertNotEmpty($m[1] ?? '', 'element c' . $uid . ' not found');
        return $m[1];
    }

    #[Test]
    public function sliderRendersBothImagesLabelsAndAccessibleHandle(): void
    {
        $this->setUpTypoScript();
        $el = $this->element($this->render(), 1);

        self::assertStringContainsString('class="compareslider compareslider--vertical"', $el);
        self::assertStringContainsString('data-orientation="vertical"', $el);
        self::assertStringContainsString('data-start="30"', $el);
        self::assertMatchesRegularExpression('#compareslider__media--after">\s*<img[^>]+after[^"]*\.png#', $el);
        self::assertMatchesRegularExpression('#compareslider__media--before">\s*<img[^>]+before[^"]*\.png#', $el);
        self::assertStringContainsString('compareslider__label--before">Old<', $el);
        self::assertStringContainsString('compareslider__label--after">New<', $el);
        self::assertStringContainsString('role="slider"', $el);
        self::assertStringContainsString('aria-valuemin="0"', $el);
        self::assertStringContainsString('aria-valuemax="100"', $el);
        self::assertStringContainsString('aria-valuenow="30"', $el);
        self::assertStringContainsString('aria-valuetext="30 %"', $el);
        self::assertStringContainsString('aria-orientation="vertical"', $el);
    }

    #[Test]
    public function headerFieldIsRenderedByFluidStyledContentAndNamesTheHandle(): void
    {
        $this->setUpTypoScript();
        $el = $this->element($this->render(), 1);

        self::assertStringContainsString('frame-type-pxe_compareslider', $this->render());
        self::assertMatchesRegularExpression('#<h2[^>]*>\s*Kitchen\s*</h2>#', $el);
        self::assertMatchesRegularExpression('#<h3[^>]*>\s*Spring 2026\s*</h3>#', $el, 'subheader');
        self::assertStringContainsString('aria-label="Kitchen"', $el);
        self::assertStringNotContainsString('compareslider__caption', $el, 'no legacy caption when the header field is used');
    }

    #[Test]
    public function handleFallsBackToTranslatedLabelWithoutAnyTitle(): void
    {
        $this->setUpTypoScript();
        $el = $this->element($this->render(), 3);

        self::assertStringContainsString('aria-label="Image comparison"', $el);
        self::assertStringContainsString('data-start="50"', $el, 'missing start position defaults to 50');
        self::assertStringContainsString('compareslider--horizontal', $el);
    }

    #[Test]
    public function legacyFlexFormTitleIsUsedWhenHeaderIsEmpty(): void
    {
        $this->setUpTypoScript();
        $html = $this->render();

        $captionOnly = $this->element($html, 2);
        self::assertStringNotContainsString('role="slider"', $captionOnly, 'no slider without images');
        self::assertStringContainsString('<strong>Caption only</strong>', $captionOnly);
        self::assertStringContainsString('Legacy subtitle', $captionOnly);

        $legacy = $this->element($html, 4);
        self::assertStringContainsString('aria-label="Legacy name"', $legacy);
        self::assertStringContainsString('<strong>Legacy name</strong>', $legacy);

        $headerWins = $this->element($html, 5);
        self::assertStringContainsString('aria-label="Header wins"', $headerWins);
        self::assertStringNotContainsString('Ignored legacy title', $headerWins);
    }

    #[Test]
    public function imagesCarryDimensionsLazyLoadingSrcsetAndAltText(): void
    {
        $this->setUpTypoScript();
        $html = $this->render();
        $el = $this->element($html, 1);

        preg_match_all('#<img[^>]+>#', $el, $images);
        self::assertCount(2, $images[0]);
        [$after, $before] = $images[0];

        // 1600x800 source, maxWidth 1540 -> scaled, no layout shift
        self::assertStringContainsString('width="1540"', $after);
        self::assertStringContainsString('height="770"', $after);
        self::assertStringContainsString('loading="lazy"', $after);
        self::assertStringContainsString('draggable="false"', $after);
        self::assertStringContainsString('sizes="100vw"', $after);
        self::assertMatchesRegularExpression('#srcset="[^"]*\b480w,[^"]*\b768w,[^"]*\b1024w,[^"]*\b1540w"#', $after);

        // alt: metadata "alternative" first, then metadata "title", then the slider label
        self::assertStringContainsString('alt="Before the renovation"', $before);
        self::assertStringContainsString('alt="After title"', $after);
        $webp = $this->element($html, 4);
        self::assertMatchesRegularExpression('#<img(?=[^>]*after\.webp)[^>]*alt="Nachher"#', $webp);
    }

    #[Test]
    public function webpIsRenderedAndEagerLoadingCanBeForced(): void
    {
        $this->setUpTypoScript();
        $el = $this->element($this->render(), 4);

        preg_match_all('#<img[^>]+>#', $el, $images);
        self::assertCount(2, $images[0]);
        foreach ($images[0] as $image) {
            self::assertStringContainsString('loading="eager"', $image);
        }
        self::assertMatchesRegularExpression('#src="[^"]*after\.webp"#', $images[0][0]);
        // 320px source is never upscaled: a single candidate remains
        self::assertMatchesRegularExpression('#srcset="[^"]*after\.webp 320w"#', $images[0][0]);
    }

    #[Test]
    public function assetsAreCollectedOnlyOnPagesWithASlider(): void
    {
        $this->setUpTypoScript();

        $withSlider = $this->render('/');
        self::assertMatchesRegularExpression('#<link[^>]+compareslider\.css#', $withSlider);
        self::assertMatchesRegularExpression('#<script[^>]*defer[^>]*compareslider\.js#', $withSlider);
        self::assertSame(1, substr_count($withSlider, 'compareslider.css'), 'stylesheet added once for four sliders');
        self::assertSame(1, substr_count($withSlider, 'compareslider.js'), 'script added once for four sliders');

        $withoutSlider = $this->render('/empty');
        self::assertStringNotContainsString('compareslider.css', $withoutSlider);
        self::assertStringNotContainsString('compareslider.js', $withoutSlider);
    }

    #[Test]
    public function assetsAreIncludedSiteWideWhenTheConstantIsSet(): void
    {
        $this->setUpTypoScript(['EXT:pxe_compareslider/Tests/Functional/Fixtures/Frontend/constants_global_assets.typoscript']);

        $withoutSlider = $this->render('/empty');
        self::assertStringContainsString('compareslider.css', $withoutSlider);
        self::assertStringContainsString('compareslider.js', $withoutSlider);

        $withSlider = $this->render('/');
        self::assertSame(1, substr_count($withSlider, 'compareslider.css'), 'no double include via AssetCollector');
        self::assertSame(1, substr_count($withSlider, 'compareslider.js'), 'no double include via AssetCollector');
    }
}
