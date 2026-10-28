<?php

declare(strict_types=1);

namespace Pixelegg\PxeCompareslider\Service;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class CompareSliderUsageService
{
    private const CTYPE = 'pxe_compareslider';

    public function __construct(
        private ConnectionPool $connectionPool,
    ) {}

    /**
     * @return list<array{contentUid:int,pageUid:int,pageTitle:string,header:string,hidden:bool,languageUid:int,hasBefore:bool,hasAfter:bool,incomplete:bool,beforeReferenceUid:?int,afterReferenceUid:?int}>
     */
    public function getUsages(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $rows = $queryBuilder
            ->select('uid', 'pid', 'header', 'hidden', 'sys_language_uid')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq(
                'CType',
                $queryBuilder->createNamedParameter(self::CTYPE)
            ))
            ->orderBy('pid')
            ->addOrderBy('sorting')
            ->executeQuery()
            ->fetchAllAssociative();

        if ($rows === []) {
            return [];
        }

        $contentUids = array_map(static fn(array $r): int => (int)$r['uid'], $rows);
        $references = $this->referenceUids($contentUids);
        $titles = $this->pageTitles(array_values(array_unique(
            array_map(static fn(array $r): int => (int)$r['pid'], $rows)
        )));

        $usages = [];
        foreach ($rows as $row) {
            $uid = (int)$row['uid'];
            $beforeRef = $references[$uid]['image_before'] ?? null;
            $afterRef = $references[$uid]['image_after'] ?? null;
            $hasBefore = $beforeRef !== null;
            $hasAfter = $afterRef !== null;
            $usages[] = [
                'contentUid' => $uid,
                'pageUid' => (int)$row['pid'],
                'pageTitle' => $titles[(int)$row['pid']] ?? '',
                'header' => (string)$row['header'],
                'hidden' => (bool)$row['hidden'],
                'languageUid' => (int)$row['sys_language_uid'],
                'hasBefore' => $hasBefore,
                'hasAfter' => $hasAfter,
                'incomplete' => !$hasBefore || !$hasAfter,
                'beforeReferenceUid' => $beforeRef,
                'afterReferenceUid' => $afterRef,
            ];
        }

        return $usages;
    }

    /**
     * @param list<array{incomplete:bool,hidden:bool,pageUid:int}> $usages
     * @return array{total:int,pages:int,hidden:int,incomplete:int}
     */
    public function getStatistics(array $usages): array
    {
        $pages = [];
        $hidden = 0;
        $incomplete = 0;
        foreach ($usages as $usage) {
            $pages[$usage['pageUid']] = true;
            if ($usage['hidden']) {
                $hidden++;
            }
            if ($usage['incomplete']) {
                $incomplete++;
            }
        }

        return [
            'total' => count($usages),
            'pages' => count($pages),
            'hidden' => $hidden,
            'incomplete' => $incomplete,
        ];
    }

    /**
     * @param list<int> $contentUids
     * @return array<int, array<string, int>> [uid_foreign => [fieldname => sys_file_reference.uid]]
     */
    private function referenceUids(array $contentUids): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $rows = $queryBuilder
            ->select('uid', 'uid_foreign', 'fieldname')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter('tt_content')),
                $queryBuilder->expr()->in('fieldname', $queryBuilder->createNamedParameter(
                    ['image_before', 'image_after'],
                    Connection::PARAM_STR_ARRAY
                )),
                $queryBuilder->expr()->in('uid_foreign', $queryBuilder->createNamedParameter(
                    $contentUids,
                    Connection::PARAM_INT_ARRAY
                ))
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();

        $map = [];
        foreach ($rows as $row) {
            $foreign = (int)$row['uid_foreign'];
            $field = (string)$row['fieldname'];
            $map[$foreign][$field] ??= (int)$row['uid'];
        }

        return $map;
    }

    /**
     * @param list<int> $pageUids
     * @return array<int, string>
     */
    private function pageTitles(array $pageUids): array
    {
        if ($pageUids === []) {
            return [];
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $rows = $queryBuilder
            ->select('uid', 'title')
            ->from('pages')
            ->where($queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter(
                $pageUids,
                Connection::PARAM_INT_ARRAY
            )))
            ->executeQuery()
            ->fetchAllAssociative();

        $titles = [];
        foreach ($rows as $row) {
            $titles[(int)$row['uid']] = (string)$row['title'];
        }

        return $titles;
    }
}
