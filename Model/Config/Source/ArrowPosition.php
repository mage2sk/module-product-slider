<?php
/**
 * Arrow Position source model for system configuration
 *
 * @package Panth_ProductSlider
 */
declare(strict_types=1);

namespace Panth\ProductSlider\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ArrowPosition implements OptionSourceInterface
{
    /**
     * Get options for arrow position dropdown
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'outside', 'label' => __('Outside')],
            ['value' => 'center', 'label' => __('Center (Overlay)')],
            ['value' => 'top', 'label' => __('Top')],
            ['value' => 'bottom', 'label' => __('Bottom')]
        ];
    }
}
