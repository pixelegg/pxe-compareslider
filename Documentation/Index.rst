=================
pxe_compareslider
=================

:Extension key: pxe_compareslider
:Package name:  pixelegg/pxe-compareslider
:Version:       2.2.0
:Language:      en
:Author:        pixelegg
:License:       GPL-2.0-or-later

Before/after image comparison slider content element for TYPO3 13.4 LTS and 14.

Two images are stacked on top of each other. Visitors drag a handle to reveal more
of the "before" or the "after" image, horizontally or vertically. The script is
dependency-free vanilla JavaScript; the handle is keyboard and touch accessible;
images are responsive.

.. figure:: /Images/frontend-horizontal.png
   :alt: Horizontal compare slider with the handle in the middle: muted landscape on the left, vivid version on the right

   Horizontal slider on a demo page with a minimal theme, handle at the start
   position 50 %.

.. contents::
   :local:
   :depth: 2

Requirements
============

*  TYPO3 13.4 LTS or 14, PHP 8.2–8.4
*  EXT:fluid_styled_content (rendering uses ``lib.contentElement``)

Installation
============

.. code-block:: bash

   composer require pixelegg/pxe-compareslider

Non-composer installations: install ``pxe_compareslider`` from the TER.

Then load the TypoScript in one of two ways:

Site Set
   For sites configured entirely through sets (no root ``sys_template`` record):
   edit your site (TYPO3 13.4: *Site Management → Sites*, TYPO3 14: *Sites → Setup*)
   and add the set **Compare slider (before/after)** (``pixelegg/compareslider``), or
   list it in the ``dependencies`` of your own set.
   The set depends on ``typo3/fluid-styled-content``. Settings are editable in the
   site's *Settings* editor.

Static template
   For sites with a root ``sys_template`` record: module *TypoScript* (TYPO3 13.4:
   under *Site Management*, TYPO3 14: under *Sites*) → edit the root TypoScript record
   → tab *Advanced Options* → *Include TypoScript sets*: add **Compare slider
   (before/after)** after *Fluid Styled Content*. Settings are TypoScript constants.

Both load the same setup. TYPO3 13/14 ignore set TypoScript as soon as the root page has
a ``sys_template`` record, so on such sites only the static template takes effect. No
database changes are required.

Usage
=====

Add a content element and choose **Compare slider (before/after)** from the tab
*Typical page content* of the new content element wizard (same name in TYPO3 13.4
and 14).

.. figure:: /Images/new-content-wizard.png
   :alt: New content element wizard with Compare slider (before/after) in the first tab

   The element in the new content element wizard (TYPO3 14, tab "Typical page
   content").

General tab
   *Header* (also the accessible name of the handle) and *Subheader*.

Slider tab
   *  **Image "before"** – shown left (horizontal) or top (vertical), fitted onto the
      "after" image.
   *  **Image "after"** – defines the size of the slider. Use the same aspect ratio
      for both images. Allowed: gif, jpg, png, svg, webp, avif.
   *  **Label "before" / "after"** – optional captions on the images; also the ``alt``
      fallback when the file has no alternative text or title.
   *  **Orientation** – horizontal (handle moves left/right) or vertical (up/down).
   *  **Start position** – initial handle position in percent (0–100, default 50).
   *  **Load images immediately** – disables lazy loading for a slider that is visible
      without scrolling.

Appearance tab
   Header layout, frame class and spacing, as for every core element.

.. figure:: /Images/backend-edit-form.png
   :alt: Tab "Slider" of the editing form with the two image fields, labels, orientation, start position and the switch for loading the images immediately

   Editing form, tab "Slider".

The slider renders only when both images are set.

.. figure:: /Images/frontend-vertical.png
   :alt: Vertical compare slider: the muted image on top, the vivid image below the handle

   Vertical orientation.

Accessibility
=============

The handle has ``role="slider"`` with ``aria-valuemin`` / ``aria-valuemax`` /
``aria-valuenow`` / ``aria-valuetext`` and ``aria-orientation``. Its accessible name is
the header (fallback: "Image comparison" / "Bildvergleich").

*  Arrow keys move the handle by 2 %, Shift + arrow by 10 %, Page Up / Page Down by
   10 %, Home / End jump to 0 % / 100 %.
*  Focus is visible (``:focus-visible`` outline, ``--cs-focus-color``).
*  Forced-colors mode (Windows High Contrast) keeps the handle visible.
*  ``alt`` texts come from the FAL metadata (alternative, then title), then from the
   labels. Both images are informative, so give them alternative texts.
*  Touch: vertical swipes over a horizontal slider scroll the page; horizontal swipes
   move the handle (``touch-action``). There are no animations.
*  Without JavaScript the slider is shown at the start position (browsers with typed
   ``attr()``; others at 50 %) with the "after" image fully loaded underneath.

Configuration
=============

TypoScript constants and Site Set settings share the same keys:

.. t3-field-list-table::
   :header-rows: 1

   -  :Key:       Constant
      :Default:   Default
      :Desc:      Description

   -  :Key:       ``plugin.tx_pxecompareslider.view.templateRootPath``
      :Default:   ``EXT:pxe_compareslider/Resources/Private/Templates/ContentElements/``
      :Desc:      Directory with your own ``Compareslider.html``.

   -  :Key:       ``plugin.tx_pxecompareslider.settings.includeAssetsGlobally``
      :Default:   ``0``
      :Desc:      ``0``: CSS/JS via AssetCollector only on pages with a slider.
                  ``1``: site-wide via ``page.includeCSS`` / ``page.includeJSFooter``
                  for themes that concatenate assets (e.g. t3sbootstrap).

   -  :Key:       ``plugin.tx_pxecompareslider.settings.image.maxWidth``
      :Default:   ``1540``
      :Desc:      Longest edge the images are scaled down to (never upscaled).

   -  :Key:       ``plugin.tx_pxecompareslider.settings.image.srcsetWidths``
      :Default:   ``480,768,1024,1540``
      :Desc:      Widths for ``srcset``; empty disables ``srcset``.

   -  :Key:       ``plugin.tx_pxecompareslider.settings.image.sizes``
      :Default:   ``100vw``
      :Desc:      Value of the ``sizes`` attribute.

Lazy loading follows ``styles.content.image.lazyLoading`` of fluid_styled_content.

Template override
-----------------

Point ``plugin.tx_pxecompareslider.view.templateRootPath`` to a directory of your
sitepackage and copy ``Resources/Private/Templates/ContentElements/Compareslider.html``
there. The template uses the ``Default`` layout and the ``Header/All`` partial of
fluid_styled_content. Variables: ``data``, ``flexform``, ``image_before`` /
``image_after`` (FileReference arrays), ``settings``. The ViewHelper
``cs:srcset(image, widths)`` builds the ``srcset`` string.

Styling
-------

BEM block ``compareslider``. Custom properties on ``.compareslider``: ``--cs-pos``
(current position 0–100, written by the script), ``--cs-handle-color``,
``--cs-handle-width``, ``--cs-knob-size``, ``--cs-focus-color``, ``--cs-label-color``,
``--cs-label-bg``.

Labels sit in a corner of their own image: horizontal "before" top left / "after" top
right, vertical "before" top left / "after" bottom left. They are covered together with
their image when the handle moves over them.

Backend module
==============

The module **Compare slider** is a top-level entry of the module menu, placed after
the page module group (*Web* in TYPO3 13.4, *Content* in TYPO3 14). It lists all
slider elements of the installation with health checks (missing images, hidden
elements) and links to edit each element or open its page. If the optional extension ``pxe_hub`` is installed, the
module is shown as a card in the hub instead of a top-level module.

.. figure:: /Images/backend-module.png
   :alt: Backend module with counters for elements, pages, hidden and incomplete sliders and a table with thumbnails, page, status and edit buttons

   The backend module. A hidden draft with only one image is flagged as
   "Missing image".

.. figure:: /Images/backend-module-de.png
   :alt: The same backend module with German labels

   The same module with the backend language set to German.

Upgrading from 2.1.x
====================

Title and subtitle moved from the FlexForm to the core *Header* / *Subheader* fields.
Existing records keep working: the old FlexForm values are rendered as a caption and
used as the handle name while the header field is empty. Sites that relied on the
site-wide asset include should set ``includeAssetsGlobally = 1``.

Changelog
=========

See ``CHANGELOG.md`` in the repository:
https://github.com/pixelegg/pxe-compareslider/blob/main/CHANGELOG.md
