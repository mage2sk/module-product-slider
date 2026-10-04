<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Test\Unit\Helper;

use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Panth\ProductSlider\Helper\Badge;
use Panth\ProductSlider\Helper\Data;
use PHPUnit\Framework\TestCase;

class BadgeTest extends TestCase
{
    /**
     * @return Badge
     */
    private function badge(): Badge
    {
        $context = $this->createStub(Context::class);

        $timezone = $this->createStub(TimezoneInterface::class);
        $timezone->method('isScopeDateInInterval')->willReturnCallback(
            static function ($scope, $from = null, $to = null): bool {
                $today = date('Y-m-d');
                return ($from === null || substr((string) $from, 0, 10) <= $today)
                    && ($to === null || substr((string) $to, 0, 10) >= $today);
            }
        );

        return new Badge($context, $timezone, new Data($context));
    }

    /**
     * Build a product stub from a set of attribute values.
     *
     * @param array $data
     * @return Product
     */
    private function product(array $data): Product
    {
        $product = $this->createStub(Product::class);
        $product->method('getSpecialPrice')->willReturn($data['special_price'] ?? null);
        $product->method('getPrice')->willReturn($data['price'] ?? null);
        $product->method('getSpecialFromDate')->willReturn($data['special_from_date'] ?? null);
        $product->method('getSpecialToDate')->willReturn($data['special_to_date'] ?? null);
        $product->method('getCreatedAt')->willReturn($data['created_at'] ?? null);
        $product->method('isSaleable')->willReturn($data['saleable'] ?? true);
        $product->method('getData')->willReturnCallback(
            static fn($key = '', $index = null) => $data[$key] ?? null
        );
        if (array_key_exists('qty', $data)) {
            $stockItem = $this->createStub(StockItemInterface::class);
            $stockItem->method('getQty')->willReturn($data['qty']);
            $extension = $this->createStub(ProductExtensionInterface::class);
            $extension->method('getStockItem')->willReturn($stockItem);
            $product->method('getExtensionAttributes')->willReturn($extension);
        }

        return $product;
    }

    public function testSaleBadgeShowsRoundedDiscountPercentage(): void
    {
        $badge = $this->badge()->getSaleBadge($this->product(['price' => 90.0, 'special_price' => 60.0]));

        $this->assertSame('sale', $badge['type']);
        $this->assertSame('-33%', $badge['label']);
        $this->assertSame(1, $badge['priority']);
    }

    public function testSaleBadgeIsNullWithoutAValidDiscount(): void
    {
        $badge = $this->badge();

        $this->assertNull($badge->getSaleBadge($this->product(['price' => 50.0])));
        $this->assertNull($badge->getSaleBadge($this->product(['special_price' => 10.0])));
        $this->assertNull($badge->getSaleBadge($this->product(['price' => 50.0, 'special_price' => 50.0])));
        $this->assertNull($badge->getSaleBadge($this->product(['price' => 50.0, 'special_price' => 70.0])));
    }

    public function testSaleBadgeRespectsSpecialPriceDateRange(): void
    {
        $badge = $this->badge();
        $base = ['price' => 100.0, 'special_price' => 80.0];
        $past = (new \DateTime('-10 days'))->format('Y-m-d 00:00:00');
        $yesterday = (new \DateTime('-1 day'))->format('Y-m-d 00:00:00');
        $future = (new \DateTime('+10 days'))->format('Y-m-d 00:00:00');

        $this->assertNull($badge->getSaleBadge($this->product($base + ['special_to_date' => $yesterday])));
        $this->assertNull($badge->getSaleBadge($this->product($base + ['special_from_date' => $future])));
        $this->assertSame('-20%', $badge->getSaleBadge($this->product(
            $base + ['special_from_date' => $past, 'special_to_date' => $future]
        ))['label']);
    }

    public function testNewBadgeDependsOnProductAge(): void
    {
        $badge = $this->badge();
        $recent = (new \DateTime('-5 days'))->format('Y-m-d H:i:s');
        $old = (new \DateTime('-90 days'))->format('Y-m-d H:i:s');

        $this->assertSame('New', (string)$badge->getNewBadge($this->product(['created_at' => $recent]), 30)['label']);
        $this->assertNull($badge->getNewBadge($this->product(['created_at' => $old]), 30));
        $this->assertNull($badge->getNewBadge($this->product(['created_at' => $recent]), 2));
        $this->assertNull($badge->getNewBadge($this->product([]), 30));
    }

    public function testStockBadgeFlagsOutOfStock(): void
    {
        $out = $this->badge()->getStockBadge($this->product(['saleable' => false]));

        $this->assertSame('Out of Stock', (string)$out['label']);
        $this->assertSame(4, $out['priority']);
    }

    public function testStockBadgeFlagsLowStockUpToTheThreshold(): void
    {
        $badge = $this->badge();

        $low = $badge->getStockBadge($this->product(['qty' => 3]));
        $this->assertSame('Low Stock', (string)$low['label']);
        $this->assertSame(3, $low['priority']);
        $this->assertNotNull($badge->getStockBadge($this->product(['qty' => 10])));
        $this->assertNull($badge->getStockBadge($this->product(['qty' => 11])));
        $this->assertNull($badge->getStockBadge($this->product(['qty' => 0])));
        $this->assertNull($badge->getStockBadge($this->product([])));
    }

    public function testFeaturedBadgeFollowsTheAttribute(): void
    {
        $badge = $this->badge();

        $this->assertSame('featured', $badge->getFeaturedBadge($this->product(['is_featured' => 1]))['type']);
        $this->assertNull($badge->getFeaturedBadge($this->product(['is_featured' => 0])));
    }

    public function testGetBadgesCollectsOnlyEnabledTypesInConfiguredOrder(): void
    {
        $product = $this->product([
            'price' => 100.0,
            'special_price' => 75.0,
            'is_featured' => 1,
            'saleable' => false,
            'created_at' => (new \DateTime('-1 day'))->format('Y-m-d H:i:s'),
        ]);

        $types = array_column($this->badge()->getBadges($product, 'featured, sale ,unknown,stock', 30), 'type');

        $this->assertSame(['featured', 'sale', 'stock'], $types);
    }

    public function testGetBadgesSkipsTypesThatDoNotApply(): void
    {
        $this->assertSame([], $this->badge()->getBadges($this->product(['price' => 100.0])));
    }

    public function testBadgePositionClassMapsPositionsWithTopLeftFallback(): void
    {
        $badge = $this->badge();

        $this->assertSame('bottom-2 right-2', $badge->getBadgePositionClass('bottom-right'));
        $this->assertSame('top-2 right-2', $badge->getBadgePositionClass('top-right'));
        $this->assertSame('top-2 left-2', $badge->getBadgePositionClass('middle'));
        $this->assertSame('top-2 left-2', $badge->getBadgePositionClass());
    }
}
