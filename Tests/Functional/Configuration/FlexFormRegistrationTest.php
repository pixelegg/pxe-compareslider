<?php

declare(strict_types=1);

namespace Pixelegg\PxeCompareslider\Tests\Functional\Configuration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The content element must resolve to its own FlexForm data structure, not to the core's
 * placeholder DS ("The Title:"). Guards against the TCA type definition overwriting the
 * FlexForm binding.
 */
final class FlexFormRegistrationTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['fluid_styled_content'];

    protected array $testExtensionsToLoad = ['pixelegg/pxe-compareslider'];

    /**
     * @return array<string, mixed>
     */
    private function resolveDataStructure(): array
    {
        $flexFormTools = $this->get(FlexFormTools::class);
        // TYPO3 14 resolves the data structure through the TCA schema; TYPO3 13 has no such
        // parameter and ignores the extra argument (hence the ignores, matched on 13 only).
        $schema = $this->get(TcaSchemaFactory::class)->get('tt_content');
        // list_type is read by the TYPO3 13 ds_pointerField "list_type,CType".
        $row = ['uid' => 1, 'pid' => 1, 'CType' => 'pxe_compareslider', 'list_type' => ''];

        $identifier = $flexFormTools->getDataStructureIdentifier( // @phpstan-ignore arguments.count
            $GLOBALS['TCA']['tt_content']['columns']['pi_flexform'],
            'tt_content',
            'pi_flexform',
            $row,
            $schema,
        );

        return $flexFormTools->parseDataStructureByIdentifier($identifier, $schema); // @phpstan-ignore arguments.count
    }

    #[Test]
    public function contentElementResolvesToItsOwnFlexFormDataStructure(): void
    {
        $dataStructure = $this->resolveDataStructure();

        $fields = $dataStructure['sheets']['main']['ROOT']['el'] ?? [];
        $found = json_encode(array_map(static fn(array $sheet): array => array_keys($sheet['ROOT']['el'] ?? []), $dataStructure['sheets'] ?? []));
        foreach (['image_before', 'image_after', 'label_before', 'label_after', 'orientation', 'start_position', 'load_eager'] as $field) {
            self::assertArrayHasKey($field, $fields, 'FlexForm field "' . $field . '" missing, resolved: ' . $found);
        }
        // Title and subtitle moved to the core header / subheader fields in 2.2.0.
        self::assertArrayNotHasKey('title', $fields);
        self::assertArrayNotHasKey('subtitle', $fields);
    }

    #[Test]
    public function imageFieldsAcceptModernFormats(): void
    {
        $dataStructure = $this->resolveDataStructure();

        foreach (['image_before', 'image_after'] as $field) {
            $config = $dataStructure['sheets']['main']['ROOT']['el'][$field]['config'];
            self::assertSame('file', $config['type']);
            foreach (['webp', 'avif', 'svg', 'png', 'jpg'] as $extension) {
                self::assertStringContainsString($extension, $config['allowed'], $field . ' must allow ' . $extension);
            }
        }
    }

    #[Test]
    public function contentElementShowsCoreHeaderFieldsAndTheFlexForm(): void
    {
        $showitem = $GLOBALS['TCA']['tt_content']['types']['pxe_compareslider']['showitem'] ?? '';
        self::assertStringContainsString('--palette--;;headers', $showitem);
        self::assertStringContainsString('pi_flexform', $showitem);
        self::assertStringContainsString('--palette--;;frames', $showitem);
    }

    #[Test]
    public function contentElementIsRegisteredInTheDefaultWizardGroupWithIconAndDescription(): void
    {
        $items = $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'];
        $item = null;
        foreach ($items as $candidate) {
            if (($candidate['value'] ?? null) === 'pxe_compareslider') {
                $item = $candidate;
            }
        }

        self::assertIsArray($item, 'CType item missing');
        self::assertSame('default', $item['group']);
        self::assertSame('content-pxe-compareslider', $item['icon']);
        self::assertStringContainsString('ctype.description', $item['description']);
        self::assertSame(
            'content-pxe-compareslider',
            $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['pxe_compareslider']
        );
    }
}
