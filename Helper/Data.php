<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    public const XML_PATH_ENABLED = 'product_slider/general/enabled';
    public const XML_PATH_CACHE_LIFETIME = 'product_slider/general/cache_lifetime';

    public const PRESET_DEFAULT = 'default';
    public const PRESET_MODERN = 'modern';
    public const PRESET_MINIMAL = 'minimal';
    public const PRESET_BOLD = 'bold';
    public const PRESET_CUSTOM = 'custom';

    protected function getConfigValue(string $path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            'product_slider/' . $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    protected function isCoreModuleEnabled(): bool
    {
        return true;
    }

    public function isEnabled($storeId = null): bool
    {
        if (!$this->isCoreModuleEnabled()) {
            return false;
        }

        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getCacheLifetime($storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_CACHE_LIFETIME,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 3600;
    }

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

    public function parseIds(?string $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $ids)));
    }

    public function getColumnClasses(int $mobile = 1, int $tablet = 2, int $desktop = 4): string
    {
        $classes = ['snap-track'];

        if ($tablet > 1) {
            $classes[] = "md:[--snap-cols:{$tablet}]";
        }

        if ($desktop > 2 && $desktop <= 3) {
            $classes[] = "lg:[--snap-cols:{$desktop}]";
        } elseif ($desktop > 3) {
            $classes[] = "lg:[--snap-cols:3]";
            $classes[] = "xl:[--snap-cols:{$desktop}]";
        }

        return implode(' ', $classes);
    }

    public function showArrows(): bool
    {
        return (bool)$this->getConfigValue('arrows/show_arrows');
    }

    public function getArrowColor(): string
    {
        return (string)($this->getConfigValue('arrows/arrow_color') ?? '');
    }

    public function getArrowBgColor(): string
    {
        return (string)($this->getConfigValue('arrows/arrow_bg_color') ?? '');
    }

    public function getArrowHoverColor(): string
    {
        return (string)($this->getConfigValue('arrows/arrow_hover_color') ?? '');
    }

    public function getArrowHoverBgColor(): string
    {
        return (string)($this->getConfigValue('arrows/arrow_hover_bg_color') ?? '');
    }

    public function getArrowBorderColor(): string
    {
        return (string)($this->getConfigValue('arrows/arrow_border_color') ?? '');
    }

    public function getArrowSize(): int
    {
        return (int)$this->getConfigValue('arrows/arrow_size') ?: 40;
    }

    public function getArrowPosition(): string
    {
        return (string)($this->getConfigValue('arrows/arrow_position') ?: 'outside');
    }
}
