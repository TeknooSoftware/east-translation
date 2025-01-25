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
 * @link        https://teknoo.software/east/translation Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\Tests\East\Translation\Doctrine\Translatable\Mapping;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactoryInterface;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\FileDriver;
use Doctrine\Persistence\Mapping\Driver\FileLocator;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Teknoo\East\Translation\Doctrine\Exception\InvalidMappingException;
use Teknoo\East\Translation\Doctrine\Object\Content as DoctrineObjectOfTest;
use Teknoo\East\Translation\Doctrine\Translatable\Mapping\Configuration;
use Teknoo\East\Translation\Doctrine\Translatable\Mapping\DriverInterface;
use Teknoo\East\Translation\Doctrine\Translatable\Mapping\DriverFactoryInterface;
use Teknoo\East\Translation\Doctrine\Translatable\Mapping\ExtensionMetadataFactory;
use Teknoo\East\Translation\Doctrine\Translatable\TranslatableListener;
use Teknoo\Tests\East\Translation\Support\Object\ChildOfObjectOfTest;
use Teknoo\Tests\East\Translation\Support\Object\ObjectOfTest;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east/translation project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 */
#[CoversClass(ExtensionMetadataFactory::class)]
class ExtensionMetadataFactoryTest extends TestCase
{
    private ?ObjectManager $objectManager = null;

    private ?ClassMetadataFactoryInterface $classMetadataFactory = null;

    private ?MappingDriver $mappingDriver = null;

    private ?DriverFactoryInterface $driverFactory = null;

    private ?CacheItemPoolInterface $cache = null;

    /**
     * @return ObjectManager|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getObjectManager(): ObjectManager
    {
        if (!$this->objectManager instanceof ObjectManager) {
            $this->objectManager = $this->createMock(ObjectManager::class);
        }

        return $this->objectManager;
    }

    /**
     * @return ClassMetadataFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getClassMetadataFactory(): ClassMetadataFactoryInterface
    {
        if (!$this->classMetadataFactory instanceof ClassMetadataFactoryInterface) {
            $this->classMetadataFactory = $this->createMock(ClassMetadataFactoryInterface::class);
        }

        return $this->classMetadataFactory;
    }

    /**
     * @return MappingDriver|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getMappingDriver(): MappingDriver
    {
        if (!$this->mappingDriver instanceof MappingDriver) {
            $this->mappingDriver = $this->createMock(MappingDriver::class);
        }

        return $this->mappingDriver;
    }

    /**
     * @return MappingDriver|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getCacheMock(): CacheItemPoolInterface&MockObject
    {
        if (!$this->cache instanceof CacheItemPoolInterface) {
            $this->cache = $this->createMock(CacheItemPoolInterface::class);
        }

        return $this->cache;
    }

    /**
     * @return DriverFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getDriverFactory(?string $useObjectClass = null): DriverFactoryInterface
    {
        if (!$this->driverFactory instanceof DriverFactoryInterface) {
            $this->driverFactory = $this->createMock(DriverFactoryInterface::class);

            $this->driverFactory->expects($this->any())
                ->method('__invoke')
                ->willReturnCallback(function () use ($useObjectClass) {
                    $driver = $this->createMock(DriverInterface::class);
                    $driver->expects($this->any())
                        ->method('readExtendedMetadata')
                        ->willReturnCallback(
                            function (ClassMetadata $meta, array &$config) use ($driver, $useObjectClass) {
                                if (!empty($useObjectClass)) {
                                    $config['useObjectClass'] = $useObjectClass;
                                }

                                $config['fields'] = ['foo', 'bar'];
                                $config['fallbacks'] = ['foo', 'bar'];

                                return $driver;
                            }
                        );

                    return $driver;
                });
        }

        return $this->driverFactory;
    }

    public function build(?string $useObjectClass = null):ExtensionMetadataFactory
    {
        return new ExtensionMetadataFactory(
            $this->getObjectManager(),
            $this->getClassMetadataFactory(),
            $this->getMappingDriver(),
            $this->getDriverFactory($useObjectClass),
            $this->getCacheMock(),
        );
    }

    public function testLoadExtensionMetadataSuperClass()
    {
        $meta = new class implements ClassMetadata
        {
            public $isMappedSuperclass = true;

            public function getName() {}
            public function getIdentifier() {}
            public function getReflectionClass() {}
            public function isIdentifier(string $fieldName) {}
            public function hasField(string $fieldName) {}
            public function hasAssociation(string $fieldName) {}
            public function isSingleValuedAssociation(string $fieldName) {}
            public function isCollectionValuedAssociation(string $fieldName) {}
            public function getFieldNames() {}
            public function getIdentifierFieldNames() {}
            public function getAssociationNames() {}
            public function getTypeOfField(string $fieldName) {}
            public function getAssociationTargetClass(string $assocName) {}
            public function isAssociationInverseSide(string $assocName) {}
            public function getAssociationMappedByTargetField(string $assocName) {}
            public function getIdentifierValues(object $object) {}
        };

        $listener = $this->createMock(TranslatableListener::class);
        $listener->expects($this->never())->method('injectConfiguration');

        self::assertInstanceOf(
            ExtensionMetadataFactory::class,
            $this->build()->loadExtensionMetadata($meta, $listener)
        );
    }

    public function testLoadExtensionMetadataMissingDriver()
    {
        $this->expectException(InvalidMappingException::class);

        $meta = new class implements ClassMetadata
        {
            public $isMappedSuperclass = false;

            public function getName() {
                return ObjectOfTest::class;
            }
            public function getIdentifier() {}
            public function getReflectionClass() {}
            public function isIdentifier(string $fieldName) {}
            public function hasField(string $fieldName) {}
            public function hasAssociation(string $fieldName) {}
            public function isSingleValuedAssociation(string $fieldName) {}
            public function isCollectionValuedAssociation(string $fieldName) {}
            public function getFieldNames() {}
            public function getIdentifierFieldNames() {}
            public function getAssociationNames() {}
            public function getTypeOfField(string $fieldName) {}
            public function getAssociationTargetClass(string $assocName) {}
            public function isAssociationInverseSide(string $assocName) {}
            public function getAssociationMappedByTargetField(string $assocName) {}
            public function getIdentifierValues(object $object) {}
        };

        $listener = $this->createMock(TranslatableListener::class);
        $listener->expects($this->never())->method('injectConfiguration');

        self::assertInstanceOf(
            ExtensionMetadataFactory::class,
            $this->build()->loadExtensionMetadata($meta, $listener)
        );
    }

    public function testLoadExtensionMetadataWithFileDriver()
    {

        $meta = new class implements ClassMetadata
        {
            public $isMappedSuperclass = false;

            public function getName() {
                return ObjectOfTest::class;
            }
            public function getIdentifier() {}
            public function getReflectionClass() {}
            public function isIdentifier(string $fieldName) {}
            public function hasField(string $fieldName) {}
            public function hasAssociation(string $fieldName) {}
            public function isSingleValuedAssociation(string $fieldName) {}
            public function isCollectionValuedAssociation(string $fieldName) {}
            public function getFieldNames() {}
            public function getIdentifierFieldNames() {}
            public function getAssociationNames() {}
            public function getTypeOfField(string $fieldName) {}
            public function getAssociationTargetClass(string $assocName) {}
            public function isAssociationInverseSide(string $assocName) {}
            public function getAssociationMappedByTargetField(string $assocName) {}
            public function getIdentifierValues(object $object) {}
        };

        $this->mappingDriver = $this->createMock(FileDriver::class);

        $locator = $this->createMock(FileLocator::class);
        $this->mappingDriver->expects($this->any())->method('getLocator')->willReturn($locator);

        $listener = $this->createMock(TranslatableListener::class);
        $listener->expects($this->once())->method('injectConfiguration');

        self::assertInstanceOf(
            ExtensionMetadataFactory::class,
            $this->build()->loadExtensionMetadata($meta, $listener)
        );
    }

    public function testLoadExtensionMetadataWithFileDriverWithUseClassAlreadySet()
    {

        $meta = new class implements ClassMetadata
        {
            public $isMappedSuperclass = false;

            public function getName() {
                return ObjectOfTest::class;
            }
            public function getIdentifier() {}
            public function getReflectionClass() {}
            public function isIdentifier(string $fieldName) {}
            public function hasField(string $fieldName) {}
            public function hasAssociation(string $fieldName) {}
            public function isSingleValuedAssociation(string $fieldName) {}
            public function isCollectionValuedAssociation(string $fieldName) {}
            public function getFieldNames() {}
            public function getIdentifierFieldNames() {}
            public function getAssociationNames() {}
            public function getTypeOfField(string $fieldName) {}
            public function getAssociationTargetClass(string $assocName) {}
            public function isAssociationInverseSide(string $assocName) {}
            public function getAssociationMappedByTargetField(string $assocName) {}
            public function getIdentifierValues(object $object) {}
        };

        $this->mappingDriver = $this->createMock(FileDriver::class);

        $locator = $this->createMock(FileLocator::class);
        $this->mappingDriver->expects($this->any())->method('getLocator')->willReturn($locator);

        $listener = $this->createMock(TranslatableListener::class);
        $listener->expects($this->once())
            ->method('injectConfiguration')
            ->willReturnCallback(
                function ($metadata, $config) use ($listener) {
                    self::assertEquals(
                        'foo',
                        $config['useObjectClass'],
                    );
                    return $listener;
                }
            );

        self::assertInstanceOf(
            ExtensionMetadataFactory::class,
            $this->build('foo')->loadExtensionMetadata($meta, $listener)
        );
    }

    public function testLoadExtensionMetadataWithMappingDriverChain()
    {

        $meta = new class implements ClassMetadata
        {
            public $isMappedSuperclass = false;

            public function getName() {
                return ObjectOfTest::class;
            }
            public function getIdentifier() {}
            public function getReflectionClass() {}
            public function isIdentifier(string $fieldName) {}
            public function hasField(string $fieldName) {}
            public function hasAssociation(string $fieldName) {}
            public function isSingleValuedAssociation(string $fieldName) {}
            public function isCollectionValuedAssociation(string $fieldName) {}
            public function getFieldNames() {}
            public function getIdentifierFieldNames() {}
            public function getAssociationNames() {}
            public function getTypeOfField(string $fieldName) {}
            public function getAssociationTargetClass(string $assocName) {}
            public function isAssociationInverseSide(string $assocName) {}
            public function getAssociationMappedByTargetField(string $assocName) {}
            public function getIdentifierValues(object $object) {}
        };

        $this->mappingDriver = $this->createMock(MappingDriverChain::class);
        $fileDriver = $this->createMock(FileDriver::class);

        $this->mappingDriver->expects($this->any())->method('getDrivers')->willReturn([
            $this->createMock(MappingDriver::class),
            $this->createMock(MappingDriver::class),
            $fileDriver
        ]);

        $locator = $this->createMock(FileLocator::class);
        $fileDriver->expects($this->any())->method('getLocator')->willReturn($locator);

        $listener = $this->createMock(TranslatableListener::class);
        $listener->expects($this->once())->method('injectConfiguration');

        self::assertInstanceOf(
            ExtensionMetadataFactory::class,
            $this->build()->loadExtensionMetadata($meta, $listener)
        );
    }

    public function testLoadExtensionMetadataWithFileDriverWithParent()
    {
        $meta = new class implements ClassMetadata
        {
            public $isMappedSuperclass = false;

            public function getName() {
                return ChildOfObjectOfTest::class;
            }
            public function getIdentifier() {}
            public function getReflectionClass() {}
            public function isIdentifier(string $fieldName) {}
            public function hasField(string $fieldName) {}
            public function hasAssociation(string $fieldName) {}
            public function isSingleValuedAssociation(string $fieldName) {}
            public function isCollectionValuedAssociation(string $fieldName) {}
            public function getFieldNames() {}
            public function getIdentifierFieldNames() {}
            public function getAssociationNames() {}
            public function getTypeOfField(string $fieldName) {}
            public function getAssociationTargetClass(string $assocName) {}
            public function isAssociationInverseSide(string $assocName) {}
            public function getAssociationMappedByTargetField(string $assocName) {}
            public function getIdentifierValues(object $object) {}
        };

        $this->mappingDriver = $this->createMock(FileDriver::class);

        $locator = $this->createMock(FileLocator::class);
        $this->mappingDriver->expects($this->any())->method('getLocator')->willReturn($locator);

        $listener = $this->createMock(TranslatableListener::class);
        $listener->expects($this->once())->method('injectConfiguration');

        $this->getClassMetadataFactory()
            ->expects($this->any())
            ->method('hasMetadataFor')
            ->willReturn(true);

        $this->getObjectManager()
            ->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($this->createMock(ClassMetadata::class));

        self::assertInstanceOf(
            ExtensionMetadataFactory::class,
            $this->build()->loadExtensionMetadata($meta, $listener)
        );
    }

    public function testLoadExtensionMetadataWitchCacheEmpty()
    {
        $meta = new class implements ClassMetadata
        {
            public $isMappedSuperclass = false;

            public function getName() {
                return ChildOfObjectOfTest::class;
            }
            public function getIdentifier() {}
            public function getReflectionClass() {}
            public function isIdentifier(string $fieldName) {}
            public function hasField(string $fieldName) {}
            public function hasAssociation(string $fieldName) {}
            public function isSingleValuedAssociation(string $fieldName) {}
            public function isCollectionValuedAssociation(string $fieldName) {}
            public function getFieldNames() {}
            public function getIdentifierFieldNames() {}
            public function getAssociationNames() {}
            public function getTypeOfField(string $fieldName) {}
            public function getAssociationTargetClass(string $assocName) {}
            public function isAssociationInverseSide(string $assocName) {}
            public function getAssociationMappedByTargetField(string $assocName) {}
            public function getIdentifierValues(object $object) {}
        };

        $this->mappingDriver = $this->createMock(FileDriver::class);

        $locator = $this->createMock(FileLocator::class);
        $this->mappingDriver->expects($this->any())->method('getLocator')->willReturn($locator);

        $listener = $this->createMock(TranslatableListener::class);
        $listener->expects($this->once())->method('injectConfiguration');

        $this->getCacheMock()->expects($this->any())->method('hasItem')->willReturn(false);

        self::assertInstanceOf(
            ExtensionMetadataFactory::class,
            $this->build()->loadExtensionMetadata($meta, $listener)
        );
    }

    public function testLoadExtensionMetadataWitchCacheNotEmpty()
    {
        $meta = new class implements ClassMetadata
        {
            public $isMappedSuperclass = false;

            public function getName() {
                return DoctrineObjectOfTest::class;
            }
            public function getIdentifier() {}
            public function getReflectionClass() {}
            public function isIdentifier(string $fieldName) {}
            public function hasField(string $fieldName) {}
            public function hasAssociation(string $fieldName) {}
            public function isSingleValuedAssociation(string $fieldName) {}
            public function isCollectionValuedAssociation(string $fieldName) {}
            public function getFieldNames() {}
            public function getIdentifierFieldNames() {}
            public function getAssociationNames() {}
            public function getTypeOfField(string $fieldName) {}
            public function getAssociationTargetClass(string $assocName) {}
            public function isAssociationInverseSide(string $assocName) {}
            public function getAssociationMappedByTargetField(string $assocName) {}
            public function getIdentifierValues(object $object) {}
        };

        $this->mappingDriver = $this->createMock(FileDriver::class);

        $locator = $this->createMock(FileLocator::class);
        $this->mappingDriver->expects($this->never())->method('getLocator')->willReturn($locator);

        $listener = $this->createMock(TranslatableListener::class);
        $listener->expects($this->once())->method('injectConfiguration');

        $this->getCacheMock()->expects($this->any())->method('hasItem')->willReturn(true);
        $this->getCacheMock()->expects($this->any())->method('getItem')->willReturn(
            new Configuration('foo', [])
        );

        self::assertInstanceOf(
            ExtensionMetadataFactory::class,
            $this->build()->loadExtensionMetadata($meta, $listener)
        );
    }

    public function testSetCache()
    {
        self::assertInstanceOf(
            ExtensionMetadataFactory::class,
            $this->build()->setCache(
                $this->createMock(CacheItemPoolInterface::class),
            ),
        );
    }
}
