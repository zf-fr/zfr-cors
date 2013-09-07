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

namespace ZfrCors\Service;

use ZfrCors\Options\CorsOptions;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;

/**
 * Service that offers a simple mechanism to handle CORS requests
 *
 * This service closely follow the specification here: https://developer.mozilla.org/en-US/docs/HTTP/Access_control_CORS
 *
 * @license MIT
 * @author  Florent Blaison <florent.blaison@gmail.com>
 */
class CorsService
{
    /**
     * @var CorsOptions
     */
    protected $options;

    /**
     * @param CorsOptions $options
     */
    public function __construct(CorsOptions $options)
    {
        $this->options = $options;
    }

    /**
     * Check if the HTTP request is a CORS request by checking if the Origin header is present
     *
     * @param HttpRequest $request
     * @return bool
     */
    public function isCorsRequest(HttpRequest $request)
    {
        return $request->getHeaders()->has('Origin');
    }

    /**
     * Check if the CORS request is a preflight request
     *
     * @param HttpRequest $request
     * @return bool
     */
    public function isPreflightRequest(HttpRequest $request)
    {
        return $this->isCorsRequest($request)
            && strtoupper($request->getMethod()) === 'OPTIONS'
            && $request->getHeaders()->has('Access-Control-Request-Method');
    }

    /**
     * Check if the origin is allowed
     *
     * @param HttpRequest $request
     * @return bool
     */
    public function isOriginAllowed(HttpRequest $request)
    {
        $origin = strtoupper($request->getHeaders('Origin')->getFieldValue());

        return in_array($origin, $this->options->getAllowedOrigins());
    }

    /**
     * Check if the method is allowed
     *
     * @param  HttpRequest $request
     * @return bool
     */
    public function isMethodAllowed(HttpRequest $request)
    {
        return in_array(strtoupper($request->getMethod()), $this->options->getAllowedMethods());
    }

    /**
     * Populate a preflight response by adding the corresponding headers
     *
     * @param  HttpRequest  $request
     * @param  HttpResponse $response
     * @return HttpResponse
     */
    public function populatePreflightCorsResponse(HttpRequest $request, HttpResponse $response)
    {
        $response->setStatusCode(200);

        $headers = $response->getHeaders();

        $headers->addHeaderLine('Access-Control-Allow-Origin', $request->getHeader('Origin')->getFieldValue());
        $headers->addHeaderLine('Access-Control-Allow-Methods', implode(',', $this->options->getAllowedMethods()));
        $headers->addHeaderLine('Access-Control-Allow-Headers', implode(',', $this->options->getAllowedHeaders()));
        $headers->addHeaderLine('Access-Control-Max-Age', $this->options->getMaxAge());
        $headers->addHeaderLine('Content-Length', 0);

        if ($this->options->getAllowedCredentials()) {
            $headers->addHeaderLine('Access-Control-Allow-Credentials', $this->options->getAllowedCredentials());
        }

        return $response;
    }

    /**
     * Populate a forbidden CORS response
     *
     * @param  HttpRequest  $request
     * @param  HttpResponse $response
     * @return HttpResponse
     */
    public function populateForbiddenCorsResponse(HttpRequest $request, HttpResponse $response)
    {
        $response = $this->populatePreflightCorsResponse($request, $response);
        $response->setStatusCode(403);

        return $response;
    }

    /**
     * Populate a simple CORS response
     *
     * @param  HttpRequest  $request
     * @param  HttpResponse $response
     * @return HttpResponse
     */
    public function populateCorsResponse(HttpRequest $request, HttpResponse $response)
    {
        $headers = $response->getHeaders();
        $headers->addHeaderLine('Access-Control-Allow-Origin', $request->getHeader('Origin')->getFieldValue());

        return $response;
    }
}
