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

namespace Teknoo\East\Translation\Doctrine\Translatable;

use Doctrine\Common\EventSubscriber;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use DomainException;
use ProxyManager\Proxy\GhostObjectInterface;
use ReflectionException;
use Teknoo\East\Translation\Contracts\Object\TranslatableInterface;
use Teknoo\East\Translation\Doctrine\Translatable\Exception\NotTranslatableClassException;
use Teknoo\East\Translation\Doctrine\Translatable\Mapping\ExtensionMetadataFactory;
use Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface as ManagerAdapterInterface;
use Teknoo\East\Translation\Doctrine\Translatable\Persistence\AdapterInterface as PersistenceAdapterInterface;
use Teknoo\East\Translation\Doctrine\Translatable\Wrapper\FactoryInterface;
use Teknoo\East\Translation\Doctrine\Translatable\Wrapper\WrapperInterface;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;
use Teknoo\Recipe\Promise\Promise;

use function array_flip;
use function get_parent_class;
use function spl_object_hash;

/**
 * The translation listener handles the generation and
 * loading of translations for object which implements
 * the TranslatableInterface interface.
 *
 * This behavior can impact the performance of your application
 * since it does an additional query for each field to translate.
 *
 * Nevertheless the xml metadata is properly cached and
 * it is not a big overhead to lookup all objects mapping since
 * the caching is activated for metadata
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
class TranslatableListener implements EventSubscriber
{
    /**
     * List of translations which do not have the foreign
     * key generated yet - MySQL case. These translations
     * will be updated with new keys on postPersist event
     * @var array<string, array<int, TranslationInterface>>
     */
    private array $pendingTranslationInserts = [];

    /**
     * Tracks objects to reload after flush
     * @var array<
     *     string,
     *     array<array{
     *      0:WrapperInterface,
     *      1: string,
     *      2:array{
     *          useObjectClass: string,
     *          translationClass: string,
     *          fields: array<int, string>,
     *          fallback: array<string, string>
     *      },
     *      3:\Doctrine\Persistence\Mapping\ClassMetadata<IdentifiedObjectInterface>
     *     }>
     * >
     */
    private array $objectsToTranslate = [];

    /**
     * List of cached object configurations leaving it static for reasons to look into
     * other listener configuration.
     * @var array<
     *     string,
     *      array{
     *        useObjectClass: string,
     *        translationClass: string,
     *        fields: array<int, string>|null,
     *        fallback: array<string, string>
     *      }
     *  >
     */
    private array $configurations = [];

    /**
     * List of cached class metadata from doctrine manager
     * @var array<string, ClassMetadata<IdentifiedObjectInterface>>
     */
    private array $classMetadata = [];

    /**
     * @var array<string, WrapperInterface>
     */
    private array $wrappers = [];

    public function __construct(
        private readonly ExtensionMetadataFactory $extensionMetadataFactory,
        private readonly ManagerAdapterInterface $manager,
        private readonly PersistenceAdapterInterface $persistence,
        private readonly FactoryInterface $wrapperFactory,
        private string $locale = 'en',
        private readonly string $defaultLocale = 'en',
        private readonly bool $translationFallback = true
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            'loadClassMetadata',
            'postLoad',
            'onFlush',
            'postPersist',
            'postFlush',
        ];
    }

    /**
     * @return ClassMetadata<IdentifiedObjectInterface>
     */
    private function getClassMetadata(string $className): ClassMetadata
    {
        if (isset($this->classMetadata[$className])) {
            return $this->classMetadata[$className];
        }

        $this->manager->findClassMetadata($className, $this);

        if (isset($this->classMetadata[$className])) {
            return $this->classMetadata[$className];
        }

        throw new DomainException("Error no classmeta data available for $className");
    }

    /**
     * @param ClassMetadata<IdentifiedObjectInterface> $classMetadata
     */
    public function registerClassMetadata(string $className, ClassMetadata $classMetadata): self
    {
        $this->classMetadata[$className] = $classMetadata;

        return $this;
    }

    public function setLocale(string $locale): self
    {
        if (empty($locale)) {
            $locale = $this->defaultLocale;
        }

        $this->locale = $locale;

        return $this;
    }

    private function getObjectClassName(TranslatableInterface $object): string
    {
        if ($object instanceof GhostObjectInterface) {
            return (string) get_parent_class($object);
        }

        return $object::class;
    }

    /**
     * @param ClassMetadata<IdentifiedObjectInterface> $metadata
     */
    private function wrap(TranslatableInterface $translatable, ClassMetadata $metadata): WrapperInterface
    {
        $oid = spl_object_hash($translatable);

        return $this->wrappers[$oid] ??= ($this->wrapperFactory)($translatable, $metadata);
    }

    /**
     * @param ClassMetadata<IdentifiedObjectInterface> $metadata
     */
    private function loadMetadataForObjectClass(ClassMetadata $metadata): void
    {
        $this->extensionMetadataFactory->loadExtensionMetadata($metadata, $this);
    }

    /**
     * @param ClassMetadata<IdentifiedObjectInterface> $metadata
     * @param array{
     *        useObjectClass: string,
     *        translationClass: string,
     *        fields: array<int, string>|null,
     *        fallback: array<string, string>
     *      } $config
     */
    public function injectConfiguration(ClassMetadata $metadata, array $config): self
    {
        $className = $metadata->getName();

        $this->configurations[$className] = $config;

        return $this;
    }

    /**
     * @param ClassMetadata<IdentifiedObjectInterface> $metadata
     * @return array{
     *        useObjectClass: string,
     *        translationClass: string,
     *        fields: array<int, string>|null,
     *        fallback: array<string, string>
     *      }
     */
    private function getConfiguration(ClassMetadata $metadata): array
    {
        $className = $metadata->getName();
        if (isset($this->configurations[$className])) {
            return $this->configurations[$className];
        }

        $this->configurations[$className] = [
            'useObjectClass' => $className,
            'translationClass' => '',
            'fields' => [],
            'fallback' => [],
        ];
        $this->loadMetadataForObjectClass($metadata);

        return $this->configurations[$className];
    }

    /**
     * @param LoadClassMetadataEventArgs<ClassMetadata<IdentifiedObjectInterface>, ObjectManager> $event
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event): self
    {
        $metadata = $event->getClassMetadata();

        $this->classMetadata[$metadata->getName()] = $metadata;

        $this->loadMetadataForObjectClass($metadata);

        return $this;
    }

    /*
     * Gets the locale to use for translation. Loads object
     * defined locale first..
     */
    private function getTranslatableLocale(
        TranslatableInterface $object
    ): string {
        return $object->getLocaleField() ?? $this->locale;
    }

    /**
     * @param array{
     *     useObjectClass: string,
     *     translationClass: string,
     *     fields: array<int, string>|null,
     *     fallback: array<string, string>
     * } $config
     * @param ClassMetadata<IdentifiedObjectInterface> $metaData
     */
    private function loadAllTranslations(
        WrapperInterface $wrapper,
        string $locale,
        string $translationClass,
        array $config,
        ClassMetadata $metaData
    ): void {
        $wrapper->loadAllTranslations(
            $this->persistence,
            $locale,
            $translationClass,
            $config['useObjectClass'],
            function (iterable $result) use ($wrapper, $config, $metaData): void {
                if (empty($result)) {
                    return;
                }

                // translate object's translatable properties
                foreach (($config['fields'] ?? []) as $field) {
                    $translated = '';
                    $isTranslated = false;
                    foreach ($result as $entry) {
                        if ($entry['field'] === $field) {
                            $translated = $entry['content'] ?? null;
                            $isTranslated = true;
                            break;
                        }
                    }

                    // update translation
                    if (
                        $isTranslated
                        || (!$this->translationFallback && empty($config['fallback'][$field]))
                    ) {
                        $this->persistence->setTranslatedValue($wrapper, $metaData, $field, $translated);
                        $wrapper->setObjectPropertyInManager($this->manager, $field);
                    }
                }
            }
        );
    }

    /**
     * After object is loaded, listener updates the translations by currently used locale
     * @param LifecycleEventArgs<ObjectManager> $event
     */
    public function postLoad(LifecycleEventArgs $event): self
    {
        $object = $event->getObject();

        if (!$object instanceof TranslatableInterface) {
            return $this;
        }

        $metaData = $this->getClassMetadata($this->getObjectClassName($object));

        $config = $this->getConfiguration($metaData);
        if (empty($config['fields'])) {
            return $this;
        }

        $locale = $this->getTranslatableLocale($object);
        $object->setLocaleField($locale);

        if ($locale === $this->defaultLocale) {
            return $this;
        }

        // fetch translations
        $translationClass = $config['translationClass'];
        $wrapper = $this->wrap($object, $metaData);

        $this->loadAllTranslations($wrapper, $locale, $translationClass, $config, $metaData);

        return $this;
    }

    /*
     * Creates and update the translation for object being flushed
     */
    private function handleTranslatableObjectChanges(
        TranslatableInterface $object,
        bool $isInsert
    ): void {
        $metaData = $this->getClassMetadata($this->getObjectClassName($object));
        $wrapper = $this->wrap($object, $metaData);
        $config = $this->getConfiguration($metaData);

        $translationClass = $config['translationClass'];

        // load the currently used locale
        $locale = $this->getTranslatableLocale($object);

        if ($locale === $this->defaultLocale) {
            return;
        }

        $this->objectsToTranslate[$locale][] = [$wrapper, $translationClass, $config, $metaData];

        $this->manager->ifObjectHasChangeSet(
            $object,
            function (array $changeSet) use (
                &$config,
                $object,
                &$locale,
                &$isInsert,
                $wrapper,
                &$translationClass,
                $metaData
            ): void {
                // check for the availability of the primary key
                $oid = spl_object_hash($object);

                $translationMetadata = $this->getClassMetadata($translationClass);
                $translationReflection = $translationMetadata->getReflectionClass();

                $translatableFields = array_flip($config['fields'] ?? []);
                $updatedTranslations = [];
                foreach ($translatableFields as $field => $notUsed) {
                    if (!isset($changeSet[$field])) {
                        continue; // locale is same and nothing changed
                    }

                    $translation = null;
                    if (!$isInsert) {
                        $translationPromise = new Promise(
                            static fn (TranslationInterface $result): TranslationInterface => $result,
                        );

                        $wrapper->findTranslation(
                            $this->persistence,
                            $locale,
                            $field,
                            $translationClass,
                            $config['useObjectClass'],
                            $translationPromise,
                        );
                        $translation = $translationPromise->fetchResult();
                    }

                    // create new translation if translation not already created and locale is different from default
                    // locale, otherwise, we have the date in the original record
                    if (!$translation instanceof TranslationInterface && $locale !== $this->defaultLocale) {
                        try {
                            $translation = $translationReflection->newInstance();
                            if (!$translation instanceof TranslationInterface) {
                                throw new NotTranslatableClassException(
                                    'Error the translation object does not implement the interface'
                                );
                            }
                        } catch (ReflectionException) {
                            throw new NotTranslatableClassException(
                                'Error the translation object does not implement the interface'
                            );
                        }

                        $translation->setLocale($locale);
                        $translation->setField($field);
                        $translation->setObjectClass($config['useObjectClass']);
                        $wrapper->linkTranslationRecord($translation);
                    }

                    if ($translation instanceof TranslationInterface) {
                        // set the translated field, take value using reflection
                        $this->persistence->updateTranslationRecord($wrapper, $metaData, $field, $translation);

                        if ($isInsert) {
                            // if we do not have the primary key yet available
                            // keep this translation in memory to insert it later with foreign key
                            $this->pendingTranslationInserts[$oid][] = $translation;
                        } else {
                            $this->persistence->persistTranslationRecord($translation);
                            $updatedTranslations[] = $translation->getIdentifier();
                        }
                    }
                }

                // check if we have default translation and need to reset the translation
                if (!$isInsert) {
                    foreach ($changeSet as $field => $changes) {
                        $this->manager->setObjectPropertyInManager($oid, $field, $changes[0]);
                        if (isset($translatableFields[$field]) && $locale !== $this->defaultLocale) {
                            $wrapper->setPropertyValue($field, $changes[0]);
                        }
                    }

                    $this->manager->recomputeSingleObjectChangeset($metaData, $object);

                    $this->persistence->removeOrphansTranslations(
                        identifier: $object->getId(),
                        updatedTranslations: $updatedTranslations,
                        translationClass: $translationClass,
                        objectClass: $config['useObjectClass'],
                    );
                }
            }
        );
    }

    /*
     * Looks for translatable objects being inserted or updated for further processing
     */
    public function onFlush(): self
    {
        $this->objectsToTranslate = [];

        $handling = function ($object, $isInsert): void {
            if (!$object instanceof TranslatableInterface) {
                return;
            }

            $metaData = $this->getClassMetadata($this->getObjectClassName($object));
            $config = $this->getConfiguration($metaData);

            if (isset($config['fields'])) {
                $this->handleTranslatableObjectChanges($object, $isInsert);
            }
        };

        // check all scheduled inserts for TranslatableInterface objects
        $this->manager->foreachScheduledObjectInsertions(static function ($object) use ($handling): void {
            $handling($object, true);
        });

        $this->manager->foreachScheduledObjectUpdates(static function ($object) use ($handling): void {
            $handling($object, false);
        });

        $this->manager->foreachScheduledObjectDeletions(function ($object): void {
            if (!$object instanceof TranslatableInterface) {
                return;
            }

            $metaData = $this->getClassMetadata($this->getObjectClassName($object));
            $config = $this->getConfiguration($metaData);

            if (isset($config['fields'])) {
                $wrapper = $this->wrap($object, $metaData);
                $wrapper->removeAssociatedTranslations(
                    $this->persistence,
                    $config['translationClass'],
                    $config['useObjectClass']
                );
            }
        });

        return $this;
    }

    public function postFlush(): self
    {
        foreach ($this->objectsToTranslate as $local => &$objects) {
            foreach ($objects as &$object) {
                $this->loadAllTranslations($object[0], $local, $object[1], $object[2], $object[3]);
            }

            unset($object);
        }

        unset($objects);

        $this->objectsToTranslate = [];

        return $this;
    }

    /**
     * Checks for inserted object to update their translation foreign keys
     * @param LifecycleEventArgs<ObjectManager> $event
     */
    public function postPersist(LifecycleEventArgs $event): self
    {
        $object = $event->getObject();

        if (!$object instanceof TranslatableInterface) {
            return $this;
        }

        $oid = spl_object_hash($object);

        if (!isset($this->pendingTranslationInserts[$oid])) {
            return $this;
        }

        $metaData = $this->getClassMetadata($this->getObjectClassName($object));
        $wrapper = $this->wrap($object, $metaData);
        // load the pending translations without key
        foreach ($this->pendingTranslationInserts[$oid] as $translation) {
            $wrapper->linkTranslationRecord($translation);
            $this->persistence->persistTranslationRecord($translation);
        }

        unset($this->pendingTranslationInserts[$oid]);

        return $this;
    }
}
