<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Panth\ProductSlider\Model\ResourceModel\Slider as SliderResourceModel;

class Slider extends AbstractModel implements IdentityInterface
{
    public const CACHE_TAG = 'panth_product_slider';

    protected $_eventPrefix = 'panth_product_slider';

    protected $_cacheTag = self::CACHE_TAG;

    protected function _construct(): void
    {
        $this->_init(SliderResourceModel::class);
    }

    public function getIdentities()
    {
        $identities = [self::CACHE_TAG];
        if ($this->getId()) {
            $identities[] = self::CACHE_TAG . '_' . $this->getId();
        }

        return $identities;
    }
}
