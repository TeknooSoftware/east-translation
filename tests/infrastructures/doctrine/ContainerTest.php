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

namespace Teknoo\Tests\East\Translation\Doctrine;

use DI\Container;
use DI\ContainerBuilder;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactoryInterface;
use Doctrine\ODM\MongoDB\Query\FilterCollection;
use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Persistence\Mapping\ClassMetadata as BaseClassMetadata;
use Doctrine\Persistence\Mapping\Driver\FileLocator;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use ProxyManager\Proxy\GhostObjectInterface;
use Teknoo\East\Common\Doctrine\Filter\ODM\SoftDeletableFilter;
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ContainerTest extends TestCase
{
    /**
     * @throws \Exception
     */
    protected function buildContainer(): Container
    {
        $containerDefinition = new ContainerBuilder();
        $containerDefinition->addDefinitions(
            __DIR__.'/../../../vendor/teknoo/east-common/infrastructures/doctrine/di.php'
        );
        $containerDefinition->addDefinitions(__DIR__.'/../../../infrastructures/doctrine/di.php');

        return $containerDefinition->build();
    }

    public function testManager(): void
    {
        $container = $this->buildContainer();
        $objectManager = $this->createMock(ObjectManager::class);

        $container->set(ObjectManager::class, $objectManager);
        $this->assertInstanceOf(ManagerInterface::class, $container->get(ManagerInterface::class));
    }

    public function testLocaleMiddlewareWithDocumentManager(): void
    {
        $container = $this->buildContainer();
        $translatableListener = $this->createMock(TranslatableListener::class);

        $objectManager = $this->createMock(DocumentManager::class);
        $container->set(ObjectManager::class, $objectManager);

        $container->set(TranslatableListener::class, $translatableListener);
        $loader = $container->get(LocaleMiddleware::class);

        $this->assertInstanceOf(LocaleMiddleware::class, $loader);
    }

    public function testLocaleMiddlewareWithoutDocumentManager(): void
    {
        $container = $this->buildContainer();
        $translatableListener = $this->createMock(TranslatableListener::class);

        $container->set(TranslatableListener::class, $translatableListener);
        $loader = $container->get(LocaleMiddleware::class);

        $this->assertInstanceOf(LocaleMiddleware::class, $loader);
    }

    public function testTranslationListenerWithDocumentManagerWithoutMappingDriver(): void
    {
        $container = $this->buildContainer();
        $objectManager = $this->createMock(DocumentManager::class);
        $configuration = new Configuration();
        $objectManager->method('getConfiguration')->willReturn($configuration);
        $objectManager->method('getFilterCollection')->willReturn(new FilterCollection($objectManager));
        $container->set(ObjectManager::class, $objectManager);
        $container->set('teknoo.east.translation.deferred_loading', true);

        $this->expectException(\RuntimeException::class);
        $listener = $container->get(TranslatableListener::class);

        $this->assertInstanceOf(TranslatableListener::class, $listener);
    }

    public function testTranslationListenerWithDocumentManager(): void
    {
        $container = $this->buildContainer();

        $driver = $this->createMock(MappingDriver::class);

        $mappingFactory = $this->createMock(ClassMetadataFactoryInterface::class);

        $objectManager = $this->createMock(DocumentManager::class);
        $configuration = new Configuration();
        $configuration->setMetadataDriverImpl($driver);
        $objectManager->method('getConfiguration')->willReturn($configuration);

        $objectManager->method('getMetadataFactory')->willReturn($mappingFactory);

        $filterCollection = new FilterCollection($objectManager);
        $objectManager->method('getFilterCollection')->willReturn($filterCollection);

        $container->set(ObjectManager::class, $objectManager);

        $listener = $container->get(TranslatableListener::class);

        $this->assertInstanceOf(TranslatableListener::class, $listener);

        $rf = new \ReflectionObject($listener);
        $rpw = $rf->getProperty('wrapperFactory');
        $closure = $rpw->getValue($listener);

        $this->assertInstanceOf(WrapperInterface::class, $closure(new ObjectOfTest(), $this->createMock(ClassMetadata::class)));

        $error = false;
        try {
            $closure(new ObjectOfTest(), $this->createMock(BaseClassMetadata::class));
        } catch (\RuntimeException $error) {
            $error = true;
        }

        $this->assertTrue($error);

        $rpe = $rf->getProperty('extensionMetadataFactory');
        $extensionMetadataFactory = $rpe->getValue($listener);

        $rf = new \ReflectionObject($extensionMetadataFactory);

        $rpe = $rf->getProperty('driverFactory');
        $driverFactory = $rpe->getValue($extensionMetadataFactory);

        $driver = $driverFactory($this->createMock(FileLocator::class));
        $this->assertInstanceOf(DriverInterface::class, $driver);

        $rf = new \ReflectionObject($driver);

        $rps = $rf->getProperty('simpleXmlFactory');
        $simpleXmlFactory = $rps->getValue($driver);

        $simpleXml = $simpleXmlFactory(__DIR__.'/Translatable/Mapping/Driver/support/valid.translate.xml');
        $this->assertInstanceOf(\SimpleXMLElement::class, $simpleXml);
    }

    public function testTranslationListenerWithWithoutDocumentManager(): void
    {
        $container = $this->buildContainer();

        $container->set(ObjectManager::class, $this->createMock(ObjectManager::class));

        $this->expectException(\RuntimeException::class);
        $container->get(TranslatableListener::class);
    }

    public function testTranslationManager(): void
    {
        $container = $this->buildContainer();

        $objectManager = $this->createMock(DocumentManager::class);
        $container->set(ObjectManager::class, $objectManager);

        $this->assertInstanceOf(TranslationManager::class, $container->get(TranslationManager::class));
    }

    public function testTranslationManagerInterface(): void
    {
        $container = $this->buildContainer();

        $objectManager = $this->createMock(DocumentManager::class);
        $container->set(ObjectManager::class, $objectManager);

        $this->assertInstanceOf(TranslationManager::class, $container->get(TranslationManagerInterface::class));
    }

    public function testTranslationManagerNonOdm(): void
    {
        $container = $this->buildContainer();

        $objectManager = $this->createMock(ObjectManager::class);
        $container->set(ObjectManager::class, $objectManager);

        $this->assertNull($container->get(TranslationManager::class));
    }

    public function testTranslationManagerInterfaceNonOdm(): void
    {
        $container = $this->buildContainer();

        $objectManager = $this->createMock(ObjectManager::class);
        $container->set(ObjectManager::class, $objectManager);

        $this->assertNull($container->get(TranslationManager::class));
    }

    public function testOriginalRecipeInterfaceStatic(): void
    {
        $container = $this->buildContainer();
        $container->set(OriginalRecipeInterface::class . ':Static', $this->createMock(OriginalRecipeInterface::class));
        $container->set(LoadTranslationsInterface::class, $this->createMock(LoadTranslationsInterface::class));

        $this->assertInstanceOf(OriginalRecipeInterface::class, $container->get(OriginalRecipeInterface::class . ':Static'));
    }
}
