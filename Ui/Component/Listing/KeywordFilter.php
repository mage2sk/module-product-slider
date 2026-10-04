<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Ui\Component\Listing;

use Magento\Framework\Api\Filter;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\View\Element\UiComponent\DataProvider\FilterApplierInterface;

class KeywordFilter implements FilterApplierInterface
{
    private const COLUMNS = ['main_table.identifier', 'main_table.title'];

    public function apply(Collection $collection, Filter $filter)
    {
        if (!$collection instanceof AbstractDb) {
            throw new \InvalidArgumentException('Database collection required.');
        }

        $keyword = trim((string)$filter->getValue());
        if ($keyword === '') {
            return;
        }

        $like = '%' . addcslashes($keyword, '\\%_') . '%';
        $collection->addFieldToFilter(self::COLUMNS, array_fill(0, count(self::COLUMNS), ['like' => $like]));
    }
}
