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

use Zend\Uri\UriFactory;
use ZfrCors\Exception\DisallowedOriginException;
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
class CorsService {

    /**
     * @var CorsOptions
     */
    protected $options;

    /**
     * @param CorsOptions $options
     */
    public function __construct(CorsOptions $options) {
        $this->options = $options;
    }

    /**
     * Check if the HTTP request is a CORS request by checking if the Origin header is present and that the
     * request URI is not the same as the one in the Origin
     *
     * @param  HttpRequest $request
     * @return bool
     */
    public function isCorsRequest(HttpRequest $request) {
        $headers = $request->getHeaders();

        if (!$headers->has('Origin')) {
            return false;
        }

        $originUri = UriFactory::factory($headers->get('Origin')->getFieldValue());
        $requestUri = $request->getUri();

        // According to the spec (http://tools.ietf.org/html/rfc6454#section-4), we should check host, port and scheme

        return (!($originUri->getHost() === $requestUri->getHost()) || !($originUri->getPort() === $requestUri->getPort()) || !($originUri->getScheme() === $requestUri->getScheme())
                );
    }

    /**
     * Check if the CORS request is a preflight request
     *
     * @param  HttpRequest $request
     * @return bool
     */
    public function isPreflightRequest(HttpRequest $request) {
        return $this->isCorsRequest($request) && strtoupper($request->getMethod()) === 'OPTIONS' && $request->getHeaders()->has('Access-Control-Request-Method');
    }

    /**
     * Create a preflight response by adding the corresponding headers
     *
     * @param  HttpRequest  $request
     * @return HttpResponse
     */
    public function createPreflightCorsResponse(HttpRequest $request) {
        $response = new HttpResponse();
        $response->setStatusCode(200);

        $headers = $response->getHeaders();

        $headers->addHeaderLine('Access-Control-Allow-Origin', $this->getAllowedOriginValue($request));
        $headers->addHeaderLine('Access-Control-Allow-Methods', implode(', ', $this->options->getAllowedMethods()));
        $headers->addHeaderLine('Access-Control-Allow-Headers', implode(', ', $this->options->getAllowedHeaders()));
        $headers->addHeaderLine('Access-Control-Max-Age', $this->options->getMaxAge());
        $headers->addHeaderLine('Content-Length', 0);

        if ($this->options->getAllowedCredentials()) {
            $headers->addHeaderLine('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }

    /**
     * Populate a simple CORS response
     *
     * @param  HttpRequest               $request
     * @param  HttpResponse              $response
     * @return HttpResponse
     * @throws DisallowedOriginException If the origin is not allowed
     */
    public function populateCorsResponse(HttpRequest $request, HttpResponse $response) {
        $origin = $this->getAllowedOriginValue($request);

        // If $origin is "null", then it means than the origin is not allowed. As this is
        // a simple request, it is useless to continue the processing as it will be refused
        // by the browser anyway, so we throw an exception
        if ($origin === 'null') {
            throw new DisallowedOriginException(
            sprintf(
                    'The origin "%s" is not authorized', $request->getHeader('Origin')->getFieldValue()
            )
            );
        }

        $headers = $response->getHeaders();
        $headers->addHeaderLine('Access-Control-Allow-Origin', $origin);
        $headers->addHeaderLine('Access-Control-Expose-Headers', implode(', ', $this->options->getExposedHeaders()));

        // If the origin is not "*", we should add the "Origin" value to the "Vary" header
        // See more: http://www.w3.org/TR/cors/#resource-implementation
        if ($origin !== '*') {
            if ($headers->has('Vary')) {
                $varyHeader = $headers->get('Vary');
                $varyValue = $varyHeader->getFieldValue() . ', Origin';

                $headers->removeHeader($varyHeader);
                $headers->addHeaderLine('Vary', $varyValue);
            } else {
                $headers->addHeaderLine('Vary', 'Origin');
            }
        }

        if ($this->options->getAllowedCredentials()) {
            $headers->addHeaderLine('Access-Control-Allow-Credentials', 'true');
        }

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
    protected function getAllowedOriginValue(HttpRequest $request) {
        $allowedOrigins = $this->options->getAllowedOrigins();

        if (in_array('*', $allowedOrigins)) {
            return '*';
        }

        $origin = $request->getHeader('Origin')->getFieldValue();
        foreach ($allowedOrigins as $allowedOrigin) {
            if (fnmatch($allowedOrigin, $origin)) {
                return $origin;
            }
        }

        return 'null';
    }

}
