<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Controller\Adminhtml\Slider;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Panth\ProductSlider\Model\SliderFactory;

class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_ProductSlider::slider_manage';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly Registry $coreRegistry,
        private readonly SliderFactory $sliderFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\View\Result\Page|\Magento\Framework\Controller\Result\Redirect
    {
        $id = (int) $this->getRequest()->getParam('slider_id');
        $model = $this->sliderFactory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This slider no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
        }

        $this->coreRegistry->register('panth_productslider_slider', $model);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Panth_ProductSlider::slider_manage');
        $resultPage->getConfig()->getTitle()->prepend(__('Product Sliders'));
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? __('Edit Slider: %1', $model->getTitle()) : __('New Slider')
        );

        return $resultPage;
    }
}
