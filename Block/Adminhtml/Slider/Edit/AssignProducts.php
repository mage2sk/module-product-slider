<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Block\Adminhtml\Slider\Edit;

use Magento\Backend\Block\Template;

class AssignProducts extends Template
{
    protected $_template = 'Panth_ProductSlider::slider/assign-products.phtml';

    public function getProductGridUrl(): string
    {
        return $this->getUrl('panth_productslider/slider/productsgrid');
    }

    public function getCategoryTreeUrl(): string
    {
        return $this->getUrl('panth_productslider/slider/categorytree');
    }

    public function getProductUrlSuffix(): string
    {
        return (string) $this->_scopeConfig->getValue(
            'catalog/seo/product_url_suffix',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getCategoryUrlSuffix(): string
    {
        return (string) $this->_scopeConfig->getValue(
            'catalog/seo/category_url_suffix',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
