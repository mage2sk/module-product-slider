<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Controller\Adminhtml\Slider;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;

class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_ProductSlider::slider_manage';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\View\Result\Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Panth_ProductSlider::slider_manage');
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Product Sliders'));

        return $resultPage;
    }
}
