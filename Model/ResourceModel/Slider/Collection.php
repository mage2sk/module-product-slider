<?php

declare(strict_types=1);

namespace Panth\ProductSlider\Model\ResourceModel\Slider;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Panth\ProductSlider\Model\Slider as SliderModel;
use Panth\ProductSlider\Model\ResourceModel\Slider as SliderResourceModel;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'slider_id';

    /**
     * Initialize collection model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(SliderModel::class, SliderResourceModel::class);
    }
}
