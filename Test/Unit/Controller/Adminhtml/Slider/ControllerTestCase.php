<?php
declare(strict_types=1);

namespace Panth\ProductSlider\Test\Unit\Controller\Adminhtml\Slider;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Message\ManagerInterface;
use Panth\ProductSlider\Model\Slider;
use PHPUnit\Framework\TestCase;

/**
 * Shared doubles for backend controller tests: records redirects and messages.
 */
abstract class ControllerTestCase extends TestCase
{
    /** @var array{0:string,1:array}|null */
    protected ?array $redirect = null;

    protected array $success = [];

    protected array $errors = [];

    /**
     * Build a backend action context around the given request data.
     *
     * @param array $params
     * @param array|null $post
     * @return Context
     */
    protected function context(array $params = [], ?array $post = null): Context
    {
        $this->redirect = null;
        $this->success = $this->errors = [];

        $request = $this->createStub(HttpRequest::class);
        $request->method('getParam')->willReturnCallback(
            static fn($key, $default = null) => array_key_exists($key, $params) ? $params[$key] : $default
        );
        $request->method('getPostValue')->willReturn($post);

        $redirect = $this->createStub(Redirect::class);
        $redirect->method('setPath')->willReturnCallback(function ($path, $args = []) use ($redirect) {
            $this->redirect = [$path, $args];
            return $redirect;
        });
        $redirectFactory = $this->createStub(RedirectFactory::class);
        $redirectFactory->method('create')->willReturn($redirect);

        $messages = $this->createStub(ManagerInterface::class);
        $messages->method('addSuccessMessage')->willReturnCallback(function ($message) use ($messages) {
            $this->success[] = (string)$message;
            return $messages;
        });
        $messages->method('addErrorMessage')->willReturnCallback(function ($message) use ($messages) {
            $this->errors[] = (string)$message;
            return $messages;
        });

        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getResultRedirectFactory')->willReturn($redirectFactory);
        $context->method('getMessageManager')->willReturn($messages);

        return $context;
    }

    /**
     * A slider model instance that does not need the object manager.
     *
     * @param array $data
     * @return Slider
     */
    protected function slider(array $data = []): Slider
    {
        $slider = (new \ReflectionClass(Slider::class))->newInstanceWithoutConstructor();
        $slider->setIdFieldName('slider_id');
        $slider->setData($data);

        return $slider;
    }
}
