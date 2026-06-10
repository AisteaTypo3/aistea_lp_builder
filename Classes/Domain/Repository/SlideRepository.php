<?php

declare(strict_types=1);

namespace Aistea\LpBuilder\Domain\Repository;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;

final class SlideRepository
{
    private ConnectionPool $connectionPool;

    public function __construct(ConnectionPool $connectionPool)
    {
        $this->connectionPool = $connectionPool;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByContentElement(int $contentElementUid, int $languageId): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_aistealpproductslider_slide');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(new FrontendRestrictionContainer());

        $rows = $queryBuilder
            ->select('*')
            ->from('tx_aistealpproductslider_slide')
            ->where(
                $queryBuilder->expr()->eq('parentid', $queryBuilder->createNamedParameter($contentElementUid, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('parenttable', $queryBuilder->createNamedParameter('tt_content')),
                $queryBuilder->expr()->in(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter([-1, 0, $languageId], ArrayParameterType::INTEGER)
                )
            )
            ->orderBy('sorting', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        if ($languageId <= 0) {
            return array_values(array_filter(
                $rows,
                static function (array $row): bool {
                    return (int)$row['sys_language_uid'] <= 0;
                }
            ));
        }

        return $this->overlayRows($rows, $languageId);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function overlayRows(array $rows, int $languageId): array
    {
        $defaultRows = [];
        $translationsByParent = [];
        $freeModeRows = [];

        foreach ($rows as $row) {
            $sysLanguageUid = (int)$row['sys_language_uid'];
            if ($sysLanguageUid === 0 || $sysLanguageUid === -1) {
                $defaultRows[(int)$row['uid']] = $row;
                continue;
            }

            if ($sysLanguageUid === $languageId) {
                $parent = (int)$row['l10n_parent'];
                if ($parent > 0) {
                    $translationsByParent[$parent] = $row;
                } else {
                    $freeModeRows[] = $row;
                }
            }
        }

        $result = [];
        foreach ($defaultRows as $uid => $defaultRow) {
            $result[] = $translationsByParent[$uid] ?? $defaultRow;
        }

        foreach ($freeModeRows as $row) {
            $result[] = $row;
        }

        usort(
            $result,
            static function (array $a, array $b): int {
                return ((int)$a['sorting']) <=> ((int)$b['sorting']);
            }
        );

        return $result;
    }
}
