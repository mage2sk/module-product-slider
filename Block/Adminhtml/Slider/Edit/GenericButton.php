<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Block\Adminhtml\Slider\Edit;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;

class GenericButton
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly UrlInterface $urlBuilder
    ) {
    }

    public function getSliderId(): ?int
    {
        $sliderId = $this->request->getParam('slider_id');

        return $sliderId ? (int) $sliderId : null;
    }

    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->urlBuilder->getUrl($route, $params);
    }
}
