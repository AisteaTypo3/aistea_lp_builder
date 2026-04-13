<?php

declare(strict_types=1);

namespace Aistea\LpBuilder\Middleware;

use Aistea\LpBuilder\Service\HeroFrameResolver;
use Doctrine\DBAL\ParameterType;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class FramesEndpointMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getUri()->getPath() !== '/_hero-sequenz/frames') {
            return $handler->handle($request);
        }

        $ceValue = (string)($request->getQueryParams()['ce'] ?? '');
        if ($ceValue === '' || !ctype_digit($ceValue)) {
            return new JsonResponse(['error' => 'Invalid ce parameter'], 400);
        }

        $ceUid = (int)$ceValue;
        if ($ceUid <= 0) {
            return new JsonResponse(['error' => 'Invalid ce parameter'], 400);
        }

        $languageId = $this->resolveLanguageId($request);
        $record = $this->findVisibleContentElement($ceUid, $languageId);
        if ($record === null) {
            return new JsonResponse(['error' => 'Content element not found'], 404);
        }

        $frames = $this->getFrameResolver()->resolveFromContentRecord($record);
        if ($frames['frames'] === []) {
            return new JsonResponse(['error' => 'No frames found'], 404);
        }

        $payload = [
            'ce' => (int)$record['uid'],
            'count' => count($frames['frames']),
            'first' => (string)$frames['firstFrameUrl'],
            'last' => (string)$frames['lastFrameUrl'],
            'frames' => $frames['frames'],
        ];

        $etag = '"' . sha1((string)$record['uid'] . '|' . implode('|', $frames['frames'])) . '"';
        $cacheHeaders = [
            'Cache-Control' => 'public, max-age=3600, s-maxage=3600',
            'ETag' => $etag,
        ];

        if (trim((string)$request->getHeaderLine('If-None-Match')) === $etag) {
            return new Response('php://temp', 304, $cacheHeaders);
        }

        return new JsonResponse($payload, 200, $cacheHeaders);
    }

    /**
     * @return array<string,mixed>|null
     */
    private function findVisibleContentElement(int $uid, int $languageId): ?array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tt_content');
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));

        $baseRecord = $queryBuilder
            ->select('uid', 'CType', 'file_collection', 'sys_language_uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('aistea_hero_sequenz')),
            )
            ->executeQuery()
            ->fetchAssociative();

        if (!is_array($baseRecord)) {
            return null;
        }

        if ((int)$baseRecord['sys_language_uid'] === $languageId || $languageId <= 0) {
            return $baseRecord;
        }

        if ((int)$baseRecord['sys_language_uid'] > 0) {
            return null;
        }

        $overlayQueryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tt_content');
        $overlayQueryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));

        $overlay = $overlayQueryBuilder
            ->select('uid', 'CType', 'file_collection', 'sys_language_uid')
            ->from('tt_content')
            ->where(
                $overlayQueryBuilder->expr()->eq('l10n_parent', $overlayQueryBuilder->createNamedParameter($uid, ParameterType::INTEGER)),
                $overlayQueryBuilder->expr()->eq('sys_language_uid', $overlayQueryBuilder->createNamedParameter($languageId, ParameterType::INTEGER)),
                $overlayQueryBuilder->expr()->eq('CType', $overlayQueryBuilder->createNamedParameter('aistea_hero_sequenz')),
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return is_array($overlay) ? $overlay : $baseRecord;
    }

    private function getFrameResolver(): HeroFrameResolver
    {
        return GeneralUtility::makeInstance(HeroFrameResolver::class);
    }

    private function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }

    private function getContext(): Context
    {
        return GeneralUtility::makeInstance(Context::class);
    }

    private function resolveLanguageId(ServerRequestInterface $request): int
    {
        $queryLanguage = (string)($request->getQueryParams()['L'] ?? '');
        if ($queryLanguage !== '' && ctype_digit($queryLanguage)) {
            return (int)$queryLanguage;
        }

        return (int)$this->getContext()->getAspect('language')->getId();
    }
}
