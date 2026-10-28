<?php

declare(strict_types=1);

namespace Pixelegg\PxeCompareslider\Tests\Functional\Service;

use Pixelegg\PxeCompareslider\Service\CompareSliderUsageService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CompareSliderUsageServiceTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['fluid_styled_content'];

    protected array $testExtensionsToLoad = ['pixelegg/pxe-compareslider'];

    private CompareSliderUsageService $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tt_content.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_reference.csv');
        $this->subject = $this->get(CompareSliderUsageService::class);
    }

    public function testGetUsagesReturnsOnlyCompareSliderElements(): void
    {
        $usages = $this->subject->getUsages();
        self::assertCount(3, $usages);
        self::assertSame([1, 2, 3], array_map(static fn($u) => $u['contentUid'], $usages));
    }

    public function testUsageCarriesPageTitleAndImageFlags(): void
    {
        $byUid = [];
        foreach ($this->subject->getUsages() as $usage) {
            $byUid[$usage['contentUid']] = $usage;
        }

        self::assertSame('Page A', $byUid[1]['pageTitle']);
        self::assertTrue($byUid[1]['hasBefore']);
        self::assertTrue($byUid[1]['hasAfter']);
        self::assertFalse($byUid[1]['incomplete']);

        self::assertTrue($byUid[2]['hasBefore']);
        self::assertFalse($byUid[2]['hasAfter']);
        self::assertTrue($byUid[2]['incomplete']);

        self::assertTrue($byUid[3]['hidden']);
        self::assertSame(104, $byUid[3]['beforeReferenceUid']);
    }

    public function testStatistics(): void
    {
        $stats = $this->subject->getStatistics($this->subject->getUsages());
        self::assertSame(3, $stats['total']);
        self::assertSame(2, $stats['pages']);
        self::assertSame(1, $stats['hidden']);
        self::assertSame(1, $stats['incomplete']);
    }
}
