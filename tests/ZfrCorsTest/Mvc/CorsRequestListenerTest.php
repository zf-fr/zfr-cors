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
use Zend\Http\Response as HttpResponse;
use Zend\Http\Request as HttpRequest;
use Zend\Mvc\MvcEvent;
use ZfrCors\Mvc\CorsRequestListener;
use ZfrCors\Options\CorsOptions;
use ZfrCors\Service\CorsService;

/**
 * Integration tests for {@see \ZfrCors\Service\CorsService}
 *
 * @author Michaël Gallego <mic.gallego@gmail.com>
 *
 * @covers \ZfrCors\Mvc\CorsRequestListener
 * @group Functional
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
        $this->corsOptions  = new CorsOptions();
        $this->corsService  = new CorsService($this->corsOptions);
        $this->corsListener = new CorsRequestListener($this->corsService);
    }

    public function testReturnNothingForNonCorsRequest()
    {
        $mvcEvent = new MvcEvent();
        $request  = new HttpRequest();
        $response = new HttpResponse();

        $mvcEvent->setRequest($request)
                 ->setResponse($response);

        $this->assertNull($this->corsListener->onCorsRequest($mvcEvent));
    }

    public function testImmediatelyReturnResponseForPreflightCorsRequest()
    {
        $mvcEvent = new MvcEvent();
        $request  = new HttpRequest();
        $response = new HttpResponse();

        $request->setMethod('OPTIONS');
        $request->getHeaders()->addHeaderLine('Origin', 'http://example.com');
        $request->getHeaders()->addHeaderLine('Access-Control-Request-Method', 'POST');

        $mvcEvent->setRequest($request)
                 ->setResponse($response);

        $this->assertEquals($response, $this->corsListener->onCorsRequest($mvcEvent));
    }

    public function testReturnNothingForNormalAuthorizedCorsRequest()
    {
        $mvcEvent = new MvcEvent();
        $request  = new HttpRequest();
        $response = new HttpResponse();

        $request->getHeaders()->addHeaderLine('Origin', 'http://example.com');

        $this->corsOptions->setAllowedOrigins(array('http://example.com'));

        $mvcEvent->setRequest($request)
                 ->setResponse($response);

        $this->assertNull($this->corsListener->onCorsRequest($mvcEvent));
    }

    public function testReturnUnauthorizedResponseForNormalUnauthorizedCorsRequest()
    {
        $mvcEvent = new MvcEvent();
        $request  = new HttpRequest();
        $response = new HttpResponse();

        $request->getHeaders()->addHeaderLine('Origin', 'http://unauthorized-domain.com');

        $mvcEvent->setRequest($request)
                 ->setResponse($response);

        $this->assertEquals($response, $this->corsListener->onCorsRequest($mvcEvent));
        $this->assertEquals(403, $response->getStatusCode());
    }
}
