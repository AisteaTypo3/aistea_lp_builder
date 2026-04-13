# aistea/lp_builder

TYPO3 v13 LTS extension providing content elements:

- `aistea_lp_product_slider` (vertical product viewer with image/video/3D/color gallery)
- `aistea_lp_horizontal_slider` (horizontal story slider with image, image sequence, or one-shot video)
- `aistea_lp_image_sequence` (single image-sequence element with manual file list or TYPO3 file collection)
- `aistea_lp_fullscreen_video` (fullscreen autoplay MP4 teaser with CTA popup for long Vimeo video)
- `aistea_lp_before_after` (before/after comparison slider)
- `aistea_lp_hotspot_image` (image with interactive hotspots)
- `aistea_hero_sequenz` (desktop hero image sequence based on TYPO3 folder collections)

## Three.js shipping

3D is optional and initialized only when a `model3d` slide is opened. The extension loads modules from local extension assets:

- `Resources/Public/JavaScript/Vendor/three.module.js`
- `Resources/Public/JavaScript/Vendor/GLTFLoader.js`

The provided files are placeholders. For production 3D rendering, replace them with the official ESM files from the Three.js project (same filenames).

No external CDN is required.
