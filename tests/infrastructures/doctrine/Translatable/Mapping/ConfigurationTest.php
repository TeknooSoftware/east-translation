<?php

/*
 * East Translation.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east/translation Project website
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\Tests\East\Translation\Doctrine\Translatable\Mapping;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Translation\Doctrine\Translatable\Mapping\Configuration;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east/translation project website
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 */
#[CoversClass(Configuration::class)]
class ConfigurationTest extends TestCase
{
    public function build(): Configuration
    {
        return new Configuration(
            'fooBar',
            [
                'foo' => 'bar',
            ],
            false
        );
    }

    public function testGetKey(): void
    {
        $this->assertEquals('fooBar', $this->build()->getKey());
    }

    public function testGet(): void
    {
        $this->assertEquals([
            'foo' => 'bar',
        ], $this->build()->get());
    }

    public function testIsHit(): void
    {
        $this->assertEquals(false, $this->build()->isHit());
        $this->assertEquals(true, new Configuration(
            'fooBar',
            [
                'foo' => 'bar',
            ],
            true
        )->isHit());
    }

    public function testSet(): void
    {
        $configuration = $this->build();

        $this->assertInstanceOf(Configuration::class, $configuration->set(
            [
                'bar' => 'foo',
            ],
        ));

        $this->assertEquals([
            'bar' => 'foo',
        ], $configuration->get());
    }

    public function testExpiresAt(): void
    {
        $configuration = $this->build();
        $this->assertNull($configuration->getExpiry());

        $this->assertInstanceOf(Configuration::class, $configuration->expiresAt(
            $date = new \DateTime('2022-06-27')
        ));

        $this->assertIsFloat($configuration->getExpiry());

        $this->assertInstanceOf(Configuration::class, $configuration->expiresAt(null));

        $this->assertNull($configuration->getExpiry());
    }

    public function testExpiresAfter(): void
    {
        $configuration = $this->build();
        $this->assertNull($configuration->getExpiry());

        $this->assertInstanceOf(Configuration::class, $configuration->expiresAfter(
            $intervale = new \DateInterval('P1W2D')
        ));

        $this->assertIsFloat($configuration->getExpiry());

        $this->assertGreaterThan((float) new \DateTime('now')->format('U.u'), $configuration->getExpiry());

        $this->assertInstanceOf(Configuration::class, $configuration->expiresAfter(null));

        $this->assertNull($configuration->getExpiry());
        $this->assertInstanceOf(Configuration::class, $configuration->expiresAfter(1234));

        $this->assertIsFloat($configuration->getExpiry());

        $this->assertGreaterThan((float) new \DateTime('now')->format('U.u'), $configuration->getExpiry());
    }
}
