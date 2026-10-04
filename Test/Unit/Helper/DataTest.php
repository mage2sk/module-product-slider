<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Panth\ProductSlider\Helper\Data;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    /**
     * Build the helper over a fixed map of config values.
     *
     * @param array $values
     * @param array $flags
     * @return Data
     */
    private function helper(array $values = [], array $flags = []): Data
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            static fn($path) => $values[$path] ?? null
        );
        $scopeConfig->method('isSetFlag')->willReturnCallback(
            static fn($path) => (bool)($flags[$path] ?? false)
        );
        $context = $this->createStub(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);

        return new Data($context);
    }

    public function testIsEnabledFollowsTheConfigFlag(): void
    {
        $this->assertTrue($this->helper([], [Data::XML_PATH_ENABLED => 1])->isEnabled());
        $this->assertFalse($this->helper()->isEnabled());
    }

    public function testApplyCardSettingsFollowsTheConfigFlag(): void
    {
        $this->assertTrue($this->helper([], [Data::XML_PATH_APPLY_CARD_SETTINGS => 1])->isApplyCardSettings());
        $this->assertFalse($this->helper()->isApplyCardSettings());
    }

    public function testDefaultLayoutUsesBuiltInValuesWhenUnset(): void
    {
        $this->assertSame(
            ['columns_mobile' => 1, 'columns_tablet' => 2, 'columns_desktop' => 4, 'page_size' => 8],
            $this->helper()->getDefaultLayout()
        );
    }

    public function testDefaultLayoutUsesPositiveConfigValuesAndIgnoresZeroOrNegative(): void
    {
        $prefix = Data::XML_PATH_DEFAULT_LAYOUT;
        $layout = $this->helper([
            $prefix . 'columns_mobile' => '2',
            $prefix . 'columns_tablet' => '0',
            $prefix . 'columns_desktop' => '-3',
            $prefix . 'page_size' => '12',
        ])->getDefaultLayout();

        $this->assertSame(2, $layout['columns_mobile']);
        $this->assertSame(2, $layout['columns_tablet']);
        $this->assertSame(4, $layout['columns_desktop']);
        $this->assertSame(12, $layout['page_size']);
    }

    public function testCacheLifetimeDefaultsToOneHour(): void
    {
        $this->assertSame(3600, $this->helper()->getCacheLifetime());
        $this->assertSame(3600, $this->helper([Data::XML_PATH_CACHE_LIFETIME => '0'])->getCacheLifetime());
        $this->assertSame(600, $this->helper([Data::XML_PATH_CACHE_LIFETIME => '600'])->getCacheLifetime());
    }

    public function testStylePresetReturnsTheNamedPreset(): void
    {
        $helper = $this->helper();

        $this->assertSame('none', $helper->getStylePreset(Data::PRESET_MINIMAL)['card_shadow']);
        $this->assertSame('gradient', $helper->getStylePreset(Data::PRESET_MODERN)['button_style']);
        $this->assertSame('1.5rem', $helper->getStylePreset(Data::PRESET_BOLD)['card_radius']);
    }

    public function testUnknownAndCustomPresetsFallBackToDefault(): void
    {
        $helper = $this->helper();
        $default = $helper->getStylePreset(Data::PRESET_DEFAULT);

        $this->assertSame($default, $helper->getStylePreset(Data::PRESET_CUSTOM));
        $this->assertSame($default, $helper->getStylePreset('does-not-exist'));
        $this->assertSame('md', $default['card_shadow']);
    }

    public function testShadowClassMapsSizesAndFallsBackToMedium(): void
    {
        $helper = $this->helper();

        $this->assertSame('panth-ps-shadow-none', $helper->getShadowClass('none'));
        $this->assertSame('panth-ps-shadow-xl', $helper->getShadowClass('2xl'));
        $this->assertSame('panth-ps-shadow-md', $helper->getShadowClass('huge'));
    }

    public function testHoverEffectClassMapsEffectsAndFallsBackToLift(): void
    {
        $helper = $this->helper();

        $this->assertSame('panth-ps-hover-grow', $helper->getHoverEffectClass('scale'));
        $this->assertSame('panth-ps-hover-lift panth-ps-hover-grow', $helper->getHoverEffectClass('both'));
        $this->assertSame('panth-ps-hover-glow', $helper->getHoverEffectClass('glow'));
        $this->assertSame('panth-ps-hover-lift', $helper->getHoverEffectClass('wobble'));
    }

    public function testParseIdsTrimsAndDropsEmptyEntries(): void
    {
        $helper = $this->helper();

        $this->assertSame([], $helper->parseIds(null));
        $this->assertSame([], $helper->parseIds(''));
        $this->assertSame(['1', '22', 'SKU-3'], array_values($helper->parseIds(' 1, ,22 ,SKU-3,')));
    }

    /**
     * @return array
     */
    public static function columnProvider(): array
    {
        return [
            'single column everywhere' => [1, 1, 1, 'snap-track'],
            'two desktop columns add nothing' => [1, 2, 2, 'snap-track md:[--snap-cols:2]'],
            'three desktop columns use lg' => [1, 2, 3, 'snap-track md:[--snap-cols:2] lg:[--snap-cols:3]'],
            'wide desktop uses lg 3 and xl' => [
                1,
                3,
                6,
                'snap-track md:[--snap-cols:3] lg:[--snap-cols:3] xl:[--snap-cols:6]',
            ],
        ];
    }

    #[DataProvider('columnProvider')]
    public function testColumnClasses(int $mobile, int $tablet, int $desktop, string $expected): void
    {
        $this->assertSame($expected, $this->helper()->getColumnClasses($mobile, $tablet, $desktop));
    }

    public function testCardStyleSheetIsScopedToTheGivenSelector(): void
    {
        $css = $this->helper()->getCardStyleSheet('#slider-1');

        $this->assertStringStartsWith('#slider-1 .panth-ps-card {', $css);
        $this->assertStringContainsString('#slider-1 .panth-ps-badge--sale { background: #B91C1C; }', $css);
        $this->assertStringContainsString('#slider-1 .panth-ps-card.panth-ps-hover-glow:hover', $css);
    }

    public function testBadgeStylesMeetTextContrastAndMinimumSize(): void
    {
        $css = $this->helper()->getCardStyleSheet('#s');

        $this->assertStringContainsString('#s .panth-ps-badge { font-size: 12px;', $css);
        $this->assertStringContainsString('color: #fff; background: #6B7280; }', $css);
        $this->assertStringContainsString('#s .panth-ps-badge--new { background: #15803D; }', $css);
        $this->assertStringContainsString('#s .panth-ps-badge--stock { background: #B45309; }', $css);
        $this->assertStringContainsString('#s .panth-ps-badge--featured { background: #7E22CE; }', $css);
        $this->assertStringNotContainsString('#EF4444', $css);
        $this->assertStringNotContainsString('#22C55E', $css);
        $this->assertStringNotContainsString('#F59E0B', $css);
        $this->assertStringNotContainsString('#A855F7', $css);
    }

    public function testArrowSettingsDefaultsWhenUnset(): void
    {
        $empty = $this->helper();

        $this->assertFalse($empty->showArrows());
        $this->assertSame('', $empty->getArrowColor());
        $this->assertSame('', $empty->getArrowBgColor());
        $this->assertSame(40, $empty->getArrowSize());
        $this->assertSame('outside', $empty->getArrowPosition());
    }

    public function testArrowSettingsReadTheirStorePaths(): void
    {
        $set = $this->helper([
            'product_slider/arrows/show_arrows' => '1',
            'product_slider/arrows/arrow_color' => '#111',
            'product_slider/arrows/arrow_bg_color' => '#fff',
            'product_slider/arrows/arrow_hover_color' => '#222',
            'product_slider/arrows/arrow_hover_bg_color' => '#eee',
            'product_slider/arrows/arrow_border_color' => '#333',
            'product_slider/arrows/arrow_size' => '48',
            'product_slider/arrows/arrow_position' => 'center',
        ]);

        $this->assertTrue($set->showArrows());
        $this->assertSame('#111', $set->getArrowColor());
        $this->assertSame('#fff', $set->getArrowBgColor());
        $this->assertSame('#222', $set->getArrowHoverColor());
        $this->assertSame('#eee', $set->getArrowHoverBgColor());
        $this->assertSame('#333', $set->getArrowBorderColor());
        $this->assertSame(48, $set->getArrowSize());
        $this->assertSame('center', $set->getArrowPosition());
    }
}
