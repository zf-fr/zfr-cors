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

namespace ZfrCorsTest\Options;

use PHPUnit_Framework_TestCase as TestCase;
use ZfrCors\Options\CorsOptions;

/**
 * Integration tests for {@see \ZfrCors\Service\CorsService}
 *
 * @author Michaël Gallego <mic.gallego@gmail.com>
 *
 * @covers \ZfrCors\Options\CorsOptions
 * @group Coverage
 */
class CorsOptionsTest extends TestCase
{
    public function testCorsOptionsAreSecuredByDefault()
    {
        $options = new CorsOptions();

        $this->assertEquals(array(), $options->getAllowedOrigins(), 'No origin are allowed by array');
        $this->assertEquals("", $options->getAllowedOriginsRegex(), 'No origins are allowed by regex');
        $this->assertEquals(array(), $options->getAllowedMethods(), 'No methods are allowed');
        $this->assertEquals(array(), $options->getAllowedHeaders(), 'No headers are allowed');
        $this->assertEquals(0, $options->getMaxAge(), 'Preflight request cannot be cached');
        $this->assertEquals(array(), $options->getExposedHeaders(), 'No headers are exposed to the browser');
        $this->assertFalse($options->getAllowedCredentials(), 'Cookies are not allowed');
    }

    public function testCanModifyOptions()
    {
        $options = new CorsOptions();

        $options->setAllowedOrigins(array('http://example1.com', 'http://example2.com'));
        $this->assertEquals(array('http://example1.com', 'http://example2.com'), $options->getAllowedOrigins());

        $options->setAllowedOriginsRegex('|http://(\w+\.)?example\.com|');
        $this->assertEquals('|http://(\w+\.)?example\.com|', $options->getAllowedOriginsRegex());

        $options->setAllowedMethods(array('POST', 'GET'));
        $this->assertEquals(array('POST', 'GET'), $options->getAllowedMethods());

        $options->setAllowedHeaders(array('Content-Type'));
        $this->assertEquals(array('Content-Type'), $options->getAllowedHeaders());

        $options->setMaxAge(30);
        $this->assertEquals(30, $options->getMaxAge());

        $options->setExposedHeaders(array('Location', 'X-Custom-Header'));
        $this->assertEquals(array('Location', 'X-Custom-Header'), $options->getExposedHeaders());

        $options->setAllowedCredentials(true);
        $this->assertTrue($options->getAllowedCredentials());
    }

    public function testNormalizeHttpMethods()
    {
        $options = new CorsOptions();

        $options->setAllowedMethods(array('post', 'GeT'));
        $this->assertEquals(array('POST', 'GET'), $options->getAllowedMethods());
    }
}
