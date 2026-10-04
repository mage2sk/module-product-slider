<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Controller\Adminhtml\Slider;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Ui\Component\MassAction\Filter;
use Panth\ProductSlider\Model\ResourceModel\Slider\CollectionFactory;
use Panth\ProductSlider\Model\ResourceModel\Slider as SliderResource;

class MassDelete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_ProductSlider::slider_manage';

    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly SliderResource $sliderResource
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\Result\Redirect
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionSize = $collection->getSize();
        $deletedCount = 0;

        foreach ($collection as $slider) {
            try {
                $this->sliderResource->delete($slider);
                $deletedCount++;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Could not delete slider ID %1: %2', $slider->getId(), $e->getMessage())
                );
            }
        }

        if ($deletedCount) {
            $this->messageManager->addSuccessMessage(
                __('A total of %1 slider(s) have been deleted.', $deletedCount)
            );
        }

        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setPath('*/*/');
    }
}
