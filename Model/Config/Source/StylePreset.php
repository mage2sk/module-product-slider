<?php
/**
 * Style Preset source model for system configuration
 *
 * @package Panth_ProductSlider
 */
declare(strict_types=1);

namespace Panth\ProductSlider\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class StylePreset implements OptionSourceInterface
{
    /**
     * Get options for style preset dropdown
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'default', 'label' => __('Default')],
            ['value' => 'modern', 'label' => __('Modern')],
            ['value' => 'minimal', 'label' => __('Minimal')],
            ['value' => 'bold', 'label' => __('Bold')],
            ['value' => 'custom', 'label' => __('Custom')]
        ];
    }
}
