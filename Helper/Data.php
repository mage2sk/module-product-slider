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

    public const XML_PATH_APPLY_CARD_SETTINGS = 'product_slider/general/apply_card_settings';

    public const XML_PATH_DEFAULT_LAYOUT = 'product_slider/default_layout/';

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

    public function isApplyCardSettings($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_APPLY_CARD_SETTINGS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getDefaultLayout(): array
    {
        $defaults = ['columns_mobile' => 1, 'columns_tablet' => 2, 'columns_desktop' => 4, 'page_size' => 8];
        foreach ($defaults as $field => $default) {
            $value = (int)$this->scopeConfig->getValue(self::XML_PATH_DEFAULT_LAYOUT . $field);
            $defaults[$field] = $value > 0 ? $value : $default;
        }

        return $defaults;
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
            'none' => 'panth-ps-shadow-none',
            'sm' => 'panth-ps-shadow-sm',
            'md' => 'panth-ps-shadow-md',
            'lg' => 'panth-ps-shadow-lg',
            'xl' => 'panth-ps-shadow-xl',
            '2xl' => 'panth-ps-shadow-xl'
        ];

        return $shadows[$size] ?? $shadows['md'];
    }

    public function getHoverEffectClass(string $effect): string
    {
        $effects = [
            'none' => 'panth-ps-hover-none',
            'lift' => 'panth-ps-hover-lift',
            'scale' => 'panth-ps-hover-grow',
            'grow' => 'panth-ps-hover-grow',
            'both' => 'panth-ps-hover-lift panth-ps-hover-grow',
            'glow' => 'panth-ps-hover-glow'
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

    public function getCardStyleSheet(string $scope): string
    {
        $card = $scope . ' .panth-ps-card';
        return $card . ' { border-radius: var(--panth-ps-radius, 0.75rem); overflow: hidden;'
            . ' transition: transform 0.3s, box-shadow 0.3s; }'
            . $card . '.panth-ps-shadow-none { box-shadow: none; }'
            . $card . '.panth-ps-shadow-sm { box-shadow: 0 1px 2px rgba(0,0,0,0.06); }'
            . $card . '.panth-ps-shadow-md { box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1); }'
            . $card . '.panth-ps-shadow-lg { box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1); }'
            . $card . '.panth-ps-shadow-xl { box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); }'
            . $card . '.panth-ps-hover-lift:hover { transform: translateY(-4px);'
            . ' box-shadow: 0 10px 15px -3px rgba(0,0,0,0.12), 0 4px 6px -4px rgba(0,0,0,0.1); }'
            . $card . '.panth-ps-hover-grow:hover { transform: scale(1.03); }'
            . $card . '.panth-ps-hover-lift.panth-ps-hover-grow:hover { transform: translateY(-4px) scale(1.03); }'
            . $card . '.panth-ps-hover-glow:hover { box-shadow: 0 0 0 3px var(--color-primary, #0D9488), 0 0 18px rgba(13,148,136,0.35); }'
            . $scope . ' .panth-ps-card .product-card-name,'
            . $scope . ' .panth-ps-card .product-item-link { font-size: var(--panth-ps-title-size, 1rem); }'
            . $scope . ' .panth-ps-badges { position: absolute; z-index: 5; display: flex; flex-direction: column; gap: 4px;'
            . ' pointer-events: none; }'
            . $scope . ' .panth-ps-badges.top-2 { top: 8px; }'
            . $scope . ' .panth-ps-badges.bottom-2 { bottom: 8px; }'
            . $scope . ' .panth-ps-badges.left-2 { left: 8px; }'
            . $scope . ' .panth-ps-badges.right-2 { right: 8px; }'
            . $scope . ' .panth-ps-badge { font-size: 12px; font-weight: 700; line-height: 1; padding: 4px 8px;'
            . ' border-radius: 9999px; color: #fff; background: #6B7280; }'
            . $scope . ' .panth-ps-badge--sale { background: #B91C1C; }'
            . $scope . ' .panth-ps-badge--new { background: #15803D; }'
            . $scope . ' .panth-ps-badge--stock { background: #B45309; }'
            . $scope . ' .panth-ps-badge--featured { background: #7E22CE; }';
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
