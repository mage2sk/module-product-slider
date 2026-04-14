<?php
/**
 * Panth ProductSlider Widget Block
 *
 * @package Panth_ProductSlider
 * @author Panth
 */
declare(strict_types=1);

namespace Panth\ProductSlider\Block\Widget;

use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogInventory\Model\ResourceModel\Stock\StatusFactory as StockStatusFactory;
use Panth\ProductSlider\Helper\Data as ProductSliderHelper;
use Panth\ProductSlider\Helper\Badge as BadgeHelper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Catalog\Helper\Image as ImageHelper;

class ProductSlider extends Template implements BlockInterface
{
    /**
     * @var string
     */
    protected $_template = 'Panth_ProductSlider::luma/widget/slider.phtml';

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $productCollectionFactory;

    /**
     * @var Visibility
     */
    private Visibility $catalogProductVisibility;

    /**
     * @var StockStatusFactory
     */
    private StockStatusFactory $stockStatusFactory;

    /**
     * @var ProductSliderHelper
     */
    private ProductSliderHelper $sliderHelper;

    /**
     * @var BadgeHelper
     */
    private BadgeHelper $badgeHelper;

    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timezone;

    /**
     * @var PriceCurrencyInterface
     */
    private PriceCurrencyInterface $priceCurrency;

    /**
     * @var \Panth\Core\Helper\Theme
     */
    private \Panth\Core\Helper\Theme $themeHelper;

    /**
     * @var ImageHelper
     */
    private ImageHelper $imageHelper;

    /**
     * @param Context $context
     * @param CollectionFactory $productCollectionFactory
     * @param Visibility $catalogProductVisibility
     * @param StockStatusFactory $stockStatusFactory
     * @param ProductSliderHelper $sliderHelper
     * @param BadgeHelper $badgeHelper
     * @param TimezoneInterface $timezone
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Panth\Core\Helper\Theme $themeHelper
     * @param ImageHelper $imageHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $productCollectionFactory,
        Visibility $catalogProductVisibility,
        StockStatusFactory $stockStatusFactory,
        ProductSliderHelper $sliderHelper,
        BadgeHelper $badgeHelper,
        TimezoneInterface $timezone,
        PriceCurrencyInterface $priceCurrency,
        \Panth\Core\Helper\Theme $themeHelper,
        ImageHelper $imageHelper,
        array $data = []
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->catalogProductVisibility = $catalogProductVisibility;
        $this->stockStatusFactory = $stockStatusFactory;
        $this->sliderHelper = $sliderHelper;
        $this->badgeHelper = $badgeHelper;
        $this->timezone = $timezone;
        $this->priceCurrency = $priceCurrency;
        $this->themeHelper = $themeHelper;
        $this->imageHelper = $imageHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get resized product image URL
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param string $imageId
     * @param int $width
     * @param int $height
     * @return string
     */
    public function getProductImageUrl($product, string $imageId = 'category_page_grid', int $width = 300, int $height = 300): string
    {
        return $this->imageHelper->init($product, $imageId)
            ->resize($width, $height)
            ->getUrl();
    }

    /**
     * Switch template based on theme: Hyva uses snap-slider, Luma uses Swiper.js
     */
    public function getTemplate()
    {
        if ($this->themeHelper->isHyva()) {
            return 'Panth_ProductSlider::widget/slider.phtml';
        }
        return parent::getTemplate();
    }

    /**
     * Get product collection with all filters applied
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getProductCollection()
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect([
                'name',
                'price',
                'special_price',
                'special_from_date',
                'special_to_date',
                'image',
                'small_image',
                'url_key',
                'status',
                'visibility',
                'created_at',
                'is_featured'
            ])
            ->setVisibility($this->catalogProductVisibility->getVisibleInCatalogIds())
            ->addStoreFilter($this->_storeManager->getStore()->getId());

        // Apply category filter
        $categoryIds = $this->getCategoryIds();
        if ($categoryIds) {
            $collection->addCategoriesFilter(['in' => $this->sliderHelper->parseIds($categoryIds)]);
        }

        // Apply product ID filter (preserve order using FIELD())
        $productIds = $this->getProductIds();
        if ($productIds) {
            $ids = $this->sliderHelper->parseIds($productIds);
            $collection->addFieldToFilter('entity_id', ['in' => $ids]);
            $collection->getSelect()->order(
                new \Zend_Db_Expr('FIELD(e.entity_id,' . implode(',', array_map('intval', $ids)) . ')')
            );
        }

        // Apply SKU filter
        $productSkus = $this->getProductSkus();
        if ($productSkus) {
            $collection->addFieldToFilter('sku', ['in' => $this->sliderHelper->parseIds($productSkus)]);
        }

        // Apply sale products filter
        if ($this->getSaleProductsOnly()) {
            $collection->addAttributeToFilter('special_price', ['notnull' => true])
                ->addAttributeToFilter('special_price', ['gt' => 0]);
        }

        // Apply new products filter
        $newDays = $this->getNewProductsDays();
        if ($newDays) {
            $date = new \DateTime();
            $date->modify("-{$newDays} days");
            $collection->addFieldToFilter('created_at', ['gteq' => $date->format('Y-m-d H:i:s')]);
        }

        // Apply price range filter
        $priceFrom = $this->getPriceFrom();
        if ($priceFrom) {
            $collection->addAttributeToFilter('price', ['gteq' => $priceFrom]);
        }

        $priceTo = $this->getPriceTo();
        if ($priceTo) {
            $collection->addAttributeToFilter('price', ['lteq' => $priceTo]);
        }

        // Exclude out of stock
        if ($this->getExcludeOutOfStock()) {
            $collection->joinField(
                'stock_status',
                'cataloginventory_stock_status',
                'stock_status',
                'product_id=entity_id',
                ['stock_status' => \Magento\CatalogInventory\Model\Stock\Status::STATUS_IN_STOCK]
            );
        }

        // Apply sorting
        $sortBy = $this->getSortBy() ?: 'position';
        $sortDirection = $this->getSortDirection() ?: 'ASC';

        if ($sortBy === 'random') {
            $collection->getSelect()->order('RAND()');
        } else {
            $collection->addAttributeToSort($sortBy, $sortDirection);
        }

        // Apply page size limit
        $pageSize = (int)($this->getPageSize() ?: 8);
        $collection->setPageSize($pageSize);

        return $collection;
    }

    /**
     * Get slider configuration
     *
     * @return array
     */
    public function getSliderConfig(): array
    {
        $preset = $this->getStylePreset() ?: 'default';
        $presetConfig = $this->sliderHelper->getStylePreset($preset);

        return [
            'title' => $this->getTitle(),
            'description' => $this->getDescription() ?: '',
            'heading_tag' => $this->getHeadingTag() ?: 'h2',
            'show_heading' => $this->getShowHeading() !== '0',
            'show_pager' => $this->getShowPager() !== '0',
            'columns_mobile' => (int)($this->getColumnsMobile() ?: 1),
            'columns_tablet' => (int)($this->getColumnsTablet() ?: 2),
            'columns_desktop' => (int)($this->getColumnsDesktop() ?: 4),
            'card_shadow' => $this->getCardShadow() ?: $presetConfig['card_shadow'],
            'card_hover' => $this->getCardHoverEffect() ?: $presetConfig['card_hover'],
            'custom_css_class' => $this->getCustomCssClass() ?: '',
            'enable_autoplay' => $this->getEnableAutoplay() === '1',
            'autoplay_interval' => (int)($this->getAutoplayInterval() ?: 3000)
        ];
    }

    /**
     * Get slider CSS classes
     *
     * @return string
     */
    public function getSliderCssClasses(): string
    {
        $config = $this->getSliderConfig();
        $classes = [];

        // Add shadow class
        if ($config['card_shadow']) {
            $classes[] = $this->sliderHelper->getShadowClass($config['card_shadow']);
        }

        // Add hover effect class
        if ($config['card_hover']) {
            $classes[] = $this->sliderHelper->getHoverEffectClass($config['card_hover']);
        }

        // Add custom classes
        if ($config['custom_css_class']) {
            $classes[] = $config['custom_css_class'];
        }

        return implode(' ', $classes);
    }

    /**
     * Get column track classes
     *
     * @return string
     */
    public function getColumnTrackClasses(): string
    {
        $config = $this->getSliderConfig();
        return $this->sliderHelper->getColumnClasses(
            $config['columns_mobile'],
            $config['columns_tablet'],
            $config['columns_desktop']
        );
    }

    /**
     * Get product badges
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function getProductBadges($product): array
    {
        if ($this->getEnableBadges() !== '1') {
            return [];
        }

        $badgeTypes = $this->getBadgeTypes() ?: 'sale,new';
        $newDays = (int)($this->getNewProductsDays() ?: 30);

        return $this->badgeHelper->getBadges($product, $badgeTypes, $newDays);
    }

    /**
     * Get badge position class
     *
     * @return string
     */
    public function getBadgePositionClass(): string
    {
        $position = $this->getBadgePosition() ?: 'top-left';
        return $this->badgeHelper->getBadgePositionClass($position);
    }

    /**
     * Get unique slider ID
     *
     * @return string
     */
    public function getSliderId(): string
    {
        $widgetId = $this->getData('widget_id');
        return 'product-slider-' . ($widgetId ?: uniqid());
    }

    /**
     * Format price with currency symbol
     *
     * @param float $price
     * @return string
     */
    public function formatPrice($price): string
    {
        return $this->priceCurrency->format(
            $price,
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $this->_storeManager->getStore()
        );
    }

    /**
     * Get media base URL
     *
     * @return string
     */
    public function getMediaBaseUrl(): string
    {
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * Check if module is enabled
     *
     * @return bool
     */
    public function isModuleEnabled(): bool
    {
        return $this->sliderHelper->isEnabled();
    }

    /**
     * Get toHtml override to check if enabled
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->isModuleEnabled()) {
            return '';
        }

        $collection = $this->getProductCollection();
        if (!$collection || $collection->getSize() == 0) {
            return '';
        }

        return parent::_toHtml();
    }
}
