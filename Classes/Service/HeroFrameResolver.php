<?php

declare(strict_types=1);

namespace Aistea\LpBuilder\Service;

use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class HeroFrameResolver
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'avif'];

    /**
     * @param array<string,mixed> $contentRecord
     * @return array{
     *     frames: array<int, string>,
     *     firstFrameUrl: string,
     *     lastFrameUrl: string,
     *     firstFrameFile: ?FileInterface,
     *     lastFrameFile: ?FileInterface
     * }
     */
    public function resolveFromContentRecord(array $contentRecord): array
    {
        return $this->resolveByCollectionUid((int)($contentRecord['file_collection'] ?? 0));
    }

    /**
     * @return array{
     *     frames: array<int, string>,
     *     firstFrameUrl: string,
     *     lastFrameUrl: string,
     *     firstFrameFile: ?FileInterface,
     *     lastFrameFile: ?FileInterface
     * }
     */
    public function resolveByCollectionUid(int $collectionUid): array
    {
        if ($collectionUid <= 0) {
            return $this->emptyResult();
        }

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_file_collection');
        $collection = $queryBuilder
            ->select('uid', 'type', 'folder_identifier', 'recursive', 'deleted')
            ->from('sys_file_collection')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($collectionUid, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('deleted', 0),
            )
            ->executeQuery()
            ->fetchAssociative();

        if (!is_array($collection) || ($collection['type'] ?? '') !== 'folder') {
            return $this->emptyResult();
        }

        $folderIdentifier = (string)($collection['folder_identifier'] ?? '');
        if ($folderIdentifier === '' || !str_contains($folderIdentifier, ':')) {
            return $this->emptyResult();
        }

        try {
            $folder = $this->getResourceFactory()->getFolderObjectFromCombinedIdentifier($folderIdentifier);
            $recursive = (bool)($collection['recursive'] ?? false);
            $folderFiles = $folder->getFiles(0, 0, Folder::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, $recursive);
        } catch (\Throwable) {
            return $this->emptyResult();
        }

        $imageFiles = array_values(array_filter(
            $folderFiles,
            function (FileInterface $file): bool {
                return in_array(strtolower((string)$file->getExtension()), self::ALLOWED_EXTENSIONS, true);
            }
        ));

        usort(
            $imageFiles,
            static fn (FileInterface $a, FileInterface $b): int => strnatcasecmp($a->getName(), $b->getName())
        );

        $resolvedFrames = [];
        foreach ($imageFiles as $file) {
            $publicUrl = (string)$file->getPublicUrl();
            if ($publicUrl === '') {
                continue;
            }
            $resolvedFrames[] = [
                'url' => $publicUrl,
                'file' => $file,
            ];
        }

        if ($resolvedFrames === []) {
            return $this->emptyResult();
        }

        $first = $resolvedFrames[0];
        $last = $resolvedFrames[array_key_last($resolvedFrames)];

        return [
            'frames' => array_values(array_column($resolvedFrames, 'url')),
            'firstFrameUrl' => (string)$first['url'],
            'lastFrameUrl' => (string)$last['url'],
            'firstFrameFile' => $first['file'] instanceof FileInterface ? $first['file'] : null,
            'lastFrameFile' => $last['file'] instanceof FileInterface ? $last['file'] : null,
        ];
    }

    /**
     * @return array{frames: array<int, string>, firstFrameUrl: string, lastFrameUrl: string, firstFrameFile: null, lastFrameFile: null}
     */
    private function emptyResult(): array
    {
        return [
            'frames' => [],
            'firstFrameUrl' => '',
            'lastFrameUrl' => '',
            'firstFrameFile' => null,
            'lastFrameFile' => null,
        ];
    }

    private function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }

    private function getResourceFactory(): ResourceFactory
    {
        return GeneralUtility::makeInstance(ResourceFactory::class);
    }
}
