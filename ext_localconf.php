<?php

defined('TYPO3') || die();

// Hiding the standalone backend module when pxe_hub is installed used to happen here via
// ExtensionManagementUtility::addUserTSConfig(), which TYPO3 14 removed. It now lives in
// Classes/EventListener/HideStandaloneModuleWhenHubPresent.php - see the class docblock.
