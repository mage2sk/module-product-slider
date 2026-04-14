<?php
/**
 * Panth ProductSlider Block - Load slider entity by identifier
 *
 * Extends the widget block to load configuration from a CRUD-managed
 * Slider entity instead of widget instance parameters.
 *
 * @package Panth_ProductSlider
 * @author Panth
 */
declare(strict_types=1);

namespace Panth\ProductSlider\Block;

use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Model\ResourceModel\Stock\StatusFactory as StockStatusFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template\Context;
use Panth\ProductSlider\Block\Widget\ProductSlider;
use Panth\ProductSlider\Helper\Badge as BadgeHelper;
use Panth\ProductSlider\Helper\Data as ProductSliderHelper;
use Panth\ProductSlider\Model\ResourceModel\Slider as SliderResource;
use Panth\ProductSlider\Model\Slider;
use Panth\ProductSlider\Model\SliderFactory;

class SliderById extends ProductSlider
{
    /**
     * @var SliderFactory
     */
    private SliderFactory $sliderFactory;

    /**
     * @var SliderResource
     */
    private SliderResource $sliderResource;

    /**
     * @param Context $context
     * @param CollectionFactory $productCollectionFactory
     * @param Visibility $catalogProductVisibility
     * @param StockStatusFactory $stockStatusFactory
     * @param ProductSliderHelper $sliderHelper
     * @param BadgeHelper $badgeHelper
     * @param TimezoneInterface $timezone
     * @param PriceCurrencyInterface $priceCurrency
     * @param SliderFactory $sliderFactory
     * @param SliderResource $sliderResource
     * @param \Panth\Core\Helper\Theme $themeHelper
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
        SliderFactory $sliderFactory,
        SliderResource $sliderResource,
        \Panth\Core\Helper\Theme $themeHelper,
        \Magento\Catalog\Helper\Image $imageHelper,
        array $data = []
    ) {
        $this->sliderFactory = $sliderFactory;
        $this->sliderResource = $sliderResource;
        parent::__construct(
            $context,
            $productCollectionFactory,
            $catalogProductVisibility,
            $stockStatusFactory,
            $sliderHelper,
            $badgeHelper,
            $timezone,
            $priceCurrency,
            $themeHelper,
            $imageHelper,
            $data
        );
    }

    /**
     * Load slider entity by identifier and inject its data before rendering
     *
     * @return $this
     */
    protected function _beforeToHtml(): static
    {
        $identifier = $this->getData('identifier');

        if ($identifier) {
            // Ensure the block has a name in layout (required for Hyva ProductListItem)
            if (!$this->getNameInLayout()) {
                $this->setNameInLayout('product_slider_' . $identifier . '_' . uniqid());
            }

            /** @var Slider $slider */
            $slider = $this->sliderFactory->create();
            $this->sliderResource->load($slider, $identifier, 'identifier');

            if ($slider->getId() && $slider->getData('is_active')) {
                $this->addData($slider->getData());
            } else {
                // Slider not found or not active — return empty
                return $this;
            }
        }

        return parent::_beforeToHtml();
    }

    /**
     * Get unique slider ID based on the entity identifier
     *
     * @return string
     */
    public function getSliderId(): string
    {
        $identifier = $this->getIdentifier();

        return 'product-slider-' . ($identifier ?: uniqid());
    }
}
