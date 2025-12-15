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

use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactoryInterface;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\FileDriver;
use Doctrine\Persistence\Mapping\Driver\FileLocator;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 */
#[CoversClass(ExtensionMetadataFactory::class)]
class ExtensionMetadataFactoryTest extends TestCase
{
    private (ObjectManager&Stub)|(ObjectManager&MockObject)|null $objectManager = null;

    private (ClassMetadataFactoryInterface&Stub)|(ClassMetadataFactoryInterface&MockObject)|null $classMetadataFactory = null;

    private (MappingDriver&Stub)|(MappingDriver&MockObject)|null $mappingDriver = null;

    private ?DriverFactoryInterface $driverFactory = null;

    private (CacheItemPoolInterface&Stub)|(CacheItemPoolInterface&MockObject)|null $cache = null;

    public function getObjectManager(bool $stub = false): (ObjectManager&Stub)|(ObjectManager&MockObject)
    {
        if (!$this->objectManager instanceof ObjectManager) {
            if ($stub) {
                $this->objectManager = $this->createStub(ObjectManager::class);
            } else {
                $this->objectManager = $this->createMock(ObjectManager::class);
            }
        }

        return $this->objectManager;
    }

    public function getClassMetadataFactory(bool $stub = false): (ClassMetadataFactoryInterface&Stub)|(ClassMetadataFactoryInterface&MockObject)
    {
        if (!$this->classMetadataFactory instanceof ClassMetadataFactoryInterface) {
            if ($stub) {
                $this->classMetadataFactory = $this->createStub(ClassMetadataFactoryInterface::class);
            } else {
                $this->classMetadataFactory = $this->createMock(ClassMetadataFactoryInterface::class);
            }
        }

        return $this->classMetadataFactory;
    }

    public function getMappingDriver(bool $stub = false): (MappingDriver&Stub)|(MappingDriver&MockObject)
    {
        if (!$this->mappingDriver instanceof MappingDriver) {
            if ($stub) {
                $this->mappingDriver = $this->createStub(MappingDriver::class);
            } else {
                $this->mappingDriver = $this->createMock(MappingDriver::class);
            }
        }

        return $this->mappingDriver;
    }

    public function getCacheMock(bool $stub = false): (CacheItemPoolInterface&Stub)|(CacheItemPoolInterface&MockObject)
    {
        if (!$this->cache instanceof CacheItemPoolInterface) {
            if ($stub) {
                $this->cache = $this->createStub(CacheItemPoolInterface::class);
            } else {
                $this->cache = $this->createMock(CacheItemPoolInterface::class);
            }
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

            $this->driverFactory
                ->method('__invoke')
                ->willReturnCallback(function () use ($useObjectClass): \PHPUnit\Framework\MockObject\MockObject {
                    $driver = $this->createMock(DriverInterface::class);
                    $driver
                        ->method('readExtendedMetadata')
                        ->willReturnCallback(
                            function (ClassMetadata $meta, array &$config) use ($driver, $useObjectClass): \PHPUnit\Framework\MockObject\MockObject {
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

    public function build(?string $useObjectClass = null): ExtensionMetadataFactory
    {
        return new ExtensionMetadataFactory(
            $this->getObjectManager(true),
            $this->getClassMetadataFactory(true),
            $this->getMappingDriver(true),
            $this->getDriverFactory($useObjectClass),
            $this->getCacheMock(true),
        );
    }

    public function testLoadExtensionMetadataSuperClass(): void
    {
        $meta = new class () implements ClassMetadata {
            public $isMappedSuperclass = true;

            public function getName(): string
            {
            }

            public function getIdentifier(): array
            {
            }

            public function getReflectionClass(): \ReflectionClass
            {
            }

            public function isIdentifier(string $fieldName): bool
            {
            }

            public function hasField(string $fieldName): bool
            {
            }

            public function hasAssociation(string $fieldName): bool
            {
            }

            public function isSingleValuedAssociation(string $fieldName): bool
            {
            }

            public function isCollectionValuedAssociation(string $fieldName): bool
            {
            }

            public function getFieldNames(): array
            {
            }

            public function getIdentifierFieldNames(): array
            {
            }

            public function getAssociationNames(): array
            {
            }

            public function getTypeOfField(string $fieldName): ?string
            {
            }

            public function getAssociationTargetClass(string $assocName): ?string
            {
            }

            public function isAssociationInverseSide(string $assocName): bool
            {
            }

            public function getAssociationMappedByTargetField(string $assocName): string
            {
            }

            public function getIdentifierValues(object $object): array
            {
            }
        };

        $listener = $this->createMock(TranslatableListener::class);
        $listener->expects($this->never())->method('injectConfiguration');

        $this->assertInstanceOf(ExtensionMetadataFactory::class, $this->build()->loadExtensionMetadata($meta, $listener));
    }

    public function testLoadExtensionMetadataMissingDriver(): void
    {
        $this->expectException(InvalidMappingException::class);

        $meta = new class () implements ClassMetadata {
            public $isMappedSuperclass = false;

            public function getName(): string
            {
                return ObjectOfTest::class;
            }

            public function getIdentifier(): array
            {
            }

            public function getReflectionClass(): \ReflectionClass
            {
            }

            public function isIdentifier(string $fieldName): bool
            {
            }

            public function hasField(string $fieldName): bool
            {
            }

            public function hasAssociation(string $fieldName): bool
            {
            }

            public function isSingleValuedAssociation(string $fieldName): bool
            {
            }

            public function isCollectionValuedAssociation(string $fieldName): bool
            {
            }

            public function getFieldNames(): array
            {
            }

            public function getIdentifierFieldNames(): array
            {
            }

            public function getAssociationNames(): array
            {
            }

            public function getTypeOfField(string $fieldName): ?string
            {
            }

            public function getAssociationTargetClass(string $assocName): ?string
            {
            }

            public function isAssociationInverseSide(string $assocName): bool
            {
            }

            public function getAssociationMappedByTargetField(string $assocName): string
            {
            }

            public function getIdentifierValues(object $object): array
            {
            }
        };

        $listener = $this->createMock(TranslatableListener::class);
        $listener->expects($this->never())->method('injectConfiguration');

        $this->assertInstanceOf(ExtensionMetadataFactory::class, $this->build()->loadExtensionMetadata($meta, $listener));
    }

    public function testLoadExtensionMetadataWithFileDriver(): void
    {

        $meta = new class () implements ClassMetadata {
            public $isMappedSuperclass = false;

            public function getName(): string
            {
                return ObjectOfTest::class;
            }

            public function getIdentifier(): array
            {
            }

            public function getReflectionClass(): \ReflectionClass
            {
            }

            public function isIdentifier(string $fieldName): bool
            {
            }

            public function hasField(string $fieldName): bool
            {
            }

            public function hasAssociation(string $fieldName): bool
            {
            }

            public function isSingleValuedAssociation(string $fieldName): bool
            {
            }

            public function isCollectionValuedAssociation(string $fieldName): bool
            {
            }

            public function getFieldNames(): array
            {
            }

            public function getIdentifierFieldNames(): array
            {
            }

            public function getAssociationNames(): array
            {
            }

            public function getTypeOfField(string $fieldName): ?string
            {
            }

            public function getAssociationTargetClass(string $assocName): ?string
            {
            }

            public function isAssociationInverseSide(string $assocName): bool
            {
            }

            public function getAssociationMappedByTargetField(string $assocName): string
            {
            }

            public function getIdentifierValues(object $object): array
            {
            }
        };

        $this->mappingDriver = $this->createMock(FileDriver::class);

        $locator = $this->createMock(FileLocator::class);
        $this->mappingDriver->method('getLocator')->willReturn($locator);

        $listener = $this->createMock(TranslatableListener::class);
        $listener->expects($this->once())->method('injectConfiguration');

        $this->assertInstanceOf(ExtensionMetadataFactory::class, $this->build()->loadExtensionMetadata($meta, $listener));
    }

    public function testLoadExtensionMetadataWithFileDriverWithUseClassAlreadySet(): void
    {

        $meta = new class () implements ClassMetadata {
            public $isMappedSuperclass = false;

            public function getName(): string
            {
                return ObjectOfTest::class;
            }

            public function getIdentifier(): array
            {
            }

            public function getReflectionClass(): \ReflectionClass
            {
            }

            public function isIdentifier(string $fieldName): bool
            {
            }

            public function hasField(string $fieldName): bool
            {
            }

            public function hasAssociation(string $fieldName): bool
            {
            }

            public function isSingleValuedAssociation(string $fieldName): bool
            {
            }

            public function isCollectionValuedAssociation(string $fieldName): bool
            {
            }

            public function getFieldNames(): array
            {
            }

            public function getIdentifierFieldNames(): array
            {
            }

            public function getAssociationNames(): array
            {
            }

            public function getTypeOfField(string $fieldName): ?string
            {
            }

            public function getAssociationTargetClass(string $assocName): ?string
            {
            }

            public function isAssociationInverseSide(string $assocName): bool
            {
            }

            public function getAssociationMappedByTargetField(string $assocName): string
            {
            }

            public function getIdentifierValues(object $object): array
            {
            }
        };

        $this->mappingDriver = $this->createMock(FileDriver::class);

        $locator = $this->createMock(FileLocator::class);
        $this->mappingDriver->method('getLocator')->willReturn($locator);

        $listener = $this->createMock(TranslatableListener::class);
        $listener->expects($this->once())
            ->method('injectConfiguration')
            ->willReturnCallback(
                function ($metadata, array $config) use ($listener): \PHPUnit\Framework\MockObject\MockObject {
                    $this->assertEquals('foo', $config['useObjectClass']);
                    return $listener;
                }
            );

        $this->assertInstanceOf(ExtensionMetadataFactory::class, $this->build('foo')->loadExtensionMetadata($meta, $listener));
    }

    public function testLoadExtensionMetadataWithMappingDriverChain(): void
    {

        $meta = new class () implements ClassMetadata {
            public $isMappedSuperclass = false;

            public function getName(): string
            {
                return ObjectOfTest::class;
            }

            public function getIdentifier(): array
            {
            }

            public function getReflectionClass(): \ReflectionClass
            {
            }

            public function isIdentifier(string $fieldName): bool
            {
            }

            public function hasField(string $fieldName): bool
            {
            }

            public function hasAssociation(string $fieldName): bool
            {
            }

            public function isSingleValuedAssociation(string $fieldName): bool
            {
            }

            public function isCollectionValuedAssociation(string $fieldName): bool
            {
            }

            public function getFieldNames(): array
            {
            }

            public function getIdentifierFieldNames(): array
            {
            }

            public function getAssociationNames(): array
            {
            }

            public function getTypeOfField(string $fieldName): ?string
            {
            }

            public function getAssociationTargetClass(string $assocName): ?string
            {
            }

            public function isAssociationInverseSide(string $assocName): bool
            {
            }

            public function getAssociationMappedByTargetField(string $assocName): string
            {
            }

            public function getIdentifierValues(object $object): array
            {
            }
        };

        $this->mappingDriver = $this->createMock(MappingDriverChain::class);
        $fileDriver = $this->createMock(FileDriver::class);

        $this->mappingDriver->method('getDrivers')->willReturn([
            $this->createMock(MappingDriver::class),
            $this->createMock(MappingDriver::class),
            $fileDriver
        ]);

        $locator = $this->createMock(FileLocator::class);
        $fileDriver->method('getLocator')->willReturn($locator);

        $listener = $this->createMock(TranslatableListener::class);
        $listener->expects($this->once())->method('injectConfiguration');

        $this->assertInstanceOf(ExtensionMetadataFactory::class, $this->build()->loadExtensionMetadata($meta, $listener));
    }

    public function testLoadExtensionMetadataWithFileDriverWithParent(): void
    {
        $meta = new class () implements ClassMetadata {
            public $isMappedSuperclass = false;

            public function getName(): string
            {
                return ChildOfObjectOfTest::class;
            }

            public function getIdentifier(): array
            {
            }

            public function getReflectionClass(): \ReflectionClass
            {
            }

            public function isIdentifier(string $fieldName): bool
            {
            }

            public function hasField(string $fieldName): bool
            {
            }

            public function hasAssociation(string $fieldName): bool
            {
            }

            public function isSingleValuedAssociation(string $fieldName): bool
            {
            }

            public function isCollectionValuedAssociation(string $fieldName): bool
            {
            }

            public function getFieldNames(): array
            {
            }

            public function getIdentifierFieldNames(): array
            {
            }

            public function getAssociationNames(): array
            {
            }

            public function getTypeOfField(string $fieldName): ?string
            {
            }

            public function getAssociationTargetClass(string $assocName): ?string
            {
            }

            public function isAssociationInverseSide(string $assocName): bool
            {
            }

            public function getAssociationMappedByTargetField(string $assocName): string
            {
            }

            public function getIdentifierValues(object $object): array
            {
            }
        };

        $this->mappingDriver = $this->createMock(FileDriver::class);

        $locator = $this->createMock(FileLocator::class);
        $this->mappingDriver->method('getLocator')->willReturn($locator);

        $listener = $this->createMock(TranslatableListener::class);
        $listener->expects($this->once())->method('injectConfiguration');

        $this->getClassMetadataFactory()
            ->method('hasMetadataFor')
            ->willReturn(true);

        $this->getObjectManager()
            ->method('getClassMetadata')
            ->willReturn($this->createMock(ClassMetadata::class));

        $this->assertInstanceOf(ExtensionMetadataFactory::class, $this->build()->loadExtensionMetadata($meta, $listener));
    }

    public function testLoadExtensionMetadataWitchCacheEmpty(): void
    {
        $meta = new class () implements ClassMetadata {
            public $isMappedSuperclass = false;

            public function getName(): string
            {
                return ChildOfObjectOfTest::class;
            }

            public function getIdentifier(): array
            {
            }

            public function getReflectionClass(): \ReflectionClass
            {
            }

            public function isIdentifier(string $fieldName): bool
            {
            }

            public function hasField(string $fieldName): bool
            {
            }

            public function hasAssociation(string $fieldName): bool
            {
            }

            public function isSingleValuedAssociation(string $fieldName): bool
            {
            }

            public function isCollectionValuedAssociation(string $fieldName): bool
            {
            }

            public function getFieldNames(): array
            {
            }

            public function getIdentifierFieldNames(): array
            {
            }

            public function getAssociationNames(): array
            {
            }

            public function getTypeOfField(string $fieldName): ?string
            {
            }

            public function getAssociationTargetClass(string $assocName): ?string
            {
            }

            public function isAssociationInverseSide(string $assocName): bool
            {
            }

            public function getAssociationMappedByTargetField(string $assocName): string
            {
            }

            public function getIdentifierValues(object $object): array
            {
            }
        };

        $this->mappingDriver = $this->createMock(FileDriver::class);

        $locator = $this->createMock(FileLocator::class);
        $this->mappingDriver->method('getLocator')->willReturn($locator);

        $listener = $this->createMock(TranslatableListener::class);
        $listener->expects($this->once())->method('injectConfiguration');

        $this->getCacheMock()->method('hasItem')->willReturn(false);

        $this->assertInstanceOf(ExtensionMetadataFactory::class, $this->build()->loadExtensionMetadata($meta, $listener));
    }

    public function testLoadExtensionMetadataWitchCacheNotEmpty(): void
    {
        $meta = new class () implements ClassMetadata {
            public $isMappedSuperclass = false;

            public function getName(): string
            {
                return DoctrineObjectOfTest::class;
            }

            public function getIdentifier(): array
            {
            }

            public function getReflectionClass(): \ReflectionClass
            {
            }

            public function isIdentifier(string $fieldName): bool
            {
            }

            public function hasField(string $fieldName): bool
            {
            }

            public function hasAssociation(string $fieldName): bool
            {
            }

            public function isSingleValuedAssociation(string $fieldName): bool
            {
            }

            public function isCollectionValuedAssociation(string $fieldName): bool
            {
            }

            public function getFieldNames(): array
            {
            }

            public function getIdentifierFieldNames(): array
            {
            }

            public function getAssociationNames(): array
            {
            }

            public function getTypeOfField(string $fieldName): ?string
            {
            }

            public function getAssociationTargetClass(string $assocName): ?string
            {
            }

            public function isAssociationInverseSide(string $assocName): bool
            {
            }

            public function getAssociationMappedByTargetField(string $assocName): string
            {
            }

            public function getIdentifierValues(object $object): array
            {
            }
        };

        $this->mappingDriver = $this->createMock(FileDriver::class);

        $locator = $this->createMock(FileLocator::class);
        $this->mappingDriver->expects($this->never())->method('getLocator')->willReturn($locator);

        $listener = $this->createMock(TranslatableListener::class);
        $listener->expects($this->once())->method('injectConfiguration');

        $this->getCacheMock()->method('hasItem')->willReturn(true);
        $this->getCacheMock()->method('getItem')->willReturn(
            new Configuration('foo', [])
        );

        $this->assertInstanceOf(ExtensionMetadataFactory::class, $this->build()->loadExtensionMetadata($meta, $listener));
    }

    public function testSetCache(): void
    {
        $this->assertInstanceOf(ExtensionMetadataFactory::class, $this->build()->setCache(
            $this->createMock(CacheItemPoolInterface::class),
        ));
    }
}
