<?php

declare(strict_types=1);

namespace Aistea\LpBuilder\DataProcessing;

use Aistea\LpBuilder\Domain\Repository\SlideRepository;
use Aistea\LpBuilder\Service\SlideDataService;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

final class ProductSliderDataProcessor implements DataProcessorInterface
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
        $slideRepository = new SlideRepository($connectionPool);
        $slideDataService = new SlideDataService($slideRepository, $resourceFactory, $connectionPool);

        $languageId = (int)$context->getPropertyFromAspect('language', 'id', 0);

        $processedData['slides'] = $slideDataService->getLightweightSlides($ceUid, $languageId);
        $processedData['endpoint'] = '/index.php?eID=aisteaLpBuilderSlides&ce=' . $ceUid;

        return $processedData;
    }
}
