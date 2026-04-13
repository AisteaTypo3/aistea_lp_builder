<?php

declare(strict_types=1);

namespace Aistea\LpBuilder\DataProcessing;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

final class HorizontalSliderDataProcessor implements DataProcessorInterface
{
    /**
     * @param array<string, mixed> $contentObjectConfiguration
     * @param array<string, mixed> $processedData
     * @param array<string, mixed> $processorConfiguration
     * @return array<string, mixed>
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        $data = $processedData['data'] ?? [];
        $ceUid = (int)($data['uid'] ?? 0);

        /** @var Context $context */
        $context = GeneralUtility::makeInstance(Context::class);
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        /** @var ResourceFactory $resourceFactory */
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);

        $languageId = (int)$context->getPropertyFromAspect('language', 'id', 0);
        $rows = $this->fetchRows($connectionPool, $ceUid, $languageId);

        $slides = [];
        foreach ($rows as $row) {
            $uid = (int)$row['uid'];
            $type = (string)($row['slide_type'] ?? 'image');
            $sequenceFrames = [];
            if ($type === 'sequence') {
                $sequenceFrames = $this->getFileUrls($connectionPool, $resourceFactory, $uid, 'media_sequence');
            }

            $imageUrl = $type === 'image'
                ? $this->getFirstFileUrl($connectionPool, $resourceFactory, $uid, 'media_image')
                : '';
            $videoUrl = $type === 'video'
                ? $this->getFirstFileUrl($connectionPool, $resourceFactory, $uid, 'media_video')
                : '';

            $fallbackImage = $imageUrl;
            if ($fallbackImage === '' && !empty($sequenceFrames)) {
                $fallbackImage = (string)$sequenceFrames[0];
            }

            $slides[] = [
                'slideUid' => $uid,
                'type' => $type,
                'title' => (string)($row['title'] ?? ''),
                'headline' => trim((string)($row['headline'] ?? '')),
                'imageUrl' => $imageUrl,
                'videoUrl' => $videoUrl,
                'sequenceFrames' => $sequenceFrames,
                'sequenceFps' => max(1, min(60, (int)($row['sequence_fps'] ?? 12))),
                'fallbackImage' => $fallbackImage,
            ];
        }

        $processedData['slides'] = $slides;
        $processedData['slidesJson'] = (string)json_encode(
            $slides,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        return $processedData;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchRows(ConnectionPool $connectionPool, int $contentElementUid, int $languageId): array
    {
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_aistealpproductslider_hslide');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(new FrontendRestrictionContainer());

        $rows = $queryBuilder
            ->select('*')
            ->from('tx_aistealpproductslider_hslide')
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
            return array_values(array_filter($rows, static fn (array $row): bool => (int)$row['sys_language_uid'] <= 0));
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
            static fn (array $a, array $b): int => ((int)$a['sorting']) <=> ((int)$b['sorting'])
        );

        return $result;
    }

    private function getFirstFileUrl(
        ConnectionPool $connectionPool,
        ResourceFactory $resourceFactory,
        int $slideUid,
        string $fieldName
    ): string {
        $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_file_reference');
        $row = $queryBuilder
            ->select('uid')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter('tx_aistealpproductslider_hslide')),
                $queryBuilder->expr()->eq('fieldname', $queryBuilder->createNamedParameter($fieldName)),
                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($slideUid, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->orderBy('sorting_foreign', 'ASC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if (!is_array($row) || !isset($row['uid'])) {
            return '';
        }

        try {
            $fileReference = $resourceFactory->getFileReferenceObject((int)$row['uid']);
            return $this->normalizePublicUrl((string)$fileReference->getOriginalFile()->getPublicUrl());
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * @return array<int, string>
     */
    private function getFileUrls(
        ConnectionPool $connectionPool,
        ResourceFactory $resourceFactory,
        int $slideUid,
        string $fieldName
    ): array {
        $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_file_reference');
        $rows = $queryBuilder
            ->select('uid')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter('tx_aistealpproductslider_hslide')),
                $queryBuilder->expr()->eq('fieldname', $queryBuilder->createNamedParameter($fieldName)),
                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($slideUid, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->orderBy('sorting_foreign', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        $urls = [];
        foreach ($rows as $row) {
            if (!isset($row['uid'])) {
                continue;
            }
            try {
                $fileReference = $resourceFactory->getFileReferenceObject((int)$row['uid']);
                $url = $this->normalizePublicUrl((string)$fileReference->getOriginalFile()->getPublicUrl());
                if ($url !== '') {
                    $urls[] = $url;
                }
            } catch (\Throwable) {
                // Ignore broken references and continue with remaining files.
            }
        }

        return $urls;
    }

    private function normalizePublicUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '//')) {
            return $url;
        }

        if (!str_starts_with($url, '/')) {
            return '/' . ltrim($url, '/');
        }

        return $url;
    }
}
