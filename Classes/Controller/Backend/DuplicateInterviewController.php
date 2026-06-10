<?php

declare(strict_types=1);

namespace Aistea\LpBuilder\Controller\Backend;

use Doctrine\DBAL\ParameterType;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class DuplicateInterviewController
{
    private const TABLE = 'tx_aistealpproductslider_interview';
    private const PARENT_TABLE = 'tt_content';
    private const PARENT_FIELD = 'tx_aistealpproductslider_interviews';
    private const MAX_ITEMS = 4;

    public function duplicateAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $uid = (int)($queryParams['uid'] ?? 0);
        $returnUrl = GeneralUtility::sanitizeLocalUrl((string)($queryParams['returnUrl'] ?? '')) ?: '../';

        try {
            $this->duplicate($uid);
            $this->addFlashMessage('Interview box duplicated.', ContextualFeedbackSeverity::OK);
        } catch (\Throwable $exception) {
            $this->addFlashMessage($exception->getMessage(), ContextualFeedbackSeverity::ERROR);
        }

        return new RedirectResponse($returnUrl, 303);
    }

    private function duplicate(int $uid): void
    {
        if ($uid <= 0) {
            throw new \RuntimeException('Invalid interview box.', 1764931110);
        }

        $record = BackendUtility::getRecord(self::TABLE, $uid);
        if (!is_array($record)) {
            throw new \RuntimeException('Interview box not found.', 1764931111);
        }

        $parentUid = (int)($record['parentid'] ?? 0);
        if ((string)($record['parenttable'] ?? '') !== self::PARENT_TABLE || $parentUid <= 0) {
            throw new \RuntimeException('Interview box is not attached to a content element.', 1764931112);
        }

        $parent = BackendUtility::getRecord(self::PARENT_TABLE, $parentUid);
        if (!is_array($parent) || (string)($parent['CType'] ?? '') !== 'aistea_lp_interview_boxes') {
            throw new \RuntimeException('Parent interview content element not found.', 1764931113);
        }

        $page = BackendUtility::getRecord('pages', (int)$record['pid']);
        if (!is_array($page) || !$this->getBackendUser()->isInWebMount($page)) {
            throw new \RuntimeException('Missing page access for this interview box.', 1764931114);
        }

        $permission = new Permission($this->getBackendUser()->calcPerms($page));
        if (!$permission->editContentPermissionIsGranted()) {
            throw new \RuntimeException('Missing edit permission for this interview box.', 1764931115);
        }

        $count = $this->countSiblings($parentUid);
        if ($count >= self::MAX_ITEMS) {
            throw new \RuntimeException('This element already has the maximum of four interview boxes.', 1764931116);
        }

        $newUid = $this->copyRecord($record, $parentUid);
        if ($newUid <= 0) {
            throw new \RuntimeException('The interview box could not be duplicated.', 1764931117);
        }

        $this->updateParentCounter($parentUid, $count + 1);
    }

    /**
     * @param array<string, mixed> $record
     */
    private function copyRecord(array $record, int $parentUid): int
    {
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], []);

        $overrideValues = [
            'parentid' => $parentUid,
            'parenttable' => self::PARENT_TABLE,
            'sorting' => $this->getNextSorting($parentUid),
        ];

        return (int)($dataHandler->copyRecord(self::TABLE, (int)$record['uid'], (int)$record['pid'], false, $overrideValues) ?? 0);
    }

    private function countSiblings(int $parentUid): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        return (int)$queryBuilder
            ->count('uid')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('parentid', $queryBuilder->createNamedParameter($parentUid, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('parenttable', $queryBuilder->createNamedParameter(self::PARENT_TABLE)),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->executeQuery()
            ->fetchOne();
    }

    private function getNextSorting(int $parentUid): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        $currentMax = $queryBuilder
            ->selectLiteral('MAX(sorting)')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('parentid', $queryBuilder->createNamedParameter($parentUid, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('parenttable', $queryBuilder->createNamedParameter(self::PARENT_TABLE)),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->executeQuery()
            ->fetchOne();

        return max(0, (int)$currentMax) + 256;
    }

    private function updateParentCounter(int $parentUid, int $count): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable(self::PARENT_TABLE);
        $connection->update(
            self::PARENT_TABLE,
            [
                self::PARENT_FIELD => $count,
                'tstamp' => time(),
            ],
            ['uid' => $parentUid],
            [
                self::PARENT_FIELD => ParameterType::INTEGER,
                'tstamp' => ParameterType::INTEGER,
                'uid' => ParameterType::INTEGER,
            ]
        );
    }

    private function addFlashMessage(string $message, ContextualFeedbackSeverity $severity): void
    {
        $this->getFlashMessageService()
            ->getMessageQueueByIdentifier()
            ->enqueue(new FlashMessage($message, 'Interview boxes', $severity, true));
    }

    private function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }

    private function getFlashMessageService(): FlashMessageService
    {
        return GeneralUtility::makeInstance(FlashMessageService::class);
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
