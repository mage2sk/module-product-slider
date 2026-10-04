<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Test\Unit\Controller\Adminhtml\Slider;

use Magento\Framework\App\Request\DataPersistorInterface;
use Panth\ProductSlider\Controller\Adminhtml\Slider\Save;
use Panth\ProductSlider\Model\ResourceModel\Slider as SliderResource;
use Panth\ProductSlider\Model\Slider;
use Panth\ProductSlider\Model\SliderFactory;

class SaveTest extends ControllerTestCase
{
    private array $persisted = [];

    private bool $cleared = false;

    private ?Slider $model = null;

    /** @var array|null Row the resource "finds" on load; null means not found. */
    private ?array $existing = null;

    private ?\Exception $loadError = null;

    private ?\Exception $saveError = null;

    private ?array $saved = null;

    /**
     * @param array|null $post
     * @param array $params
     * @return Save
     */
    private function controller(?array $post, array $params = []): Save
    {
        $this->persisted = [];
        $this->cleared = false;
        $this->saved = null;
        $this->model = $this->slider();

        $factory = $this->createStub(SliderFactory::class);
        $factory->method('create')->willReturn($this->model);

        $resource = $this->createStub(SliderResource::class);
        $resource->method('load')->willReturnCallback(function ($model) use ($resource) {
            if ($this->loadError !== null) {
                throw $this->loadError;
            }
            if ($this->existing !== null) {
                $model->setData($this->existing);
            }
            return $resource;
        });
        $resource->method('save')->willReturnCallback(function ($model) use ($resource) {
            if ($this->saveError !== null) {
                throw $this->saveError;
            }
            $this->saved = $model->getData();
            if (!$model->getId()) {
                $model->setId(42);
            }
            return $resource;
        });

        $persistor = $this->createStub(DataPersistorInterface::class);
        $persistor->method('set')->willReturnCallback(function ($key, $value) {
            $this->persisted[$key] = $value;
        });
        $persistor->method('clear')->willReturnCallback(function () {
            $this->cleared = true;
        });

        return new Save($this->context($params, $post), $factory, $resource, $persistor);
    }

    public function testEmptyPostRedirectsToTheGrid(): void
    {
        $this->controller(null)->execute();

        $this->assertSame(['*/*/', []], $this->redirect);
        $this->assertNull($this->saved);
    }

    public function testMissingIdentifierIsRejectedAndPersisted(): void
    {
        $this->controller(['title' => 'X', 'form_key' => 'abc'])->execute();

        $this->assertSame(['*/*/new', []], $this->redirect);
        $this->assertSame(['The identifier field is required.'], $this->errors);
        $this->assertSame(['title' => 'X'], $this->persisted['panth_productslider_slider']);
        $this->assertNull($this->saved);
    }

    public function testMissingIdentifierOnExistingSliderReturnsToEdit(): void
    {
        $this->controller(['title' => 'X', 'slider_id' => '7'])->execute();

        $this->assertSame(['*/*/edit', ['slider_id' => '7']], $this->redirect);
        $this->assertSame(['The identifier field is required.'], $this->errors);
        $this->assertSame(['title' => 'X', 'slider_id' => '7'], $this->persisted['panth_productslider_slider']);
        $this->assertNull($this->saved);
    }

    public function testIdentifierWithInvalidCharactersIsRejectedOnNewSlider(): void
    {
        $this->controller(['title' => 'X', 'identifier' => 'my "slider"', 'slider_id' => ''])->execute();

        $this->assertSame(['*/*/new', []], $this->redirect);
        $this->assertSame(['The identifier must start with a letter and can contain only letters, digits, "-", "_" and "/".'], $this->errors);
        $this->assertArrayHasKey('panth_productslider_slider', $this->persisted);
        $this->assertNull($this->saved);
    }

    public function testIdentifierWithInvalidCharactersOnExistingSliderReturnsToEdit(): void
    {
        $this->controller(['title' => 'X', 'identifier' => 'a b', 'slider_id' => '7'])->execute();

        $this->assertSame(['*/*/edit', ['slider_id' => '7']], $this->redirect);
        $this->assertNull($this->saved);
    }

    public function testIdentifierIsTrimmedBeforeSaving(): void
    {
        $this->controller(['title' => 'X', 'identifier' => '  home_featured-2  ', 'slider_id' => ''])->execute();

        $this->assertSame([], $this->errors);
        $this->assertNotNull($this->saved);
        $this->assertSame('home_featured-2', $this->saved['identifier']);
    }

    public function testEmptyOptionalNumbersAreSavedAsNull(): void
    {
        $this->controller([
            'title' => 'X',
            'identifier' => 'home',
            'slider_id' => '',
            'new_products_days' => '',
            'price_from' => ' ',
            'price_to' => '25',
        ])->execute();

        $this->assertNull($this->saved['new_products_days']);
        $this->assertNull($this->saved['price_from']);
        $this->assertSame('25', $this->saved['price_to']);
    }

    public function testMissingTitleOnExistingSliderReturnsToEdit(): void
    {
        $this->controller(['identifier' => 'home', 'slider_id' => '7'])->execute();

        $this->assertSame(['*/*/edit', ['slider_id' => '7']], $this->redirect);
        $this->assertSame(['The title field is required.'], $this->errors);
        $this->assertArrayHasKey('panth_productslider_slider', $this->persisted);
    }

    public function testMissingTitleOnNewSliderReturnsToNew(): void
    {
        $this->controller(['identifier' => 'home', 'slider_id' => ''])->execute();

        $this->assertSame(['*/*/new', []], $this->redirect);
        $this->assertArrayNotHasKey('slider_id', $this->persisted['panth_productslider_slider']);
    }

    public function testNewSliderIsSavedWithoutEmptyIdAndFormKey(): void
    {
        $this->controller(['identifier' => 'home', 'title' => 'Home', 'slider_id' => '', 'form_key' => 'k'])
            ->execute();

        $this->assertSame(['identifier' => 'home', 'title' => 'Home'], $this->saved);
        $this->assertSame(['You saved the slider.'], $this->success);
        $this->assertTrue($this->cleared);
        $this->assertSame(['*/*/', []], $this->redirect);
    }

    public function testBackParamReturnsToTheSavedSlider(): void
    {
        $this->controller(['identifier' => 'home', 'title' => 'Home'], ['back' => 'edit'])->execute();

        $this->assertSame(['*/*/edit', ['slider_id' => 42]], $this->redirect);
    }

    public function testStoreIdsArrayIsDeduplicatedAndJoined(): void
    {
        $this->controller(['identifier' => 'a', 'title' => 'A', 'store_ids' => ['2', ' 1', '2', '']])->execute();

        $this->assertSame('2,1', $this->saved['store_ids']);
    }

    public function testStoreIdsStringIsNormalised(): void
    {
        $this->controller(['identifier' => 'a', 'title' => 'A', 'store_ids' => '3, 4,,3'])->execute();

        $this->assertSame('3,4', $this->saved['store_ids']);
    }

    public function testAllStoresWinsOverSpecificStores(): void
    {
        $this->controller(['identifier' => 'a', 'title' => 'A', 'store_ids' => ['1', '0', '2']])->execute();

        $this->assertSame('0', $this->saved['store_ids']);
    }

    public function testEmptyStoreSelectionMeansAllStores(): void
    {
        $this->controller(['identifier' => 'a', 'title' => 'A', 'store_ids' => ''])->execute();

        $this->assertSame('0', $this->saved['store_ids']);
    }

    public function testUnknownExistingSliderIsRejected(): void
    {
        $this->controller(['identifier' => 'a', 'title' => 'A', 'slider_id' => '99'])->execute();

        $this->assertSame(['This slider no longer exists.'], $this->errors);
        $this->assertSame(['*/*/', []], $this->redirect);
        $this->assertNull($this->saved);
    }

    public function testLoadFailureIsReportedAsMissingSlider(): void
    {
        $this->loadError = new \RuntimeException('db');

        $this->controller(['identifier' => 'a', 'title' => 'A', 'slider_id' => '5'])->execute();

        $this->assertSame(['This slider no longer exists.'], $this->errors);
        $this->assertNull($this->saved);
    }

    public function testExistingSliderIsUpdated(): void
    {
        $this->existing = ['slider_id' => 5, 'identifier' => 'old', 'title' => 'Old'];

        $this->controller(['identifier' => 'new', 'title' => 'New', 'slider_id' => '5'])->execute();

        $this->assertSame(['identifier' => 'new', 'title' => 'New', 'slider_id' => '5'], $this->saved);
        $this->assertSame(['*/*/', []], $this->redirect);
    }

    public function testSaveFailureOnNewSliderPersistsDataAndReturnsToNew(): void
    {
        $this->saveError = new \RuntimeException('Duplicate identifier');

        $this->controller(['identifier' => 'a', 'title' => 'A'])->execute();

        $this->assertSame(['Duplicate identifier'], $this->errors);
        $this->assertSame(['identifier' => 'a', 'title' => 'A'], $this->persisted['panth_productslider_slider']);
        $this->assertSame(['*/*/new', []], $this->redirect);
        $this->assertFalse($this->cleared);
    }

    public function testDuplicateIdentifierShowsAReadableMessage(): void
    {
        $this->saveError = new \Magento\Framework\Exception\AlreadyExistsException(
            __('Unique constraint violation found')
        );

        $this->controller(['identifier' => 'home', 'title' => 'A'])->execute();

        $this->assertSame(['A slider with the identifier "home" already exists.'], $this->errors);
        $this->assertSame(['*/*/new', []], $this->redirect);
        $this->assertArrayHasKey('panth_productslider_slider', $this->persisted);
    }

    public function testSaveFailureOnExistingSliderReturnsToEdit(): void
    {
        $this->existing = ['slider_id' => 5];
        $this->saveError = new \RuntimeException('Nope');

        $this->controller(['identifier' => 'a', 'title' => 'A', 'slider_id' => '5'])->execute();

        $this->assertSame(['*/*/edit', ['slider_id' => '5']], $this->redirect);
    }
}
