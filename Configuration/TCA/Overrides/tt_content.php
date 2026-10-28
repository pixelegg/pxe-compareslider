<?php

declare(strict_types=1);

defined('TYPO3') or die();

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$flexForm = 'FILE:EXT:pxe_compareslider/Configuration/FlexForms/ContentElements/Compareslider.xml';

// "group" and "description" put the element into the "New content element" wizard
// (tab "Default") on TYPO3 13 and 14 without any page TSconfig.
$item = [
    'label' => 'LLL:EXT:pxe_compareslider/Resources/Private/Language/Backend/locallang.xlf:ctype.title',
    'description' => 'LLL:EXT:pxe_compareslider/Resources/Private/Language/Backend/locallang.xlf:ctype.description',
    'value' => 'pxe_compareslider',
    'icon' => 'content-pxe-compareslider',
    'group' => 'default',
];

if ((new Typo3Version())->getMajorVersion() >= 14) {
    // TYPO3 14: addPlugin(item, flexForm) stores the data structure in the type's columnsOverrides.
    ExtensionManagementUtility::addPlugin($item, $flexForm);
} else {
    // TYPO3 13.4: the data structure is looked up via ds_pointerField "list_type,CType",
    // a CType-based element has an empty list_type, hence the "*" key.
    // The 3-argument signature exists on 13 only; PHPStan analysing against 14 cannot know that.
    ExtensionManagementUtility::addPlugin($item, 'CType', 'pxe_compareslider'); // @phpstan-ignore arguments.count
    ExtensionManagementUtility::addPiFlexFormValue('*', $flexForm, 'pxe_compareslider');
}

// Only set showitem: replacing the whole type array would drop the columnsOverrides
// (and with it the FlexForm data structure) that addPlugin() registered on TYPO3 14.
// Header, subheader, frames and spacing are the core fields, so the element behaves
// like any other fluid_styled_content element in the Appearance tab.
$GLOBALS['TCA']['tt_content']['types']['pxe_compareslider']['showitem']
    = '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,'
    . '--palette--;;general,'
    . '--palette--;;headers,'
    . '--div--;LLL:EXT:pxe_compareslider/Resources/Private/Language/Backend/locallang.xlf:tab.slider,'
    . 'pi_flexform,'
    . '--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,'
    . '--palette--;;frames,--palette--;;appearanceLinks,'
    . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,--palette--;;language,'
    . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,'
    . '--palette--;;hidden,--palette--;;access,'
    . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,categories,'
    . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,rowDescription,'
    . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended';
$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['pxe_compareslider'] = 'content-pxe-compareslider';
