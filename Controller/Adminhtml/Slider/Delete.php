<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Controller\Adminhtml\Slider;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Panth\ProductSlider\Model\SliderFactory;
use Panth\ProductSlider\Model\ResourceModel\Slider as SliderResource;

class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_ProductSlider::slider_manage';

    public function __construct(
        Context $context,
        private readonly SliderFactory $sliderFactory,
        private readonly SliderResource $sliderResource
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\Result\Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int) $this->getRequest()->getParam('slider_id');

        if (!$id) {
            $this->messageManager->addErrorMessage(__('We can\'t find a slider to delete.'));

            return $resultRedirect->setPath('*/*/');
        }

        try {
            $model = $this->sliderFactory->create();
            $this->sliderResource->load($model, $id);

            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This slider no longer exists.'));

                return $resultRedirect->setPath('*/*/');
            }

            $this->sliderResource->delete($model);
            $this->messageManager->addSuccessMessage(__('You deleted the slider.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}
