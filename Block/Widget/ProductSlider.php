<?php
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
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Framework\DataObject\IdentityInterface;

class ProductSlider extends Template implements BlockInterface, IdentityInterface
{
    private const MAX_PAGE_SIZE = 100;

    private const MAX_COLUMNS = 12;

    private const SORT_FIELDS = ['position', 'name', 'price', 'created_at', 'random'];

    private const HEADING_TAGS = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

    protected $_template = 'Panth_ProductSlider::luma/widget/slider.phtml';

    private CollectionFactory $productCollectionFactory;

    private Visibility $catalogProductVisibility;

    private StockStatusFactory $stockStatusFactory;

    private ProductSliderHelper $sliderHelper;

    private BadgeHelper $badgeHelper;

    private TimezoneInterface $timezone;

    private PriceCurrencyInterface $priceCurrency;

    private \Panth\Core\Helper\Theme $themeHelper;

    private ImageHelper $imageHelper;

    private ?\Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection = null;

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

    public function getProductImageUrl($product, string $imageId = 'category_page_grid', int $width = 300, int $height = 300): string
    {
        return $this->imageHelper->init($product, $imageId)
            ->resize($width, $height)
            ->getUrl();
    }

    public function getTemplate()
    {
        if ($this->themeHelper->isHyva()) {
            return 'Panth_ProductSlider::widget/slider.phtml';
        }
        return parent::getTemplate();
    }

    public function getProductCollection()
    {
        if ($this->productCollection !== null) {
            return $this->productCollection;
        }

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
            ->addAttributeToFilter('status', ProductStatus::STATUS_ENABLED)
            ->setVisibility($this->catalogProductVisibility->getVisibleInCatalogIds())
            ->addStoreFilter($this->_storeManager->getStore()->getId())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents();

        $categoryIds = $this->getCategoryIds();
        if ($categoryIds) {
            $categoryIdList = array_values(array_filter(array_map('intval', $this->sliderHelper->parseIds($categoryIds))));
            if ($categoryIdList && (string)$this->getIncludeChildCategories() === '1') {
                $categoryIdList = $this->addChildCategoryIds($collection, $categoryIdList);
            }
            $collection->addCategoriesFilter(['in' => $categoryIdList ?: [0]]);
        }

        $productIds = $this->getProductIds();
        if ($productIds) {
            $ids = array_values(array_filter(array_map('intval', $this->sliderHelper->parseIds($productIds))));
            if (!$ids) {
                $ids = [0];
            }
            $collection->addFieldToFilter('entity_id', ['in' => $ids]);
            $collection->getSelect()->order(
                new \Zend_Db_Expr('FIELD(e.entity_id,' . implode(',', $ids) . ')')
            );
        }

        $productSkus = $this->getProductSkus();
        if ($productSkus) {
            $collection->addFieldToFilter('sku', ['in' => $this->sliderHelper->parseIds($productSkus)]);
        }

        if ($this->getSaleProductsOnly()) {
            $today = $this->timezone->scopeDate($this->_storeManager->getStore())->format('Y-m-d');
            $collection->addAttributeToFilter('special_price', ['notnull' => true])
                ->addAttributeToFilter('special_price', ['gt' => 0])
                ->addAttributeToFilter(
                    'special_from_date',
                    [['null' => true], ['lteq' => $today . ' 23:59:59']],
                    'left'
                )
                ->addAttributeToFilter(
                    'special_to_date',
                    [['null' => true], ['gteq' => $today]],
                    'left'
                );
            $collection->getSelect()->where('price_index.final_price < price_index.price');
        }

        $newDays = (int)$this->getNewProductsDays();
        if ($newDays > 0) {
            $date = new \DateTime('now', new \DateTimeZone('UTC'));
            $date->modify("-{$newDays} days");
            $collection->addFieldToFilter('created_at', ['gteq' => $date->format('Y-m-d H:i:s')]);
        }

        $priceFrom = $this->getPriceFrom();
        if (is_numeric($priceFrom) && (float)$priceFrom > 0) {
            $collection->addAttributeToFilter('price', ['gteq' => (float)$priceFrom]);
        }

        $priceTo = $this->getPriceTo();
        if (is_numeric($priceTo) && (float)$priceTo > 0) {
            $collection->addAttributeToFilter('price', ['lteq' => (float)$priceTo]);
        }

        if ($this->getExcludeOutOfStock()) {
            $collection->getSelect()->joinLeft(
                ['_stock_status' => $collection->getTable('cataloginventory_stock_status')],
                'e.entity_id = _stock_status.product_id AND _stock_status.website_id = 0',
                []
            )->where(
                '_stock_status.stock_status = ?',
                \Magento\CatalogInventory\Model\Stock\Status::STATUS_IN_STOCK
            );
            $collection->getSelect()->distinct(true);
        }

        $sortBy = (string)$this->getSortBy();
        if (!in_array($sortBy, self::SORT_FIELDS, true)) {
            $sortBy = 'position';
        }
        $sortDirection = strtoupper((string)$this->getSortDirection()) === 'DESC' ? 'DESC' : 'ASC';

        if ($sortBy === 'random') {
            $collection->getSelect()->order(new \Zend_Db_Expr('RAND()'));
        } else {
            $collection->addAttributeToSort($sortBy, $sortDirection);
        }

        $pageSize = (int)($this->getPageSize() ?: 8);
        $pageSize = max(1, min(self::MAX_PAGE_SIZE, $pageSize));
        $collection->setPageSize($pageSize);

        return $this->productCollection = $collection;
    }

    private function addChildCategoryIds($collection, array $categoryIds): array
    {
        $connection = $collection->getConnection();
        $table = $collection->getTable('catalog_category_entity');
        $select = $connection->select()->from($table, ['entity_id']);
        foreach ($categoryIds as $categoryId) {
            $select->orWhere('path LIKE ?', '%/' . (int)$categoryId . '/%');
        }
        $children = array_map('intval', $connection->fetchCol($select));

        return array_values(array_unique(array_merge($categoryIds, $children)));
    }

    public function getSliderConfig(): array
    {
        $preset = (string)($this->getStylePreset() ?: 'default');
        $presetConfig = $this->sliderHelper->getStylePreset($preset);
        $usePreset = in_array(
            $preset,
            [ProductSliderHelper::PRESET_MODERN, ProductSliderHelper::PRESET_MINIMAL, ProductSliderHelper::PRESET_BOLD],
            true
        );

        $headingTag = strtolower((string)$this->getHeadingTag());
        if (!in_array($headingTag, self::HEADING_TAGS, true)) {
            $headingTag = 'h2';
        }

        return [
            'title' => $this->getTitle(),
            'description' => $this->getDescription() ?: '',
            'heading_tag' => $headingTag,
            'show_heading' => (string)$this->getShowHeading() !== '0',
            'show_pager' => (string)$this->getShowPager() !== '0',
            'columns_mobile' => $this->clampColumns($this->getColumnsMobile(), 1),
            'columns_tablet' => $this->clampColumns($this->getColumnsTablet(), 2),
            'columns_desktop' => $this->clampColumns($this->getColumnsDesktop(), 4),
            'card_shadow' => $usePreset ? $presetConfig['card_shadow'] : ($this->getCardShadow() ?: $presetConfig['card_shadow']),
            'card_hover' => $usePreset
                ? $presetConfig['card_hover']
                : ($this->getCardHoverEffect() ?: $presetConfig['card_hover']),
            'style_preset' => $preset,
            'card_radius' => $presetConfig['card_radius'],
            'title_size' => $presetConfig['title_size'],
            'apply_card_settings' => $this->sliderHelper->isApplyCardSettings(),
            'hide_details' => (string)$this->getHideDetails() === '1',
            'hide_rating' => (string)$this->getHideRating() === '1',
            'show_add_to_cart' => (string)$this->getShowAddToCart() !== '0',
            'custom_css_class' => $this->sanitizeCssClasses((string)$this->getCustomCssClass()),
            'enable_autoplay' => (string)$this->getEnableAutoplay() === '1',
            'autoplay_interval' => max(1000, (int)($this->getAutoplayInterval() ?: 3000))
        ];
    }

    public function getDescriptionHtml(): string
    {
        $description = (string)($this->getDescription() ?: '');
        if ($description === '') {
            return '';
        }

        return (string)$this->_escaper->escapeHtml(
            $description,
            ['a', 'b', 'strong', 'em', 'i', 'u', 'br', 'p', 'span', 'small', 'div']
        );
    }

    public function getIdentities()
    {
        $identities = [Product::CACHE_TAG];
        try {
            foreach ($this->getProductCollection()->getItems() as $product) {
                $identities[] = Product::CACHE_TAG . '_' . $product->getId();
            }
        } catch (\Throwable) {
            return $identities;
        }

        return $identities;
    }

    private function clampColumns($value, int $default): int
    {
        $columns = (int)($value ?: $default);

        return max(1, min(self::MAX_COLUMNS, $columns));
    }

    private function sanitizeCssClasses(string $classes): string
    {
        $classes = preg_replace('/[^A-Za-z0-9_\- ]/', '', $classes) ?? '';

        return trim((string)preg_replace('/\s+/', ' ', $classes));
    }

    public function getSliderCssClasses(): string
    {
        $config = $this->getSliderConfig();
        if (!$config['apply_card_settings']) {
            return '';
        }

        return trim(
            $this->sliderHelper->getShadowClass((string)$config['card_shadow']) . ' '
            . $this->sliderHelper->getHoverEffectClass((string)$config['card_hover'])
        );
    }

    public function getCardStyleCss(string $selector): string
    {
        $config = $this->getSliderConfig();
        if (!$config['apply_card_settings']) {
            return '';
        }
        $radius = preg_match('/^\d+(\.\d+)?(rem|px)?$/', (string)$config['card_radius'])
            ? $config['card_radius'] : '0.75rem';
        $titleSize = preg_match('/^\d+(\.\d+)?(rem|px)$/', (string)$config['title_size'])
            ? $config['title_size'] : '1rem';

        return $selector . ' { --panth-ps-radius: ' . $this->toPixels((string)$radius)
            . '; --panth-ps-title-size: ' . $this->toPixels((string)$titleSize) . '; }';
    }

    private function toPixels(string $value): string
    {
        if (substr($value, -3) === 'rem') {
            return rtrim(rtrim(number_format((float)$value * 16, 2, '.', ''), '0'), '.') . 'px';
        }
        if ($value === '0' || substr($value, -2) === 'px') {
            return $value;
        }

        return $value . 'px';
    }

    public function getColumnTrackClasses(): string
    {
        $config = $this->getSliderConfig();
        return $this->sliderHelper->getColumnClasses(
            $config['columns_mobile'],
            $config['columns_tablet'],
            $config['columns_desktop']
        );
    }

    public function getCardStyleSheet(string $scope): string
    {
        return $this->sliderHelper->getCardStyleSheet($scope);
    }

    public function getProductBadges($product): array
    {
        if ((string)$this->getEnableBadges() !== '1' || !$this->sliderHelper->isApplyCardSettings()) {
            return [];
        }

        $badgeTypes = $this->getBadgeTypes() ?: 'sale,new';
        $newDays = (int)($this->getNewProductsDays() ?: 30);

        return $this->badgeHelper->getBadges($product, $badgeTypes, $newDays);
    }

    public function getBadgePositionClass(): string
    {
        $position = $this->getBadgePosition() ?: 'top-left';
        return $this->badgeHelper->getBadgePositionClass($position);
    }

    public function getSliderId(): string
    {
        $widgetId = $this->getData('widget_id');
        return 'product-slider-' . $this->sanitizeDomId((string)($widgetId ?: uniqid()));
    }

    protected function sanitizeDomId(string $value): string
    {
        $value = (string)preg_replace('/[^A-Za-z0-9_-]/', '-', $value);

        return $value !== '' ? $value : uniqid();
    }

    public function formatPrice($price): string
    {
        return $this->priceCurrency->format(
            $price,
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $this->_storeManager->getStore()
        );
    }

    public function getMediaBaseUrl(): string
    {
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    public function isModuleEnabled(): bool
    {
        return $this->sliderHelper->isEnabled();
    }

    protected function _toHtml()
    {
        if (!$this->isModuleEnabled()) {
            return '';
        }

        try {
            $items = $this->getProductCollection()->getItems();
        } catch (\Throwable) {
            return '';
        }
        if (empty($items)) {
            return '';
        }

        return parent::_toHtml();
    }
}
