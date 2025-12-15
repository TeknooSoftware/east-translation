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

namespace Teknoo\Tests\East\Translation\Doctrine\Translatable\Mapping\Driver;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\FileLocator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Translation\Doctrine\Exception\InvalidMappingException;
use Teknoo\East\Translation\Doctrine\Translatable\Mapping\Driver\SimpleXmlFactoryInterface;
use Teknoo\East\Translation\Doctrine\Translatable\Mapping\Driver\Xml;

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
#[CoversClass(Xml::class)]
class XmlTest extends TestCase
{
    private (FileLocator&Stub)|(FileLocator&MockObject)|null $locator = null;

    private ?SimpleXmlFactoryInterface $simpleXmlFactory = null;

    public function getLocator(bool $stub = false): (FileLocator&Stub)|(FileLocator&MockObject)
    {
        if (!$this->locator instanceof FileLocator) {
            if ($stub) {
                $this->locator = $this->createStub(FileLocator::class);
            } else {
                $this->locator = $this->createMock(FileLocator::class);
            }
        }

        return $this->locator;
    }

    /**
     * @return SimpleXmlFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getSimpleXmlFactory(): SimpleXmlFactoryInterface
    {
        if (!$this->simpleXmlFactory instanceof SimpleXmlFactoryInterface) {
            $this->simpleXmlFactory = $this->createMock(SimpleXmlFactoryInterface::class);

            $this->simpleXmlFactory
                ->method('__invoke')
                ->willReturnCallback(fn (string $file): \SimpleXMLElement => new \SimpleXMLElement($file, 0, true));
        }

        return $this->simpleXmlFactory;
    }

    public function build(): Xml
    {
        return new Xml($this->getLocator(true), $this->getSimpleXmlFactory());
    }

    public function testReadExtendedMetadataFileNotExist(): void
    {
        $classMeta = $this->createMock(ClassMetadata::class);
        $classMeta->method('getName')->willReturn('Foo');

        $this->getLocator()->method('findMappingFile')->willReturn('');

        $result = [];

        $this->assertInstanceOf(Xml::class, $this->build()->readExtendedMetadata($classMeta, $result));

        $this->assertEmpty($result);
    }

    public function testReadExtendedMetadataFileInvalid(): void
    {
        $this->expectException(\RuntimeException::class);

        $classMeta = $this->createMock(ClassMetadata::class);
        $classMeta->method('getName')->willReturn('Foo');

        $this->getLocator()->method('findMappingFile')->willReturn(
            __DIR__.'/support/invalid.xml'
        );

        $result = [];

        $this->assertInstanceOf(Xml::class, $this->build()->readExtendedMetadata($classMeta, $result));

        $this->assertEmpty($result);
    }

    public function testReadExtendedMetadataWrongTranslationClass(): void
    {
        $this->expectException(InvalidMappingException::class);

        $classMeta = $this->createMock(ClassMetadata::class);
        $classMeta->method('getName')->willReturn('Foo');

        $this->getLocator()->method('findMappingFile')->willReturn(
            __DIR__.'/support/wrong-translation.xml'
        );

        $result = [];

        $this->assertInstanceOf(Xml::class, $this->build()->readExtendedMetadata($classMeta, $result));

        $this->assertEmpty($result);
    }

    public function testReadExtendedMetadata(): void
    {
        $classMeta = $this->createMock(ClassMetadata::class);
        $classMeta->method('getName')->willReturn('Foo');

        $this->getLocator()->method('findMappingFile')->willReturn(
            __DIR__.'/support/valid.xml'
        );

        $result = [];

        $this->assertInstanceOf(Xml::class, $this->build()->readExtendedMetadata($classMeta, $result));

        $this->assertNotEmpty($result);
    }

    public function testReadExtendedMetadataWithUseObjectClass(): void
    {
        $classMeta = $this->createMock(ClassMetadata::class);
        $classMeta->method('getName')->willReturn('Foo');

        $this->getLocator()->method('findMappingFile')->willReturn(
            __DIR__.'/support/valid-with-object-class.xml'
        );

        $result = [];

        $this->assertInstanceOf(Xml::class, $this->build()->readExtendedMetadata($classMeta, $result));

        $this->assertNotEmpty($result);
        $this->assertEquals('Teknoo\East\Translation\Object\Content', $result['useObjectClass']);
    }

    public function testReadExtendedMetadataWithoutField(): void
    {
        $classMeta = $this->createMock(ClassMetadata::class);
        $classMeta->method('getName')->willReturn('Foo');

        $this->getLocator()->method('findMappingFile')->willReturn(
            __DIR__.'/support/valid-without-field.xml'
        );

        $result = [];

        $this->assertInstanceOf(Xml::class, $this->build()->readExtendedMetadata($classMeta, $result));

        $this->assertNotEmpty($result);
        $this->assertEmpty($result['fields']);
    }
}
