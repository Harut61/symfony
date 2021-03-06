<?php

/**
 * (c) Evis Bregu <evis.bregu@gmail.com>.
 */

namespace App\Tests\EventListener;

use App\Api\ResponseFactory;
use App\EventListener\ApiExceptionSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiExceptionSubscriberTest extends TestCase
{
    public function testApiException()
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/test');
        $exception = new \Exception('foo');
        $event = new GetResponseForExceptionEvent(new TestKernel(), $request, HttpKernelInterface::MASTER_REQUEST, $exception);

        $logger = $this->getMockBuilder("Psr\Log\LoggerInterface")->getMock();
        $responseFactory = new ResponseFactory();

        $apiExceptionSubscriber = new ApiExceptionSubscriber(false, $logger, $responseFactory);
        $apiExceptionSubscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertSame(500, $response->getStatusCode());

        $responseData = $response->getContent();
        $this->assertNotEmpty($responseData);
        $this->assertContains('about:blank', $responseData);

        $subscribedEvents = ApiExceptionSubscriber::getSubscribedEvents();
        $this->assertInternalType('array', $subscribedEvents);
        $this->assertSame([KernelEvents::EXCEPTION => 'onKernelException'], $subscribedEvents);

        $exception = new HttpException('404', 'FOO Not Found');
        $event = new GetResponseForExceptionEvent(new TestKernel(), $request, HttpKernelInterface::MASTER_REQUEST, $exception);
        $apiExceptionSubscriber->onKernelException($event);
        $response = $event->getResponse();
        $responseData = $response->getContent();

        $this->assertContains('detail', $responseData);
        $this->assertContains('FOO Not Found', $responseData);

        //Check that debbug mode is working. When debug is set to true ApiExceptionSubscriber will return
        //without setting the response for the event. We do this to get Symfony's error page in development
        $exception = new \Exception('foo');
        $apiExceptionSubscriber = new ApiExceptionSubscriber(true, $logger, $responseFactory);
        $event = new GetResponseForExceptionEvent(new TestKernel(), $request, HttpKernelInterface::MASTER_REQUEST, $exception);
        $apiExceptionSubscriber->onKernelException($event);

        $this->assertNull($event->getResponse());
    }
}

class TestKernel implements HttpKernelInterface
{
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        return new Response('foo');
    }
}
