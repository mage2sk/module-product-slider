<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Model\Product;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Badge extends AbstractHelper
{
    protected $timezone;

    protected $dataHelper;

    public function __construct(
        Context $context,
        TimezoneInterface $timezone,
        Data $dataHelper
    ) {
        parent::__construct($context);
        $this->timezone = $timezone;
        $this->dataHelper = $dataHelper;
    }

    public function getBadges(Product $product, string $enabledTypes = 'sale,new', int $newDays = 30): array
    {
        $badges = [];
        $types = $this->dataHelper->parseIds($enabledTypes);

        foreach ($types as $type) {
            switch ($type) {
                case 'sale':
                    if ($badge = $this->getSaleBadge($product)) {
                        $badges[] = $badge;
                    }
                    break;
                case 'new':
                    if ($badge = $this->getNewBadge($product, $newDays)) {
                        $badges[] = $badge;
                    }
                    break;
                case 'stock':
                    if ($badge = $this->getStockBadge($product)) {
                        $badges[] = $badge;
                    }
                    break;
                case 'featured':
                    if ($badge = $this->getFeaturedBadge($product)) {
                        $badges[] = $badge;
                    }
                    break;
            }
        }

        return $badges;
    }

    public function getSaleBadge(Product $product): ?array
    {
        $specialPrice = $product->getSpecialPrice();
        $regularPrice = $product->getPrice();

        if (!$specialPrice || !$regularPrice || $specialPrice >= $regularPrice) {
            return null;
        }

        $discount = round((($regularPrice - $specialPrice) / $regularPrice) * 100);

        return [
            'type' => 'sale',
            'label' => "-{$discount}%",
            'class' => 'bg-red-500 text-white',
            'priority' => 1
        ];
    }

    public function getNewBadge(Product $product, int $days = 30): ?array
    {
        $createdAt = $product->getCreatedAt();
        if (!$createdAt) {
            return null;
        }

        $createdDate = new \DateTime($createdAt);
        $now = new \DateTime();
        $diff = $now->diff($createdDate)->days;

        if ($diff <= $days) {
            return [
                'type' => 'new',
                'label' => __('New'),
                'class' => 'bg-green-500 text-white',
                'priority' => 2
            ];
        }

        return null;
    }

    public function getStockBadge(Product $product): ?array
    {
        if (!$product->isSaleable()) {
            return [
                'type' => 'stock',
                'label' => __('Out of Stock'),
                'class' => 'bg-gray-500 text-white',
                'priority' => 4
            ];
        }

        $extensionAttributes = $product->getExtensionAttributes();
        $stockItem = $extensionAttributes ? $extensionAttributes->getStockItem() : null;
        $qty = $stockItem ? (float)$stockItem->getQty() : 0;
        $lowStockThreshold = 10;

        if ($qty > 0 && $qty <= $lowStockThreshold) {
            return [
                'type' => 'stock',
                'label' => __('Low Stock'),
                'class' => 'bg-amber-500 text-white',
                'priority' => 3
            ];
        }

        return null;
    }

    public function getFeaturedBadge(Product $product): ?array
    {
        $isFeatured = $product->getData('is_featured');

        if ($isFeatured) {
            return [
                'type' => 'featured',
                'label' => __('Featured'),
                'class' => 'bg-purple-500 text-white',
                'priority' => 2
            ];
        }

        return null;
    }

    public function getBadgePositionClass(string $position = 'top-left'): string
    {
        $positions = [
            'top-left' => 'top-2 left-2',
            'top-right' => 'top-2 right-2',
            'bottom-left' => 'bottom-2 left-2',
            'bottom-right' => 'bottom-2 right-2'
        ];

        return $positions[$position] ?? $positions['top-left'];
    }
}
