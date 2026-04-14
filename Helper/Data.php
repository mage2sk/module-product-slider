<?php
/**
 * Panth ProductSlider Configuration Helper
 *
 * @package Panth_ProductSlider
 * @author Panth
 */
declare(strict_types=1);

namespace Panth\ProductSlider\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    public const XML_PATH_ENABLED = 'product_slider/general/enabled';
    public const XML_PATH_CACHE_LIFETIME = 'product_slider/general/cache_lifetime';

    // Style Presets
    public const PRESET_DEFAULT = 'default';
    public const PRESET_MODERN = 'modern';
    public const PRESET_MINIMAL = 'minimal';
    public const PRESET_BOLD = 'bold';
    public const PRESET_CUSTOM = 'custom';

    /**
     * Get config value
     *
     * @param string $path
     * @param int|null $storeId
     * @return mixed
     */
    protected function getConfigValue(string $path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            'product_slider/' . $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if Core module is enabled
     *
     * @return bool
     */
    protected function isCoreModuleEnabled(): bool
    {
        return true;
    }

    /**
     * Check if module is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null): bool
    {
        // First check if Core module is enabled (required dependency)
        if (!$this->isCoreModuleEnabled()) {
            return false;
        }

        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get cache lifetime
     *
     * @param int|null $storeId
     * @return int
     */
    public function getCacheLifetime($storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_CACHE_LIFETIME,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 3600;
    }

    /**
     * Get style preset configuration
     *
     * @param string $preset
     * @return array
     */
    public function getStylePreset(string $preset): array
    {
        $presets = [
            self::PRESET_DEFAULT => [
                'card_shadow' => 'md',
                'card_radius' => '0.75rem',
                'card_hover' => 'lift',
                'title_size' => '1rem',
                'button_style' => 'primary'
            ],
            self::PRESET_MODERN => [
                'card_shadow' => 'lg',
                'card_radius' => '1rem',
                'card_hover' => 'both',
                'title_size' => '1.125rem',
                'button_style' => 'gradient'
            ],
            self::PRESET_MINIMAL => [
                'card_shadow' => 'none',
                'card_radius' => '0',
                'card_hover' => 'none',
                'title_size' => '0.875rem',
                'button_style' => 'outline'
            ],
            self::PRESET_BOLD => [
                'card_shadow' => 'xl',
                'card_radius' => '1.5rem',
                'card_hover' => 'scale',
                'title_size' => '1.25rem',
                'button_style' => 'primary'
            ]
        ];

        return $presets[$preset] ?? $presets[self::PRESET_DEFAULT];
    }

    /**
     * Get Tailwind shadow class
     *
     * @param string $size
     * @return string
     */
    public function getShadowClass(string $size): string
    {
        $shadows = [
            'none' => '',
            'sm' => 'shadow-sm',
            'md' => 'shadow-md',
            'lg' => 'shadow-lg',
            'xl' => 'shadow-xl',
            '2xl' => 'shadow-2xl'
        ];

        return $shadows[$size] ?? $shadows['md'];
    }

    /**
     * Get hover effect classes
     *
     * @param string $effect
     * @return string
     */
    public function getHoverEffectClass(string $effect): string
    {
        $effects = [
            'none' => '',
            'lift' => 'hover:shadow-lg transition-shadow duration-300',
            'scale' => 'hover:scale-105 transition-transform duration-300',
            'both' => 'hover:shadow-lg hover:scale-105 transition-all duration-300'
        ];

        return $effects[$effect] ?? $effects['lift'];
    }

    /**
     * Parse comma-separated IDs to array
     *
     * @param string|null $ids
     * @return array
     */
    public function parseIds(?string $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $ids)));
    }

    /**
     * Get responsive column classes
     *
     * @param int $mobile
     * @param int $tablet
     * @param int $desktop
     * @return string
     */
    public function getColumnClasses(int $mobile = 1, int $tablet = 2, int $desktop = 4): string
    {
        $classes = ['snap-track'];

        // Hyva snap-slider breakpoints (matching Tailwind CSS build):
        // md (768px+), lg (1024px+), xl (1280px+)
        // Available: md:[--snap-cols:2], lg:[--snap-cols:3], xl:[--snap-cols:4], xl:[--snap-cols:5]

        if ($tablet > 1) {
            $classes[] = "md:[--snap-cols:{$tablet}]";
        }

        // For 3 columns: use lg breakpoint (1024px+)
        // For 4+ columns: step through lg:3 then xl:4/5
        if ($desktop > 2 && $desktop <= 3) {
            $classes[] = "lg:[--snap-cols:{$desktop}]";
        } elseif ($desktop > 3) {
            $classes[] = "lg:[--snap-cols:3]";
            $classes[] = "xl:[--snap-cols:{$desktop}]";
        }

        return implode(' ', $classes);
    }

    // ========================================
    // Arrow Navigation Methods
    // ========================================

    /**
     * Check if arrows should be shown
     *
     * @return bool
     */
    public function showArrows(): bool
    {
        return (bool)$this->getConfigValue('arrows/show_arrows');
    }

    /**
     * Get arrow icon color
     *
     * @return string
     */
    public function getArrowColor(): string
    {
        return (string)($this->getConfigValue('arrows/arrow_color') ?? '');
    }

    /**
     * Get arrow background color
     *
     * @return string
     */
    public function getArrowBgColor(): string
    {
        return (string)($this->getConfigValue('arrows/arrow_bg_color') ?? '');
    }

    /**
     * Get arrow hover icon color
     *
     * @return string
     */
    public function getArrowHoverColor(): string
    {
        return (string)($this->getConfigValue('arrows/arrow_hover_color') ?? '');
    }

    /**
     * Get arrow hover background color
     *
     * @return string
     */
    public function getArrowHoverBgColor(): string
    {
        return (string)($this->getConfigValue('arrows/arrow_hover_bg_color') ?? '');
    }

    /**
     * Get arrow border color
     *
     * @return string
     */
    public function getArrowBorderColor(): string
    {
        return (string)($this->getConfigValue('arrows/arrow_border_color') ?? '');
    }

    /**
     * Get arrow button size
     *
     * @return int
     */
    public function getArrowSize(): int
    {
        return (int)$this->getConfigValue('arrows/arrow_size') ?: 40;
    }

    /**
     * Get arrow position
     *
     * @return string
     */
    public function getArrowPosition(): string
    {
        return (string)($this->getConfigValue('arrows/arrow_position') ?: 'outside');
    }
}
