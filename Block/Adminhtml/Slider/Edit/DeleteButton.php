<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Block\Adminhtml\Slider\Edit;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    private Escaper $escaper;

    public function __construct(
        RequestInterface $request,
        UrlInterface $urlBuilder,
        Escaper $escaper
    ) {
        parent::__construct($request, $urlBuilder);
        $this->escaper = $escaper;
    }

    public function getButtonData(): array
    {
        if (!$this->getSliderId()) {
            return [];
        }

        return [
            'label' => __('Delete'),
            'class' => 'delete',
            'on_click' => 'deleteConfirm(\''
                . $this->escaper->escapeJs((string) __('Are you sure you want to delete this slider?'))
                . '\', \''
                . $this->getUrl('*/*/delete', ['slider_id' => $this->getSliderId()])
                . '\', {data: {}})',
            'sort_order' => 20,
        ];
    }
}
