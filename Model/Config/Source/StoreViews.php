<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\System\Store as SystemStore;

class StoreViews implements OptionSourceInterface
{
    private SystemStore $systemStore;

    public function __construct(SystemStore $systemStore)
    {
        $this->systemStore = $systemStore;
    }

    public function toOptionArray(): array
    {
        return $this->systemStore->getStoreValuesForForm(false, true);
    }
}
