<?php

declare(strict_types=1);

namespace Aistea\LpBuilder\DataProcessing;

use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

final class ThreeDHotspotViewerDataProcessor implements DataProcessorInterface
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

        $hotspots = $this->getHotspots($connectionPool, $contentUid);
        $backgroundColor = trim((string)($data['tx_aistealpproductslider_3dhv_background_color'] ?? ''));
        $initialHotspot = $hotspots[0] ?? null;

        $processedData['threeDHotspotViewer'] = [
            'modelUrl' => $this->getFileUrl($connectionPool, $resourceFactory, $contentUid, 'tx_aistealpproductslider_3dhv_model'),
            'posterUrl' => $this->getFileUrl($connectionPool, $resourceFactory, $contentUid, 'tx_aistealpproductslider_3dhv_poster'),
            'envMapUrl' => $this->getFileUrl($connectionPool, $resourceFactory, $contentUid, 'tx_aistealpproductslider_3dhv_env_map'),
            'backgroundColor' => $backgroundColor !== '' ? $backgroundColor : '#0f1014',
            'debugPickerEnabled' => (int)($data['tx_aistealpproductslider_3dhv_debug_picker'] ?? 0) === 1,
            'hotspots' => $hotspots,
            'hotspotsJson' => json_encode($hotspots, JSON_THROW_ON_ERROR | JSON_HEX_TAG),
            'initialHotspot' => $initialHotspot,
        ];

        return $processedData;
    }

    private function getFileUrl(
        ConnectionPool $connectionPool,
        ResourceFactory $resourceFactory,
        int $contentUid,
        string $fieldName
    ): string {
        if ($contentUid <= 0 || $fieldName === '') {
            return '';
        }

        $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_file_reference');
        $row = $queryBuilder
            ->select('uid')
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
     * @return list<array{uid:int,title:string,description:string,position:array{x:float,y:float,z:float}}>
     */
    private function getHotspots(ConnectionPool $connectionPool, int $contentUid): array
    {
        if ($contentUid <= 0) {
            return [];
        }

        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_aistealpproductslider_3dhotspot');
        $rows = $queryBuilder
            ->select('uid', 'title', 'description', 'pos_x', 'pos_y', 'pos_z')
            ->from('tx_aistealpproductslider_3dhotspot')
            ->where(
                $queryBuilder->expr()->eq('parentid', $queryBuilder->createNamedParameter($contentUid, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('parenttable', $queryBuilder->createNamedParameter('tt_content')),
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->eq('hidden', 0)
            )
            ->orderBy('sorting', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        return array_values(array_map(static fn (array $row): array => [
            'uid' => (int)$row['uid'],
            'title' => (string)($row['title'] ?? ''),
            'description' => (string)($row['description'] ?? ''),
            'position' => [
                'x' => (float)($row['pos_x'] ?? 0),
                'y' => (float)($row['pos_y'] ?? 0),
                'z' => (float)($row['pos_z'] ?? 0),
            ],
        ], $rows));
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
