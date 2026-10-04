<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Test\Unit\Ui;

use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Panth\ProductSlider\Ui\Component\Listing\Column\Actions;
use PHPUnit\Framework\TestCase;

class ActionsColumnTest extends TestCase
{
    /**
     * @return Actions
     */
    private function column(): Actions
    {
        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(
            static fn($route, $params = []) => 'https://admin.test/' . $route . '?' . http_build_query($params)
        );
        $escaper = $this->createStub(Escaper::class);
        $escaper->method('escapeHtml')->willReturnCallback(
            static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES)
        );

        return new Actions(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $url,
            $escaper,
            [],
            ['name' => 'actions']
        );
    }

    public function testRowsGetEditAndPostDeleteLinks(): void
    {
        $items = $this->column()->prepareDataSource([
            'data' => ['items' => [['slider_id' => 7, 'title' => 'Summer']]],
        ])['data']['items'];

        $actions = $items[0]['actions'];
        $this->assertSame('https://admin.test/panth_productslider/slider/edit?slider_id=7', $actions['edit']['href']);
        $this->assertSame(
            'https://admin.test/panth_productslider/slider/delete?slider_id=7',
            $actions['delete']['href']
        );
        $this->assertTrue($actions['delete']['post']);
        $this->assertSame('Delete "Summer"', (string)$actions['delete']['confirm']['title']);
    }

    public function testTitleIsEscapedInTheConfirmation(): void
    {
        $items = $this->column()->prepareDataSource([
            'data' => ['items' => [['slider_id' => 1, 'title' => '<img onerror=x>']]],
        ])['data']['items'];

        $message = (string)$items[0]['actions']['delete']['confirm']['message'];
        $this->assertStringNotContainsString('<img', $message);
        $this->assertStringContainsString('&lt;img onerror=x&gt;', $message);
    }

    public function testRowsWithoutIdAreLeftAlone(): void
    {
        $source = ['data' => ['items' => [['title' => 'No id']]]];

        $this->assertSame($source, $this->column()->prepareDataSource($source));
        $this->assertSame(['data' => []], $this->column()->prepareDataSource(['data' => []]));
    }
}
