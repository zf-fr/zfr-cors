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

namespace ZfrCors\Mvc;

use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Http\Response as HttpResponse;
use Zend\Http\Request as HttpRequest;
use Zend\Mvc\MvcEvent;
use ZfrCors\Exception\DisallowedMethodException;
use ZfrCors\Exception\DisallowedOriginException;
use ZfrCors\Service\CorsService;

/**
 * DisallowedCorsRequest
 *
 * @license MIT
 * @author  Florent Blaison <florent.blaison@gmail.com>
 */
class DisallowedCorsRequestListener extends AbstractListenerAggregate
{
    /**
     * @var CorsService
     */
    protected $corsService;

    /**
     * @param CorsService $corsService
     */
    public function __construct(CorsService $corsService)
    {
        $this->corsService = $corsService;
    }

    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'onDisallowedCorsRequest'), 1000);
    }

    /**
     * Capture DisallowedOriginException
     *
     * @param  MvcEvent $event
     * @return mixed
     */
    public function onDisallowedCorsRequest(MvcEvent $event)
    {
        /** @var $response HttpResponse */
        $response  = $event->getResponse();
        $exception = $event->getParam('exception');

        // We just deal with our Http error codes here !
        if (!$response instanceof HttpResponse
            || !$exception instanceof DisallowedOriginException
            || !$exception instanceof DisallowedMethodException
        ) {
            return;
        }

        $response = $this->corsService->populateForbiddenCorsResponse($event->getRequest(), $response);
        $response->setContent($exception->getMessage());

        $event->setResponse($response);
        $event->setResult($response);
    }
}
