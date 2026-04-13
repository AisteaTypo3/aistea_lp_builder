<?php

declare(strict_types=1);

namespace Aistea\LpBuilder\Eid;

use Aistea\LpBuilder\Domain\Repository\SlideRepository;
use Aistea\LpBuilder\Service\SlideDataService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class SlidesEid
{
    public static function main(?ServerRequestInterface $request = null): ResponseInterface
    {
        try {
            $request ??= $GLOBALS['TYPO3_REQUEST'] ?? null;
            if (!$request instanceof ServerRequestInterface) {
                return self::json(['error' => 'Missing request context'], 500);
            }

            $queryParams = $request->getQueryParams();
            $ceUid = (int)($queryParams['ce'] ?? 0);
            if ($ceUid <= 0) {
                return self::json(['error' => 'Missing or invalid ce parameter'], 400);
            }

            /** @var ConnectionPool $connectionPool */
            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
            /** @var ResourceFactory $resourceFactory */
            $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);

            $languageId = isset($queryParams['L']) ? (int)$queryParams['L'] : 0;

            $slideRepository = new SlideRepository($connectionPool);
            $slideDataService = new SlideDataService($slideRepository, $resourceFactory, $connectionPool);

            $slides = $slideDataService->getHeavySlides($ceUid, $languageId);
            return self::json([
                'ce' => $ceUid,
                'count' => count($slides),
                'slides' => $slides,
                'generatedAt' => gmdate(DATE_ATOM),
            ]);
        } catch (\Throwable $exception) {
            return self::json([
                'error' => 'Endpoint error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * @param array<string,mixed> $payload
     */
    private static function json(array $payload, int $statusCode = 200): JsonResponse
    {
        return new JsonResponse($payload, $statusCode, [
            'Cache-Control' => 'public, max-age=3600, s-maxage=3600',
        ]);
    }
}
