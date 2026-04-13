<?php

declare(strict_types=1);

namespace Aistea\LpBuilder\Middleware;

use Aistea\LpBuilder\Service\SlideDataService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;

final readonly class SlideEndpointMiddleware implements MiddlewareInterface
{
    public function __construct(private SlideDataService $slideDataService)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        if ($path !== '/_lp-product-slider/slides') {
            return $handler->handle($request);
        }

        if (strtoupper($request->getMethod()) !== 'GET') {
            return new JsonResponse(['error' => 'Method not allowed'], 405, ['Allow' => 'GET']);
        }

        $queryParams = $request->getQueryParams();
        $ceUid = (int)($queryParams['ce'] ?? 0);
        if ($ceUid <= 0) {
            return new JsonResponse(['error' => 'Missing or invalid ce parameter'], 400);
        }

        $language = $request->getAttribute('language');
        $languageId = is_object($language) && method_exists($language, 'getLanguageId') ? (int)$language->getLanguageId() : 0;

        $slides = $this->slideDataService->getHeavySlides($ceUid, $languageId);
        $json = [
            'ce' => $ceUid,
            'count' => count($slides),
            'slides' => $slides,
            'generatedAt' => gmdate(DATE_ATOM),
        ];

        $encodedJson = json_encode($json);
        if (!is_string($encodedJson)) {
            $encodedJson = '{}';
        }
        $etag = '"' . sha1($encodedJson) . '"';
        $ifNoneMatch = $request->getHeaderLine('If-None-Match');
        if ($ifNoneMatch !== '' && $ifNoneMatch === $etag) {
            return new JsonResponse(null, 304, [
                'ETag' => $etag,
                'Cache-Control' => 'public, max-age=3600, s-maxage=3600',
            ]);
        }

        return new JsonResponse($json, 200, [
            'Cache-Control' => 'public, max-age=3600, s-maxage=3600',
            'ETag' => $etag,
            'Vary' => 'Accept-Language',
        ]);
    }
}
