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

namespace Teknoo\Tests\East\Translation\Doctrine\Translatable;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Persistence\Mapping\ClassMetadata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use ProxyManager\Proxy\GhostObjectInterface;
use Teknoo\East\Translation\Contracts\Object\TranslatableInterface;
use Teknoo\East\Translation\Doctrine\Object\Translation;
use Teknoo\East\Translation\Doctrine\Translatable\Mapping\ExtensionMetadataFactory;
use Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface as ManagerAdapterInterface;
use Teknoo\East\Translation\Doctrine\Translatable\Persistence\AdapterInterface;
use Teknoo\East\Translation\Doctrine\Translatable\Persistence\AdapterInterface as PersistenceAdapterInterface;
use Teknoo\East\Translation\Doctrine\Translatable\TranslatableListener;
use Teknoo\East\Translation\Doctrine\Translatable\Wrapper\FactoryInterface;
use Teknoo\East\Translation\Doctrine\Translatable\Wrapper\WrapperInterface;
use Teknoo\Tests\East\Translation\Support\Object\NonTranslatableObjectOfTest;
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
#[CoversClass(TranslatableListener::class)]
class TranslatableListenerTest extends TestCase
{
    private (ExtensionMetadataFactory&Stub)|(ExtensionMetadataFactory&MockObject)|null $extensionMetadataFactory = null;

    private (ManagerAdapterInterface&Stub)|(ManagerAdapterInterface&MockObject)|null $manager = null;

    private (PersistenceAdapterInterface&Stub)|(PersistenceAdapterInterface&MockObject)|null $persistence = null;

    private (FactoryInterface&Stub)|(FactoryInterface&MockObject)|null $wrapperFactory = null;

    public function getExtensionMetadataFactory(bool $stub = false): (ExtensionMetadataFactory&Stub)|(ExtensionMetadataFactory&MockObject)
    {
        if (!$this->extensionMetadataFactory instanceof ExtensionMetadataFactory) {
            if ($stub) {
                $this->extensionMetadataFactory = $this->createStub(ExtensionMetadataFactory::class);
            } else {
                $this->extensionMetadataFactory = $this->createMock(ExtensionMetadataFactory::class);
            }
        }

        return $this->extensionMetadataFactory;
    }

    public function getManager(bool $stub = false): (ManagerAdapterInterface&Stub)|(ManagerAdapterInterface&MockObject)
    {
        if (!$this->manager instanceof ManagerAdapterInterface) {
            if ($stub) {
                $this->manager = $this->createStub(ManagerAdapterInterface::class);
            } else {
                $this->manager = $this->createMock(ManagerAdapterInterface::class);
            }
        }

        return $this->manager;
    }

    public function getPersistence(bool $stub = false): (PersistenceAdapterInterface&Stub)|(PersistenceAdapterInterface&MockObject)
    {
        if (!$this->persistence instanceof PersistenceAdapterInterface) {
            if ($stub) {
                $this->persistence = $this->createStub(PersistenceAdapterInterface::class);
            } else {
                $this->persistence = $this->createMock(PersistenceAdapterInterface::class);
            }
        }

        return $this->persistence;
    }

    public function getWrapperFactory(bool $stub = false): (FactoryInterface&Stub)|(FactoryInterface&MockObject)
    {
        if (!$this->wrapperFactory instanceof FactoryInterface) {
            if ($stub) {
                $this->wrapperFactory = $this->createStub(FactoryInterface::class);
            } else {
                $this->wrapperFactory = $this->createMock(FactoryInterface::class);
            }
        }

        return $this->wrapperFactory;
    }

    public function build(string $locale = 'en', bool $fallback = true): TranslatableListener
    {
        return new TranslatableListener(
            $this->getExtensionMetadataFactory(true),
            $this->getManager(true),
            $this->getPersistence(true),
            $this->getWrapperFactory(true),
            $locale,
            'en',
            $fallback
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertIsArray($this->build()->getSubscribedEvents());
    }

    public function testRegisterClassMetadata(): void
    {
        $this->assertInstanceOf(TranslatableListener::class, $this->build()->registerClassMetadata(
            'foo',
            $this->createMock(ClassMetadata::class)
        ));
    }

    public function testSetLocale(): void
    {
        $this->assertInstanceOf(TranslatableListener::class, $this->build()->setLocale(
            'fr'
        ));
    }

    public function testSetLocaleEmpty(): void
    {
        $this->assertInstanceOf(TranslatableListener::class, $this->build()->setLocale(
            ''
        ));
    }

    public function testInjectConfiguration(): void
    {
        $this->assertInstanceOf(TranslatableListener::class, $this->build()->injectConfiguration(
            $this->createMock(ClassMetadata::class),
            ['fields' => ['foo', 'bar']]
        ));
    }

    public function testLoadClassMetadata(): void
    {
        $classMeta = $this->createMock(ClassMetadata::class);
        $classMeta->method('getName')->willReturn(ObjectOfTest::class);

        $event = $this->createMock(LoadClassMetadataEventArgs::class);
        $event->method('getClassMetadata')->willReturn($classMeta);

        $this->getExtensionMetadataFactory()
            ->method('loadExtensionMetadata')
            ->willReturnCallback(
                function (ClassMetadata $metaData, TranslatableListener $listener): \Teknoo\East\Translation\Doctrine\Translatable\Mapping\ExtensionMetadataFactory {
                    $listener->injectConfiguration(
                        $metaData,
                        ['fields' => ['title'], 'fallback' => []]
                    );

                    return $this->getExtensionMetadataFactory();
                }
            );

        $this->assertInstanceOf(TranslatableListener::class, $this->build()->loadClassMetadata(
            $event
        ));
    }

    public function testPostLoadNonTranslatable(): void
    {
        $object = $this->createMock(NonTranslatableObjectOfTest::class);

        $event = $this->createStub(LifecycleEventArgs::class);
        $event->method('getObject')->willReturn($object);

        $this->getManager()
            ->expects($this->never())
            ->method('findClassMetadata');

        $this->assertInstanceOf(TranslatableListener::class, $this->build()->postLoad(
            $event
        ));
    }

    public function testPostLoadWithNoTranslationConfig(): void
    {
        $object = $this->createMock(ObjectOfTest::class);

        $event = $this->createStub(LifecycleEventArgs::class);
        $event->method('getObject')->willReturn($object);

        $classMeta = $this->createMock(ClassMetadata::class);

        $this->getManager()
            ->method('findClassMetadata')
            ->willReturnCallback(
                function (string $class, TranslatableListener $listener) use ($classMeta): \Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface {
                    $listener->registerClassMetadata(
                        $class,
                        $classMeta
                    );

                    return $this->getManager();
                }
            );

        $this->getExtensionMetadataFactory()
            ->method('loadExtensionMetadata')
            ->willReturnCallback(
                function (ClassMetadata $metaData, TranslatableListener $listener): \Teknoo\East\Translation\Doctrine\Translatable\Mapping\ExtensionMetadataFactory {
                    $listener->injectConfiguration(
                        $metaData,
                        ['fields' => [], 'fallback' => []]
                    );

                    return $this->getExtensionMetadataFactory();
                }
            );

        $wrapper = $this->createMock(WrapperInterface::class);
        $wrapper->expects($this->never())
            ->method('loadAllTranslations');

        $this->getWrapperFactory()
            ->expects($this->never())
            ->method('__invoke');

        $this->assertInstanceOf(TranslatableListener::class, $this->build()->postLoad(
            $event
        ));
    }

    public function testPostLoadErrorWithNoClassMetaData(): void
    {
        $object = $this->createMock(ObjectOfTest::class);

        $event = $this->createStub(LifecycleEventArgs::class);
        $event->method('getObject')->willReturn($object);

        $this->getManager()
            ->method('findClassMetadata')
            ->willReturnCallback(
                fn (string $class, TranslatableListener $listener): \Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface => $this->getManager()
            );

        $wrapper = $this->createMock(WrapperInterface::class);
        $wrapper->expects($this->never())
            ->method('loadAllTranslations');

        $this->getWrapperFactory()
            ->expects($this->never())
            ->method('__invoke');

        $this->expectException(\DomainException::class);
        $this->assertInstanceOf(TranslatableListener::class, $this->build()->postLoad(
            $event
        ));
    }

    public function testPostLoadWithDefaultLocale(): void
    {
        $object = $this->createMock(ObjectOfTest::class);

        $event = $this->createStub(LifecycleEventArgs::class);
        $event->method('getObject')->willReturn($object);

        $classMeta = $this->createMock(ClassMetadata::class);

        $this->getManager()
            ->method('findClassMetadata')
            ->willReturnCallback(
                function (string $class, TranslatableListener $listener) use ($classMeta): \Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface {
                    $listener->registerClassMetadata(
                        $class,
                        $classMeta
                    );

                    return $this->getManager();
                }
            );

        $this->getExtensionMetadataFactory()
            ->method('loadExtensionMetadata')
            ->willReturnCallback(
                function (ClassMetadata $metaData, TranslatableListener $listener): \Teknoo\East\Translation\Doctrine\Translatable\Mapping\ExtensionMetadataFactory {
                    $listener->injectConfiguration(
                        $metaData,
                        ['fields' => ['title'], 'fallback' => [], 'translationClass' => Translation::class, 'useObjectClass' => ObjectOfTest::class]
                    );

                    return $this->getExtensionMetadataFactory();
                }
            );

        $wrapper = $this->createMock(WrapperInterface::class);
        $wrapper->expects($this->never())
            ->method('loadAllTranslations');

        $this->getWrapperFactory()
            ->expects($this->never())
            ->method('__invoke');

        $this->assertInstanceOf(TranslatableListener::class, $this->build()->postLoad(
            $event
        ));
    }

    public function testPostLoadWithNoTranslationFound(): void
    {
        $object = $this->createMock(ObjectOfTest::class);

        $event = $this->createStub(LifecycleEventArgs::class);
        $event->method('getObject')->willReturn($object);

        $classMeta = $this->createMock(ClassMetadata::class);

        $this->getManager()
            ->method('findClassMetadata')
            ->willReturnCallback(
                function (string $class, TranslatableListener $listener) use ($classMeta): \Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface {
                    $listener->registerClassMetadata(
                        $class,
                        $classMeta
                    );

                    return $this->getManager();
                }
            );

        $this->getExtensionMetadataFactory()
            ->method('loadExtensionMetadata')
            ->willReturnCallback(
                function (ClassMetadata $metaData, TranslatableListener $listener): \Teknoo\East\Translation\Doctrine\Translatable\Mapping\ExtensionMetadataFactory {
                    $listener->injectConfiguration(
                        $metaData,
                        ['fields' => ['title'], 'fallback' => [], 'translationClass' => Translation::class, 'useObjectClass' => ObjectOfTest::class]
                    );

                    return $this->getExtensionMetadataFactory();
                }
            );

        $wrapper = $this->createStub(WrapperInterface::class);
        $wrapper
            ->method('loadAllTranslations')
            ->willReturnCallback(
                function (
                    AdapterInterface $adapter,
                    string $locale,
                    string $translationClass,
                    string $objectClass,
                    callable $callback
                ) use ($wrapper): WrapperInterface {
                    $callback([]);

                    return $wrapper;
                }
            );

        $this->getWrapperFactory()
            ->method('__invoke')
            ->willReturnCallback(
                fn (TranslatableInterface $object, ClassMetadata $metadata): WrapperInterface => $wrapper
            );

        $this->assertInstanceOf(TranslatableListener::class, $this->build()->setLocale('fr')->postLoad(
            $event
        ));
    }

    public function testPostLoadWithTranslationFound(): void
    {
        $object = $this->createMock(ObjectOfTest::class);

        $event = $this->createStub(LifecycleEventArgs::class);
        $event->method('getObject')->willReturn($object);

        $classMeta = $this->createMock(ClassMetadata::class);

        $this->getManager()
            ->method('findClassMetadata')
            ->willReturnCallback(
                function (string $class, TranslatableListener $listener) use ($classMeta): \Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface {
                    $listener->registerClassMetadata(
                        $class,
                        $classMeta
                    );

                    return $this->getManager();
                }
            );

        $this->getExtensionMetadataFactory()
            ->method('loadExtensionMetadata')
            ->willReturnCallback(
                function (ClassMetadata $metaData, TranslatableListener $listener): \Teknoo\East\Translation\Doctrine\Translatable\Mapping\ExtensionMetadataFactory {
                    $listener->injectConfiguration(
                        $metaData,
                        ['fields' => ['title', 'subtitle'], 'fallback' => [], 'translationClass' => Translation::class, 'useObjectClass' => ObjectOfTest::class]
                    );

                    return $this->getExtensionMetadataFactory();
                }
            );

        $wrapper = $this->createStub(WrapperInterface::class);
        $wrapper
            ->method('loadAllTranslations')
            ->willReturnCallback(
                function (
                    AdapterInterface $adapter,
                    string $locale,
                    string $translationClass,
                    string $objectClass,
                    callable $callback
                ) use ($wrapper): WrapperInterface {
                    $callback([
                        ['field' => 'title', 'ObjectOfTest' => 'foo'],
                        ['field' => 'subtitle', 'ObjectOfTest' => 'bar'],
                    ]);

                    return $wrapper;
                }
            );

        $this->getWrapperFactory()
            ->method('__invoke')
            ->willReturnCallback(
                fn (TranslatableInterface $object, ClassMetadata $metadata): WrapperInterface => $wrapper
            );

        $this->assertInstanceOf(TranslatableListener::class, $this->build()->setLocale('fr')->postLoad(
            $event
        ));
    }

    public function testPostLoadWithTranslationFoundWithoutUseObjectClass(): void
    {
        $object = $this->createMock(ObjectOfTest::class);

        $event = $this->createStub(LifecycleEventArgs::class);
        $event->method('getObject')->willReturn($object);

        $classMeta = $this->createMock(ClassMetadata::class);
        $classMeta
            ->method('getName')
            ->willReturn(ObjectOfTest::class);

        $this->getManager()
            ->method('findClassMetadata')
            ->willReturnCallback(
                function (string $class, TranslatableListener $listener) use ($classMeta): \Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface {
                    $listener->registerClassMetadata(
                        $class,
                        $classMeta
                    );

                    return $this->getManager();
                }
            );

        $this->getExtensionMetadataFactory()
            ->method('loadExtensionMetadata');

        $wrapper = $this->createMock(WrapperInterface::class);
        $wrapper
            ->method('loadAllTranslations')
            ->willReturnCallback(
                function (
                    AdapterInterface $adapter,
                    string $locale,
                    string $translationClass,
                    string $objectClass,
                    callable $callback
                ) use ($wrapper): \PHPUnit\Framework\MockObject\MockObject {
                    $callback([
                        ['field' => 'title', 'ObjectOfTest' => 'foo'],
                        ['field' => 'subtitle', 'ObjectOfTest' => 'bar'],
                    ]);

                    return $wrapper;
                }
            );

        $this->getWrapperFactory()
            ->method('__invoke')
            ->willReturnCallback(
                fn (TranslatableInterface $object, ClassMetadata $metadata): \PHPUnit\Framework\MockObject\MockObject => $wrapper
            );

        $this->assertInstanceOf(TranslatableListener::class, $this->build()->setLocale('fr')->postLoad(
            $event
        ));
    }

    public function testPostLoadWithTranslationFoundForAProxy(): void
    {
        $object = new class () extends ObjectOfTest implements GhostObjectInterface {
            public function setProxyInitializer(?\Closure $initializer = null): void
            {
            }

            public function getProxyInitializer(): \Closure
            {
            }

            public function initializeProxy(): bool
            {
            }

            public function isProxyInitialized(): bool
            {
            }
        };

        $event = $this->createStub(LifecycleEventArgs::class);
        $event->method('getObject')->willReturn($object);

        $classMeta = $this->createMock(ClassMetadata::class);

        $this->getManager()
            ->method('findClassMetadata')
            ->willReturnCallback(
                function (string $class, TranslatableListener $listener) use ($classMeta): \Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface {
                    $listener->registerClassMetadata(
                        $class,
                        $classMeta
                    );

                    return $this->getManager();
                }
            );

        $this->getExtensionMetadataFactory()
            ->method('loadExtensionMetadata')
            ->willReturnCallback(
                function (ClassMetadata $metaData, TranslatableListener $listener): \Teknoo\East\Translation\Doctrine\Translatable\Mapping\ExtensionMetadataFactory {
                    $listener->injectConfiguration(
                        $metaData,
                        ['fields' => ['title', 'subtitle'], 'fallback' => [], 'translationClass' => Translation::class, 'useObjectClass' => ObjectOfTest::class]
                    );

                    return $this->getExtensionMetadataFactory();
                }
            );

        $wrapper = $this->createMock(WrapperInterface::class);
        $wrapper
            ->method('loadAllTranslations')
            ->willReturnCallback(
                function (
                    AdapterInterface $adapter,
                    string $locale,
                    string $translationClass,
                    string $objectClass,
                    callable $callback
                ) use ($wrapper): \PHPUnit\Framework\MockObject\MockObject {
                    $callback([
                        ['field' => 'title', 'ObjectOfTest' => 'foo'],
                        ['field' => 'subtitle', 'ObjectOfTest' => 'bar'],
                    ]);

                    return $wrapper;
                }
            );

        $this->getWrapperFactory()
            ->method('__invoke')
            ->willReturnCallback(
                fn (TranslatableInterface $object, ClassMetadata $metadata): \PHPUnit\Framework\MockObject\MockObject => $wrapper
            );

        $this->assertInstanceOf(TranslatableListener::class, $this->build()->setLocale('fr')->postLoad(
            $event
        ));
    }

    public function testOnFlushOnDefaultLocale(): void
    {
        $classMeta = $this->createMock(ClassMetadata::class);
        $classMeta
            ->method('getReflectionClass')
            ->willReturn(new \ReflectionClass(Translation::class));

        $this->getManager()
            ->method('findClassMetadata')
            ->willReturnCallback(
                function (string $class, TranslatableListener $listener) use ($classMeta): \Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface {
                    $listener->registerClassMetadata(
                        $class,
                        $classMeta
                    );

                    return $this->getManager();
                }
            );

        $this->getExtensionMetadataFactory()
            ->method('loadExtensionMetadata')
            ->willReturnCallback(
                function (ClassMetadata $metaData, TranslatableListener $listener): \Teknoo\East\Translation\Doctrine\Translatable\Mapping\ExtensionMetadataFactory {
                    $listener->injectConfiguration(
                        $metaData,
                        ['fields' => ['title', 'subtitle'], 'fallback' => [], 'translationClass' => Translation::class, 'useObjectClass' => ObjectOfTest::class]
                    );

                    return $this->getExtensionMetadataFactory();
                }
            );

        $wrapper = $this->createMock(WrapperInterface::class);
        $wrapper->expects($this->never())
            ->method('findTranslation');

        $this->getWrapperFactory()
            ->method('__invoke')
            ->willReturnCallback(
                fn (TranslatableInterface $object, ClassMetadata $metadata): \PHPUnit\Framework\MockObject\MockObject => $wrapper
            );

        $this->getManager()
            ->expects($this->never())
            ->method('ifObjectHasChangeSet');

        $this->getManager()
            ->method('foreachScheduledObjectInsertions')
            ->willReturnCallback(function (callable $callback): \Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface {
                $callback(new NonTranslatableObjectOfTest());
                $callback(new ObjectOfTest());

                return $this->getManager();
            });

        $this->getManager()
            ->method('foreachScheduledObjectUpdates')
            ->willReturnCallback(function (callable $callback): \Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface {
                $callback(new NonTranslatableObjectOfTest());
                $callback(new ObjectOfTest());

                return $this->getManager();
            });

        $this->getManager()
            ->method('foreachScheduledObjectDeletions')
            ->willReturnCallback(function (callable $callback): \Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface {
                $callback(new NonTranslatableObjectOfTest());
                $callback(new ObjectOfTest());

                return $this->getManager();
            });

        $this->assertInstanceOf(TranslatableListener::class, $this->build()->setLocale('en')->onFlush());
    }

    public function testOnFlushOnDifferentLocaleAndPostFlushAndPostPersist(): void
    {
        $classMeta = $this->createMock(ClassMetadata::class);
        $classMeta
            ->method('getReflectionClass')
            ->willReturn(new \ReflectionClass(Translation::class));

        $this->getManager()
            ->method('findClassMetadata')
            ->willReturnCallback(
                function (string $class, TranslatableListener $listener) use ($classMeta): \Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface {
                    $listener->registerClassMetadata(
                        $class,
                        $classMeta
                    );

                    return $this->getManager();
                }
            );

        $this->getExtensionMetadataFactory()
            ->method('loadExtensionMetadata')
            ->willReturnCallback(
                function (ClassMetadata $metaData, TranslatableListener $listener): \Teknoo\East\Translation\Doctrine\Translatable\Mapping\ExtensionMetadataFactory {
                    $listener->injectConfiguration(
                        $metaData,
                        ['fields' => ['title', 'subtitle'], 'fallback' => [], 'translationClass' => Translation::class, 'useObjectClass' => ObjectOfTest::class]
                    );

                    return $this->getExtensionMetadataFactory();
                }
            );

        $wrapper = $this->createMock(WrapperInterface::class);
        $wrapper
            ->method('findTranslation')
            ->willReturnCallback(
                function (
                    AdapterInterface $adapter,
                    string $locale,
                    string $field,
                    string $translationClass,
                    string $objectClass,
                    callable $callback
                ) use ($wrapper): \PHPUnit\Framework\MockObject\MockObject {
                    $callback($this->createMock(Translation::class));

                    return $wrapper;
                }
            );

        $this->getWrapperFactory()
            ->method('__invoke')
            ->willReturnCallback(
                fn (TranslatableInterface $object, ClassMetadata $metadata): \PHPUnit\Framework\MockObject\MockObject => $wrapper
            );

        $this->getManager()
            ->method('ifObjectHasChangeSet')
            ->willReturnCallback(
                function ($object, callable $callback): \Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface {
                    $callback(['title' => ['foo', 'foo1']]);

                    return $this->getManager();
                }
            );

        $ObjectOfTest = new ObjectOfTest();
        $this->getManager()
            ->method('foreachScheduledObjectInsertions')
            ->willReturnCallback(function (callable $callback) use ($ObjectOfTest): \Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface {
                $callback(new NonTranslatableObjectOfTest());
                $callback($ObjectOfTest);

                return $this->getManager();
            });

        $this->getManager()
            ->method('foreachScheduledObjectUpdates')
            ->willReturnCallback(function (callable $callback): \Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface {
                $callback(new NonTranslatableObjectOfTest());
                $callback(new ObjectOfTest());

                return $this->getManager();
            });

        $this->getManager()
            ->method('foreachScheduledObjectDeletions')
            ->willReturnCallback(function (callable $callback): \Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface {
                $callback(new NonTranslatableObjectOfTest());
                $callback(new ObjectOfTest());

                return $this->getManager();
            });

        $listener = $this->build()->setLocale('fr');
        $this->assertInstanceOf(TranslatableListener::class, $listener->onFlush());

        $event = $this->createStub(LifecycleEventArgs::class);
        $event->method('getObject')->willReturn($ObjectOfTest);
        $this->assertInstanceOf(TranslatableListener::class, $listener->postPersist($event));

        $this->assertInstanceOf(TranslatableListener::class, $listener->postFlush());
    }

    public function testOnFlushErrorOnNewTranslationInstance(): void
    {
        $refClass = new class () extends \ReflectionClass {
            public function __construct()
            {
                parent::__construct(ObjectOfTest::class);
            }

            public function newInstance(... $args): object
            {
                throw new \ReflectionException();
            }
        };

        $classMeta = $this->createMock(ClassMetadata::class);
        $classMeta
            ->method('getReflectionClass')
            ->willReturn($refClass);

        $this->getManager()
            ->method('findClassMetadata')
            ->willReturnCallback(
                function (string $class, TranslatableListener $listener) use ($classMeta): \Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface {
                    $listener->registerClassMetadata(
                        $class,
                        $classMeta
                    );

                    return $this->getManager();
                }
            );

        $this->getExtensionMetadataFactory()
            ->method('loadExtensionMetadata')
            ->willReturnCallback(
                function (ClassMetadata $metaData, TranslatableListener $listener): \Teknoo\East\Translation\Doctrine\Translatable\Mapping\ExtensionMetadataFactory {
                    $listener->injectConfiguration(
                        $metaData,
                        ['fields' => ['title', 'subtitle'], 'fallback' => [], 'translationClass' => Translation::class, 'useObjectClass' => ObjectOfTest::class]
                    );

                    return $this->getExtensionMetadataFactory();
                }
            );

        $wrapper = $this->createMock(WrapperInterface::class);
        $wrapper
            ->method('findTranslation')
            ->willReturnCallback(
                fn (AdapterInterface $adapter, string $locale, string $field, string $translationClass, string $objectClass, callable $callback): \PHPUnit\Framework\MockObject\MockObject => $wrapper
            );

        $this->getWrapperFactory()
            ->method('__invoke')
            ->willReturnCallback(
                fn (TranslatableInterface $object, ClassMetadata $metadata): \PHPUnit\Framework\MockObject\MockObject => $wrapper
            );

        $this->getManager()
            ->method('ifObjectHasChangeSet')
            ->willReturnCallback(
                function ($object, callable $callback): \Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface {
                    $callback(['title' => ['foo', 'foo1']]);

                    return $this->getManager();
                }
            );

        $ObjectOfTest = new ObjectOfTest();
        $this->getManager()
            ->method('foreachScheduledObjectInsertions')
            ->willReturnCallback(function (callable $callback) use ($ObjectOfTest): \Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface {
                $callback(new NonTranslatableObjectOfTest());
                $callback($ObjectOfTest);

                return $this->getManager();
            });

        $this->expectException(\RuntimeException::class);

        $listener = $this->build()->setLocale('fr');
        $this->assertInstanceOf(TranslatableListener::class, $listener->onFlush());
    }

    public function testOnFlushErrorOnNewTranslationInstanceNotGoodObject(): void
    {
        $refClass = new class () extends \ReflectionClass {
            public function __construct()
            {
                parent::__construct(ObjectOfTest::class);
            }

            public function newInstance(... $args): object
            {
                return new \stdClass();
            }
        };

        $classMeta = $this->createMock(ClassMetadata::class);
        $classMeta
            ->method('getReflectionClass')
            ->willReturn($refClass);

        $this->getManager()
            ->method('findClassMetadata')
            ->willReturnCallback(
                function (string $class, TranslatableListener $listener) use ($classMeta): \Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface {
                    $listener->registerClassMetadata(
                        $class,
                        $classMeta
                    );

                    return $this->getManager();
                }
            );

        $this->getExtensionMetadataFactory()
            ->method('loadExtensionMetadata')
            ->willReturnCallback(
                function (ClassMetadata $metaData, TranslatableListener $listener): \Teknoo\East\Translation\Doctrine\Translatable\Mapping\ExtensionMetadataFactory {
                    $listener->injectConfiguration(
                        $metaData,
                        ['fields' => ['title', 'subtitle'], 'fallback' => [], 'translationClass' => Translation::class, 'useObjectClass' => ObjectOfTest::class]
                    );

                    return $this->getExtensionMetadataFactory();
                }
            );

        $wrapper = $this->createMock(WrapperInterface::class);
        $wrapper
            ->method('findTranslation')
            ->willReturnCallback(
                fn (AdapterInterface $adapter, string $locale, string $field, string $translationClass, string $objectClass, callable $callback): \PHPUnit\Framework\MockObject\MockObject => $wrapper
            );

        $this->getWrapperFactory()
            ->method('__invoke')
            ->willReturnCallback(
                fn (TranslatableInterface $object, ClassMetadata $metadata): \PHPUnit\Framework\MockObject\MockObject => $wrapper
            );

        $this->getManager()
            ->method('ifObjectHasChangeSet')
            ->willReturnCallback(
                function ($object, callable $callback): \Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface {
                    $callback(['title' => ['foo', 'foo1']]);

                    return $this->getManager();
                }
            );

        $ObjectOfTest = new ObjectOfTest();
        $this->getManager()
            ->method('foreachScheduledObjectInsertions')
            ->willReturnCallback(function (callable $callback) use ($ObjectOfTest): \Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface {
                $callback(new NonTranslatableObjectOfTest());
                $callback($ObjectOfTest);

                return $this->getManager();
            });

        $this->expectException(\RuntimeException::class);

        $listener = $this->build()->setLocale('fr');
        $this->assertInstanceOf(TranslatableListener::class, $listener->onFlush());
    }

    public function testPostPersistNonTranslatable(): void
    {
        $event = $this->createStub(LifecycleEventArgs::class);
        $event->method('getObject')->willReturn(new NonTranslatableObjectOfTest());
        $this->assertInstanceOf(TranslatableListener::class, $this->build()->postPersist($event));
    }

    public function testPostPersistNonInserted(): void
    {
        $event = $this->createStub(LifecycleEventArgs::class);
        $event->method('getObject')->willReturn(new ObjectOfTest());
        $this->assertInstanceOf(TranslatableListener::class, $this->build()->postPersist($event));
    }
}
