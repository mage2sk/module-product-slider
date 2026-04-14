<?php

declare(strict_types=1);

namespace Panth\ProductSlider\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Slider extends AbstractDb
{
    /**
     * @var string
     */
    protected $_idFieldName = 'slider_id';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('panth_product_slider', 'slider_id');
    }
}
