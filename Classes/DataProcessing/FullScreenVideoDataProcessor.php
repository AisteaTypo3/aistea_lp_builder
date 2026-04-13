<?php

declare(strict_types=1);

namespace Aistea\LpBuilder\DataProcessing;

use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

final class FullScreenVideoDataProcessor implements DataProcessorInterface
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

        $shortVideoUrl = $this->getShortVideoUrl($connectionPool, $resourceFactory, $contentUid);
        $vimeoRaw = $this->getLongVideoUrl($connectionPool, $resourceFactory, $contentUid);
        $vimeoEmbedUrl = $this->toVimeoEmbedUrl($vimeoRaw);

        $fsv = [
            'shortVideoUrl' => $shortVideoUrl,
            'vimeoEmbedUrl' => $vimeoEmbedUrl,
            'kicker' => trim((string)($data['tx_aistealpproductslider_fsv_kicker'] ?? '')),
            'headline' => trim((string)($data['tx_aistealpproductslider_fsv_headline'] ?? '')),
            'buttonLabel' => trim((string)($data['tx_aistealpproductslider_fsv_button_label'] ?? '')) ?: 'Watch full video',
        ];

        $processedData['fsv'] = $fsv;

        return $processedData;
    }

    private function getShortVideoUrl(ConnectionPool $connectionPool, ResourceFactory $resourceFactory, int $contentUid): string
    {
        return $this->getFileUrlByField($connectionPool, $resourceFactory, $contentUid, 'tx_aistealpproductslider_fsv_short_video');
    }

    private function getLongVideoUrl(ConnectionPool $connectionPool, ResourceFactory $resourceFactory, int $contentUid): string
    {
        return $this->getFileUrlByField($connectionPool, $resourceFactory, $contentUid, 'tx_aistealpproductslider_fsv_long_video');
    }

    private function getFileUrlByField(
        ConnectionPool $connectionPool,
        ResourceFactory $resourceFactory,
        int $contentUid,
        string $fieldName
    ): string {
        if ($contentUid <= 0) {
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

    private function toVimeoEmbedUrl(string $input): string
    {
        if ($input === '') {
            return '';
        }

        if (preg_match('/(?:vimeo\\.com\\/)(?:video\\/)?(\\d+)/', $input, $matches) === 1) {
            $id = trim((string)$matches[1]);
            if ($id !== '') {
                return 'https://player.vimeo.com/video/' . rawurlencode($id) . '?autoplay=1&title=0&byline=0&portrait=0';
            }
        }

        return '';
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
