<?php

declare(strict_types=1);

namespace Aistea\LpBuilder\Service;

use Aistea\LpBuilder\Domain\Repository\SlideRepository;
use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ResourceFactory;

final readonly class SlideDataService
{
    public function __construct(
        private SlideRepository $slideRepository,
        private ResourceFactory $resourceFactory,
        private ConnectionPool $connectionPool,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLightweightSlides(int $contentElementUid, int $languageId): array
    {
        $slides = $this->slideRepository->findByContentElement($contentElementUid, $languageId);

        return array_map(function (array $slide): array {
            return [
                'uid' => (int)$slide['uid'],
                'type' => (string)$slide['slide_type'],
                'title' => (string)$slide['title'],
                'body' => (string)($slide['bodytext'] ?? ''),
                'ariaLabel' => trim((string)($slide['aria_label'] ?? '')),
                'fallbackImage' => $this->resolveFallbackImage((int)$slide['uid'], (string)$slide['slide_type']),
            ];
        }, $slides);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getHeavySlides(int $contentElementUid, int $languageId): array
    {
        $slides = $this->slideRepository->findByContentElement($contentElementUid, $languageId);
        $result = [];

        foreach ($slides as $slide) {
            $uid = (int)$slide['uid'];
            $slideType = (string)$slide['slide_type'];
            $item = [
                'slideUid' => $uid,
                'type' => $slideType,
                'title' => (string)$slide['title'],
                'body' => (string)($slide['bodytext'] ?? ''),
                'ariaLabel' => trim((string)($slide['aria_label'] ?? '')),
                'fallbackImage' => $this->resolveFallbackImage($uid, $slideType),
            ];

            if ($slideType === 'image') {
                $item['imageUrl'] = $this->getFileUrl($uid, 'media_image');
            } elseif ($slideType === 'video') {
                $item['videoUrl'] = $this->getFileUrl($uid, 'media_video');
                $item['posterUrl'] = $this->getFileUrl($uid, 'video_poster') ?: $item['fallbackImage'];
                $item['startframeUrl'] = $this->getFileUrl($uid, 'video_startframe');
                $item['endframeUrl'] = $this->getFileUrl($uid, 'video_endframe');
            } elseif ($slideType === 'model3d') {
                $item['modelUrl'] = $this->getFileUrl($uid, 'model_gltf');
                $item['posterUrl'] = $this->getFileUrl($uid, 'model_poster') ?: $item['fallbackImage'];
                $item['envMapUrl'] = $this->getFileUrl($uid, 'model_envmap');
                $item['modelOptions'] = [
                    'autorotate' => (bool)$slide['model_autorotate'],
                    'cameraPreset' => (string)($slide['model_camera_preset'] ?? 'front'),
                    'backgroundColor' => (string)($slide['model_bg_color'] ?? ''),
                ];
            } elseif ($slideType === 'colorGallery') {
                $item['colorGallery'] = $this->decodeColorVariants((string)($slide['color_variants'] ?? ''));
            }

            $result[] = $item;
        }

        return $result;
    }

    public function isValidProductSliderContentElement(int $contentElementUid): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $row = $queryBuilder
            ->select('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($contentElementUid, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('aistea_lp_product_slider')),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return is_array($row);
    }

    private function resolveFallbackImage(int $slideUid, string $slideType): string
    {
        return match ($slideType) {
            'image' => $this->getFileUrl($slideUid, 'media_image'),
            'video' => $this->getFileUrl($slideUid, 'video_poster') ?: $this->getFileUrl($slideUid, 'video_startframe'),
            'model3d' => $this->getFileUrl($slideUid, 'model_poster'),
            'colorGallery' => $this->extractFirstColorVariantImage($slideUid),
            default => '',
        };
    }

    private function extractFirstColorVariantImage(int $slideUid): string
    {
        return $this->getFileUrl($slideUid, 'media_image') ?: '';
    }

    private function getFileUrl(int $slideUid, string $fieldName): string
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_reference');
        $referenceRow = $queryBuilder
            ->select('uid')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter('tx_aistealpproductslider_slide')),
                $queryBuilder->expr()->eq('fieldname', $queryBuilder->createNamedParameter($fieldName)),
                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($slideUid, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->orderBy('sorting_foreign', 'ASC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if (!is_array($referenceRow) || !isset($referenceRow['uid'])) {
            return '';
        }

        try {
            $fileReference = $this->resourceFactory->getFileReferenceObject((int)$referenceRow['uid']);
            return $this->normalizePublicUrl((string)$fileReference->getOriginalFile()->getPublicUrl());
        } catch (\Throwable) {
            return '';
        }
    }

    private function normalizePublicUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        // Keep absolute URLs or protocol-relative URLs unchanged.
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '//')) {
            return $url;
        }

        // Ensure root-relative URL so FE language prefixes like /en/ do not break media paths.
        if (!str_starts_with($url, '/')) {
            return '/' . ltrim($url, '/');
        }

        return $url;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function decodeColorVariants(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        try {
            $decoded = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable) {
            return [];
        }
    }
}
