<?php

declare(strict_types=1);

namespace Aistea\LpBuilder\DataProcessing;

use Doctrine\DBAL\ParameterType;
use Traversable;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Collection\AbstractFileCollection;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

final class ImageSequenceDataProcessor implements DataProcessorInterface
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

        $source = (string)($data['tx_aistealpproductslider_sequence_source'] ?? 'filelist');
        $collectionSelection = trim((string)($data['tx_aistealpproductslider_sequence_collection'] ?? ''));
        $frames = [];
        if ($source === 'collection' && $collectionSelection !== '') {
            $frames = $this->getCollectionFrameUrls($connectionPool, $resourceFactory, $collectionSelection);
        }
        if ($frames === []) {
            $frames = $this->getFrameUrls($connectionPool, $resourceFactory, $contentUid);
        }
        $fps = max(1, min(60, (int)($data['tx_aistealpproductslider_sequence_fps'] ?? 12)));
        $loop = (bool)($data['tx_aistealpproductslider_sequence_loop'] ?? false);

        $sequence = [
            'frames' => $frames,
            'fps' => $fps,
            'loop' => $loop,
            'firstFrame' => $frames[0] ?? '',
        ];

        $processedData['sequence'] = $sequence;
        $processedData['sequenceJson'] = (string)json_encode(
            $sequence,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        return $processedData;
    }

    /**
     * @return array<int, string>
     */
    private function getFrameUrls(ConnectionPool $connectionPool, ResourceFactory $resourceFactory, int $contentUid): array
    {
        if ($contentUid <= 0) {
            return [];
        }

        $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_file_reference');
        $rows = $queryBuilder
            ->select('uid')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter('tt_content')),
                $queryBuilder->expr()->eq('fieldname', $queryBuilder->createNamedParameter('tx_aistealpproductslider_sequence_frames')),
                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($contentUid, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->orderBy('sorting_foreign', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        $urls = [];
        foreach ($rows as $row) {
            if (!isset($row['uid'])) {
                continue;
            }
            try {
                $fileReference = $resourceFactory->getFileReferenceObject((int)$row['uid']);
                $url = $this->normalizePublicUrl((string)$fileReference->getOriginalFile()->getPublicUrl());
                if ($url !== '') {
                    $urls[] = $url;
                }
            } catch (\Throwable) {
                // Ignore broken references and continue.
            }
        }

        return $urls;
    }

    /**
     * @return array<int, string>
     */
    private function getCollectionFrameUrls(
        ConnectionPool $connectionPool,
        ResourceFactory $resourceFactory,
        string $collectionSelection
    ): array {
        $collectionUid = $this->extractCollectionUid($collectionSelection);
        if ($collectionUid <= 0) {
            return [];
        }

        $folderCollectionUrls = $this->getFolderCollectionFrameUrls($connectionPool, $resourceFactory, $collectionUid);
        if ($folderCollectionUrls !== []) {
            return $folderCollectionUrls;
        }

        try {
            if (!is_callable([$resourceFactory, 'getFileCollectionObject'])) {
                return [];
            }
            /** @var mixed $collection */
            $collection = call_user_func([$resourceFactory, 'getFileCollectionObject'], $collectionUid);
            if (!$collection instanceof AbstractFileCollection) {
                return [];
            }

            $collection->loadContents();
            $items = $collection->getItems();
            if ($items instanceof Traversable) {
                $items = iterator_to_array($items, false);
            } elseif (!is_array($items)) {
                $items = [];
            }

            $files = [];
            foreach ($items as $item) {
                $file = $this->resolveCollectionItemToFile($item);
                if ($file instanceof File) {
                    $files[] = $file;
                }
            }

            if ($files === []) {
                $fallbackFiles = $collection->getItems();
                if ($fallbackFiles instanceof Traversable) {
                    foreach ($fallbackFiles as $item) {
                        $file = $this->resolveCollectionItemToFile($item);
                        if ($file instanceof File) {
                            $files[] = $file;
                        }
                    }
                }
            }
            usort(
                $files,
                static fn (File $a, File $b): int => strnatcasecmp($a->getName(), $b->getName())
            );

            $urls = [];
            foreach ($files as $file) {
                if (!$this->isAllowedSequenceFile($file->getExtension())) {
                    continue;
                }
                $url = $this->normalizePublicUrl((string)$file->getPublicUrl());
                if ($url !== '') {
                    $urls[] = $url;
                }
            }

            return $urls;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<int, string>
     */
    private function getFolderCollectionFrameUrls(
        ConnectionPool $connectionPool,
        ResourceFactory $resourceFactory,
        int $collectionUid
    ): array {
        $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_file_collection');
        $row = $queryBuilder
            ->select('type', 'folder_identifier', 'recursive')
            ->from('sys_file_collection')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($collectionUid, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if (!is_array($row)) {
            return [];
        }

        $type = (string)($row['type'] ?? '');
        $folderIdentifier = trim((string)($row['folder_identifier'] ?? ''));
        $recursive = (bool)($row['recursive'] ?? false);
        if ($type !== 'folder' || $folderIdentifier === '') {
            return [];
        }

        try {
            $folderObject = $resourceFactory->retrieveFileOrFolderObject($folderIdentifier);
            if (!$folderObject instanceof Folder) {
                return [];
            }

            $files = $this->collectFilesFromFolder($folderObject, $recursive);
            usort(
                $files,
                static fn (File $a, File $b): int => strnatcasecmp($a->getName(), $b->getName())
            );

            $urls = [];
            foreach ($files as $file) {
                if (!$this->isAllowedSequenceFile($file->getExtension())) {
                    continue;
                }
                $url = $this->normalizePublicUrl((string)$file->getPublicUrl());
                if ($url !== '') {
                    $urls[] = $url;
                }
            }

            return $urls;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<int, File>
     */
    private function collectFilesFromFolder(Folder $folder, bool $recursive): array
    {
        $files = [];
        foreach ($folder->getFiles() as $file) {
            if ($file instanceof File) {
                $files[] = $file;
            }
        }

        if (!$recursive) {
            return $files;
        }

        foreach ($folder->getSubfolders() as $subfolder) {
            if (!$subfolder instanceof Folder) {
                continue;
            }
            $files = array_merge($files, $this->collectFilesFromFolder($subfolder, true));
        }

        return $files;
    }

    private function extractCollectionUid(string $raw): int
    {
        $value = trim($raw);
        if ($value === '') {
            return 0;
        }

        if (str_contains($value, ',')) {
            $parts = array_filter(array_map('trim', explode(',', $value)));
            $value = (string)($parts[0] ?? '');
        }

        if (str_starts_with($value, 'sys_file_collection_')) {
            $value = (string)substr($value, strlen('sys_file_collection_'));
        }

        return (int)$value;
    }

    private function isAllowedSequenceFile(string $extension): bool
    {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
        return in_array(strtolower(trim($extension)), $allowed, true);
    }

    private function resolveCollectionItemToFile(mixed $item): ?File
    {
        if ($item instanceof File) {
            return $item;
        }

        if ($item instanceof FileReference) {
            try {
                return $item->getOriginalFile();
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
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
