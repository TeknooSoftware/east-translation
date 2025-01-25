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
 * @author      Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */

declare(strict_types=1);

namespace Teknoo\East\Translation\Doctrine\Translatable\Mapping;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactoryInterface;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\FileDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as ClassMetadataODM;
use Psr\Cache\CacheItemPoolInterface;
use Teknoo\East\Translation\Doctrine\Exception\InvalidMappingException;
use Teknoo\East\Translation\Doctrine\Translatable\TranslatableListener;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;

use function array_reverse;
use function class_parents;
use function is_callable;
use function property_exists;
use function str_replace;

/**
 * The extension metadata factory is responsible for extension driver
 * initialization and fully reading the extension metadata
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
class ExtensionMetadataFactory
{
    public function __construct(
        private readonly ObjectManager $objectManager,
        private readonly ClassMetadataFactoryInterface $classMetadataFactory,
        private readonly MappingDriver $mappingDriver,
        private readonly DriverFactoryInterface $driverFactory,
        private ?CacheItemPoolInterface $cache = null,
    ) {
    }

    public function setCache(CacheItemPoolInterface $cache): self
    {
        $this->cache = $cache;

        return $this;
    }

    private function getDriver(): DriverInterface
    {
        $omDriver = $this->mappingDriver;
        if ($omDriver instanceof MappingDriverChain) {
            $drivers = $omDriver->getDrivers();
            foreach ($drivers as $nestedOmDriver) {
                if ($nestedOmDriver instanceof FileDriver) {
                    $omDriver = $nestedOmDriver;

                    break;
                }
            }
        }

        if (!$omDriver instanceof FileDriver) {
            throw new InvalidMappingException('Driver not found');
        }

        return ($this->driverFactory)($omDriver->getLocator());
    }

    private static function getCacheId(string $className): string
    {
        return str_replace(
            search: '\\',
            replace: '-',
            subject: $className . '\\$_TRANSLATE_METADATA'
        );
    }

    /**
     * @param ClassMetadata<IdentifiedObjectInterface>|ClassMetadataODM<IdentifiedObjectInterface> $metaData
     */
    public function loadExtensionMetadata(
        ClassMetadata $metaData,
        TranslatableListener $listener
    ): self {
        if (property_exists($metaData, 'isMappedSuperclass') && !empty($metaData->isMappedSuperclass)) {
            return $this;
        }

        $cacheId = self::getCacheId($metaData->getName());
        if (null !== $this->cache && $this->cache->hasItem($cacheId)) {
            /** @var Configuration $config */
            $config = $this->cache->getItem($cacheId);
            $listener->injectConfiguration($metaData, $config->get());

            return $this;
        }

        $driver = $this->getDriver();
        $useObjectName = $metaData->getName();

        // collect metadata from inherited classes
        $config = [];
        foreach (array_reverse((array) class_parents($useObjectName)) as $parentClass) {
            // read only inherited mapped classes
            /** @var class-string $parentClass */
            if ($this->classMetadataFactory->hasMetadataFor($parentClass)) {
                $parentMetaClass = $this->objectManager->getClassMetadata($parentClass);
                $driver->readExtendedMetadata($parentMetaClass, $config);

                if (
                    empty($parentMetaClass->parentClasses)
                    && !empty($config)
                    && (
                        !is_callable([$parentMetaClass, 'isInheritanceTypeNone'])
                        || !$parentMetaClass->isInheritanceTypeNone()
                    )
                ) {
                    $useObjectName = $parentMetaClass->getName();
                }
            }
        }

        $driver->readExtendedMetadata($metaData, $config);

        if (!empty($config) && empty($config['useObjectClass'])) {
            $config['useObjectClass'] = $useObjectName;
        }

        if (null !== $this->cache) {
            $this->cache->save(
                new Configuration(
                    cacheId: $cacheId,
                    configurations: $config,
                    isHit: false,
                )
            );
        }

        $listener->injectConfiguration($metaData, $config);

        return $this;
    }
}
