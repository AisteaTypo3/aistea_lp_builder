<?php

declare(strict_types=1);

namespace Aistea\LpBuilder\Backend;

use TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;

final class ContentElementPreviewRenderer extends StandardContentPreviewRenderer
{
    public function renderPageModulePreviewContent(GridColumnItem $item): string
    {
        $record = $item->getRecord();
        $cType = (string)($record['CType'] ?? '');

        return match ($cType) {
            'aistea_lp_product_slider' => $this->renderProductSliderPreview($record),
            'aistea_lp_horizontal_slider' => $this->renderHorizontalSliderPreview($record),
            'aistea_lp_image_sequence' => $this->renderImageSequencePreview($record),
            'aistea_lp_fullscreen_video' => $this->renderFullScreenVideoPreview($record),
            'aistea_lp_before_after' => $this->renderBeforeAfterPreview($record),
            'aistea_lp_hotspot_image' => $this->renderHotspotImagePreview($record),
            'aistea_hero_sequenz' => $this->renderHeroSequencePreview($record),
            default => parent::renderPageModulePreviewContent($item),
        };
    }

    /**
     * @param array<string, mixed> $record
     */
    private function renderProductSliderPreview(array $record): string
    {
        $parts = [
            $this->badge('Slides', (int)($record['tx_aistealpproductslider_slides'] ?? 0)),
            $this->badge('Theme', (string)($record['tx_aistealpproductslider_theme'] ?? 'dark')),
            $this->badge('Layout', (string)($record['tx_aistealpproductslider_layout_mode'] ?? 'default')),
            $this->badge('Breakpoint', (int)($record['tx_aistealpproductslider_breakpoint_mobile'] ?? 768) . 'px'),
            $this->badge('Preload', (string)($record['tx_aistealpproductslider_preload_strategy'] ?? 'smart')),
        ];

        $parts[] = $this->metaBadge('Aspect', (string)($record['tx_aistealpproductslider_stage_aspect_ratio'] ?? '1/1'));
        $parts[] = $this->metaBadge(
            'Autoplay',
            sprintf(
                'D:%s M:%s',
                $this->boolLabel((bool)($record['tx_aistealpproductslider_video_autoplay_desktop'] ?? false)),
                $this->boolLabel((bool)($record['tx_aistealpproductslider_video_autoplay_mobile'] ?? false))
            )
        );
        $parts[] = $this->metaBadge(
            'Motion',
            (string)($record['tx_aistealpproductslider_reduced_motion_behavior'] ?? 'static')
        );

        return $this->wrapPreview(
            'Interactive product viewer with image, video, 3D and color gallery slides.',
            $parts
        );
    }

    /**
     * @param array<string, mixed> $record
     */
    private function renderHorizontalSliderPreview(array $record): string
    {
        return $this->wrapPreview(
            'Horizontal story slider with image, sequence or video slides.',
            [
                $this->badge('Slides', (int)($record['tx_aistealpproductslider_hslides'] ?? 0)),
            ]
        );
    }

    /**
     * @param array<string, mixed> $record
     */
    private function renderImageSequencePreview(array $record): string
    {
        $source = (string)($record['tx_aistealpproductslider_sequence_source'] ?? 'filelist');
        $parts = [
            $this->badge('Source', $source),
            $this->badge('FPS', max(1, (int)($record['tx_aistealpproductslider_sequence_fps'] ?? 12))),
            $this->badge('Loop', $this->boolLabel((bool)($record['tx_aistealpproductslider_sequence_loop'] ?? false))),
        ];
        $parts[] = $source === 'collection'
            ? $this->metaBadge('Collection', (int)($record['tx_aistealpproductslider_sequence_collection'] ?? 0))
            : $this->metaBadge('Frames', (int)($record['tx_aistealpproductslider_sequence_frames'] ?? 0));

        return $this->wrapPreview('Canvas-based image sequence animation block.', $parts);
    }

    /**
     * @param array<string, mixed> $record
     */
    private function renderFullScreenVideoPreview(array $record): string
    {
        $parts = [
            $this->badge('Short MP4', $this->boolLabel((int)($record['tx_aistealpproductslider_fsv_short_video'] ?? 0) > 0)),
            $this->badge('Long Vimeo', $this->boolLabel((int)($record['tx_aistealpproductslider_fsv_long_video'] ?? 0) > 0)),
        ];

        $headline = trim((string)($record['tx_aistealpproductslider_fsv_headline'] ?? ''));
        if ($headline !== '') {
            $parts[] = $this->metaBadge('Headline', $headline);
        }

        return $this->wrapPreview('Fullscreen teaser video with modal CTA.', $parts);
    }

    /**
     * @param array<string, mixed> $record
     */
    private function renderBeforeAfterPreview(array $record): string
    {
        $parts = [
            $this->badge('Before', $this->boolLabel((int)($record['tx_aistealpproductslider_ba_image_before'] ?? 0) > 0)),
            $this->badge('After', $this->boolLabel((int)($record['tx_aistealpproductslider_ba_image_after'] ?? 0) > 0)),
            $this->badge('Handle', (int)($record['tx_aistealpproductslider_ba_initial_position'] ?? 50) . '%'),
        ];

        $labelBefore = trim((string)($record['tx_aistealpproductslider_ba_label_before'] ?? ''));
        $labelAfter  = trim((string)($record['tx_aistealpproductslider_ba_label_after'] ?? ''));
        if ($labelBefore !== '') {
            $parts[] = $this->metaBadge('Label B', $labelBefore);
        }
        if ($labelAfter !== '') {
            $parts[] = $this->metaBadge('Label A', $labelAfter);
        }

        return $this->wrapPreview('Drag-handle image comparison block.', $parts);
    }

    /**
     * @param array<string, mixed> $record
     */
    private function renderHotspotImagePreview(array $record): string
    {
        return $this->wrapPreview(
            'Image with clickable hotspot markers.',
            [
                $this->badge('Image', $this->boolLabel((int)($record['tx_aistealpproductslider_hi_image'] ?? 0) > 0)),
                $this->badge('Hotspots', (int)($record['tx_aistealpproductslider_hi_hotspots'] ?? 0)),
            ]
        );
    }

    /**
     * @param array<string, mixed> $record
     */
    private function renderHeroSequencePreview(array $record): string
    {
        return $this->wrapPreview(
            'Desktop-only hero image sequence sourced from a TYPO3 folder collection.',
            [
                $this->badge('Collection', (int)($record['file_collection'] ?? 0)),
                $this->badge('FPS', max(1, (int)($record['desktop_fps'] ?? 24))),
                $this->badge('Loop', $this->boolLabel((bool)($record['desktop_loop'] ?? false))),
                $this->metaBadge('Mobile', (string)($record['mobile_mode'] ?? 'lastFrame')),
                $this->metaBadge('Preload', (string)($record['preload_strategy'] ?? 'smart')),
            ]
        );
    }

    /**
     * @param array<int, string> $parts
     */
    private function wrapPreview(string $summary, array $parts): string
    {
        $html = '<div class="small text-body-secondary">' . htmlspecialchars($summary) . '</div>';
        $html .= '<div style="margin-top:.5rem;display:flex;flex-wrap:wrap;gap:.35rem;">';
        foreach ($parts as $part) {
            $html .= $part;
        }
        $html .= '</div>';

        return $html;
    }

    private function badge(string $label, string|int $value): string
    {
        return sprintf(
            '<span class="badge bg-secondary">%s: %s</span>',
            htmlspecialchars($label),
            htmlspecialchars((string)$value)
        );
    }

    private function metaBadge(string $label, string|int $value): string
    {
        return sprintf(
            '<span class="badge bg-light text-dark border">%s: %s</span>',
            htmlspecialchars($label),
            htmlspecialchars((string)$value)
        );
    }

    private function boolLabel(bool $value): string
    {
        return $value ? 'Yes' : 'No';
    }
}
