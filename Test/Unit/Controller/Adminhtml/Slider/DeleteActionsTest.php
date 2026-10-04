<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Test\Unit\Controller\Adminhtml\Slider;

use Magento\Ui\Component\MassAction\Filter;
use Panth\ProductSlider\Controller\Adminhtml\Slider\Delete;
use Panth\ProductSlider\Controller\Adminhtml\Slider\MassDelete;
use Panth\ProductSlider\Model\ResourceModel\Slider as SliderResource;
use Panth\ProductSlider\Model\ResourceModel\Slider\Collection;
use Panth\ProductSlider\Model\ResourceModel\Slider\CollectionFactory;
use Panth\ProductSlider\Model\SliderFactory;

class DeleteActionsTest extends ControllerTestCase
{
    private array $deleted = [];

    /**
     * Resource stub recording deletes; ids in $failIds throw.
     *
     * @param array|null $row
     * @param array $failIds
     * @return SliderResource
     */
    private function resource(?array $row = null, array $failIds = []): SliderResource
    {
        $this->deleted = [];
        $resource = $this->createStub(SliderResource::class);
        $resource->method('load')->willReturnCallback(function ($model) use ($row, $resource) {
            if ($row !== null) {
                $model->setData($row);
            }
            return $resource;
        });
        $resource->method('delete')->willReturnCallback(function ($model) use ($failIds, $resource) {
            if (in_array((int)$model->getId(), $failIds, true)) {
                throw new \RuntimeException('locked');
            }
            $this->deleted[] = (int)$model->getId();
            return $resource;
        });

        return $resource;
    }

    /**
     * @return SliderFactory
     */
    private function factory(): SliderFactory
    {
        $factory = $this->createStub(SliderFactory::class);
        $factory->method('create')->willReturnCallback(fn() => $this->slider());

        return $factory;
    }

    public function testDeleteWithoutIdIsRejected(): void
    {
        $controller = new Delete($this->context(), $this->factory(), $this->resource(['slider_id' => 1]));

        $controller->execute();

        $this->assertSame(["We can't find a slider to delete."], $this->errors);
        $this->assertSame([], $this->deleted);
        $this->assertSame(['*/*/', []], $this->redirect);
    }

    public function testDeleteOfUnknownSliderIsRejected(): void
    {
        $controller = new Delete($this->context(['slider_id' => '9']), $this->factory(), $this->resource());

        $controller->execute();

        $this->assertSame(['This slider no longer exists.'], $this->errors);
        $this->assertSame([], $this->deleted);
    }

    public function testDeleteRemovesTheSlider(): void
    {
        $controller = new Delete(
            $this->context(['slider_id' => '3']),
            $this->factory(),
            $this->resource(['slider_id' => 3])
        );

        $controller->execute();

        $this->assertSame([3], $this->deleted);
        $this->assertSame(['You deleted the slider.'], $this->success);
        $this->assertSame(['*/*/', []], $this->redirect);
    }

    public function testDeleteFailureIsReported(): void
    {
        $controller = new Delete(
            $this->context(['slider_id' => '3']),
            $this->factory(),
            $this->resource(['slider_id' => 3], [3])
        );

        $controller->execute();

        $this->assertSame(['locked'], $this->errors);
        $this->assertSame([], $this->success);
    }

    /**
     * @param array $ids
     * @param array $failIds
     * @return MassDelete
     */
    private function massDelete(array $ids, array $failIds = []): MassDelete
    {
        $context = $this->context();
        $sliders = array_map(fn($id) => $this->slider(['slider_id' => $id]), $ids);
        $collection = $this->createStub(Collection::class);
        $collection->method('getSize')->willReturn(count($sliders));
        $collection->method('getIterator')->willReturn(new \ArrayIterator($sliders));
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        $filter = $this->createStub(Filter::class);
        $filter->method('getCollection')->willReturnArgument(0);

        return new MassDelete($context, $filter, $factory, $this->resource(null, $failIds));
    }

    public function testMassDeleteRemovesEverySelectedSlider(): void
    {
        $this->massDelete([1, 2, 3])->execute();

        $this->assertSame([1, 2, 3], $this->deleted);
        $this->assertSame(['A total of 3 slider(s) have been deleted.'], $this->success);
        $this->assertSame(['*/*/', []], $this->redirect);
    }

    public function testMassDeleteReportsFailuresAndCountsOnlySuccesses(): void
    {
        $this->massDelete([1, 2], [2])->execute();

        $this->assertSame([1], $this->deleted);
        $this->assertSame(['Could not delete slider ID 2: locked'], $this->errors);
        $this->assertSame(['A total of 1 slider(s) have been deleted.'], $this->success);
    }

    public function testMassDeleteWithNothingDeletedAddsNoSuccessMessage(): void
    {
        $this->massDelete([])->execute();

        $this->assertSame([], $this->success);
        $this->assertSame(['*/*/', []], $this->redirect);
    }
}
