<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Ui\DataProvider;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Panth\ProductSlider\Model\ResourceModel\Slider\CollectionFactory;

class SliderFormDataProvider extends AbstractDataProvider
{
    private ?array $loadedData = null;

    private DataPersistorInterface $dataPersistor;

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
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
            $this->loadedData[$slider->getSliderId()] = $slider->getData();
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
}
