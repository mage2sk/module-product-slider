<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Controller\Adminhtml\Slider;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Panth\ProductSlider\Model\SliderFactory;
use Panth\ProductSlider\Model\ResourceModel\Slider as SliderResource;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_ProductSlider::slider_manage';

    public function __construct(
        Context $context,
        private readonly SliderFactory $sliderFactory,
        private readonly SliderResource $sliderResource,
        private readonly DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\Result\Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        // Clean empty slider_id
        if (isset($data['slider_id']) && $data['slider_id'] === '') {
            unset($data['slider_id']);
        }

        // Remove form_key
        unset($data['form_key']);

        // Validate required fields
        if (empty($data['identifier'])) {
            $this->messageManager->addErrorMessage(__('The identifier field is required.'));
            $this->dataPersistor->set('panth_productslider_slider', $data);

            return $resultRedirect->setPath('*/*/new');
        }

        if (empty($data['title'])) {
            $this->messageManager->addErrorMessage(__('The title field is required.'));
            $this->dataPersistor->set('panth_productslider_slider', $data);

            if (isset($data['slider_id'])) {
                return $resultRedirect->setPath('*/*/edit', ['slider_id' => $data['slider_id']]);
            }

            return $resultRedirect->setPath('*/*/new');
        }

        $model = $this->sliderFactory->create();

        if (!empty($data['slider_id'])) {
            try {
                $this->sliderResource->load($model, (int) $data['slider_id']);
                if (!$model->getId()) {
                    $this->messageManager->addErrorMessage(__('This slider no longer exists.'));

                    return $resultRedirect->setPath('*/*/');
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('This slider no longer exists.'));

                return $resultRedirect->setPath('*/*/');
            }
        }

        $model->setData($data);

        try {
            $this->sliderResource->save($model);
            $this->messageManager->addSuccessMessage(__('You saved the slider.'));
            $this->dataPersistor->clear('panth_productslider_slider');

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['slider_id' => $model->getId()]);
            }

            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->dataPersistor->set('panth_productslider_slider', $data);

            if ($model->getId()) {
                return $resultRedirect->setPath('*/*/edit', ['slider_id' => $model->getId()]);
            }

            return $resultRedirect->setPath('*/*/new');
        }
    }
}
