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
use ZfrCors\Options\CorsOptions;
use ZfrCors\Service\CorsService;

/**
 * Integration tests for {@see \ZfrCors\Service\CorsService}
 *
 * @author Florent Blaison <florent.blaison@gmail.com>
 *
 * @covers \ZfrRest\Service\CorsService
 * @group Functional
 */
class CorsServiceTest extends TestCase
{
    /**
     * @var CorsService
     */
    protected $corsService;

    /**
     * @var HttpResponse
     */
    protected $response;

    /**
     * @var HttpRequest
     */
    protected $request;

    /**
     * @var MvcEvent
     */
    protected $event;

    /**
     * @var CorsOptions
     */
    protected $corsOptions;

    /**
     * Set up
     */
    public function setUp()
    {
        parent::setUp();

        $this->corsOptions = new CorsOptions(array(
            'origins' => array('origin-header'),
            'allowed_methods' => array('GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'),
            'allowed_headers' => array('content-type', 'accept'),
            'max_age' => 10,
            'allowed_credentials' => true,
        ));
        $this->corsService = new CorsService($this->corsOptions);
    }

    public function testIfIsCorsRequest()
    {
        $request = new HttpRequest();
        $request->getHeaders()->addHeaderLine('Origin', 'origin-header');

        $result = $this->corsService->isCorsRequest($request);

        $this->assertEquals(true, $result);
    }

    public function testIfIsNotCorsRequest()
    {
        $request = new HttpRequest();

        $result = $this->corsService->isCorsRequest($request);

        $this->assertEquals(false, $result);
    }

    public function testIfOriginIsAllowed()
    {
        $request = new HttpRequest();
        $request->getHeaders()->addHeaderLine('Origin', 'origin-header');

        $result = $this->corsService->isOriginAllowed($request);

        $this->assertEquals(true, $result);
    }

    public function testIfOriginIsNotAllowed()
    {
        $request = new HttpRequest();
        $request->getHeaders()->addHeaderLine('Origin', 'origin-no-header');

        $result = $this->corsService->isOriginAllowed($request);

        $this->assertEquals(false, $result);
    }

    public function testIfIsPreflightRequest()
    {
        $request = new HttpRequest();
        $request->setMethod('options');
        $request->getHeaders()->addHeaderLine('Access-Control-Request-Method', 'method');

        $result = $this->corsService->isPreflightRequest($request);

        $this->assertEquals(true, $result);
    }

    public function testIfIsPreflightRequestWithoutOptions()
    {
        $request = new HttpRequest();
        $request->getHeaders()->addHeaderLine('Access-Control-Request-Method', 'method');

        $result = $this->corsService->isPreflightRequest($request);

        $this->assertEquals(false, $result);
    }

    public function testIfIsPreflightRequestWithoutACRM()
    {
        $request = new HttpRequest();
        $request->setMethod('options');

        $result = $this->corsService->isPreflightRequest($request);

        $this->assertEquals(false, $result);
    }

    public function testPrePopulateCorsResponse()
    {
        $request  = new HttpRequest();
        $response = new HttpResponse();
        $request->getHeaders()->addHeaderLine('Origin', 'origin-header');

        $this->corsService->prePopulateCorsResponse($request, $response);

        $this->assertEquals(true, $response->getHeaders()->has('Access-Control-Allow-Origin'));
    }

    public function testPrePopulateCorsResponseWithoutOrigin()
    {
        $this->setExpectedException('ZfrCors\Exception\DisallowedOriginException');

        $request  = new HttpRequest();
        $response = new HttpResponse();
        $request->getHeaders()->addHeaderLine('Origin', 'origin-no-header');

        $this->corsService->prePopulateCorsResponse($request, $response);
    }

    public function testPopulateCorsResponse()
    {
        $response = new HttpResponse();
        $response = $this->corsService->populateCorsResponse($response);

        $this->assertEquals(204, $response->getStatusCode());

        $headers = $response->getHeaders();

        $this->assertEquals('GET,POST,PUT,DELETE,OPTIONS', $headers->get('Access-Control-Allow-Methods')->getFieldValue());

        $this->assertEquals('content-type,accept', $headers->get('Access-Control-Allow-Headers')->getFieldValue());

        $this->assertEquals(10, $headers->get('Access-Control-Max-Age')->getFieldValue());

        $this->assertEquals(0, $headers->get('Content-Length')->getFieldValue());

        $this->assertEquals(true, $headers->get('Access-Control-Allow-Credentials')->getFieldValue());
    }
}
