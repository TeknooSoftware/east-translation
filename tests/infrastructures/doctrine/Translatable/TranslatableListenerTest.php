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

namespace Teknoo\Tests\East\Translation\Doctrine\Translatable;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Persistence\Mapping\ClassMetadata;
use PHPUnit\Framework\Attributes\CoversClass;
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
 * @link        http://teknoo.software/east/translation project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 */
#[CoversClass(TranslatableListener::class)]
class TranslatableListenerTest extends TestCase
{
    private ?ExtensionMetadataFactory $extensionMetadataFactory = null;

    private ?ManagerAdapterInterface $manager = null;

    private ?PersistenceAdapterInterface $persistence = null;

    private ?FactoryInterface $wrapperFactory = null;

    /**
     * @return ExtensionMetadataFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getExtensionMetadataFactory(): ExtensionMetadataFactory
    {
        if (!$this->extensionMetadataFactory instanceof ExtensionMetadataFactory) {
            $this->extensionMetadataFactory = $this->createMock(ExtensionMetadataFactory::class);
        }

        return $this->extensionMetadataFactory;
    }

    /**
     * @return ManagerAdapterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getManager(): ManagerAdapterInterface
    {
        if (!$this->manager instanceof ManagerAdapterInterface) {
            $this->manager = $this->createMock(ManagerAdapterInterface::class);
        }

        return $this->manager;
    }

    /**
     * @return PersistenceAdapterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getPersistence(): PersistenceAdapterInterface
    {
        if (!$this->persistence instanceof PersistenceAdapterInterface) {
            $this->persistence = $this->createMock(PersistenceAdapterInterface::class);
        }

        return $this->persistence;
    }

    /**
     * @return FactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getWrapperFactory(): FactoryInterface
    {
        if (!$this->wrapperFactory instanceof FactoryInterface) {
            $this->wrapperFactory = $this->createMock(FactoryInterface::class);
        }

        return $this->wrapperFactory;
    }

    public function build(string $locale = 'en', bool $fallback = true): TranslatableListener
    {
        return new TranslatableListener(
            $this->getExtensionMetadataFactory(),
            $this->getManager(),
            $this->getPersistence(),
            $this->getWrapperFactory(),
            $locale,
            'en',
            $fallback
        );
    }

    public function testGetSubscribedEvents()
    {
        self::assertIsArray(
            $this->build()->getSubscribedEvents()
        );
    }

    public function testRegisterClassMetadata()
    {
        self::assertInstanceOf(
            TranslatableListener::class,
            $this->build()->registerClassMetadata(
                'foo',
                $this->createMock(ClassMetadata::class)
            )
        );
    }

    public function testSetLocale()
    {
        self::assertInstanceOf(
            TranslatableListener::class,
            $this->build()->setLocale(
                'fr'
            )
        );
    }

    public function testSetLocaleEmpty()
    {
        self::assertInstanceOf(
            TranslatableListener::class,
            $this->build()->setLocale(
                ''
            )
        );
    }

    public function testInjectConfiguration()
    {
        self::assertInstanceOf(
            TranslatableListener::class,
            $this->build()->injectConfiguration(
                $this->createMock(ClassMetadata::class),
                ['fields' => ['foo', 'bar']]
            )
        );
    }

    public function testLoadClassMetadata()
    {
        $classMeta = $this->createMock(ClassMetadata::class);
        $classMeta->expects($this->any())->method('getName')->willReturn(ObjectOfTest::class);

        $event = $this->createMock(LoadClassMetadataEventArgs::class);
        $event->expects($this->any())->method('getClassMetadata')->willReturn($classMeta);

        $this->getExtensionMetadataFactory()
            ->expects($this->any())
            ->method('loadExtensionMetadata')
            ->willReturnCallback(
                function (ClassMetadata $metaData, TranslatableListener $listener) {
                    $listener->injectConfiguration(
                        $metaData,
                        ['fields' => ['title'], 'fallback' => []]
                    );

                    return $this->getExtensionMetadataFactory();
                }
            );

        self::assertInstanceOf(
            TranslatableListener::class,
            $this->build()->loadClassMetadata(
                $event
            )
        );
    }

    public function testPostLoadNonTranslatable()
    {
        $object = $this->createMock(NonTranslatableObjectOfTest::class);

        $event = $this->createMock(LifecycleEventArgs::class);
        $event->expects($this->any())->method('getObject')->willReturn($object);

        $this->getManager()
            ->expects($this->never())
            ->method('findClassMetadata');

        self::assertInstanceOf(
            TranslatableListener::class,
            $this->build()->postLoad(
                $event
            )
        );
    }

    public function testPostLoadWithNoTranslationConfig()
    {
        $object = $this->createMock(ObjectOfTest::class);

        $event = $this->createMock(LifecycleEventArgs::class);
        $event->expects($this->any())->method('getObject')->willReturn($object);

        $classMeta = $this->createMock(ClassMetadata::class);

        $this->getManager()
            ->expects($this->any())
            ->method('findClassMetadata')
            ->willReturnCallback(
                function (string $class, TranslatableListener $listener) use ($classMeta) {
                    $listener->registerClassMetadata(
                        $class,
                        $classMeta
                    );

                    return $this->getManager();
                }
            );

        $this->getExtensionMetadataFactory()
            ->expects($this->any())
            ->method('loadExtensionMetadata')
            ->willReturnCallback(
                function (ClassMetadata $metaData, TranslatableListener $listener) {
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

        self::assertInstanceOf(
            TranslatableListener::class,
            $this->build()->postLoad(
                $event
            )
        );
    }

    public function testPostLoadErrorWithNoClassMetaData()
    {
        $object = $this->createMock(ObjectOfTest::class);

        $event = $this->createMock(LifecycleEventArgs::class);
        $event->expects($this->any())->method('getObject')->willReturn($object);

        $this->getManager()
            ->expects($this->any())
            ->method('findClassMetadata')
            ->willReturnCallback(
                fn(string $class, TranslatableListener $listener) => $this->getManager()
            );

        $wrapper = $this->createMock(WrapperInterface::class);
        $wrapper->expects($this->never())
            ->method('loadAllTranslations');

        $this->getWrapperFactory()
            ->expects($this->never())
            ->method('__invoke');

        $this->expectException(\DomainException::class);
        self::assertInstanceOf(
            TranslatableListener::class,
            $this->build()->postLoad(
                $event
            )
        );
    }

    public function testPostLoadWithDefaultLocale()
    {
        $object = $this->createMock(ObjectOfTest::class);

        $event = $this->createMock(LifecycleEventArgs::class);
        $event->expects($this->any())->method('getObject')->willReturn($object);

        $classMeta = $this->createMock(ClassMetadata::class);

        $this->getManager()
            ->expects($this->any())
            ->method('findClassMetadata')
            ->willReturnCallback(
                function (string $class, TranslatableListener $listener) use ($classMeta) {
                    $listener->registerClassMetadata(
                        $class,
                        $classMeta
                    );

                    return $this->getManager();
                }
            );

        $this->getExtensionMetadataFactory()
            ->expects($this->any())
            ->method('loadExtensionMetadata')
            ->willReturnCallback(
                function (ClassMetadata $metaData, TranslatableListener $listener) {
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

        self::assertInstanceOf(
            TranslatableListener::class,
            $this->build()->postLoad(
                $event
            )
        );
    }

    public function testPostLoadWithNoTranslationFound()
    {
        $object = $this->createMock(ObjectOfTest::class);

        $event = $this->createMock(LifecycleEventArgs::class);
        $event->expects($this->any())->method('getObject')->willReturn($object);

        $classMeta = $this->createMock(ClassMetadata::class);

        $this->getManager()
            ->expects($this->any())
            ->method('findClassMetadata')
            ->willReturnCallback(
                function (string $class, TranslatableListener $listener) use ($classMeta) {
                    $listener->registerClassMetadata(
                        $class,
                        $classMeta
                    );

                    return $this->getManager();
                }
            );

        $this->getExtensionMetadataFactory()
            ->expects($this->any())
            ->method('loadExtensionMetadata')
            ->willReturnCallback(
                function (ClassMetadata $metaData, TranslatableListener $listener) {
                    $listener->injectConfiguration(
                        $metaData,
                        ['fields' => ['title'], 'fallback' => [], 'translationClass' => Translation::class, 'useObjectClass' => ObjectOfTest::class]
                    );

                    return $this->getExtensionMetadataFactory();
                }
            );

        $wrapper = $this->createMock(WrapperInterface::class);
        $wrapper->expects($this->any())
            ->method('loadAllTranslations')
            ->willReturnCallback(
                function (
                    AdapterInterface $adapter,
                    string $locale,
                    string $translationClass,
                    string $objectClass,
                    callable $callback
                ) use ($wrapper) {
                    $callback([]);

                    return $wrapper;
                }
            );

        $this->getWrapperFactory()
            ->expects($this->any())
            ->method('__invoke')
            ->willReturnCallback(
                fn(TranslatableInterface $object, ClassMetadata $metadata) => $wrapper
            );

        self::assertInstanceOf(
            TranslatableListener::class,
            $this->build()->setLocale('fr')->postLoad(
                $event
            )
        );
    }

    public function testPostLoadWithTranslationFound()
    {
        $object = $this->createMock(ObjectOfTest::class);

        $event = $this->createMock(LifecycleEventArgs::class);
        $event->expects($this->any())->method('getObject')->willReturn($object);

        $classMeta = $this->createMock(ClassMetadata::class);

        $this->getManager()
            ->expects($this->any())
            ->method('findClassMetadata')
            ->willReturnCallback(
                function (string $class, TranslatableListener $listener) use ($classMeta) {
                    $listener->registerClassMetadata(
                        $class,
                        $classMeta
                    );

                    return $this->getManager();
                }
            );

        $this->getExtensionMetadataFactory()
            ->expects($this->any())
            ->method('loadExtensionMetadata')
            ->willReturnCallback(
                function (ClassMetadata $metaData, TranslatableListener $listener) {
                    $listener->injectConfiguration(
                        $metaData,
                        ['fields' => ['title', 'subtitle'], 'fallback' => [], 'translationClass' => Translation::class, 'useObjectClass' => ObjectOfTest::class]
                    );

                    return $this->getExtensionMetadataFactory();
                }
            );

        $wrapper = $this->createMock(WrapperInterface::class);
        $wrapper->expects($this->any())
            ->method('loadAllTranslations')
            ->willReturnCallback(
                function (
                    AdapterInterface $adapter,
                    string $locale,
                    string $translationClass,
                    string $objectClass,
                    callable $callback
                ) use ($wrapper) {
                    $callback([
                        ['field' => 'title', 'ObjectOfTest' => 'foo'],
                        ['field' => 'subtitle', 'ObjectOfTest' => 'bar'],
                    ]);

                    return $wrapper;
                }
            );

        $this->getWrapperFactory()
            ->expects($this->any())
            ->method('__invoke')
            ->willReturnCallback(
                fn(TranslatableInterface $object, ClassMetadata $metadata) => $wrapper
            );

        self::assertInstanceOf(
            TranslatableListener::class,
            $this->build()->setLocale('fr')->postLoad(
                $event
            )
        );
    }

    public function testPostLoadWithTranslationFoundWithoutUseObjectClass()
    {
        $object = $this->createMock(ObjectOfTest::class);

        $event = $this->createMock(LifecycleEventArgs::class);
        $event->expects($this->any())->method('getObject')->willReturn($object);

        $classMeta = $this->createMock(ClassMetadata::class);
        $classMeta->expects($this->any())
            ->method('getName')
            ->willReturn(ObjectOfTest::class);

        $this->getManager()
            ->expects($this->any())
            ->method('findClassMetadata')
            ->willReturnCallback(
                function (string $class, TranslatableListener $listener) use ($classMeta) {
                    $listener->registerClassMetadata(
                        $class,
                        $classMeta
                    );

                    return $this->getManager();
                }
            );

        $this->getExtensionMetadataFactory()
            ->expects($this->any())
            ->method('loadExtensionMetadata');

        $wrapper = $this->createMock(WrapperInterface::class);
        $wrapper->expects($this->any())
            ->method('loadAllTranslations')
            ->willReturnCallback(
                function (
                    AdapterInterface $adapter,
                    string $locale,
                    string $translationClass,
                    string $objectClass,
                    callable $callback
                ) use ($wrapper) {
                    $callback([
                        ['field' => 'title', 'ObjectOfTest' => 'foo'],
                        ['field' => 'subtitle', 'ObjectOfTest' => 'bar'],
                    ]);

                    return $wrapper;
                }
            );

        $this->getWrapperFactory()
            ->expects($this->any())
            ->method('__invoke')
            ->willReturnCallback(
                fn(TranslatableInterface $object, ClassMetadata $metadata) => $wrapper
            );

        self::assertInstanceOf(
            TranslatableListener::class,
            $this->build()->setLocale('fr')->postLoad(
                $event
            )
        );
    }

    public function testPostLoadWithTranslationFoundForAProxy()
    {
        $object = new class extends ObjectOfTest implements GhostObjectInterface {
            public function setProxyInitializer(\Closure $initializer = null)
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

        $event = $this->createMock(LifecycleEventArgs::class);
        $event->expects($this->any())->method('getObject')->willReturn($object);

        $classMeta = $this->createMock(ClassMetadata::class);

        $this->getManager()
            ->expects($this->any())
            ->method('findClassMetadata')
            ->willReturnCallback(
                function (string $class, TranslatableListener $listener) use ($classMeta) {
                    $listener->registerClassMetadata(
                        $class,
                        $classMeta
                    );

                    return $this->getManager();
                }
            );

        $this->getExtensionMetadataFactory()
            ->expects($this->any())
            ->method('loadExtensionMetadata')
            ->willReturnCallback(
                function (ClassMetadata $metaData, TranslatableListener $listener) {
                    $listener->injectConfiguration(
                        $metaData,
                        ['fields' => ['title', 'subtitle'], 'fallback' => [], 'translationClass' => Translation::class, 'useObjectClass' => ObjectOfTest::class]
                    );

                    return $this->getExtensionMetadataFactory();
                }
            );

        $wrapper = $this->createMock(WrapperInterface::class);
        $wrapper->expects($this->any())
            ->method('loadAllTranslations')
            ->willReturnCallback(
                function (
                    AdapterInterface $adapter,
                    string $locale,
                    string $translationClass,
                    string $objectClass,
                    callable $callback
                ) use ($wrapper) {
                    $callback([
                        ['field' => 'title', 'ObjectOfTest' => 'foo'],
                        ['field' => 'subtitle', 'ObjectOfTest' => 'bar'],
                    ]);

                    return $wrapper;
                }
            );

        $this->getWrapperFactory()
            ->expects($this->any())
            ->method('__invoke')
            ->willReturnCallback(
                fn(TranslatableInterface $object, ClassMetadata $metadata) => $wrapper
            );

        self::assertInstanceOf(
            TranslatableListener::class,
            $this->build()->setLocale('fr')->postLoad(
                $event
            )
        );
    }

    public function testOnFlushOnDefaultLocale()
    {
        $classMeta = $this->createMock(ClassMetadata::class);
        $classMeta->expects($this->any())
            ->method('getReflectionClass')
            ->willReturn(new \ReflectionClass(Translation::class));

        $this->getManager()
            ->expects($this->any())
            ->method('findClassMetadata')
            ->willReturnCallback(
                function (string $class, TranslatableListener $listener) use ($classMeta) {
                    $listener->registerClassMetadata(
                        $class,
                        $classMeta
                    );

                    return $this->getManager();
                }
            );

        $this->getExtensionMetadataFactory()
            ->expects($this->any())
            ->method('loadExtensionMetadata')
            ->willReturnCallback(
                function (ClassMetadata $metaData, TranslatableListener $listener) {
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
            ->expects($this->any())
            ->method('__invoke')
            ->willReturnCallback(
                fn(TranslatableInterface $object, ClassMetadata $metadata) => $wrapper
            );

        $this->getManager()
            ->expects($this->never())
            ->method('ifObjectHasChangeSet');

        $this->getManager()
            ->expects($this->any())
            ->method('foreachScheduledObjectInsertions')
            ->willReturnCallback(function (callable $callback) {
                $callback(new NonTranslatableObjectOfTest());
                $callback(new ObjectOfTest());

                return $this->getManager();
            });

        $this->getManager()
            ->expects($this->any())
            ->method('foreachScheduledObjectUpdates')
            ->willReturnCallback(function (callable $callback) {
                $callback(new NonTranslatableObjectOfTest());
                $callback(new ObjectOfTest());

                return $this->getManager();
            });

        $this->getManager()
            ->expects($this->any())
            ->method('foreachScheduledObjectDeletions')
            ->willReturnCallback(function (callable $callback) {
                $callback(new NonTranslatableObjectOfTest());
                $callback(new ObjectOfTest());

                return $this->getManager();
            });

        self::assertInstanceOf(
            TranslatableListener::class,
            $this->build()->setLocale('en')->onFlush()
        );
    }

    public function testOnFlushOnDifferentLocaleAndPostFlushAndPostPersist()
    {
        $classMeta = $this->createMock(ClassMetadata::class);
        $classMeta->expects($this->any())
            ->method('getReflectionClass')
            ->willReturn(new \ReflectionClass(Translation::class));

        $this->getManager()
            ->expects($this->any())
            ->method('findClassMetadata')
            ->willReturnCallback(
                function (string $class, TranslatableListener $listener) use ($classMeta) {
                    $listener->registerClassMetadata(
                        $class,
                        $classMeta
                    );

                    return $this->getManager();
                }
            );

        $this->getExtensionMetadataFactory()
            ->expects($this->any())
            ->method('loadExtensionMetadata')
            ->willReturnCallback(
                function (ClassMetadata $metaData, TranslatableListener $listener) {
                    $listener->injectConfiguration(
                        $metaData,
                        ['fields' => ['title', 'subtitle'], 'fallback' => [], 'translationClass' => Translation::class, 'useObjectClass' => ObjectOfTest::class]
                    );

                    return $this->getExtensionMetadataFactory();
                }
            );

        $wrapper = $this->createMock(WrapperInterface::class);
        $wrapper->expects($this->any())
            ->method('findTranslation')
            ->willReturnCallback(
                function (
                    AdapterInterface $adapter,
                    string $locale,
                    string $field,
                    string $translationClass,
                    string $objectClass,
                    callable $callback
                ) use ($wrapper) {
                    $callback($this->createMock(Translation::class));

                    return $wrapper;
                }
            );

        $this->getWrapperFactory()
            ->expects($this->any())
            ->method('__invoke')
            ->willReturnCallback(
                fn(TranslatableInterface $object, ClassMetadata $metadata) => $wrapper
            );

        $this->getManager()
            ->expects($this->any())
            ->method('ifObjectHasChangeSet')
            ->willReturnCallback(
                function ($object, callable $callback) {
                    $callback(['title' => ['foo', 'foo1']]);

                    return $this->getManager();
                }
            );

        $ObjectOfTest = new ObjectOfTest();
        $this->getManager()
            ->expects($this->any())
            ->method('foreachScheduledObjectInsertions')
            ->willReturnCallback(function (callable $callback) use ($ObjectOfTest) {
                $callback(new NonTranslatableObjectOfTest());
                $callback($ObjectOfTest);

                return $this->getManager();
            });

        $this->getManager()
            ->expects($this->any())
            ->method('foreachScheduledObjectUpdates')
            ->willReturnCallback(function (callable $callback) {
                $callback(new NonTranslatableObjectOfTest());
                $callback(new ObjectOfTest());

                return $this->getManager();
            });

        $this->getManager()
            ->expects($this->any())
            ->method('foreachScheduledObjectDeletions')
            ->willReturnCallback(function (callable $callback) {
                $callback(new NonTranslatableObjectOfTest());
                $callback(new ObjectOfTest());

                return $this->getManager();
            });

        $listener = $this->build()->setLocale('fr');
        self::assertInstanceOf(
            TranslatableListener::class,
            $listener->onFlush()
        );

        $event = $this->createMock(LifecycleEventArgs::class);
        $event->expects($this->any())->method('getObject')->willReturn($ObjectOfTest);
        self::assertInstanceOf(
            TranslatableListener::class,
            $listener->postPersist($event)
        );

        self::assertInstanceOf(
            TranslatableListener::class,
            $listener->postFlush()
        );
    }

    public function testOnFlushErrorOnNewTranslationInstance()
    {
        $refClass = new class extends \ReflectionClass {
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
        $classMeta->expects($this->any())
            ->method('getReflectionClass')
            ->willReturn($refClass);

        $this->getManager()
            ->expects($this->any())
            ->method('findClassMetadata')
            ->willReturnCallback(
                function (string $class, TranslatableListener $listener) use ($classMeta) {
                    $listener->registerClassMetadata(
                        $class,
                        $classMeta
                    );

                    return $this->getManager();
                }
            );

        $this->getExtensionMetadataFactory()
            ->expects($this->any())
            ->method('loadExtensionMetadata')
            ->willReturnCallback(
                function (ClassMetadata $metaData, TranslatableListener $listener) {
                    $listener->injectConfiguration(
                        $metaData,
                        ['fields' => ['title', 'subtitle'], 'fallback' => [], 'translationClass' => Translation::class, 'useObjectClass' => ObjectOfTest::class]
                    );

                    return $this->getExtensionMetadataFactory();
                }
            );

        $wrapper = $this->createMock(WrapperInterface::class);
        $wrapper->expects($this->any())
            ->method('findTranslation')
            ->willReturnCallback(
                fn(AdapterInterface $adapter, string $locale, string $field, string $translationClass, string $objectClass, callable $callback) => $wrapper
            );

        $this->getWrapperFactory()
            ->expects($this->any())
            ->method('__invoke')
            ->willReturnCallback(
                fn(TranslatableInterface $object, ClassMetadata $metadata) => $wrapper
            );

        $this->getManager()
            ->expects($this->any())
            ->method('ifObjectHasChangeSet')
            ->willReturnCallback(
                function ($object, callable $callback) {
                    $callback(['title' => ['foo', 'foo1']]);

                    return $this->getManager();
                }
            );

        $ObjectOfTest = new ObjectOfTest();
        $this->getManager()
            ->expects($this->any())
            ->method('foreachScheduledObjectInsertions')
            ->willReturnCallback(function (callable $callback) use ($ObjectOfTest) {
                $callback(new NonTranslatableObjectOfTest());
                $callback($ObjectOfTest);

                return $this->getManager();
            });

        $this->expectException(\RuntimeException::class);

        $listener = $this->build()->setLocale('fr');
        self::assertInstanceOf(
            TranslatableListener::class,
            $listener->onFlush()
        );
    }

    public function testOnFlushErrorOnNewTranslationInstanceNotGoodObject()
    {
        $refClass = new class extends \ReflectionClass {
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
        $classMeta->expects($this->any())
            ->method('getReflectionClass')
            ->willReturn($refClass);

        $this->getManager()
            ->expects($this->any())
            ->method('findClassMetadata')
            ->willReturnCallback(
                function (string $class, TranslatableListener $listener) use ($classMeta) {
                    $listener->registerClassMetadata(
                        $class,
                        $classMeta
                    );

                    return $this->getManager();
                }
            );

        $this->getExtensionMetadataFactory()
            ->expects($this->any())
            ->method('loadExtensionMetadata')
            ->willReturnCallback(
                function (ClassMetadata $metaData, TranslatableListener $listener) {
                    $listener->injectConfiguration(
                        $metaData,
                        ['fields' => ['title', 'subtitle'], 'fallback' => [], 'translationClass' => Translation::class, 'useObjectClass' => ObjectOfTest::class]
                    );

                    return $this->getExtensionMetadataFactory();
                }
            );

        $wrapper = $this->createMock(WrapperInterface::class);
        $wrapper->expects($this->any())
            ->method('findTranslation')
            ->willReturnCallback(
                fn(AdapterInterface $adapter, string $locale, string $field, string $translationClass, string $objectClass, callable $callback) => $wrapper
            );

        $this->getWrapperFactory()
            ->expects($this->any())
            ->method('__invoke')
            ->willReturnCallback(
                fn(TranslatableInterface $object, ClassMetadata $metadata) => $wrapper
            );

        $this->getManager()
            ->expects($this->any())
            ->method('ifObjectHasChangeSet')
            ->willReturnCallback(
                function ($object, callable $callback) {
                    $callback(['title' => ['foo', 'foo1']]);

                    return $this->getManager();
                }
            );

        $ObjectOfTest = new ObjectOfTest();
        $this->getManager()
            ->expects($this->any())
            ->method('foreachScheduledObjectInsertions')
            ->willReturnCallback(function (callable $callback) use ($ObjectOfTest) {
                $callback(new NonTranslatableObjectOfTest());
                $callback($ObjectOfTest);

                return $this->getManager();
            });

        $this->expectException(\RuntimeException::class);

        $listener = $this->build()->setLocale('fr');
        self::assertInstanceOf(
            TranslatableListener::class,
            $listener->onFlush()
        );
    }

    public function testPostPersistNonTranslatable()
    {
        $event = $this->createMock(LifecycleEventArgs::class);
        $event->expects($this->any())->method('getObject')->willReturn(new NonTranslatableObjectOfTest());
        self::assertInstanceOf(
            TranslatableListener::class,
            $this->build()->postPersist($event)
        );
    }

    public function testPostPersistNonInserted()
    {
        $event = $this->createMock(LifecycleEventArgs::class);
        $event->expects($this->any())->method('getObject')->willReturn(new ObjectOfTest());
        self::assertInstanceOf(
            TranslatableListener::class,
            $this->build()->postPersist($event)
        );
    }
}
