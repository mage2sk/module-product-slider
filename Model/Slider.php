<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Model;

use Magento\Framework\Model\AbstractModel;
use Panth\ProductSlider\Model\ResourceModel\Slider as SliderResourceModel;

class Slider extends AbstractModel
{
    protected $_eventPrefix = 'panth_product_slider';

    protected function _construct(): void
    {
        $this->_init(SliderResourceModel::class);
    }
}
