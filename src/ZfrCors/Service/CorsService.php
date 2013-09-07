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
     * Populate a preflight response by adding the corresponding headers
     *
     * @param  HttpRequest  $request
     * @param  HttpResponse $response
     * @return HttpResponse
     */
    public function populatePreflightCorsResponse(HttpRequest $request, HttpResponse $response)
    {
        $response->setStatusCode(200);
        $response->setContent(''); // Preflight answer should not have body

        $headers = $response->getHeaders();

        $headers->addHeaderLine('Access-Control-Allow-Origin', $this->getAllowedOriginValue($request));
        $headers->addHeaderLine('Access-Control-Allow-Methods', implode(', ', $this->options->getAllowedMethods()));
        $headers->addHeaderLine('Access-Control-Allow-Headers', implode(', ', $this->options->getAllowedHeaders()));
        $headers->addHeaderLine('Access-Control-Max-Age', $this->options->getMaxAge());
        $headers->addHeaderLine('Content-Length', 0);

        if ($this->options->getAllowedCredentials()) {
            $value = $this->options->getAllowedCredentials() ? 'true' : 'false';
            $headers->addHeaderLine('Access-Control-Allow-Credentials', $value);
        }

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
        $headers->addHeaderLine('Access-Control-Allow-Origin', $this->getAllowedOriginValue($request));
        $headers->addHeaderLine('Access-Control-Expose-Headers', implode(', ', $this->options->getExposedHeaders()));

        return $response;
    }

    /**
     * Get a single value for the "Access-Control-Allow-Origin" header
     *
     * According to the spec, it is not valid to set multiple origins separated by commas. Only accepted
     * value are wildcard ("*"), an exact domain or a null string.
     *
     * @link http://www.w3.org/TR/cors/#access-control-allow-origin-response-header
     * @param  HttpRequest $request
     * @return string
     */
    protected function getAllowedOriginValue(HttpRequest $request)
    {
        $allowedOrigins = $this->options->getAllowedOrigins();

        if (in_array('*', $allowedOrigins)) {
            return '*';
        }

        if (in_array($request->getHeader('Origin')->getFieldValue(), $allowedOrigins)) {
            return $request->getHeader('Origin')->getFieldValue();
        }

        return 'null';
    }
}
