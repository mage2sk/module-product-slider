<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Test\Unit\Block\Widget;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\CatalogInventory\Model\ResourceModel\Stock\StatusFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Panth\ProductSlider\Block\Widget\ProductSlider;
use Panth\ProductSlider\Helper\Badge;
use Panth\ProductSlider\Helper\Data;

class ProductSliderTest extends ProductSliderTestCase
{
    /**
     * @param array $data
     * @return ProductSlider
     */
    private function block(array $data = []): ProductSlider
    {
        return new ProductSlider(
            $this->context,
            $this->collectionFactory(),
            $this->visibility(),
            $this->createStub(StatusFactory::class),
            $this->sliderHelper(),
            $this->badgeHelper(),
            $this->timezone,
            $this->priceCurrency,
            $this->theme,
            $this->imageHelper,
            $data
        );
    }

    public function testBaseCollectionFiltersEnabledVisibleProductsOfTheCurrentStore(): void
    {
        $this->block()->getProductCollection();

        $this->assertContains(['status', ProductStatus::STATUS_ENABLED], $this->callsTo('addAttributeToFilter'));
        $this->assertSame([[[2, 4]]], $this->callsTo('setVisibility'));
        $this->assertSame([[1]], $this->callsTo('addStoreFilter'));
        $this->assertSame([['position', 'ASC']], $this->callsTo('addAttributeToSort'));
        $this->assertSame([[8]], $this->callsTo('setPageSize'));
        $this->assertSame([], $this->callsTo('addCategoriesFilter'));
    }

    public function testCollectionIsBuiltOnlyOnce(): void
    {
        $block = $this->block();

        $this->assertSame($block->getProductCollection(), $block->getProductCollection());
        $this->assertCount(1, $this->callsTo('addStoreFilter'));
    }

    public function testProductIdsFilterKeepsTheGivenOrder(): void
    {
        $this->block(['product_ids' => '7, 3,abc,9'])->getProductCollection();

        $this->assertContains(['entity_id', ['in' => [7, 3, 9]]], $this->callsTo('addFieldToFilter'));
        $order = $this->selectCallsTo('order');
        $this->assertCount(1, $order);
        $this->assertSame('FIELD(e.entity_id,7,3,9)', (string)$order[0][0]);
    }

    public function testProductIdsWithoutAnyNumericIdMatchNothing(): void
    {
        $this->block(['product_ids' => 'abc, ,x'])->getProductCollection();

        $this->assertContains(['entity_id', ['in' => [0]]], $this->callsTo('addFieldToFilter'));
    }

    public function testCategoryFilterWithoutChildren(): void
    {
        $this->block(['category_ids' => '5,6'])->getProductCollection();

        $this->assertSame([[['in' => [5, 6]]]], $this->callsTo('addCategoriesFilter'));
        $this->assertSame([], $this->selectCallsTo('orWhere'));
    }

    public function testCategoryFilterAddsChildCategoriesWhenRequested(): void
    {
        $this->childCategoryIds = ['11', '12', '5'];

        $this->block(['category_ids' => '5', 'include_child_categories' => '1'])->getProductCollection();

        $this->assertSame([[['in' => [5, 11, 12]]]], $this->callsTo('addCategoriesFilter'));
        $this->assertSame([['path LIKE ?', '%/5/%']], $this->selectCallsTo('orWhere'));
        $this->assertSame([['pfx_catalog_category_entity', ['entity_id']]], $this->selectCallsTo('from'));
    }

    public function testInvalidCategoryIdsMatchNothing(): void
    {
        $this->block(['category_ids' => 'x,y'])->getProductCollection();

        $this->assertSame([[['in' => [0]]]], $this->callsTo('addCategoriesFilter'));
    }

    public function testSkuFilter(): void
    {
        $this->block(['product_skus' => 'A-1, B-2'])->getProductCollection();

        $filters = $this->callsTo('addFieldToFilter');
        $this->assertSame('sku', $filters[0][0]);
        $this->assertSame(['A-1', 'B-2'], array_values($filters[0][1]['in']));
    }

    public function testSaleOnlyFiltersOnActiveSpecialPrice(): void
    {
        $this->block(['sale_products_only' => 1])->getProductCollection();

        $filters = $this->callsTo('addAttributeToFilter');
        $this->assertContains(['special_price', ['notnull' => true]], $filters);
        $this->assertContains(['special_price', ['gt' => 0]], $filters);
        $this->assertContains(
            ['special_from_date', [['null' => true], ['lteq' => '2026-03-15 23:59:59']], 'left'],
            $filters
        );
        $this->assertContains(['special_to_date', [['null' => true], ['gteq' => '2026-03-15']], 'left'], $filters);
    }

    public function testSaleOnlyRequiresFinalPriceBelowRegularPrice(): void
    {
        $this->block(['sale_products_only' => 1])->getProductCollection();

        $this->assertSame([['price_index.final_price < price_index.price']], $this->selectCallsTo('where'));
    }

    public function testFinalPriceConditionIsNotAddedWithoutSaleOnly(): void
    {
        $this->block()->getProductCollection();

        $this->assertSame([], $this->selectCallsTo('where'));
    }

    public function testNewProductsDaysFiltersOnCreationDate(): void
    {
        $this->block(['new_products_days' => '10'])->getProductCollection();

        $filter = $this->callsTo('addFieldToFilter')[0];
        $this->assertSame('created_at', $filter[0]);
        $since = new \DateTime($filter[1]['gteq'], new \DateTimeZone('UTC'));
        $expected = new \DateTime('-10 days', new \DateTimeZone('UTC'));
        $this->assertLessThan(5, abs($expected->getTimestamp() - $since->getTimestamp()));
    }

    public function testPriceRangeIgnoresNonNumericAndNonPositiveBounds(): void
    {
        $this->block(['price_from' => '10.5', 'price_to' => '99'])->getProductCollection();
        $filters = $this->callsTo('addAttributeToFilter');
        $this->assertContains(['price', ['gteq' => 10.5]], $filters);
        $this->assertContains(['price', ['lteq' => 99.0]], $filters);

        $this->setUp();
        $this->block(['price_from' => 'abc', 'price_to' => '0'])->getProductCollection();
        foreach ($this->callsTo('addAttributeToFilter') as $args) {
            $this->assertNotSame('price', $args[0]);
        }
    }

    public function testExcludeOutOfStockJoinsStockStatus(): void
    {
        $this->block(['exclude_out_of_stock' => 1])->getProductCollection();

        $join = $this->selectCallsTo('joinLeft');
        $this->assertSame(['_stock_status' => 'pfx_cataloginventory_stock_status'], $join[0][0]);
        $this->assertSame([['_stock_status.stock_status = ?', 1]], $this->selectCallsTo('where'));
        $this->assertSame([[true]], $this->selectCallsTo('distinct'));
    }

    public function testSortingIsWhitelistedAndDirectionNormalised(): void
    {
        $this->block(['sort_by' => 'price', 'sort_direction' => 'desc'])->getProductCollection();
        $this->assertSame([['price', 'DESC']], $this->callsTo('addAttributeToSort'));

        $this->setUp();
        $this->block(['sort_by' => 'entity_id; DROP', 'sort_direction' => 'sideways'])->getProductCollection();
        $this->assertSame([['position', 'ASC']], $this->callsTo('addAttributeToSort'));
    }

    public function testRandomSortUsesRandOrder(): void
    {
        $this->block(['sort_by' => 'random'])->getProductCollection();

        $this->assertSame([], $this->callsTo('addAttributeToSort'));
        $this->assertSame('RAND()', (string)$this->selectCallsTo('order')[0][0]);
    }

    public function testPageSizeIsClampedBetweenOneAndOneHundred(): void
    {
        $this->block(['page_size' => '500'])->getProductCollection();
        $this->assertSame([[100]], $this->callsTo('setPageSize'));

        $this->setUp();
        $this->block(['page_size' => '-4'])->getProductCollection();
        $this->assertSame([[1]], $this->callsTo('setPageSize'));
    }

    public function testSliderConfigDefaults(): void
    {
        $config = $this->block(['title' => 'Picks'])->getSliderConfig();

        $this->assertSame('Picks', $config['title']);
        $this->assertSame('', $config['description']);
        $this->assertSame('h2', $config['heading_tag']);
        $this->assertTrue($config['show_heading']);
        $this->assertTrue($config['show_pager']);
        $this->assertSame([1, 2, 4], [$config['columns_mobile'], $config['columns_tablet'], $config['columns_desktop']]);
        $this->assertSame('md', $config['card_shadow']);
        $this->assertSame('lift', $config['card_hover']);
        $this->assertSame('default', $config['style_preset']);
        $this->assertFalse($config['enable_autoplay']);
        $this->assertSame(3000, $config['autoplay_interval']);
        $this->assertTrue($config['show_add_to_cart']);
    }

    public function testSliderConfigNormalisesUserInput(): void
    {
        $config = $this->block([
            'heading_tag' => 'H3',
            'show_heading' => '0',
            'show_pager' => '0',
            'columns_mobile' => '0',
            'columns_tablet' => '30',
            'columns_desktop' => '6',
            'custom_css_class' => ' my-class  "onload=x <b>  other_1 ',
            'enable_autoplay' => '1',
            'autoplay_interval' => '200',
            'hide_details' => '1',
            'show_add_to_cart' => '0',
        ])->getSliderConfig();

        $this->assertSame('h3', $config['heading_tag']);
        $this->assertFalse($config['show_heading']);
        $this->assertFalse($config['show_pager']);
        $this->assertSame([1, 12, 6], [$config['columns_mobile'], $config['columns_tablet'], $config['columns_desktop']]);
        $this->assertSame('my-class onloadx b other_1', $config['custom_css_class']);
        $this->assertTrue($config['enable_autoplay']);
        $this->assertSame(1000, $config['autoplay_interval']);
        $this->assertTrue($config['hide_details']);
        $this->assertFalse($config['show_add_to_cart']);
    }

    public function testInvalidHeadingTagFallsBackToH2(): void
    {
        $this->assertSame('h2', $this->block(['heading_tag' => 'script'])->getSliderConfig()['heading_tag']);
    }

    public function testNamedPresetOverridesCustomCardSettings(): void
    {
        $config = $this->block([
            'style_preset' => 'minimal',
            'card_shadow' => 'xl',
            'card_hover_effect' => 'glow',
        ])->getSliderConfig();

        $this->assertSame('none', $config['card_shadow']);
        $this->assertSame('none', $config['card_hover']);
        $this->assertSame('0', $config['card_radius']);
    }

    public function testCustomPresetUsesExplicitCardSettings(): void
    {
        $config = $this->block([
            'style_preset' => 'custom',
            'card_shadow' => 'xl',
            'card_hover_effect' => 'glow',
        ])->getSliderConfig();

        $this->assertSame('xl', $config['card_shadow']);
        $this->assertSame('glow', $config['card_hover']);
    }

    public function testCardClassesAndCssAreEmptyWhenCardSettingsAreOff(): void
    {
        $block = $this->block(['style_preset' => 'bold']);

        $this->assertSame('', $block->getSliderCssClasses());
        $this->assertSame('', $block->getCardStyleCss('#s'));
    }

    public function testCardClassesAndCssWhenCardSettingsAreOn(): void
    {
        $this->flags[Data::XML_PATH_APPLY_CARD_SETTINGS] = 1;
        $block = $this->block(['style_preset' => 'bold']);

        $this->assertSame('panth-ps-shadow-xl panth-ps-hover-grow', $block->getSliderCssClasses());
        $this->assertSame(
            '#s { --panth-ps-radius: 24px; --panth-ps-title-size: 20px; }',
            $block->getCardStyleCss('#s')
        );
    }

    public function testCardSizesArePrintedInPixelsSoLumaRootFontSizeDoesNotShrinkThem(): void
    {
        $this->flags[Data::XML_PATH_APPLY_CARD_SETTINGS] = 1;

        $this->assertSame(
            '#s { --panth-ps-radius: 12px; --panth-ps-title-size: 16px; }',
            $this->block(['style_preset' => 'default'])->getCardStyleCss('#s')
        );
        $this->assertSame(
            '#s { --panth-ps-radius: 0; --panth-ps-title-size: 14px; }',
            $this->block(['style_preset' => 'minimal'])->getCardStyleCss('#s')
        );
        $this->assertSame(
            '#s { --panth-ps-radius: 16px; --panth-ps-title-size: 18px; }',
            $this->block(['style_preset' => 'modern'])->getCardStyleCss('#s')
        );
    }

    public function testColumnTrackClassesUseClampedColumns(): void
    {
        $this->assertSame(
            'snap-track md:[--snap-cols:3] lg:[--snap-cols:3] xl:[--snap-cols:12]',
            $this->block(['columns_tablet' => '3', 'columns_desktop' => '40'])->getColumnTrackClasses()
        );
    }

    public function testDescriptionHtmlKeepsOnlyAllowedTags(): void
    {
        $block = $this->block(['description' => '<p>Hi <script>x</script><strong>there</strong></p>']);

        $this->assertSame('<p>Hi x<strong>there</strong></p>', $block->getDescriptionHtml());
        $this->assertSame('', $this->block()->getDescriptionHtml());
    }

    public function testProductBadgesRequireBadgesAndCardSettings(): void
    {
        $product = $this->createStub(Product::class);

        $this->assertSame([], $this->block(['enable_badges' => '1'])->getProductBadges($product));

        $this->flags[Data::XML_PATH_APPLY_CARD_SETTINGS] = 1;
        $this->assertSame([], $this->block(['enable_badges' => '0'])->getProductBadges($product));
    }

    public function testProductBadgesDelegateWithDefaults(): void
    {
        $this->flags[Data::XML_PATH_APPLY_CARD_SETTINGS] = 1;
        $product = $this->createStub(Product::class);
        $badges = $this->createMock(Badge::class);
        $badges->expects($this->once())->method('getBadges')->with($product, 'sale,new', 30)
            ->willReturn([['type' => 'sale']]);
        $this->badgeHelper = $badges;

        $this->assertSame([['type' => 'sale']], $this->block(['enable_badges' => '1'])->getProductBadges($product));
    }

    public function testProductBadgesPassConfiguredTypesAndDays(): void
    {
        $this->flags[Data::XML_PATH_APPLY_CARD_SETTINGS] = 1;
        $product = $this->createStub(Product::class);
        $badges = $this->createMock(Badge::class);
        $badges->expects($this->once())->method('getBadges')->with($product, 'stock', 7)->willReturn([]);
        $this->badgeHelper = $badges;

        $this->block(['enable_badges' => '1', 'badge_types' => 'stock', 'new_products_days' => '7'])
            ->getProductBadges($product);
    }

    public function testBadgePositionClassDefaultsToTopLeft(): void
    {
        $badges = $this->createMock(Badge::class);
        $badges->expects($this->exactly(2))->method('getBadgePositionClass')
            ->willReturnCallback(static fn($position) => 'pos:' . $position);
        $this->badgeHelper = $badges;

        $this->assertSame('pos:top-left', $this->block()->getBadgePositionClass());
        $this->assertSame('pos:bottom-right', $this->block(['badge_position' => 'bottom-right'])->getBadgePositionClass());
    }

    public function testSliderIdIsSanitisedFromWidgetId(): void
    {
        $this->assertSame('product-slider-a-b-c_1', $this->block(['widget_id' => 'a b/c_1'])->getSliderId());
        $this->assertMatchesRegularExpression('/^product-slider-[a-f0-9]+$/', $this->block()->getSliderId());
    }

    public function testTemplateDependsOnTheActiveTheme(): void
    {
        $this->theme->method('isHyva')->willReturnOnConsecutiveCalls(true, false);

        $this->assertSame('Panth_ProductSlider::widget/slider.phtml', $this->block()->getTemplate());
        $this->assertSame('Panth_ProductSlider::luma/widget/slider.phtml', $this->block()->getTemplate());
    }

    public function testFormatPriceUsesTheCurrentStore(): void
    {
        $price = $this->createMock(PriceCurrencyInterface::class);
        $price->expects($this->once())->method('format')
            ->with(12.5, false, PriceCurrencyInterface::DEFAULT_PRECISION, $this->store)
            ->willReturn('$12.50');
        $this->priceCurrency = $price;

        $this->assertSame('$12.50', $this->block()->formatPrice(12.5));
    }

    public function testMediaBaseUrlComesFromTheStore(): void
    {
        $this->assertSame('https://shop.test/media/', $this->block()->getMediaBaseUrl());
    }

    public function testProductImageUrlResizesThroughTheImageHelper(): void
    {
        $product = $this->createStub(Product::class);
        $image = $this->createMock(\Magento\Catalog\Helper\Image::class);
        $image->expects($this->once())->method('init')->with($product, 'small')->willReturnSelf();
        $image->expects($this->once())->method('resize')->with(120, 90)->willReturnSelf();
        $image->method('getUrl')->willReturn('https://shop.test/img.jpg');
        $this->imageHelper = $image;

        $this->assertSame('https://shop.test/img.jpg', $this->block()->getProductImageUrl($product, 'small', 120, 90));
    }

    public function testIdentitiesIncludeEveryLoadedProduct(): void
    {
        $this->items = [new DataObject(['id' => 4]), new DataObject(['id' => 9])];

        $this->assertSame(
            [Product::CACHE_TAG, Product::CACHE_TAG . '_4', Product::CACHE_TAG . '_9'],
            $this->block()->getIdentities()
        );
    }

    public function testIdentitiesFallBackToTheProductTagWhenLoadingFails(): void
    {
        $this->collectionError = new \RuntimeException('db down');

        $this->assertSame([Product::CACHE_TAG], $this->block()->getIdentities());
    }

    public function testRenderIsEmptyWhenModuleIsDisabled(): void
    {
        $this->items = [new DataObject(['id' => 1])];

        $this->assertFalse($this->block()->isModuleEnabled());
        $this->assertSame('', $this->invoke($this->block(), '_toHtml'));
    }

    public function testRenderIsEmptyWithoutProductsOrOnError(): void
    {
        $this->flags[Data::XML_PATH_ENABLED] = 1;
        $this->assertTrue($this->block()->isModuleEnabled());
        $this->assertSame('', $this->invoke($this->block(), '_toHtml'));

        $this->collectionError = new \RuntimeException('boom');
        $this->assertSame('', $this->invoke($this->block(), '_toHtml'));
    }
}
