<?php

declare(strict_types=1);

namespace Pixelegg\PxeCompareslider\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Pixelegg\PxeCompareslider\Service\CompareSliderUsageService;
use TYPO3\CMS\Core\Database\ConnectionPool;

final class CompareSliderUsageServiceTest extends TestCase
{
    private CompareSliderUsageService $subject;

    protected function setUp(): void
    {
        parent::setUp();
        // getStatistics() is pure; the connection pool is never touched.
        $this->subject = new CompareSliderUsageService(self::createStub(ConnectionPool::class));
    }

    #[Test]
    public function statisticsOfNoUsagesAreAllZero(): void
    {
        self::assertSame(
            ['total' => 0, 'pages' => 0, 'hidden' => 0, 'incomplete' => 0],
            $this->subject->getStatistics([])
        );
    }

    #[Test]
    public function statisticsCountDistinctPagesHiddenAndIncompleteElements(): void
    {
        $usages = [
            ['pageUid' => 10, 'hidden' => false, 'incomplete' => false],
            ['pageUid' => 10, 'hidden' => true, 'incomplete' => true],
            ['pageUid' => 11, 'hidden' => true, 'incomplete' => false],
            ['pageUid' => 12, 'hidden' => false, 'incomplete' => true],
        ];

        self::assertSame(
            ['total' => 4, 'pages' => 3, 'hidden' => 2, 'incomplete' => 2],
            $this->subject->getStatistics($usages)
        );
    }
}
