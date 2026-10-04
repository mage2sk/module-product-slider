<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Test\Unit\Controller\Adminhtml\Slider;

use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ProductSlider\Controller\Adminhtml\Slider\CategoryTree;
use Panth\ProductSlider\Controller\Adminhtml\Slider\ProductsGrid;

class JsonEndpointsTest extends ControllerTestCase
{
    private ?array $json = null;

    private array $filters = [];

    /**
     * @return JsonFactory
     */
    private function jsonFactory(): JsonFactory
    {
        $json = $this->createStub(Json::class);
        $json->method('setData')->willReturnCallback(function ($data) use ($json) {
            $this->json = $data;
            return $json;
        });
        $factory = $this->createStub(JsonFactory::class);
        $factory->method('create')->willReturn($json);

        return $factory;
    }

    /**
     * @param string $suffixPath
     * @param string $suffix
     * @return ScopeConfigInterface
     */
    private function scopeConfig(string $suffixPath, string $suffix): ScopeConfigInterface
    {
        $config = $this->createStub(ScopeConfigInterface::class);
        $config->method('getValue')->willReturnCallback(
            static fn($path) => $path === $suffixPath ? $suffix : null
        );

        return $config;
    }

    /**
     * @return StoreManagerInterface
     */
    private function storeManager(): StoreManagerInterface
    {
        $store = $this->createStub(Store::class);
        $store->method('getBaseUrl')->willReturn('https://shop.test/');
        $manager = $this->createStub(StoreManagerInterface::class);
        $manager->method('getStore')->willReturn($store);

        return $manager;
    }

    /**
     * Configure a fluent collection stub that records filters and iterates $items.
     *
     * @param object $collection
     * @param array $items
     * @return void
     */
    private function fluent(object $collection, array $items): void
    {
        foreach (['addAttributeToSelect', 'addAttributeToFilter', 'setPageSize', 'setOrder'] as $method) {
            $collection->method($method)->willReturnCallback(function (...$args) use ($method, $collection) {
                $this->filters[] = [$method, $args[0], $args[1] ?? null];
                return $collection;
            });
        }
        $collection->method('getIterator')->willReturn(new \ArrayIterator($items));
    }

    public function testProductsGridReturnsEnabledVisibleProductsWithUrls(): void
    {
        $collection = $this->createStub(ProductCollection::class);
        $this->fluent($collection, [
            new DataObject([
                'id' => '5', 'name' => 'Tee', 'sku' => 'T-1', 'type_id' => 'simple', 'price' => '19.90',
                'url_key' => 'tee',
            ]),
            new DataObject(['id' => 6, 'name' => null, 'sku' => 'NOURL', 'type_id' => 'virtual', 'price' => null]),
        ]);
        $factory = $this->createStub(ProductCollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $controller = new ProductsGrid(
            $this->context(),
            $factory,
            $this->jsonFactory(),
            $this->scopeConfig('catalog/seo/product_url_suffix', '.html'),
            $this->storeManager()
        );
        $controller->execute();

        $this->assertSame([
            ['id' => 5, 'name' => 'Tee', 'sku' => 'T-1', 'type' => 'simple', 'price' => 19.9,
                'url' => 'https://shop.test/tee.html'],
            ['id' => 6, 'name' => '', 'sku' => 'NOURL', 'type' => 'virtual', 'price' => 0.0, 'url' => ''],
        ], $this->json['products']);
        $this->assertContains(['addAttributeToFilter', 'status', 1], $this->filters);
        $this->assertContains(['addAttributeToFilter', 'visibility', ['in' => [2, 3, 4]]], $this->filters);
        $this->assertContains(['setPageSize', 500, null], $this->filters);
    }

    public function testCategoryTreeNestsChildrenUnderTheirParents(): void
    {
        $collection = $this->createStub(CategoryCollection::class);
        $this->fluent($collection, [
            new DataObject(['id' => 3, 'name' => 'Shirts', 'level' => 3, 'parent_id' => 2, 'url_path' => 'men/shirts']),
            new DataObject(['id' => 2, 'name' => 'Men', 'level' => 2, 'parent_id' => 1, 'url_key' => 'men']),
            new DataObject(['id' => 4, 'name' => 'Polos', 'level' => 4, 'parent_id' => 3]),
            new DataObject(['id' => 1, 'name' => 'Root', 'level' => 1, 'parent_id' => 0]),
        ]);
        $factory = $this->createStub(CategoryCollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $controller = new CategoryTree(
            $this->context(),
            $factory,
            $this->jsonFactory(),
            $this->scopeConfig('catalog/seo/category_url_suffix', '/'),
            $this->storeManager()
        );
        $controller->execute();

        $tree = $this->json['categories'];
        $this->assertCount(1, $tree);
        $this->assertSame('Root', $tree[0]['name']);
        $this->assertSame('', $tree[0]['url']);
        $men = $tree[0]['children'][0];
        $this->assertSame('Men', $men['name']);
        $this->assertSame('https://shop.test/men/', $men['url']);
        $shirts = $men['children'][0];
        $this->assertSame('https://shop.test/men/shirts/', $shirts['url']);
        $this->assertSame(4, $shirts['children'][0]['id']);
        $this->assertSame([], $shirts['children'][0]['children']);
        $this->assertContains(['addAttributeToFilter', 'level', ['gt' => 0]], $this->filters);
    }

    public function testCategoryWithMissingParentBecomesARoot(): void
    {
        $collection = $this->createStub(CategoryCollection::class);
        $this->fluent($collection, [
            new DataObject(['id' => 8, 'name' => 'Orphan', 'level' => 3, 'parent_id' => 77]),
            new DataObject(['id' => 9, 'name' => 'Other', 'level' => 2, 'parent_id' => 1]),
        ]);
        $factory = $this->createStub(CategoryCollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $controller = new CategoryTree(
            $this->context(),
            $factory,
            $this->jsonFactory(),
            $this->scopeConfig('catalog/seo/category_url_suffix', ''),
            $this->storeManager()
        );
        $controller->execute();

        $this->assertSame(['Orphan', 'Other'], array_column($this->json['categories'], 'name'));
    }
}
