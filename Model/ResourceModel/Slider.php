<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Slider extends AbstractDb
{
    protected $_idFieldName = 'slider_id';

    protected function _construct(): void
    {
        $this->_init('panth_product_slider', 'slider_id');
    }
}
