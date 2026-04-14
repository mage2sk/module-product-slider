<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Block\Adminhtml\Slider\Edit;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;

class UsageGuide extends Template
{
    protected $_template = 'Panth_ProductSlider::slider/usage-guide.phtml';

    private RequestInterface $request;

    private ResourceConnection $resourceConnection;

    public function __construct(
        Context $context,
        RequestInterface $request,
        ResourceConnection $resourceConnection,
        array $data = []
    ) {
        $this->request = $request;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $data);
    }

    public function getSliderId(): ?int
    {
        $id = $this->request->getParam('slider_id');
        return $id ? (int) $id : null;
    }

    public function getSliderIdentifier(): string
    {
        if ($this->getSliderId()) {
            try {
                $connection = $this->resourceConnection->getConnection();
                $tableName = $this->resourceConnection->getTableName('panth_product_slider');
                $identifier = $connection->fetchOne(
                    'SELECT identifier FROM ' . $connection->quoteIdentifier($tableName) . ' WHERE slider_id = ?',
                    [$this->getSliderId()]
                );
                return $identifier ?: 'your_identifier';
            } catch (\Exception $e) {
                return 'your_identifier';
            }
        }
        return 'your_identifier';
    }
}
