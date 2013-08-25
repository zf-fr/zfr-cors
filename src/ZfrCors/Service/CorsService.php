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

use ZfrCors\Exception\DisallowedOriginException;
use ZfrCors\Options\CorsOptions;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;

/**
 * CorsService
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
     * @param HttpRequest $request
     * @return bool
     */
    public function isCorsRequest(HttpRequest $request)
    {
        return $request->getHeaders()->has('Origin');
    }

    /**
     * @param HttpRequest $request
     * @return bool
     */
    public function isOriginAllowed(HttpRequest $request)
    {
        return $this->isCorsRequest($request)
            && in_array($request->getHeader('Origin')->getFieldValue(), $this->options->getOrigins());
    }

    /**
     * @param HttpRequest $request
     * @return bool
     */
    public function isPreflightRequest(HttpRequest $request)
    {
        return $request->getMethod() === 'OPTIONS'
            && $request->getHeaders()->has('Access-Control-Request-Method');
    }

    /**
     * @param HttpRequest $request
     * @param HttpResponse $response
     * @return HttpResponse
     * @throws \ZfrCors\Exception\DisallowedOriginException
     */
    public function prePopulateCorsResponse(HttpRequest $request, HttpResponse $response)
    {
        if ($this->isOriginAllowed($request)) {
            $response->getHeaders()->addHeaderLine(
                'Access-Control-Allow-Origin',
                $request->getHeader('Origin')->getFieldValue()
            );
        } else {
            throw new DisallowedOriginException();
        }

        return $response;
    }

    /**
     * @param HttpResponse $response
     * @return HttpResponse
     */
    public function populateCorsResponse(HttpResponse $response)
    {
        $response->setStatusCode(204);
        $headers = $response->getHeaders();
        $headers->addHeaderLine('Access-Control-Allow-Methods', implode(',', $this->options->getAllowedMethods()));
        $headers->addHeaderLine('Access-Control-Allow-Headers', implode(',', $this->options->getAllowedHeaders()));
        $headers->addHeaderLine('Access-Control-Max-Age', $this->options->getMaxAge());
        $headers->addHeaderLine('Content-Length', 0);
        if ($this->options->getAllowedCredentials()) {
            $headers->addHeaderLine('Access-Control-Allow-Credentials', $this->options->getAllowedCredentials());
        }

        return $response;
    }
}