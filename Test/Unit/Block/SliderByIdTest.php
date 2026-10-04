<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Test\Unit\Block;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Model\ResourceModel\Stock\StatusFactory;
use Panth\ProductSlider\Block\SliderById;
use Panth\ProductSlider\Model\ResourceModel\Slider as SliderResource;
use Panth\ProductSlider\Model\Slider;
use Panth\ProductSlider\Model\SliderFactory;
use Panth\ProductSlider\Test\Unit\Block\Widget\ProductSliderTestCase;

class SliderByIdTest extends ProductSliderTestCase
{
    /** @var array|null Row returned when the slider is loaded by identifier. */
    private ?array $row = null;

    /** @var array Identifiers requested from the resource model. */
    private array $loaded = [];

    /**
     * @param array $data
     * @return SliderById
     */
    private function block(array $data = []): SliderById
    {
        $factory = $this->createStub(SliderFactory::class);
        $factory->method('create')->willReturnCallback(static function () {
            $slider = (new \ReflectionClass(Slider::class))->newInstanceWithoutConstructor();
            $slider->setIdFieldName('slider_id');
            return $slider;
        });

        $resource = $this->createStub(SliderResource::class);
        $resource->method('load')->willReturnCallback(function ($slider, $value, $field = null) use ($resource) {
            $this->loaded[] = [$value, $field];
            if ($this->row !== null) {
                $slider->setData($this->row);
            }
            return $resource;
        });

        return new SliderById(
            $this->context,
            $this->collectionFactory(),
            $this->visibility(),
            $this->createStub(StatusFactory::class),
            $this->sliderHelper(),
            $this->badgeHelper(),
            $this->timezone,
            $this->priceCurrency,
            $factory,
            $resource,
            $this->theme,
            $this->imageHelper,
            $data
        );
    }

    public function testActiveSliderVisibleInAllStoresIsMergedIntoTheBlock(): void
    {
        $this->row = ['slider_id' => 5, 'is_active' => 1, 'store_ids' => '0', 'title' => 'Best sellers'];
        $block = $this->block(['identifier' => 'best']);

        $this->invoke($block, '_beforeToHtml');

        $this->assertSame([['best', 'identifier']], $this->loaded);
        $this->assertTrue($block->getData('slider_active'));
        $this->assertSame('Best sellers', $block->getTitle());
        $this->assertStringStartsWith('product_slider_best_', $block->getNameInLayout());
    }

    public function testEmptyStoreListMeansAllStores(): void
    {
        $this->row = ['slider_id' => 5, 'is_active' => 1, 'store_ids' => ''];
        $block = $this->block(['identifier' => 'best']);

        $this->invoke($block, '_beforeToHtml');

        $this->assertTrue($block->getData('slider_active'));
    }

    public function testSliderAssignedToTheCurrentStoreIsShown(): void
    {
        $this->row = ['slider_id' => 5, 'is_active' => 1, 'store_ids' => '3, 1'];
        $block = $this->block(['identifier' => 'best']);

        $this->invoke($block, '_beforeToHtml');

        $this->assertTrue($block->getData('slider_active'));
    }

    public function testSliderOfOtherStoresIsHidden(): void
    {
        $this->row = ['slider_id' => 5, 'is_active' => 1, 'store_ids' => '2,3', 'title' => 'Hidden'];
        $block = $this->block(['identifier' => 'best']);

        $this->invoke($block, '_beforeToHtml');

        $this->assertNull($block->getData('slider_active'));
        $this->assertNull($block->getTitle());
        $this->assertSame('', $this->invoke($block, '_toHtml'));
    }

    public function testInactiveSliderIsNotShown(): void
    {
        $this->row = ['slider_id' => 5, 'is_active' => 0, 'store_ids' => '0'];
        $block = $this->block(['identifier' => 'best']);

        $this->invoke($block, '_beforeToHtml');

        $this->assertNull($block->getData('slider_active'));
        $this->assertSame('', $this->invoke($block, '_toHtml'));
    }

    public function testUnknownIdentifierIsNotShown(): void
    {
        $block = $this->block(['identifier' => 'missing']);

        $this->invoke($block, '_beforeToHtml');

        $this->assertNull($block->getData('slider_active'));
        $this->assertSame([Slider::CACHE_TAG], $block->getIdentities());
    }

    public function testNoIdentifierSkipsLoading(): void
    {
        $block = $this->block();

        $this->invoke($block, '_beforeToHtml');

        $this->assertSame([], $this->loaded);
        $this->assertSame('', $this->invoke($block, '_toHtml'));
    }

    public function testIdentitiesOfAnInactiveSliderIncludeItsOwnTagOnly(): void
    {
        $this->row = ['slider_id' => 8, 'is_active' => 0];
        $block = $this->block(['identifier' => 'off']);
        $this->invoke($block, '_beforeToHtml');

        $this->assertSame([Slider::CACHE_TAG, Slider::CACHE_TAG . '_8'], $block->getIdentities());
    }

    public function testIdentitiesOfAnActiveSliderAddProductTags(): void
    {
        $this->row = ['slider_id' => 8, 'is_active' => 1, 'store_ids' => '0'];
        $this->collectionError = new \RuntimeException('no db');
        $block = $this->block(['identifier' => 'on']);
        $this->invoke($block, '_beforeToHtml');

        $this->assertSame(
            [Slider::CACHE_TAG, Slider::CACHE_TAG . '_8', Product::CACHE_TAG],
            $block->getIdentities()
        );
    }

    public function testSliderIdIsDerivedFromTheSanitisedIdentifier(): void
    {
        $this->assertSame('product-slider-home-sale-', $this->block(['identifier' => 'home sale!'])->getSliderId());
        $this->assertMatchesRegularExpression('/^product-slider-[a-f0-9]+$/', $this->block()->getSliderId());
    }
}
