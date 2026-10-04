<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Ui\DataProvider;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Panth\ProductSlider\Helper\Data as SliderHelper;
use Panth\ProductSlider\Model\ResourceModel\Slider\CollectionFactory;

class SliderFormDataProvider extends AbstractDataProvider
{
    private ?array $loadedData = null;

    private DataPersistorInterface $dataPersistor;

    private SliderHelper $sliderHelper;

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = [],
        ?SliderHelper $sliderHelper = null
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->sliderHelper = $sliderHelper
            ?? \Magento\Framework\App\ObjectManager::getInstance()->get(SliderHelper::class);
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        if ($this->loadedData !== null) {
            return $this->loadedData;
        }

        $this->loadedData = [];
        $items = $this->collection->getItems();

        foreach ($items as $slider) {
            $sliderData = $slider->getData();
            $sliderData['store_ids'] = $this->splitStoreIds($sliderData['store_ids'] ?? '0');
            $this->loadedData[$slider->getSliderId()] = $sliderData;
        }

        $data = $this->dataPersistor->get('panth_productslider_slider');
        if (!empty($data)) {
            $slider = $this->collection->getNewEmptyItem();
            $slider->setData($data);
            $this->loadedData[$slider->getSliderId()] = $slider->getData();
            $this->dataPersistor->clear('panth_productslider_slider');
        }

        return $this->loadedData;
    }

    public function getMeta(): array
    {
        $meta = parent::getMeta();
        $fieldsets = [
            'columns_mobile' => 'layout',
            'columns_tablet' => 'layout',
            'columns_desktop' => 'layout',
            'page_size' => 'sorting',
        ];
        foreach ($this->sliderHelper->getDefaultLayout() as $field => $value) {
            $fieldset = $fieldsets[$field];
            $meta[$fieldset]['children'][$field]['arguments']['data']['config']['default'] = (string)$value;
        }

        return $meta;
    }

    private function splitStoreIds($value): array
    {
        if (is_array($value)) {
            return array_map('strval', $value);
        }
        $ids = array_filter(array_map('trim', explode(',', (string)$value)), 'strlen');

        return $ids ? array_values($ids) : ['0'];
    }
}
