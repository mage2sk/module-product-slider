<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Test\Unit\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;
use Panth\ProductSlider\Block\Adminhtml\Slider\Edit\AssignProducts;
use Panth\ProductSlider\Block\Adminhtml\Slider\Edit\BackButton;
use Panth\ProductSlider\Block\Adminhtml\Slider\Edit\DeleteButton;
use Panth\ProductSlider\Block\Adminhtml\Slider\Edit\GenericButton;
use Panth\ProductSlider\Block\Adminhtml\Slider\Edit\UsageGuide;
use Magento\Framework\Escaper;
use Magento\Framework\Phrase;
use Magento\Framework\Phrase\RendererInterface;
use PHPUnit\Framework\TestCase;

class AdminBlocksTest extends TestCase
{
    /**
     * The backend Template constructor pulls optional helpers from the static object manager.
     */
    protected function setUp(): void
    {
        ObjectManager::setInstance($this->createStub(ObjectManagerInterface::class));
    }

    protected function tearDown(): void
    {
        $instance = new \ReflectionProperty(ObjectManager::class, '_instance');
        $instance->setValue(null, null);
    }

    /**
     * @return UrlInterface
     */
    private function urlBuilder(): UrlInterface
    {
        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(
            static fn($route = null, $params = []) => 'https://admin.test/' . $route
                . ($params ? '?' . http_build_query($params) : '')
        );

        return $url;
    }

    /**
     * @param mixed $sliderId
     * @return RequestInterface
     */
    private function request($sliderId): RequestInterface
    {
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(
            static fn($key) => $key === 'slider_id' ? $sliderId : null
        );

        return $request;
    }

    public function testGenericButtonCastsTheSliderId(): void
    {
        $this->assertSame(5, (new GenericButton($this->request('5'), $this->urlBuilder()))->getSliderId());
        $this->assertNull((new GenericButton($this->request(''), $this->urlBuilder()))->getSliderId());
        $this->assertSame(
            'https://admin.test/x/y?a=1',
            (new GenericButton($this->request(null), $this->urlBuilder()))->getUrl('x/y', ['a' => 1])
        );
    }

    public function testDeleteButtonIsHiddenForNewSliders(): void
    {
        $this->assertSame(
            [],
            (new DeleteButton($this->request(null), $this->urlBuilder(), new Escaper()))->getButtonData()
        );
    }

    public function testDeleteButtonConfirmsAndTargetsTheSlider(): void
    {
        $data = (new DeleteButton($this->request('9'), $this->urlBuilder(), new Escaper()))->getButtonData();

        $this->assertSame('delete', $data['class']);
        $this->assertStringContainsString('https://admin.test/*/*/delete?slider_id=9', $data['on_click']);
        $this->assertStringStartsWith(
            "deleteConfirm('" . (new Escaper())->escapeJs('Are you sure you want to delete this slider?') . "'",
            $data['on_click']
        );
    }

    public function testDeleteButtonEscapesTranslatedTextForJavascript(): void
    {
        $renderer = $this->createStub(RendererInterface::class);
        $renderer->method('render')->willReturn("Supprimer l'element ?");
        $previous = Phrase::getRenderer();
        Phrase::setRenderer($renderer);
        try {
            $data = (new DeleteButton($this->request('9'), $this->urlBuilder(), new Escaper()))->getButtonData();
        } finally {
            Phrase::setRenderer($previous);
        }

        $this->assertStringNotContainsString("l'element", $data['on_click']);
        $this->assertStringStartsWith(
            "deleteConfirm('" . (new Escaper())->escapeJs("Supprimer l'element ?") . "', '",
            $data['on_click']
        );
        $this->assertSame(4, substr_count($data['on_click'], "'"));
    }

    public function testBackButtonGoesToTheGrid(): void
    {
        $data = (new BackButton($this->request(null), $this->urlBuilder()))->getButtonData();

        $this->assertSame("location.href = 'https://admin.test/*/*/';", $data['on_click']);
        $this->assertSame(10, $data['sort_order']);
    }

    /**
     * @param array $config
     * @return Context
     */
    private function backendContext(array $config = []): Context
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(static fn($path) => $config[$path] ?? null);
        $context = $this->createStub(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);
        $context->method('getUrlBuilder')->willReturn($this->urlBuilder());

        return $context;
    }

    public function testAssignProductsExposesEndpointsAndSuffixes(): void
    {
        $block = new AssignProducts($this->backendContext([
            'catalog/seo/product_url_suffix' => '.html',
            'catalog/seo/category_url_suffix' => '/',
        ]));

        $this->assertSame('https://admin.test/panth_productslider/slider/productsgrid', $block->getProductGridUrl());
        $this->assertSame('https://admin.test/panth_productslider/slider/categorytree', $block->getCategoryTreeUrl());
        $this->assertSame('.html', $block->getProductUrlSuffix());
        $this->assertSame('/', $block->getCategoryUrlSuffix());
    }

    public function testAssignProductsSuffixesDefaultToEmpty(): void
    {
        $block = new AssignProducts($this->backendContext());

        $this->assertSame('', $block->getProductUrlSuffix());
        $this->assertSame('', $block->getCategoryUrlSuffix());
    }

    /**
     * @param mixed $sliderId
     * @param mixed $identifier
     * @param bool $fail
     * @return UsageGuide
     */
    private function usageGuide($sliderId, $identifier = false, bool $fail = false): UsageGuide
    {
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('quoteIdentifier')->willReturnCallback(static fn($name) => '`' . $name . '`');
        $connection->method('fetchOne')->willReturnCallback(
            static function ($sql, $bind) use ($identifier, $fail) {
                if ($fail) {
                    throw new \RuntimeException('no table');
                }
                return $sql === 'SELECT identifier FROM `pfx_panth_product_slider` WHERE slider_id = ?'
                    && $bind === [7] ? $identifier : false;
            }
        );
        $resource = $this->createStub(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnCallback(static fn($table) => 'pfx_' . $table);

        return new UsageGuide($this->backendContext(), $this->request($sliderId), $resource);
    }

    public function testUsageGuideLooksUpTheIdentifierOfTheEditedSlider(): void
    {
        $guide = $this->usageGuide('7', 'summer_sale');

        $this->assertSame(7, $guide->getSliderId());
        $this->assertSame('summer_sale', $guide->getSliderIdentifier());
    }

    public function testUsageGuideFallsBackToAPlaceholder(): void
    {
        $this->assertNull($this->usageGuide(null)->getSliderId());
        $this->assertSame('your_identifier', $this->usageGuide(null)->getSliderIdentifier());
        $this->assertSame('your_identifier', $this->usageGuide('7', false)->getSliderIdentifier());
        $this->assertSame('your_identifier', $this->usageGuide('7', 'x', true)->getSliderIdentifier());
    }
}
