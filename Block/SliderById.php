<?php
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
    private SliderFactory $sliderFactory;

    private SliderResource $sliderResource;

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

    protected function _beforeToHtml(): static
    {
        $identifier = $this->getData('identifier');

        if ($identifier) {
            if (!$this->getNameInLayout()) {
                $this->setNameInLayout('product_slider_' . $identifier . '_' . uniqid());
            }

            $slider = $this->sliderFactory->create();
            $this->sliderResource->load($slider, $identifier, 'identifier');

            if ($slider->getId() && $slider->getData('is_active')) {
                $this->addData($slider->getData());
            } else {
                return $this;
            }
        }

        return parent::_beforeToHtml();
    }

    public function getSliderId(): string
    {
        $identifier = $this->getIdentifier();

        return 'product-slider-' . ($identifier ?: uniqid());
    }
}
