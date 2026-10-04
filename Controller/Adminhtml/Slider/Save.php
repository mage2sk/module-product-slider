<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Controller\Adminhtml\Slider;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\AlreadyExistsException;
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

        if (isset($data['slider_id']) && $data['slider_id'] === '') {
            unset($data['slider_id']);
        }

        unset($data['form_key']);
        if (array_key_exists('store_ids', $data)) {
            $storeIds = is_array($data['store_ids']) ? $data['store_ids'] : explode(',', (string)$data['store_ids']);
            $storeIds = array_values(array_unique(array_map('intval', array_filter(
                array_map('trim', array_map('strval', $storeIds)),
                'strlen'
            ))));
            $data['store_ids'] = (!$storeIds || in_array(0, $storeIds, true)) ? '0' : implode(',', $storeIds);
        }

        foreach (['new_products_days', 'price_from', 'price_to'] as $nullableField) {
            if (array_key_exists($nullableField, $data) && trim((string)$data[$nullableField]) === '') {
                $data[$nullableField] = null;
            }
        }

        if (isset($data['identifier']) && is_string($data['identifier'])) {
            $data['identifier'] = trim($data['identifier']);
        }

        if (!empty($data['identifier'])
            && !preg_match('/^[A-Za-z][A-Za-z0-9_\/-]*$/', (string)$data['identifier'])
        ) {
            $this->messageManager->addErrorMessage(
                __('The identifier must start with a letter and can contain only letters, digits, "-", "_" and "/".')
            );
            $this->dataPersistor->set('panth_productslider_slider', $data);

            if (isset($data['slider_id'])) {
                return $resultRedirect->setPath('*/*/edit', ['slider_id' => $data['slider_id']]);
            }

            return $resultRedirect->setPath('*/*/new');
        }

        if (empty($data['identifier'])) {
            $this->messageManager->addErrorMessage(__('The identifier field is required.'));
            $this->dataPersistor->set('panth_productslider_slider', $data);

            if (isset($data['slider_id'])) {
                return $resultRedirect->setPath('*/*/edit', ['slider_id' => $data['slider_id']]);
            }

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
            $message = $e instanceof AlreadyExistsException
                ? (string)__('A slider with the identifier "%1" already exists.', (string)$data['identifier'])
                : $e->getMessage();
            $this->messageManager->addErrorMessage($message);
            $this->dataPersistor->set('panth_productslider_slider', $data);

            if ($model->getId()) {
                return $resultRedirect->setPath('*/*/edit', ['slider_id' => $model->getId()]);
            }

            return $resultRedirect->setPath('*/*/new');
        }
    }
}
