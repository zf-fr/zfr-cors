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
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ResponseCollection;
use Zend\Http\PhpEnvironment\Request;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;
use Zend\Http\Response;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\RouteListener;
use Zend\Router\Http\TreeRouteStack;
use Zend\Stdlib\ResponseInterface;
use Zend\Uri\Uri;
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
            ->with(MvcEvent::EVENT_ROUTE, $this->isType('callable'), $this->LessThan(1));
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

        $mvcEvent->setRequest($request)
            ->setResponse($response);

        $this->assertNull($this->corsListener->onCorsPreflight($mvcEvent));
        $this->assertNull($this->corsListener->onCorsRequest($mvcEvent));
    }

    public function testImmediatelyReturnResponseForPreflightCorsRequest()
    {
        $mvcEvent = new MvcEvent();
        $request = new HttpRequest();
        $response = new HttpResponse();

        $request->setMethod('OPTIONS');
        $request->getHeaders()->addHeaderLine('Origin', 'http://example.com');
        $request->getHeaders()->addHeaderLine('Access-Control-Request-Method', 'POST');

        $mvcEvent->setRequest($request)
            ->setResponse($response);

        $this->assertInstanceOf('Zend\Http\Response', $this->corsListener->onCorsPreflight($mvcEvent));
    }

    public function testReturnNothingForNormalAuthorizedCorsRequest()
    {
        $mvcEvent = new MvcEvent();
        $request = new HttpRequest();
        $response = new HttpResponse();

        $request->getHeaders()->addHeaderLine('Origin', 'http://example.com');

        $this->corsOptions->setAllowedOrigins(['http://example.com']);

        $mvcEvent->setRequest($request)
            ->setResponse($response);

        $this->assertNull($this->corsListener->onCorsRequest($mvcEvent));
    }

    public function testReturnUnauthorizedResponseForNormalUnauthorizedCorsRequest()
    {
        $mvcEvent = new MvcEvent();
        $request = new HttpRequest();
        $response = new HttpResponse();

        $request->getHeaders()->addHeaderLine('Origin', 'http://unauthorized-domain.com');

        $mvcEvent->setRequest($request)
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

        $request->getHeaders()->addHeaderLine('Origin', 'file:');

        $mvcEvent->setRequest($request)
            ->setResponse($response);

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

        $mvcEvent->setRequest($request)
            ->setResponse($response);

        $this->corsListener->onCorsRequest($mvcEvent);
    }

    /**
     * This is some kind of integration test, not quite sure if this is the correct place where to put that
     * but I recently realized this in our production environment.
     */
    public function testPreflightWorksWithMethodRoutes()
    {
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
                            ],
                        ],
                    ],
                ],
            ]);

        // Copy & paste from Application::run
        // Define callback used to determine whether or not to short-circuit
        $event = $this->getMockBuilder(MvcEvent::class)->getMock();
        $event
            ->expects($this->any())
            ->method('getName')
            ->willReturn(MvcEvent::EVENT_ROUTE);

        $event
            ->expects($this->any())
            ->method('getRouter')
            ->willReturn($router);

        $request = new Request();
        $request->setRequestUri(new Uri('/foo'));
        $request->setMethod('OPTIONS');

        $event
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        $events = new EventManager();

        $target = $this
            ->getMockBuilder(Application::class)
            ->disableOriginalConstructor()
            ->getMock();

        $eventManagerMock = $this
            ->getMockBuilder(EventManager::class)
            ->getMock();

        $responses = new ResponseCollection();
        $responses->add(0, new Response());

        $eventManagerMock
            ->expects($this->once())
            ->method('triggerEvent')
            ->willReturn($responses);

        $target
            ->expects($this->any())
            ->method('getEventManager')
            ->willReturn($eventManagerMock);

        $event
            ->expects($this->any())
            ->method('getTarget')
            ->willReturn($target);

        (new RouteListener())->attach($events);
        $corsService = $this
            ->getMockBuilder(CorsService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $corsService
            ->expects($this->once())
            ->method('isCorsRequest')
            ->with($request)
            ->willReturn(true);

        $corsRequestListener = new CorsRequestListener($corsService);
        $corsRequestListener->attach($events);

        $shortCircuit = function ($r) use ($event) {
            if ($r instanceof ResponseInterface) {
                return true;
            }
            if ($event->getError()) {
                return true;
            }
            return false;
        };

        $events->triggerEventUntil($shortCircuit, $event);
    }
}
