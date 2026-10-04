<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Controller\Adminhtml\Slider;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;

class NewAction extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_ProductSlider::slider_manage';

    public function __construct(
        Context $context,
        private readonly ForwardFactory $resultForwardFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Backend\Model\View\Result\Forward
    {
        $resultForward = $this->resultForwardFactory->create();

        return $resultForward->forward('edit');
    }
}
