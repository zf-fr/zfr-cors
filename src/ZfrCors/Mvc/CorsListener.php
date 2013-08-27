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
use ZfrCors\Exception\DisallowedOriginException;
use ZfrCors\Service\CorsService;

/**
 * CorsListener
 *
 * @license MIT
 * @author  Florent Blaison <florent.blaison@gmail.com>
 */
class CorsListener extends AbstractListenerAggregate
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
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, array($this, 'onCors'), -10);
    }

    /**
     * Get the preflight options request authorization
     *
     * @param  MvcEvent $event
     * @return mixed
     */
    public function onCors(MvcEvent $event)
    {
        /** @var $request HttpRequest */
        $request  = $event->getRequest();
        /** @var $response HttpResponse */
        $response = $event->getResponse();

        //try {
        $response = $this->corsService->prePopulateCorsResponse($request, $response);
        /*} catch (DisallowedOriginException $e) {
            $response->setStatusCode(403);
        }*/

        if ($this->corsService->isPreflightRequest($request)) {
            $response = $this->corsService->populateCorsResponse($response);

            return $response;
        }
    }
}
