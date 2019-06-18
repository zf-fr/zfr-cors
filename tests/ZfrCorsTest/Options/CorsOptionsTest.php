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

use PHPUnit\Framework\TestCase as TestCase;
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

        $this->assertEquals([], $options->getAllowedOrigins(), 'No origin are allowed');
        $this->assertEquals([], $options->getAllowedMethods(), 'No methods are allowed');
        $this->assertEquals([], $options->getAllowedHeaders(), 'No headers are allowed');
        $this->assertEquals(0, $options->getMaxAge(), 'Preflight request cannot be cached');
        $this->assertEquals([], $options->getExposedHeaders(), 'No headers are exposed to the browser');
        $this->assertFalse($options->getAllowedCredentials(), 'Cookies are not allowed');
    }

    public function testCanModifyOptions()
    {
        $options = new CorsOptions();

        $options->setAllowedOrigins(['http://example1.com', 'http://example2.com']);
        $this->assertEquals(['http://example1.com', 'http://example2.com'], $options->getAllowedOrigins());

        $options->setAllowedMethods(['POST', 'GET']);
        $this->assertEquals(['POST', 'GET'], $options->getAllowedMethods());

        $options->setAllowedHeaders(['Content-Type']);
        $this->assertEquals(['Content-Type'], $options->getAllowedHeaders());

        $options->setMaxAge(30);
        $this->assertEquals(30, $options->getMaxAge());

        $options->setExposedHeaders(['Location', 'X-Custom-Header']);
        $this->assertEquals(['Location', 'X-Custom-Header'], $options->getExposedHeaders());

        $options->setAllowedCredentials(true);
        $this->assertTrue($options->getAllowedCredentials());
    }

    public function testNormalizeHttpMethods()
    {
        $options = new CorsOptions();

        $options->setAllowedMethods(['post', 'GeT']);
        $this->assertEquals(['POST', 'GET'], $options->getAllowedMethods());
    }
}
