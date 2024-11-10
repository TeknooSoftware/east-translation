<?php

/*
 * East Translation.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/east/translation Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\Tests\East\Translation\Doctrine\Translatable\Mapping\Driver;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\FileLocator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Translation\Doctrine\Exception\InvalidMappingException;
use Teknoo\East\Translation\Doctrine\Translatable\Mapping\Driver\SimpleXmlFactoryInterface;
use Teknoo\East\Translation\Doctrine\Translatable\Mapping\Driver\Xml;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/east/translation project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 */
#[CoversClass(Xml::class)]
class XmlTest extends TestCase
{
    private ?FileLocator $locator = null;

    private ?SimpleXmlFactoryInterface $simpleXmlFactory = null;

    /**
     * @return FileLocator|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getLocator(): FileLocator
    {
        if (!$this->locator instanceof FileLocator) {
            $this->locator = $this->createMock(FileLocator::class);
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

            $this->simpleXmlFactory->expects($this->any())
                ->method('__invoke')
                ->willReturnCallback(fn ($file) => new \SimpleXMLElement($file, 0, true));
        }

        return $this->simpleXmlFactory;
    }

    public function build(): Xml
    {
        return new Xml($this->getLocator(), $this->getSimpleXmlFactory());
    }

    public function testReadExtendedMetadataFileNotExist()
    {
        $classMeta = $this->createMock(ClassMetadata::class);
        $classMeta->expects($this->any())->method('getName')->willReturn('Foo');

        $this->getLocator()->expects($this->any())->method('findMappingFile')->willReturn('');

        $result = [];

        self::assertInstanceOf(
            Xml::class,
            $this->build()->readExtendedMetadata($classMeta, $result)
        );

        self::assertEmpty($result);
    }

    public function testReadExtendedMetadataFileInvalid()
    {
        $this->expectException(\RuntimeException::class);

        $classMeta = $this->createMock(ClassMetadata::class);
        $classMeta->expects($this->any())->method('getName')->willReturn('Foo');

        $this->getLocator()->expects($this->any())->method('findMappingFile')->willReturn(
            __DIR__.'/support/invalid.xml'
        );

        $result = [];

        self::assertInstanceOf(
            Xml::class,
            $this->build()->readExtendedMetadata($classMeta, $result)
        );

        self::assertEmpty($result);
    }

    public function testReadExtendedMetadataWrongTranslationClass()
    {
        $this->expectException(InvalidMappingException::class);

        $classMeta = $this->createMock(ClassMetadata::class);
        $classMeta->expects($this->any())->method('getName')->willReturn('Foo');

        $this->getLocator()->expects($this->any())->method('findMappingFile')->willReturn(
            __DIR__.'/support/wrong-translation.xml'
        );

        $result = [];

        self::assertInstanceOf(
            Xml::class,
            $this->build()->readExtendedMetadata($classMeta, $result)
        );

        self::assertEmpty($result);
    }

    public function testReadExtendedMetadata()
    {
        $classMeta = $this->createMock(ClassMetadata::class);
        $classMeta->expects($this->any())->method('getName')->willReturn('Foo');

        $this->getLocator()->expects($this->any())->method('findMappingFile')->willReturn(
            __DIR__.'/support/valid.xml'
        );

        $result = [];

        self::assertInstanceOf(
            Xml::class,
            $this->build()->readExtendedMetadata($classMeta, $result)
        );

        self::assertNotEmpty($result);
    }

    public function testReadExtendedMetadataWithUseObjectClass()
    {
        $classMeta = $this->createMock(ClassMetadata::class);
        $classMeta->expects($this->any())->method('getName')->willReturn('Foo');

        $this->getLocator()->expects($this->any())->method('findMappingFile')->willReturn(
            __DIR__.'/support/valid-with-object-class.xml'
        );

        $result = [];

        self::assertInstanceOf(
            Xml::class,
            $this->build()->readExtendedMetadata($classMeta, $result)
        );

        self::assertNotEmpty($result);
        self::assertEquals(
            'Teknoo\East\Translation\Object\Content',
            $result['useObjectClass'],
        );
    }

    public function testReadExtendedMetadataWithoutField()
    {
        $classMeta = $this->createMock(ClassMetadata::class);
        $classMeta->expects($this->any())->method('getName')->willReturn('Foo');

        $this->getLocator()->expects($this->any())->method('findMappingFile')->willReturn(
            __DIR__.'/support/valid-without-field.xml'
        );

        $result = [];

        self::assertInstanceOf(
            Xml::class,
            $this->build()->readExtendedMetadata($classMeta, $result)
        );

        self::assertNotEmpty($result);
        self::assertEmpty($result['fields']);
    }
}
