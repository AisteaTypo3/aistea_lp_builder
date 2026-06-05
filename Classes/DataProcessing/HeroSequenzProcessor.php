<?php

declare(strict_types=1);

namespace Aistea\LpBuilder\DataProcessing;

use Aistea\LpBuilder\Service\HeroFrameResolver;
use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

final class HeroSequenzProcessor implements DataProcessorInterface
{
    /**
     * @param array<string,mixed> $contentObjectConfiguration
     * @param array<string,mixed> $processorConfiguration
     * @param array<string,mixed> $processedData
     * @return array<string,mixed>
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData,
    ): array {
        $record = (array)($processedData['data'] ?? []);
        $uid = (int)($record['uid'] ?? 0);

        $frameResolver = GeneralUtility::makeInstance(HeroFrameResolver::class);
        $frameData = $frameResolver->resolveFromContentRecord($record);

        $firstFrameUrl = (string)$frameData['firstFrameUrl'];
        $lastFrameUrl = (string)$frameData['lastFrameUrl'];
        $staticImageReference = $this->resolveStaticImageReference($uid);
        $fallbackUrl = $this->resolveFallbackUrl($record, $firstFrameUrl, $lastFrameUrl, $staticImageReference);

        $endpointUrl = '/_hero-sequenz/frames?ce=' . $uid;
        $languageId = GeneralUtility::makeInstance(Context::class)->getAspect('language')->getId();
        if ($languageId > 0) {
            $endpointUrl .= '&L=' . $languageId;
        }

        $scrollDriven = (bool)($record['scroll_driven'] ?? false);
        $fps = max(1, (int)($record['desktop_fps'] ?? 24));
        $breakpoint = max(0, (int)($record['mobile_breakpoint'] ?? 768));
        $loop = (bool)($record['desktop_loop'] ?? false);
        $preloadStrategy = (string)($record['preload_strategy'] ?? 'smart');
        if (!in_array($preloadStrategy, ['smart', 'all'], true)) {
            $preloadStrategy = 'smart';
        }

        $altText = trim((string)($record['alt_text'] ?? ''));
        if ($altText === '') {
            $altText = trim((string)($record['header'] ?? ''));
        }

        $backgroundColor = trim((string)($record['background_color'] ?? ''));
        $dimensions = $this->resolveDimensions(
            $frameData['lastFrameFile'] instanceof FileInterface ? $frameData['lastFrameFile'] : null,
            $frameData['firstFrameFile'] instanceof FileInterface ? $frameData['firstFrameFile'] : null,
            $staticImageReference
        );

        $processedData['heroSequenz'] = [
            'contentUid' => $uid,
            'endpointUrl' => $endpointUrl,
            'fallbackUrl' => $fallbackUrl,
            'firstFrameUrl' => $firstFrameUrl,
            'lastFrameUrl' => $lastFrameUrl,
            'backgroundColor' => $backgroundColor,
            'altText' => $altText,
            'breakpoint' => $breakpoint,
            'scrollDriven' => $scrollDriven,
            'fps' => $fps,
            'loop' => $loop,
            'preloadStrategy' => $preloadStrategy,
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'aspectRatio' => $dimensions['aspectRatio'],
            // Field exists for compatibility but intentionally not used in runtime behavior.
            'renderFullListOnMobile' => (bool)($record['render_full_list_on_mobile'] ?? false),
        ];

        return $processedData;
    }

    /**
     * @param array<string,mixed> $record
     */
    private function resolveFallbackUrl(array $record, string $firstFrameUrl, string $lastFrameUrl, ?FileInterface $staticImageReference): string
    {
        $mobileMode = (string)($record['mobile_mode'] ?? 'lastFrame');
        if ($mobileMode === 'staticImage' && $staticImageReference !== null) {
            $publicUrl = (string)$staticImageReference->getPublicUrl();
            if ($publicUrl !== '') {
                return $publicUrl;
            }
        }

        if ($mobileMode === 'firstFrame' && $firstFrameUrl !== '') {
            return $firstFrameUrl;
        }

        if ($lastFrameUrl !== '') {
            return $lastFrameUrl;
        }

        return $firstFrameUrl;
    }

    /**
     * @return array{width:int,height:int,aspectRatio:string}
     */
    private function resolveDimensions(?FileInterface $lastFrameFile, ?FileInterface $firstFrameFile, ?FileInterface $staticImageReference): array
    {
        $width = 0;
        $height = 0;

        if ($staticImageReference !== null) {
            $width = (int)$staticImageReference->getProperty('width');
            $height = (int)$staticImageReference->getProperty('height');
        }

        if ($width <= 0 || $height <= 0) {
            $source = $lastFrameFile ?? $firstFrameFile;
            if ($source instanceof FileInterface) {
                $width = (int)$source->getProperty('width');
                $height = (int)$source->getProperty('height');
            }
        }

        $aspectRatio = '';
        if ($width > 0 && $height > 0) {
            $aspectRatio = $width . ' / ' . $height;
        }

        return [
            'width' => $width,
            'height' => $height,
            'aspectRatio' => $aspectRatio,
        ];
    }

    private function resolveStaticImageReference(int $contentUid): ?FileInterface
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        $referenceUid = $queryBuilder
            ->select('uid')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter('tt_content')),
                $queryBuilder->expr()->eq('fieldname', $queryBuilder->createNamedParameter('mobile_static_image')),
                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($contentUid, ParameterType::INTEGER))
            )
            ->orderBy('sorting_foreign', 'ASC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();
        if (!is_numeric($referenceUid)) {
            return null;
        }

        try {
            return GeneralUtility::makeInstance(ResourceFactory::class)->getFileReferenceObject((int)$referenceUid);
        } catch (\Throwable) {
            return null;
        }
    }
}
