<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

namespace ZfrCorsTest\Mvc;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\EventManager\EventManager;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\RouteListener;
use Zend\Router\Http\TreeRouteStack;
use ZfrCors\Mvc\CorsRequestListener;
use ZfrCors\Options\CorsOptions;
use ZfrCors\Service\CorsService;

/**
 * Integration tests for {@see \ZfrCors\Service\CorsService}
 *
 * @author Michaël Gallego <mic.gallego@gmail.com>
 *
 * @covers \ZfrCors\Mvc\CorsRequestListener
 * @group  Coverage
 */
class CorsRequestListenerTest extends TestCase
{

    /**
     * @var CorsService
     */
    protected $corsService;

    /**
     * @var CorsOptions
     */
    protected $corsOptions;

    /**
     * @var CorsRequestListener
     */
    protected $corsListener;

    public function setUp()
    {
        $this->corsOptions = new CorsOptions();
        $this->corsService = new CorsService($this->corsOptions);
        $this->corsListener = new CorsRequestListener($this->corsService);
    }

    public function testAttach()
    {
        $eventManager = $this->getMockBuilder('Zend\EventManager\EventManagerInterface')->getMock();

        $eventManager
            ->expects($this->at(0))
            ->method('attach')
            ->with(MvcEvent::EVENT_ROUTE, $this->isType('callable'), $this->equalTo(2));
        $eventManager
            ->expects($this->at(1))
            ->method('attach')
            ->with(MvcEvent::EVENT_FINISH, $this->isType('callable'), $this->greaterThan(1));

        $this->corsListener->attach($eventManager);
    }

    public function testReturnNothingForNonCorsRequest()
    {
        $mvcEvent = new MvcEvent();
        $request = new HttpRequest();
        $response = new HttpResponse();

        $mvcEvent
            ->setRequest($request)
            ->setResponse($response);

        $this->assertNull($this->corsListener->onCorsPreflight($mvcEvent));
        $this->assertNull($this->corsListener->onCorsRequest($mvcEvent));
    }

    public function testImmediatelyReturnResponseForPreflightCorsRequest()
    {
        $mvcEvent = new MvcEvent();
        $request = new HttpRequest();
        $response = new HttpResponse();
        $router = new TreeRouteStack();

        $request->setMethod('OPTIONS');
        $request->getHeaders()->addHeaderLine('Origin', 'http://example.com');
        $request->getHeaders()->addHeaderLine('Access-Control-Request-Method', 'POST');

        $mvcEvent
            ->setRequest($request)
            ->setResponse($response)
            ->setRouter($router);

        $this->assertInstanceOf('Zend\Http\Response', $this->corsListener->onCorsPreflight($mvcEvent));
    }

    public function testReturnNothingForNormalAuthorizedCorsRequest()
    {
        $mvcEvent = new MvcEvent();
        $request = new HttpRequest();
        $response = new HttpResponse();

        $request->getHeaders()->addHeaderLine('Origin', 'http://example.com');

        $this->corsOptions->setAllowedOrigins(['http://example.com']);

        $mvcEvent
            ->setRequest($request)
            ->setResponse($response);

        $this->assertNull($this->corsListener->onCorsRequest($mvcEvent));
    }

    public function testReturnUnauthorizedResponseForNormalUnauthorizedCorsRequest()
    {
        $mvcEvent = new MvcEvent();
        $request = new HttpRequest();
        $response = new HttpResponse();

        $request->getHeaders()->addHeaderLine('Origin', 'http://unauthorized-domain.com');

        $mvcEvent
            ->setRequest($request)
            ->setResponse($response);

        $this->corsListener->onCorsRequest($mvcEvent);

        // NOTE: a new response is created for security purpose
        $newResponse = $mvcEvent->getResponse();
        $this->assertNotEquals($response, $newResponse);
        $this->assertEquals(403, $newResponse->getStatusCode());
        $this->assertEquals('', $newResponse->getContent());
    }

    public function testImmediatelyReturnBadRequestResponseForInvalidOriginHeaderValue()
    {
        $mvcEvent = new MvcEvent();
        $request = new HttpRequest();
        $response = new HttpResponse();
        $router = new TreeRouteStack();

        $request->getHeaders()->addHeaderLine('Origin', 'file:');

        $mvcEvent
            ->setRequest($request)
            ->setResponse($response)
            ->setRouter($router);

        $returnedResponse = $this->corsListener->onCorsPreflight($mvcEvent);

        $this->assertEquals($response, $returnedResponse);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('', $response->getContent());
    }

    /**
     * Application always triggers `MvcEvent::EVENT_FINISH` and since the `CorsRequestListener` is listening on it, we
     * should handle the exception aswell.
     *
     *
     * @return void
     */
    public function testOnCorsRequestCanHandleInvalidOriginHeaderValue()
    {
        $mvcEvent = new MvcEvent();
        $request = new HttpRequest();
        $response = new HttpResponse();

        $request->getHeaders()->addHeaderLine('Origin', 'file:');

        $mvcEvent
            ->setRequest($request)
            ->setResponse($response);

        $this->corsListener->onCorsRequest($mvcEvent);
    }


    public function testPreflightWorksWithMethodRoutes()
    {
        $mvcEvent = new MvcEvent();
        $request = new HttpRequest();
        $request->setUri('/foo');
        $request->setMethod('OPTIONS');
        $request->getHeaders()->addHeaderLine('Origin', 'http://example.com');
        $request->getHeaders()->addHeaderLine('Access-Control-Request-Method', 'GET');
        $response = new HttpResponse();
        $router = new TreeRouteStack();
        $router
            ->addRoutes([
                'home' => [
                    'type' => 'literal',
                    'options' => [
                        'route' => '/foo',
                    ],
                    'may_terminate' => false,
                    'child_routes' => [
                        'get' => [
                            'type' => 'method',
                            'options' => [
                                'verb' => 'get',
                                'defaults' => [
                                    \ZfrCors\Options\CorsOptions::ROUTE_PARAM => [
                                        'allowed_origins' => ['http://example.com'],
                                        'allowed_methods' => ['GET'],
                                    ],
                                ]
                            ],
                        ],
                    ],
                ],
            ]);

        $mvcEvent
            ->setRequest($request)
            ->setResponse($response)
            ->setRouter($router);

        $events = new EventManager();
        $this->corsListener->attach($events);
        (new RouteListener())->attach($events);

        $event = new MvcEvent(MvcEvent::EVENT_ROUTE);
        $event->setRouter($router);
        $event->setRequest($request);

        $shortCircuit = function ($r) {
            $this->assertInstanceOf(\Zend\Http\Response::class, $r);
            $this->assertEquals(200, $r->getStatusCode());
            $this->assertEquals('GET', $r->getHeaders()->get('Access-Control-Allow-Methods')->getFieldValue());
            $this->assertEquals(
                'http://example.com',
                $r->getHeaders()->get('Access-Control-Allow-Origin')->getFieldValue()
            );
            return true;
        };
        $events->triggerEventUntil($shortCircuit, $event);
    }
}
