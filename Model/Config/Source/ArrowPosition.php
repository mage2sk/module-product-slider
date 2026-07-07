<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ArrowPosition implements OptionSourceInterface
{
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
