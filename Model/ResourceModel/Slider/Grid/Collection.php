<?php

declare(strict_types=1);

namespace Panth\ProductSlider\Model\ResourceModel\Slider\Grid;

use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document;
use Psr\Log\LoggerInterface;

class Collection extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    /**
     * @var string
     */
    protected $_idFieldName = 'slider_id';

    /**
     * @var AggregationInterface
     */
    protected $aggregations;

    /**
     * @param EntityFactoryInterface $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param string $mainTable
     * @param string $resourceModel
     * @param string|null $identifierName
     * @param string|null $connectionName
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        string $mainTable = 'panth_product_slider',
        string $resourceModel = \Panth\ProductSlider\Model\ResourceModel\Slider::class,
        ?string $identifierName = null,
        ?string $connectionName = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel,
            $identifierName,
            $connectionName
        );
    }

    /**
     * Initialize select with filter mappings
     *
     * @return $this
     */
    protected function _initSelect(): static
    {
        parent::_initSelect();

        $this->addFilterToMap('slider_id', 'main_table.slider_id');
        $this->addFilterToMap('identifier', 'main_table.identifier');
        $this->addFilterToMap('title', 'main_table.title');
        $this->addFilterToMap('is_active', 'main_table.is_active');
        $this->addFilterToMap('created_at', 'main_table.created_at');
        $this->addFilterToMap('updated_at', 'main_table.updated_at');

        return $this;
    }

    /**
     * Set items list - ensure each item has an ID set
     *
     * @return $this
     */
    protected function _afterLoad(): static
    {
        parent::_afterLoad();

        foreach ($this->_items as $item) {
            if ($item->getData('slider_id')) {
                $item->setId($item->getData('slider_id'));
            }
        }

        return $this;
    }

    /**
     * Get aggregations
     *
     * @return AggregationInterface
     */
    public function getAggregations(): AggregationInterface
    {
        return $this->aggregations;
    }

    /**
     * Set aggregations
     *
     * @param AggregationInterface $aggregations
     * @return $this
     */
    public function setAggregations($aggregations): static
    {
        $this->aggregations = $aggregations;
        return $this;
    }

    /**
     * Get search criteria
     *
     * @return SearchCriteriaInterface|null
     */
    public function getSearchCriteria(): ?SearchCriteriaInterface
    {
        return null;
    }

    /**
     * Set search criteria
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return $this
     */
    public function setSearchCriteria(SearchCriteriaInterface $searchCriteria): static
    {
        return $this;
    }

    /**
     * Get total count
     *
     * @return int
     */
    public function getTotalCount(): int
    {
        return $this->getSize();
    }

    /**
     * Set total count
     *
     * @param int $totalCount
     * @return $this
     */
    public function setTotalCount($totalCount): static
    {
        return $this;
    }

    /**
     * Set items
     *
     * @param array|null $items
     * @return $this
     */
    public function setItems(?array $items = null): static
    {
        return $this;
    }
}
