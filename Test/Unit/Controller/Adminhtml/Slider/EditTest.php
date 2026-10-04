<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Test\Unit\Controller\Adminhtml\Slider;

use Magento\Backend\Model\View\Result\Forward;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Registry;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\PageFactory;
use Panth\ProductSlider\Controller\Adminhtml\Slider\Edit;
use Panth\ProductSlider\Controller\Adminhtml\Slider\NewAction;
use Panth\ProductSlider\Model\Slider;
use Panth\ProductSlider\Model\SliderFactory;

class EditTest extends ControllerTestCase
{
    private array $titles = [];

    private array $registered = [];

    private ?Page $page = null;

    /**
     * @param array $params
     * @param int|null $foundId
     * @param string $title
     * @return Edit
     */
    private function controller(array $params, ?int $foundId, string $title = ''): Edit
    {
        $this->titles = $this->registered = [];

        $model = new class ($foundId, $title) extends Slider {
            /** @var array */
            private array $row;

            /**
             * @param int|null $foundId
             * @param string $title
             */
            public function __construct(?int $foundId, string $title)
            {
                $this->_idFieldName = 'slider_id';
                $this->row = $foundId ? ['slider_id' => $foundId, 'title' => $title] : [];
            }

            /**
             * @inheritdoc
             */
            public function load($modelId, $field = null)
            {
                $this->setData($this->row);
                return $this;
            }
        };
        $factory = $this->createStub(SliderFactory::class);
        $factory->method('create')->willReturn($model);

        $titleObject = $this->createStub(Title::class);
        $titleObject->method('prepend')->willReturnCallback(function ($text) {
            $this->titles[] = (string)$text;
        });
        $config = $this->createStub(PageConfig::class);
        $config->method('getTitle')->willReturn($titleObject);
        $this->page = $this->createStub(Page::class);
        $this->page->method('getConfig')->willReturn($config);
        $pageFactory = $this->createStub(PageFactory::class);
        $pageFactory->method('create')->willReturn($this->page);

        $registry = $this->createStub(Registry::class);
        $registry->method('register')->willReturnCallback(function ($key, $value) {
            $this->registered[$key] = $value;
        });

        return new Edit($this->context($params), $pageFactory, $registry, $factory);
    }

    public function testUnknownSliderRedirectsToTheGrid(): void
    {
        $result = $this->controller(['slider_id' => '12'], null)->execute();

        $this->assertNotSame($this->page, $result);
        $this->assertSame(['This slider no longer exists.'], $this->errors);
        $this->assertSame(['*/*/', []], $this->redirect);
        $this->assertSame([], $this->registered);
    }

    public function testNewSliderPageIsTitledNewSlider(): void
    {
        $result = $this->controller([], null)->execute();

        $this->assertSame($this->page, $result);
        $this->assertArrayHasKey('panth_productslider_slider', $this->registered);
        $this->assertSame(['Product Sliders', 'New Slider'], $this->titles);
    }

    public function testExistingSliderPageShowsItsTitle(): void
    {
        $result = $this->controller(['slider_id' => '4'], 4, 'Summer')->execute();

        $this->assertSame($this->page, $result);
        $this->assertSame(['Product Sliders', 'Edit Slider: Summer'], $this->titles);
        $this->assertSame([], $this->errors);
    }

    public function testNewActionForwardsToEdit(): void
    {
        $forward = $this->createMock(Forward::class);
        $forward->expects($this->once())->method('forward')->with('edit')->willReturnSelf();
        $factory = $this->createStub(ForwardFactory::class);
        $factory->method('create')->willReturn($forward);

        $this->assertSame($forward, (new NewAction($this->context(), $factory))->execute());
    }
}
