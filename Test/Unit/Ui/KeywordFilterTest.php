<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Test\Unit\Ui;

use Magento\Framework\Api\Filter;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\Collection\AbstractDb;
use Panth\ProductSlider\Ui\Component\Listing\KeywordFilter;
use PHPUnit\Framework\TestCase;

class KeywordFilterTest extends TestCase
{
    private function filter(string $value): Filter
    {
        $filter = $this->createStub(Filter::class);
        $filter->method('getValue')->willReturn($value);

        return $filter;
    }

    public function testKeywordMatchesIdentifierOrTitle(): void
    {
        $collection = $this->createMock(AbstractDb::class);
        $collection->expects($this->once())->method('addFieldToFilter')->with(
            ['main_table.identifier', 'main_table.title'],
            [['like' => '%Sample Iso%'], ['like' => '%Sample Iso%']]
        );

        (new KeywordFilter())->apply($collection, $this->filter('  Sample Iso '));
    }

    public function testLikeWildcardsInTheKeywordAreEscaped(): void
    {
        $collection = $this->createMock(AbstractDb::class);
        $collection->expects($this->once())->method('addFieldToFilter')->with(
            $this->anything(),
            [['like' => '%50\\%\\_off%'], ['like' => '%50\\%\\_off%']]
        );

        (new KeywordFilter())->apply($collection, $this->filter('50%_off'));
    }

    public function testEmptyKeywordAddsNoFilter(): void
    {
        $collection = $this->createMock(AbstractDb::class);
        $collection->expects($this->never())->method('addFieldToFilter');

        (new KeywordFilter())->apply($collection, $this->filter('   '));
    }

    public function testNonDatabaseCollectionIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new KeywordFilter())->apply($this->createStub(Collection::class), $this->filter('x'));
    }
}
