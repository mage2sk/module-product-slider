<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Test\Unit\Model;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\System\Store as SystemStore;
use Panth\ProductSlider\Helper\Badge;
use Panth\ProductSlider\Helper\Data;
use Panth\ProductSlider\Model\Config\Source\ArrowPosition;
use Panth\ProductSlider\Model\Config\Source\BadgePosition;
use Panth\ProductSlider\Model\Config\Source\StoreViews;
use Panth\ProductSlider\Model\Config\Source\StylePreset;
use Panth\ProductSlider\Model\Slider;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use PHPUnit\Framework\TestCase;

class SliderAndSourcesTest extends TestCase
{
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

    public function testIdentitiesOfANewSliderAreTheGenericTag(): void
    {
        $this->assertSame([Slider::CACHE_TAG], $this->slider()->getIdentities());
    }

    public function testIdentitiesOfASavedSliderIncludeItsIdTag(): void
    {
        $this->assertSame(
            [Slider::CACHE_TAG, Slider::CACHE_TAG . '_12'],
            $this->slider(['slider_id' => 12])->getIdentities()
        );
    }

    public function testEveryBadgePositionOptionMapsToItsOwnClass(): void
    {
        $context = $this->createStub(Context::class);
        $badge = new Badge($context, $this->createStub(TimezoneInterface::class), new Data($context));

        $classes = [];
        foreach ((new BadgePosition())->toOptionArray() as $option) {
            $classes[] = $badge->getBadgePositionClass($option['value']);
        }

        $this->assertCount(4, $classes);
        $this->assertCount(4, array_unique($classes));
    }

    public function testEveryNamedStylePresetIsKnownToTheHelper(): void
    {
        $helper = new Data($this->createStub(Context::class));
        $default = $helper->getStylePreset(Data::PRESET_DEFAULT);
        $values = array_column((new StylePreset())->toOptionArray(), 'value');

        $this->assertSame(['default', 'modern', 'minimal', 'bold', 'custom'], $values);
        foreach (['modern', 'minimal', 'bold'] as $preset) {
            $this->assertNotSame($default, $helper->getStylePreset($preset), $preset);
        }
    }

    public function testArrowPositionsIncludeTheHelperDefault(): void
    {
        $values = array_column((new ArrowPosition())->toOptionArray(), 'value');

        $this->assertContains('outside', $values);
        $this->assertSame(['outside', 'center', 'top', 'bottom'], $values);
    }

    public function testStoreViewsDelegateToTheSystemStoreWithAllStoreViewsOption(): void
    {
        $systemStore = $this->createMock(SystemStore::class);
        $systemStore->expects($this->once())->method('getStoreValuesForForm')->with(false, true)
            ->willReturn([['value' => 0, 'label' => 'All Store Views']]);

        $this->assertSame(
            [['value' => 0, 'label' => 'All Store Views']],
            (new StoreViews($systemStore))->toOptionArray()
        );
    }
}
