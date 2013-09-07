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

namespace ZfrCorsTest;

use PHPUnit_Framework_TestCase;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceManager;
use ZfrCors\Module;
use ZfrCorsTest\Util\ServiceManagerFactory;

/**
 * Tests for {@see \ZfrCors\Module}
 *
 * @license MIT
 * @author  Marco Pivetta <ocramius@gmail.com>
 */
class ModuleTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers \ZfrCors\Module::getConfig
     */
    public function testGetConfig()
    {
        $module = new Module();

        $this->assertInternalType('array', $module->getConfig());
        $this->assertSame($module->getConfig(), unserialize(serialize($module->getConfig())), 'Config is serializable');
    }

    /**
     * @covers \ZfrCors\Module::onBootstrap
     */
    public function testAssertListenerIsCorrectlyRegistered()
    {
        $this->markTestIncomplete('To be done');

        /*$module = new Module();

        $serviceManager = ServiceManagerFactory::getServiceManager();

        $application = new Application(array(), $serviceManager);
        $event       = new MvcEvent();
        $event->setTarget($application);

        $module->onBootstrap($event);

        $eventManager = $serviceManager->get('EventManager');*/
    }
}
