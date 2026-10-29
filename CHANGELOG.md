# Changelog

All notable changes to this extension are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

## [2.2.1] – 2026-10-29

### Fixed
- Backend module: the extension no longer ships its own `Module` layout, which replaced
  the core layout and left the module without docheader and without the padded module
  body in TYPO3 14. The core layout is used on 13.4 and 14; the docheader has a bookmark
  button.
- Backend module: counters use singular/plural labels ("1 Page", "2 Pages") and the
  locale's thousands separator from the XLF; footer line reads
  "pxe_compareslider · pixelegg.de" with only "pixelegg.de" linked.
- Backend module position in TYPO3 14: placed after the "Content" group (the former
  "web" identifier is only an alias there and was ignored by the module sorting).
- pxe_hub card: status text with singular/plural and translated labels.
- Vertical sliders: the "after" label was placed under the "before" image and was not
  visible. It now sits bottom left; long labels are truncated instead of overlapping.
- `Documentation/guides.xml` passes the XSD validation of render-guides (namespace,
  TYPO3 docs theme extension).

### Changed
- Documentation: screenshots of frontend and backend (TYPO3 14) in README and manual.
- TER upload comment is generated as plain text from this changelog.
- README / documentation: wizard tab "Typical page content", module menu position and
  backend paths for both TYPO3 13.4 and 14.

### Added
- Functional tests rendering the backend module end-to-end (core frame, docheader,
  plural labels, German translation, module position).

## [2.2.0] – 2026-10-28

First public release on Packagist / TER. Compared with the in-house 2.1.x:

### Added
- TYPO3 14 support (constraints `^13.4 || ^14.0`, PHP 8.2–8.4).
- Site Set `pixelegg/compareslider` as an alternative to the static TypoScript template,
  with editable settings (template path, asset strategy, image sizes). Note: TYPO3 13/14
  only use set TypoScript when the root page has no `sys_template` record.
- Assets are loaded only on pages that contain a slider (AssetCollector). The constant
  `plugin.tx_pxecompareslider.settings.includeAssetsGlobally = 1` restores site-wide
  `page.includeCSS` / `page.includeJSFooter` for themes that concatenate assets.
- Responsive images: `srcset` / `sizes` (constants `image.srcsetWidths`, `image.sizes`),
  `width` / `height` attributes, `loading="lazy"` with a per-element "load immediately"
  switch, `alt` from FAL metadata (alternative, title) with the slider label as fallback.
- Image fields accept `webp` and `avif`.
- German translations for all backend labels and the module.
- Keyboard: PageUp / PageDown, `aria-valuetext`; sliders inserted later into the DOM are
  initialised automatically; CSS custom properties `--cs-handle-color`, `--cs-knob-size`,
  `--cs-focus-color`, `--cs-label-*` for styling; forced-colors support.
- GitHub Actions: CI matrix (TYPO3 13.4 × PHP 8.2–8.4, TYPO3 14 × PHP 8.3–8.4) and
  TER publishing on tags.

### Changed
- Title and subtitle are now the core fields *Header* / *Subheader*; the element renders
  through `lib.contentElement`, so header layout, frame class and spacing from the
  Appearance tab apply. The handle's accessible name is the header.
- Content element label "Compare slider (before/after)" / "Vergleichsslider
  (vorher/nachher)", wizard registration through TCA (no page TSconfig needed).
- Backend module uses the core backend styling instead of a custom theme.
- Touch: vertical swipes over a horizontal slider scroll the page (`touch-action: pan-y`).
- Dev toolchain: PHPStan 2 (level 8, no baseline), phpstan-typo3 2/3, typo3-rector 3,
  typo3/coding-standards 0.9.

### Fixed
- FlexForm binding on TYPO3 14: the TCA type definition overwrote the `columnsOverrides`
  that `addPlugin()` registers, editors only saw the core placeholder form.
- Image fields are `type=file` (no on-the-fly migration, dead `EXT:lang` labels removed).
- Translatable `aria-label` fallback ("Image comparison" / "Bildvergleich").
- Functional tests loaded the wrong package name.

### Legacy
- Records created with 2.1.x still carry `title` / `subtitle` in the FlexForm. They are
  rendered as a caption (and used as the accessible name) as long as the core header
  field is empty. Moving the text into the header field makes the fallback irrelevant;
  no upgrade wizard is provided.

### Removed
- `Configuration/TsConfig/Page/Wizards/ContentElements.tsconfig` and
  `Configuration/page.tsconfig` (wizard entry now comes from the TCA item).
- Duplicate icon file; `Resources/Public/Icons/Extension.svg` is the single source.

## [2.1.0] – 2026-08

- Internal release used on pixelegg.de: pxe_hub panel, usage module, TYPO3 13.4.
