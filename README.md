# aistea/lp_builder

TYPO3 v13 LTS extension providing content elements:

- `aistea_lp_product_slider` (vertical product viewer with image/video/3D/color gallery)
- `aistea_lp_horizontal_slider` (horizontal story slider with image, image sequence, or one-shot video)
- `aistea_lp_image_sequence` (single image-sequence element with manual file list or TYPO3 file collection)
- `aistea_lp_fullscreen_video` (fullscreen autoplay MP4 teaser with CTA popup for long Vimeo video)
- `aistea_lp_before_after` (before/after comparison slider)
- `aistea_lp_hotspot_image` (image with interactive hotspots)
- `aistea_hero_sequenz` (desktop hero image sequence based on TYPO3 folder collections)

## Migration

`aistea/lp_builder` replaces the former packages:

- `aistea/hero_sequenz`
- `aistea/lp_product_slider`

Migration behavior:

- Existing `CType` values stay compatible: `aistea_lp_product_slider`, `aistea_lp_horizontal_slider`, `aistea_lp_image_sequence`, `aistea_lp_fullscreen_video`, `aistea_lp_before_after`, `aistea_lp_hotspot_image`, `aistea_hero_sequenz`
- Existing slider DB fields stay compatible via the original `tx_aistealpproductslider_*` field names
- The hero sequence keeps the existing `aistea_hero_sequenz` `CType`, so no content-record migration is required

Recommended project cleanup:

1. Keep only `aistea/lp_builder` in the root `composer.json`
2. Run `composer update aistea/lp_builder`
3. Run TYPO3 database compare
4. Flush caches completely

The package declares Composer `replace` entries for the two old packages so they are not installed in parallel again.

## Three.js shipping

3D is optional and initialized only when a `model3d` slide is opened. The extension loads modules from local extension assets:

- `Resources/Public/JavaScript/Vendor/three.module.js`
- `Resources/Public/JavaScript/Vendor/GLTFLoader.js`

The provided files are placeholders. For production 3D rendering, replace them with the official ESM files from the Three.js project (same filenames).

No external CDN is required.
