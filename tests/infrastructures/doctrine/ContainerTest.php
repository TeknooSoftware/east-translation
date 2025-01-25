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

namespace Teknoo\Tests\East\Translation\Doctrine;

use DI\Container;
use DI\ContainerBuilder;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactoryInterface;
use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Persistence\Mapping\ClassMetadata as BaseClassMetadata;
use Doctrine\Persistence\Mapping\Driver\FileLocator;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use ProxyManager\Proxy\GhostObjectInterface;
use Teknoo\East\Translation\Contracts\DBSource\TranslationManagerInterface;
use Teknoo\East\Common\Contracts\DBSource\ManagerInterface;
use Teknoo\East\Translation\Contracts\Recipe\Step\LoadTranslationsInterface;
use Teknoo\East\Translation\Doctrine\Translatable\Mapping\DriverInterface;
use Teknoo\East\Translation\Doctrine\Translatable\TranslatableListener;
use Teknoo\East\Translation\Doctrine\Translatable\TranslationManager;
use Teknoo\East\Translation\Doctrine\Translatable\Wrapper\WrapperInterface;
use Teknoo\East\Common\Middleware\LocaleMiddleware;
use Teknoo\East\Common\Contracts\Service\ProxyDetectorInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\Recipe\RecipeInterface as OriginalRecipeInterface;
use Teknoo\Tests\East\Translation\Support\Object\ObjectOfTest;

/**
 * Class DefinitionProviderTest.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east/translation project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ContainerTest extends TestCase
{
    /**
     * @return Container
     * @throws \Exception
     */
    protected function buildContainer() : Container
    {
        $containerDefinition = new ContainerBuilder();
        $containerDefinition->addDefinitions(
            __DIR__.'/../../../vendor/teknoo/east-common/infrastructures/doctrine/di.php'
        );
        $containerDefinition->addDefinitions(__DIR__.'/../../../infrastructures/doctrine/di.php');

        return $containerDefinition->build();
    }

    public function testManager()
    {
        $container = $this->buildContainer();
        $objectManager = $this->createMock(ObjectManager::class);

        $container->set(ObjectManager::class, $objectManager);
        self::assertInstanceOf(ManagerInterface::class, $container->get(ManagerInterface::class));
    }

    private function generateTestForRepository(string $objectClass, string $repositoryClass, string $repositoryType)
    {
        $container = $this->buildContainer();
        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects($this->any())->method('getRepository')->with($objectClass)->willReturn(
            $this->createMock($repositoryType)
        );

        $container->set(ObjectManager::class, $objectManager);
        $repository = $container->get($repositoryClass);

        self::assertInstanceOf(
            $repositoryClass,
            $repository
        );
    }

    private function generateTestForRepositoryWithUnsupportedRepository(string $objectClass, string $repositoryClass)
    {
        $container = $this->buildContainer();
        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects($this->any())->method('getRepository')->with($objectClass)->willReturn(
            $this->createMock(\DateTime::class)
        );

        $container->set(ObjectManager::class, $objectManager);
        $container->get($repositoryClass);
    }

    public function testLocaleMiddlewareWithDocumentManager()
    {
        $container = $this->buildContainer();
        $translatableListener = $this->createMock(TranslatableListener::class);

        $objectManager = $this->createMock(DocumentManager::class);
        $container->set(ObjectManager::class, $objectManager);

        $container->set(TranslatableListener::class, $translatableListener);
        $loader = $container->get(LocaleMiddleware::class);

        self::assertInstanceOf(
            LocaleMiddleware::class,
            $loader
        );
    }

    public function testLocaleMiddlewareWithoutDocumentManager()
    {
        $container = $this->buildContainer();
        $translatableListener = $this->createMock(TranslatableListener::class);

        $container->set(TranslatableListener::class, $translatableListener);
        $loader = $container->get(LocaleMiddleware::class);

        self::assertInstanceOf(
            LocaleMiddleware::class,
            $loader
        );
    }

    public function testTranslationListenerWithDocumentManagerWithoutMappingDriver()
    {
        $container = $this->buildContainer();
        $objectManager = $this->createMock(DocumentManager::class);
        $container->set(ObjectManager::class, $objectManager);
        $container->set('teknoo.east.translation.deferred_loading', true);

        $this->expectException(\RuntimeException::class);
        $listener = $container->get(TranslatableListener::class);

        self::assertInstanceOf(
            TranslatableListener::class,
            $listener
        );
    }

    public function testTranslationListenerWithDocumentManager()
    {
        $container = $this->buildContainer();

        $driver = $this->createMock(MappingDriver::class);

        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->any())->method('getMetadataDriverImpl')->willReturn($driver);

        $mappingFactory = $this->createMock(ClassMetadataFactoryInterface::class);

        $objectManager = $this->createMock(DocumentManager::class);
        $objectManager->expects($this->any())->method('getConfiguration')->willReturn($configuration);
        $objectManager->expects($this->any())->method('getMetadataFactory')->willReturn($mappingFactory);

        $container->set(ObjectManager::class, $objectManager);

        $listener = $container->get(TranslatableListener::class);

        self::assertInstanceOf(
            TranslatableListener::class,
            $listener
        );

        $rf = new \ReflectionObject($listener);
        $rpw = $rf->getProperty('wrapperFactory');

        $rpw->setAccessible(true);
        $closure = $rpw->getValue($listener);

        self::assertInstanceOf(
            WrapperInterface::class,
            $closure(new ObjectOfTest(), $this->createMock(ClassMetadata::class))
        );

        $error = false;
        try {
            $closure(new ObjectOfTest(), $this->createMock(BaseClassMetadata::class));
        } catch (\RuntimeException $error) {
            $error = true;
        }
        self::assertTrue($error);

        $rpe = $rf->getProperty('extensionMetadataFactory');
        $rpe->setAccessible(true);
        $extensionMetadataFactory = $rpe->getValue($listener);

        $rf = new \ReflectionObject($extensionMetadataFactory);

        $rpe = $rf->getProperty('driverFactory');
        $rpe->setAccessible(true);
        $driverFactory = $rpe->getValue($extensionMetadataFactory);

        $driver = $driverFactory($this->createMock(FileLocator::class));
        self::assertInstanceOf(
            DriverInterface::class,
            $driver
        );

        $rf = new \ReflectionObject($driver);

        $rps = $rf->getProperty('simpleXmlFactory');
        $rps->setAccessible(true);
        $simpleXmlFactory = $rps->getValue($driver);

        $simpleXml = $simpleXmlFactory(__DIR__.'/Translatable/Mapping/Driver/support/valid.translate.xml');
        self::assertInstanceOf(
            \SimpleXMLElement::class,
            $simpleXml
        );
    }

    public function testTranslationListenerWithWithoutDocumentManager()
    {
        $container = $this->buildContainer();

        $container->set(ObjectManager::class, $this->createMock(ObjectManager::class));

        $this->expectException(\RuntimeException::class);
        $container->get(TranslatableListener::class);
    }

    public function testProxyDetectorInterface()
    {
        $container = $this->buildContainer();
        $proxyDetector = $container->get(ProxyDetectorInterface::class);

        $p1 = $this->createMock(PromiseInterface::class);
        $p1->expects($this->never())->method('success');
        $p1->expects($this->once())->method('fail');

        self::assertInstanceOf(
            ProxyDetectorInterface::class,
            $proxyDetector->checkIfInstanceBehindProxy(new \stdClass(), $p1)
        );

        $p2 = $this->createMock(PromiseInterface::class);
        $p2->expects($this->never())->method('success');
        $p2->expects($this->once())->method('fail');

        self::assertInstanceOf(
            ProxyDetectorInterface::class,
            $proxyDetector->checkIfInstanceBehindProxy(new class implements GhostObjectInterface {
                public function setProxyInitializer(?\Closure $initializer = null): never
                {
                    throw new \RuntimeException('Must not be called');
                }

                public function getProxyInitializer(): never
                {
                    throw new \RuntimeException('Must not be called');
                }

                public function initializeProxy(): never
                {
                    throw new \RuntimeException('Must not be called');
                }

                public function isProxyInitialized(): bool
                {
                    return true;
                }
            }, $p2)
        );

        $p3 = $this->createMock(PromiseInterface::class);
        $p3->expects($this->once())->method('success');
        $p3->expects($this->never())->method('fail');

        self::assertInstanceOf(
            ProxyDetectorInterface::class,
            $proxyDetector->checkIfInstanceBehindProxy(new class implements GhostObjectInterface {
                public function setProxyInitializer(?\Closure $initializer = null): never
                {
                    throw new \RuntimeException('Must not be called');
                }

                public function getProxyInitializer(): never
                {
                    throw new \RuntimeException('Must not be called');
                }

                public function initializeProxy(): never
                {
                    throw new \RuntimeException('Must not be called');
                }

                public function isProxyInitialized(): bool
                {
                    return false;
                }
            }, $p3)
        );
    }

    public function testTranslationManager()
    {
        $container = $this->buildContainer();

        $objectManager = $this->createMock(DocumentManager::class);
        $container->set(ObjectManager::class, $objectManager);

        self::assertInstanceOf(
            TranslationManager::class,
            $container->get(TranslationManager::class)
        );
    }

    public function testTranslationManagerInterface()
    {
        $container = $this->buildContainer();

        $objectManager = $this->createMock(DocumentManager::class);
        $container->set(ObjectManager::class, $objectManager);

        self::assertInstanceOf(
            TranslationManager::class,
            $container->get(TranslationManagerInterface::class)
        );
    }

    public function testTranslationManagerNonOdm()
    {
        $container = $this->buildContainer();

        $objectManager = $this->createMock(ObjectManager::class);
        $container->set(ObjectManager::class, $objectManager);

        self::assertNull(
            $container->get(TranslationManager::class)
        );
    }

    public function testTranslationManagerInterfaceNonOdm()
    {
        $container = $this->buildContainer();

        $objectManager = $this->createMock(ObjectManager::class);
        $container->set(ObjectManager::class, $objectManager);

        self::assertNull(
            $container->get(TranslationManager::class)
        );
    }

    public function testOriginalRecipeInterfaceStatic()
    {
        $container = $this->buildContainer();
        $container->set(OriginalRecipeInterface::class . ':Static', $this->createMock(OriginalRecipeInterface::class));
        $container->set(LoadTranslationsInterface::class, $this->createMock(LoadTranslationsInterface::class));

        self::assertInstanceOf(
            OriginalRecipeInterface::class,
            $container->get(OriginalRecipeInterface::class . ':Static')
        );
    }
}
