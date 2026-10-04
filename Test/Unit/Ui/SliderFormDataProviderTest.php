<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Test\Unit\Ui;

use Magento\Framework\App\Request\DataPersistorInterface;
use Panth\ProductSlider\Helper\Data;
use Panth\ProductSlider\Model\ResourceModel\Slider\Collection;
use Panth\ProductSlider\Model\ResourceModel\Slider\CollectionFactory;
use Panth\ProductSlider\Model\Slider;
use PHPUnit\Framework\TestCase;

class SliderFormDataProviderTest extends TestCase
{
    private array $cleared = [];

    /**
     * @param array $data
     * @return Slider
     */
    private function slider(array $data = []): Slider
    {
        $slider = (new \ReflectionClass(Slider::class))->newInstanceWithoutConstructor();
        $slider->setIdFieldName('slider_id');
        $slider->setData($data);

        return $slider;
    }

    /**
     * @param array $rows
     * @param array|null $persisted
     * @param array $meta
     * @param array $layout
     * @return \Panth\ProductSlider\Ui\DataProvider\SliderFormDataProvider
     */
    private function provider(array $rows, ?array $persisted = null, array $meta = [], array $layout = [])
    {
        $this->cleared = [];
        $collection = $this->createStub(Collection::class);
        $collection->method('getItems')->willReturn(array_map(fn($row) => $this->slider($row), $rows));
        $collection->method('getNewEmptyItem')->willReturnCallback(fn() => $this->slider());
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $persistor = $this->createStub(DataPersistorInterface::class);
        $persistor->method('get')->willReturn($persisted);
        $persistor->method('clear')->willReturnCallback(function ($key) {
            $this->cleared[] = $key;
        });

        $helper = $this->createStub(Data::class);
        $helper->method('getDefaultLayout')->willReturn($layout);

        return new \Panth\ProductSlider\Ui\DataProvider\SliderFormDataProvider(
            'slider_form_data_source',
            'slider_id',
            'slider_id',
            $factory,
            $persistor,
            $meta,
            [],
            $helper
        );
    }

    public function testDataIsKeyedBySliderIdWithStoreIdsSplit(): void
    {
        $data = $this->provider([
            ['slider_id' => 3, 'title' => 'A', 'store_ids' => '1, 2'],
            ['slider_id' => 4, 'title' => 'B', 'store_ids' => ''],
            ['slider_id' => 5, 'title' => 'C'],
        ])->getData();

        $this->assertSame(['1', '2'], $data[3]['store_ids']);
        $this->assertSame(['0'], $data[4]['store_ids']);
        $this->assertSame(['0'], $data[5]['store_ids']);
        $this->assertSame('B', $data[4]['title']);
        $this->assertSame([], $this->cleared);
    }

    public function testArrayStoreIdsAreCastToStrings(): void
    {
        $data = $this->provider([['slider_id' => 3, 'store_ids' => [1, 4]]])->getData();

        $this->assertSame(['1', '4'], $data[3]['store_ids']);
    }

    public function testPersistedFormDataOverridesAndIsClearedOnce(): void
    {
        $provider = $this->provider(
            [['slider_id' => 3, 'title' => 'Saved', 'store_ids' => '0']],
            ['slider_id' => 3, 'title' => 'Unsaved edit']
        );

        $data = $provider->getData();
        $again = $provider->getData();

        $this->assertSame('Unsaved edit', $data[3]['title']);
        $this->assertSame($data, $again);
        $this->assertSame(['panth_productslider_slider'], $this->cleared);
    }

    public function testMetaInjectsConfiguredLayoutDefaultsIntoTheirFieldsets(): void
    {
        $meta = $this->provider(
            [],
            null,
            ['layout' => ['arguments' => ['data' => ['config' => ['label' => 'Layout']]]]],
            ['columns_mobile' => 2, 'columns_tablet' => 3, 'columns_desktop' => 5, 'page_size' => 10]
        )->getMeta();

        $field = static fn($fieldset, $name) => $meta[$fieldset]['children'][$name]['arguments']['data']['config'];
        $this->assertSame('Layout', $meta['layout']['arguments']['data']['config']['label']);
        $this->assertSame('2', $field('layout', 'columns_mobile')['default']);
        $this->assertSame('3', $field('layout', 'columns_tablet')['default']);
        $this->assertSame('5', $field('layout', 'columns_desktop')['default']);
        $this->assertSame('10', $field('sorting', 'page_size')['default']);
    }
}
