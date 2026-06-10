<?php

declare(strict_types=1);

namespace Aistea\LpBuilder\EventListener;

use TYPO3\CMS\Backend\Form\Event\ModifyInlineElementControlsEvent;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;

final readonly class InterviewInlineDuplicateControl
{
    public function __construct(
        private IconFactory $iconFactory,
        private UriBuilder $uriBuilder,
    ) {
    }

    public function __invoke(ModifyInlineElementControlsEvent $event): void
    {
        if ($event->getForeignTable() !== 'tx_aistealpproductslider_interview') {
            return;
        }

        if ($event->isVirtual()) {
            return;
        }

        $record = $event->getRecord();
        $uid = (string)($record['uid'] ?? '');
        if ($uid === '' || str_starts_with($uid, 'NEW')) {
            return;
        }

        $returnUrl = (string)($GLOBALS['TYPO3_REQUEST'] ?? null)?->getUri();
        $href = (string)$this->uriBuilder->buildUriFromRoute('aistea_lp_builder_duplicate_interview', [
            'uid' => (int)$uid,
            'returnUrl' => $returnUrl,
        ]);

        $title = 'Duplicate interview box';
        $icon = $this->iconFactory->getIcon('actions-edit-copy', IconSize::SMALL)->render();
        $event->setControl(
            'duplicate',
            '<a href="' . htmlspecialchars($href) . '" class="btn btn-default" title="' . htmlspecialchars($title) . '" aria-label="' . htmlspecialchars($title) . '">' . $icon . '</a>'
        );
    }
}
