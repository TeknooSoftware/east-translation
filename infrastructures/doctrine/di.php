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

namespace Teknoo\East\Translation\Doctrine;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as OdmClassMetadata;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\FileLocator;
use Doctrine\Persistence\ObjectManager;
use Psr\Container\ContainerInterface;
use SimpleXMLElement;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Teknoo\East\Common\Contracts\DBSource\ManagerInterface;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;
use Teknoo\East\Common\Contracts\Recipe\Plan\CreateObjectEndPointInterface;
use Teknoo\East\Common\Contracts\Recipe\Plan\DeleteObjectEndPointInterface;
use Teknoo\East\Common\Contracts\Recipe\Plan\EditObjectEndPointInterface;
use Teknoo\East\Common\Contracts\Recipe\Plan\ListObjectEndPointInterface;
use Teknoo\East\Common\Contracts\Recipe\Plan\MinifierCommandInterface;
use Teknoo\East\Common\Contracts\Recipe\Plan\RenderStaticContentEndPointInterface;
use Teknoo\East\Translation\Contracts\DBSource\TranslationManagerInterface;
use Teknoo\East\Translation\Contracts\Object\TranslatableInterface;
use Teknoo\East\Translation\Contracts\Recipe\Step\LoadTranslationsInterface;
use Teknoo\East\Translation\Doctrine\Exception\NotSupportedException;
use Teknoo\East\Translation\Doctrine\Recipe\Step\LoadTranslations;
use Teknoo\East\Translation\Doctrine\Translatable\Mapping\Driver\SimpleXmlFactoryInterface;
use Teknoo\East\Translation\Doctrine\Translatable\Mapping\Driver\Xml;
use Teknoo\East\Translation\Doctrine\Translatable\Mapping\DriverFactoryInterface;
use Teknoo\East\Translation\Doctrine\Translatable\Mapping\DriverInterface;
use Teknoo\East\Translation\Doctrine\Translatable\Mapping\ExtensionMetadataFactory;
use Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\Adapter\ODM as ODMAdapter;
use Teknoo\East\Translation\Doctrine\Translatable\Persistence\Adapter\ODM as ODMPersistence;
use Teknoo\East\Translation\Doctrine\Translatable\TranslatableListener;
use Teknoo\East\Translation\Doctrine\Translatable\TranslationManager;
use Teknoo\East\Translation\Doctrine\Translatable\Wrapper\DocumentWrapper;
use Teknoo\East\Translation\Doctrine\Translatable\Wrapper\FactoryInterface as WrapperFactory;
use Teknoo\East\Translation\Doctrine\Translatable\Wrapper\WrapperInterface;
use Teknoo\East\Common\Middleware\LocaleMiddleware;

use function DI\create;
use function DI\decorate;
use function DI\get;

return [

    ODMPersistence::class => static function (ContainerInterface $container): ODMPersistence {
        $objectManager = $container->get(ObjectManager::class);

        if (!$objectManager instanceof DocumentManager) {
            throw new NotSupportedException('Sorry currently, this listener supports only ODM');
        }

        $deferred = false;
        if ($container->has('teknoo.east.translation.deferred_loading')) {
            $deferred = !empty($container->get('teknoo.east.translation.deferred_loading'));
        }

        return new ODMPersistence(
            manager: $objectManager,
            deferred: $deferred,
        );
    },

    TranslationManager::class => static function (ContainerInterface $container): ?TranslationManager {
        $objectManager = $container->get(ObjectManager::class);

        if (!$objectManager instanceof DocumentManager) {
            return null;
        }

        return new TranslationManager(
            $container->get(ODMPersistence::class),
        );
    },

    TranslationManagerInterface::class => get(TranslationManager::class),

    LoadTranslations::class => create()
        ->constructor(get(TranslationManagerInterface::class)),

    LoadTranslationsInterface::class => get(LoadTranslations::class),

    TranslatableListener::class => static function (ContainerInterface $container): TranslatableListener {
        $objectManager = $container->get(ObjectManager::class);
        $eastManager = $container->get(ManagerInterface::class);
        $persistence = $container->get(ODMPersistence::class);

        $eventManager = $objectManager->getEventManager();

        $translatableManagerAdapter = new ODMAdapter(
            $eastManager,
            $objectManager
        );

        $mappingDriver = $objectManager->getConfiguration()->getMetadataDriverImpl();
        if (null === $mappingDriver) {
            throw new NotSupportedException('The Mapping Driver is not available from the Doctrine manager');
        }

        $extensionMetadataFactory = new ExtensionMetadataFactory(
            $objectManager,
            $objectManager->getMetadataFactory(),
            $mappingDriver,
            new class () implements DriverFactoryInterface {
                public function __invoke(FileLocator $locator): DriverInterface
                {
                    return new Xml(
                        $locator,
                        new class () implements SimpleXmlFactoryInterface {
                            public function __invoke(string $file): SimpleXMLElement
                            {
                                return new SimpleXMLElement($file, 0, true);
                            }
                        }
                    );
                }
            },
            $container->get(ArrayAdapter::class),
        );

        $translatableListener = new TranslatableListener(
            $extensionMetadataFactory,
            $translatableManagerAdapter,
            $persistence,
            new class () implements WrapperFactory {
                /**
                 * @param ClassMetadata<IdentifiedObjectInterface> $metadata
                 */
                public function __invoke(TranslatableInterface $object, ClassMetadata $metadata): WrapperInterface
                {
                    if (!$metadata instanceof OdmClassMetadata) {
                        throw new NotSupportedException('Error wrapper support only ' . OdmClassMetadata::class);
                    }

                    return new DocumentWrapper($object, $metadata);
                }
            }
        );

        $eventManager->addEventSubscriber($translatableListener);

        return $translatableListener;
    },

    LocaleMiddleware::class => static function (ContainerInterface $container): LocaleMiddleware {
        if (
            $container->has(ObjectManager::class)
            && ($container->get(ObjectManager::class)) instanceof DocumentManager
        ) {
            $listener = $container->get(TranslatableListener::class);
            $callback = $listener->setLocale(...);
        } else {
            //do nothing
            $callback = null;
        }

        return new LocaleMiddleware($callback);
    },

    // @codeCoverageIgnoreStart
    CreateObjectEndPointInterface::class => decorate(
        static function (
            CreateObjectEndPointInterface $plan,
            ContainerInterface $container,
        ): CreateObjectEndPointInterface {
            $plan->add(
                action: $container->get(LoadTranslationsInterface::class),
                position: 1,
            );

            return $plan;
        }
    ),

    DeleteObjectEndPointInterface::class => decorate(
        static function (
            DeleteObjectEndPointInterface $plan,
            ContainerInterface $container,
        ): DeleteObjectEndPointInterface {
            $plan->add(
                action: $container->get(LoadTranslationsInterface::class),
                position: 1,
            );

            return $plan;
        }
    ),

    EditObjectEndPointInterface::class => decorate(
        static function (
            EditObjectEndPointInterface $plan,
            ContainerInterface $container,
        ): EditObjectEndPointInterface {
            $plan->add(
                action: $container->get(LoadTranslationsInterface::class),
                position: 1,
            );

            return $plan;
        }
    ),

    ListObjectEndPointInterface::class => decorate(
        static function (
            ListObjectEndPointInterface $plan,
            ContainerInterface $container,
        ): ListObjectEndPointInterface {
            $plan->add(
                action: $container->get(LoadTranslationsInterface::class),
                position: 1,
            );

            return $plan;
        }
    ),

    MinifierCommandInterface::class => decorate(
        static function (
            MinifierCommandInterface $plan,
            ContainerInterface $container,
        ): MinifierCommandInterface {
            $plan->add(
                action: $container->get(LoadTranslationsInterface::class),
                position: 1,
            );

            return $plan;
        }
    ),

    RenderStaticContentEndPointInterface::class => decorate(
        static function (
            RenderStaticContentEndPointInterface $plan,
            ContainerInterface $container,
        ): RenderStaticContentEndPointInterface {
            $plan->add(
                action: $container->get(LoadTranslationsInterface::class),
                position: 1,
            );

            return $plan;
        }
    ),
    // @codeCoverageIgnoreEnd
];
