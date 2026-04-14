<?php
/**
 * Badge Position source model for system configuration
 *
 * @package Panth_ProductSlider
 */
declare(strict_types=1);

namespace Panth\ProductSlider\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class BadgePosition implements OptionSourceInterface
{
    /**
     * Get options for badge position dropdown
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'top-left', 'label' => __('Top Left')],
            ['value' => 'top-right', 'label' => __('Top Right')],
            ['value' => 'bottom-left', 'label' => __('Bottom Left')],
            ['value' => 'bottom-right', 'label' => __('Bottom Right')]
        ];
    }
}
