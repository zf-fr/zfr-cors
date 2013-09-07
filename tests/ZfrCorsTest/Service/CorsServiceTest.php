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
 * @covers \ZfrCors\Service\CorsService
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
            'allowed_origins'     => array('http://example.com'),
            'allowed_methods'     => array('GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'),
            'allowed_headers'     => array('Content-Type', 'Accept'),
            'exposed_headers'     => array('Location'),
            'max_age'             => 10,
            'allowed_credentials' => true,
        ));

        $this->corsService = new CorsService($this->corsOptions);
    }

    public function testCanDetectCorsRequest()
    {
        $request = new HttpRequest();

        $this->assertFalse($this->corsService->isCorsRequest($request));

        $request->getHeaders()->addHeaderLine('Origin', 'http://example.com');
        $this->assertEquals(true, $this->corsService->isCorsRequest($request));
    }

    public function testCanDetectPreflightRequest()
    {
        $request = new HttpRequest();

        $this->assertFalse($this->corsService->isPreflightRequest($request));

        $request->setMethod('OPTIONS');
        $this->assertFalse($this->corsService->isPreflightRequest($request));

        $request->getHeaders()->addHeaderLine('Origin', 'http://example.com');
        $this->assertFalse($this->corsService->isPreflightRequest($request));

        $request->getHeaders()->addHeaderLine('Access-Control-Request-Method', 'POST');
        $this->assertTrue($this->corsService->isPreflightRequest($request));
    }

    public function testResponseBodyIsClearedWhenResponseIsPreflighted()
    {
        $request = new HttpRequest();
        $request->getHeaders()->addHeaderLine('Origin', 'foo');

        $response = new HttpResponse();
        $response->setContent('foo');

        $this->corsService->populatePreflightCorsResponse($request, $response);

        $this->assertEmpty($response->getContent());
    }

    public function testProperlyPopulatePreflightRequest()
    {
        $request  = new HttpRequest();
        $response = new HttpResponse();

        $request->getHeaders()->addHeaderLine('Origin', 'http://example.com');

        $this->corsService->populatePreflightCorsResponse($request, $response);

        $headers = $response->getHeaders();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('', $response->getContent());
        $this->assertEquals('http://example.com', $headers->get('Access-Control-Allow-Origin')->getFieldValue());
        $this->assertEquals('GET, POST, PUT, DELETE, OPTIONS', $headers->get('Access-Control-Allow-Methods')->getFieldValue());
        $this->assertEquals('Content-Type, Accept', $headers->get('Access-Control-Allow-Headers')->getFieldValue());
        $this->assertEquals(10, $headers->get('Access-Control-Max-Age')->getFieldValue());
        $this->assertEquals(0, $headers->get('Content-Length')->getFieldValue());

        $this->assertEquals('true', $headers->get('Access-Control-Allow-Credentials')->getFieldValue());
    }

    public function testDoesNotAddAllowCredentialsHeadersIfAsked()
    {
        $request  = new HttpRequest();
        $response = new HttpResponse();

        $request->getHeaders()->addHeaderLine('Origin', 'http://example.com');
        $this->corsOptions->setAllowedCredentials(false);

        $this->corsService->populatePreflightCorsResponse($request, $response);

        $headers = $response->getHeaders();
        $this->assertFalse($headers->has('Access-Control-Allow-Credentials'));
    }

    public function testCanReturnWildCardAllowOrigin()
    {
        $request  = new HttpRequest();
        $response = new HttpResponse();

        $request->getHeaders()->addHeaderLine('Origin', 'http://funny-origin.com');
        $this->corsOptions->setAllowedOrigins(array('*'));

        $this->corsService->populatePreflightCorsResponse($request, $response);

        $headers = $response->getHeaders();
        $this->assertEquals('*', $headers->get('Access-Control-Allow-Origin')->getFieldValue());
    }

    public function testReturnNullForUnknownOrigin()
    {
        $request  = new HttpRequest();
        $response = new HttpResponse();

        $request->getHeaders()->addHeaderLine('Origin', 'http://unauthorized-origin.com');

        $this->corsService->populatePreflightCorsResponse($request, $response);

        $headers = $response->getHeaders();
        $this->assertEquals('null', $headers->get('Access-Control-Allow-Origin')->getFieldValue());
    }

    public function testCanPopulateNormalCorsRequest()
    {
        $request  = new HttpRequest();
        $response = new HttpResponse();

        $request->getHeaders()->addHeaderLine('Origin', 'http://example.com');

        $this->corsService->populateCorsResponse($request, $response);

        $headers = $response->getHeaders();

        $this->assertEquals('http://example.com', $headers->get('Access-Control-Allow-Origin')->getFieldValue());
        $this->assertEquals('Location', $headers->get('Access-Control-Expose-Headers')->getFieldValue());
    }
}
