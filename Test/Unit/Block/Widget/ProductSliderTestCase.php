<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Test\Unit\Block\Widget;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Model\ResourceModel\Stock\StatusFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context as HelperContext;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Escaper;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\Core\Helper\Theme;
use Panth\ProductSlider\Helper\Badge;
use Panth\ProductSlider\Helper\Data;
use PHPUnit\Framework\TestCase;

/**
 * Shared doubles for the slider blocks: a recording product collection and a real config helper.
 */
abstract class ProductSliderTestCase extends TestCase
{
    /** @var array<int, array{0:string,1:array}> */
    protected array $calls = [];

    /** @var array<int, array{0:string,1:array}> */
    protected array $selectCalls = [];

    protected array $flags = [];

    protected array $items = [];

    protected array $childCategoryIds = [];

    protected ?\Throwable $collectionError = null;

    protected Store $store;

    protected Context $context;

    protected Theme $theme;

    protected PriceCurrencyInterface $priceCurrency;

    protected TimezoneInterface $timezone;

    protected ImageHelper $imageHelper;

    protected ?Badge $badgeHelper = null;

    protected function setUp(): void
    {
        $this->calls = $this->selectCalls = $this->flags = $this->items = $this->childCategoryIds = [];
        $this->collectionError = null;
        $this->badgeHelper = null;

        $this->store = $this->createStub(Store::class);
        $this->store->method('getId')->willReturn(1);
        $this->store->method('getBaseUrl')->willReturn('https://shop.test/media/');
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($this->store);

        $escaper = $this->createStub(Escaper::class);
        $escaper->method('escapeHtml')->willReturnCallback(
            static fn($value, $allowed = null) => strip_tags((string)$value, $allowed ?? [])
        );

        $this->context = $this->createStub(Context::class);
        $this->context->method('getStoreManager')->willReturn($storeManager);
        $this->context->method('getEscaper')->willReturn($escaper);

        $this->theme = $this->createStub(Theme::class);
        $this->priceCurrency = $this->createStub(PriceCurrencyInterface::class);
        $this->timezone = $this->createStub(TimezoneInterface::class);
        $this->timezone->method('scopeDate')->willReturn(new \DateTime('2026-03-15 10:00:00'));
        $this->imageHelper = $this->createStub(ImageHelper::class);
    }

    /**
     * A real config helper backed by $this->flags.
     *
     * @return Data
     */
    protected function sliderHelper(): Data
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturnCallback(fn($path) => (bool)($this->flags[$path] ?? false));
        $context = $this->createStub(HelperContext::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);

        return new Data($context);
    }

    /**
     * Collection factory returning a product collection that records every builder call.
     *
     * @return CollectionFactory
     */
    protected function collectionFactory(): CollectionFactory
    {
        $select = $this->createStub(Select::class);
        foreach (['order', 'joinLeft', 'where', 'distinct', 'from', 'orWhere'] as $method) {
            $select->method($method)->willReturnCallback(function (...$args) use ($method, $select) {
                $this->selectCalls[] = [$method, $args];
                return $select;
            });
        }

        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchCol')->willReturnCallback(fn() => $this->childCategoryIds);

        $collection = $this->createStub(Collection::class);
        $fluent = [
            'addAttributeToSelect', 'addAttributeToFilter', 'setVisibility', 'addStoreFilter', 'addMinimalPrice',
            'addFinalPrice', 'addTaxPercents', 'addCategoriesFilter', 'addFieldToFilter', 'addAttributeToSort',
            'setPageSize',
        ];
        foreach ($fluent as $method) {
            $collection->method($method)->willReturnCallback(function (...$args) use ($method, $collection) {
                $this->calls[] = [$method, $args];
                return $collection;
            });
        }
        $collection->method('getSelect')->willReturn($select);
        $collection->method('getConnection')->willReturn($connection);
        $collection->method('getTable')->willReturnCallback(static fn($table) => 'pfx_' . $table);
        $collection->method('getItems')->willReturnCallback(fn() => $this->items);

        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturnCallback(function () use ($collection) {
            if ($this->collectionError !== null) {
                throw $this->collectionError;
            }
            return $collection;
        });

        return $factory;
    }

    /**
     * Visibility source stub.
     *
     * @return Visibility
     */
    protected function visibility(): Visibility
    {
        $visibility = $this->createStub(Visibility::class);
        $visibility->method('getVisibleInCatalogIds')->willReturn([2, 4]);

        return $visibility;
    }

    /**
     * Arguments of every recorded call to $method.
     *
     * @param string $method
     * @return array
     */
    protected function callsTo(string $method): array
    {
        $found = [];
        foreach ($this->calls as [$name, $args]) {
            if ($name === $method) {
                $found[] = $this->trimDefaults($args, $method === 'addAttributeToFilter' ? 'inner' : null);
            }
        }

        return $found;
    }

    /**
     * Drop trailing default arguments that the mock framework passes along.
     *
     * @param array $args
     * @param string|null $default
     * @return array
     */
    private function trimDefaults(array $args, ?string $default = null): array
    {
        while ($args && (end($args) === null || end($args) === $default)) {
            array_pop($args);
        }

        return $args;
    }

    /**
     * Arguments of every recorded select call to $method.
     *
     * @param string $method
     * @return array
     */
    protected function selectCallsTo(string $method): array
    {
        $found = [];
        foreach ($this->selectCalls as [$name, $args]) {
            if ($name === $method) {
                $found[] = $this->trimDefaults($args);
            }
        }

        return $found;
    }

    /**
     * Badge helper used by the block; a stub unless a test injected one.
     *
     * @return Badge
     */
    protected function badgeHelper(): Badge
    {
        return $this->badgeHelper ?? $this->createStub(Badge::class);
    }

    /**
     * Invoke a protected method.
     *
     * @param object $object
     * @param string $method
     * @param array $args
     * @return mixed
     */
    protected function invoke(object $object, string $method, array $args = [])
    {
        $reflection = new \ReflectionMethod($object, $method);

        return $reflection->invokeArgs($object, $args);
    }
}
