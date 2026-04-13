<?php

declare(strict_types=1);

namespace Aistea\LpBuilder\DataProcessing;

use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

final class BeforeAfterDataProcessor implements DataProcessorInterface
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
        $contentUid = (int)($data['uid'] ?? 0);

        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        /** @var ResourceFactory $resourceFactory */
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);

        $initialPosition = max(0, min(100, (int)($data['tx_aistealpproductslider_ba_initial_position'] ?? 50)));

        $processedData['ba'] = [
            'beforeImage' => $this->getImageData($connectionPool, $resourceFactory, $contentUid, 'tx_aistealpproductslider_ba_image_before'),
            'afterImage'  => $this->getImageData($connectionPool, $resourceFactory, $contentUid, 'tx_aistealpproductslider_ba_image_after'),
            'labelBefore' => trim((string)($data['tx_aistealpproductslider_ba_label_before'] ?? '')),
            'labelAfter'  => trim((string)($data['tx_aistealpproductslider_ba_label_after'] ?? '')),
            'initialPosition' => $initialPosition,
        ];

        return $processedData;
    }

    /**
     * @return array{url: string, alt: string}
     */
    private function getImageData(
        ConnectionPool $connectionPool,
        ResourceFactory $resourceFactory,
        int $contentUid,
        string $fieldName
    ): array {
        $empty = ['url' => '', 'alt' => ''];

        if ($contentUid <= 0) {
            return $empty;
        }

        $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_file_reference');
        $row = $queryBuilder
            ->select('uid', 'alternative')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter('tt_content')),
                $queryBuilder->expr()->eq('fieldname', $queryBuilder->createNamedParameter($fieldName)),
                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($contentUid, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->orderBy('sorting_foreign', 'ASC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if (!is_array($row) || !isset($row['uid'])) {
            return $empty;
        }

        try {
            $fileReference = $resourceFactory->getFileReferenceObject((int)$row['uid']);
            $url = $this->normalizePublicUrl((string)$fileReference->getOriginalFile()->getPublicUrl());
            $alt = trim((string)($row['alternative'] ?? ''))
                ?: trim((string)($fileReference->getOriginalFile()->getProperty('alternative') ?? ''));

            return ['url' => $url, 'alt' => $alt];
        } catch (\Throwable) {
            return $empty;
        }
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
