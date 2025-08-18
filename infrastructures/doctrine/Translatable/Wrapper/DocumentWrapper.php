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

namespace Teknoo\East\Translation\Doctrine\Translatable\Wrapper;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Types\Type;
use ProxyManager\Proxy\GhostObjectInterface;
use Teknoo\East\Translation\Contracts\Object\TranslatableInterface;
use Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface as ManagerAdapterInterface;
use Teknoo\East\Translation\Doctrine\Translatable\Persistence\AdapterInterface;
use Teknoo\East\Translation\Doctrine\Translatable\TranslationInterface;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;

use function spl_object_id;

/**
 * Implementation of WrapperInterface dedicated to Document managed by Doctrine ODM, to allow this extension to work
 * evenly with Doctrine Document and Doctrine Entity.
 * This wrapped redirects calls to they wrapped object or class metadata and allow this extension to update value in
 * the wrapped object, manipulate data in the object's manager (according to its implementations/technology)
 * or manage `TranslationInterface` instances linked to the wrapped object. *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class DocumentWrapper implements WrapperInterface
{
    /**
     * @param ClassMetadata<IdentifiedObjectInterface> $meta
     */
    public function __construct(
        private readonly TranslatableInterface $object,
        private readonly ClassMetadata $meta,
    ) {
    }

    private function getIdentifier(): string
    {
        return $this->object->getId();
    }

    private function initialize(): void
    {
        if ($this->object instanceof GhostObjectInterface && !$this->object->isProxyInitialized()) {
            $this->object->initializeProxy();
        }
    }

    private function getPropertyValue(string $name): mixed
    {
        $this->initialize();

        $propertyReflection = $this->meta->getReflectionProperty($name);

        return $propertyReflection->getValue($this->object);
    }

    public function setPropertyValue(string $name, mixed $value): WrapperInterface
    {
        $this->initialize();

        $propertyReflection = $this->meta->getReflectionProperty($name);
        if (null !== $value || $propertyReflection->getType()?->allowsNull()) {
            $propertyReflection->setValue($this->object, $value);
        }

        return $this;
    }

    public function setObjectPropertyInManager(ManagerAdapterInterface $manager, string $name): WrapperInterface
    {
        // ensure clean changeset
        $manager->setObjectPropertyInManager(
            spl_object_id($this->object),
            $name,
            $this->getPropertyValue($name)
        );

        return $this;
    }

    /**
     * @param Type $type
     */
    public function updateTranslationRecord(
        TranslationInterface $translation,
        string $name,
        mixed $type
    ): WrapperInterface {
        $value = $this->getPropertyValue($name);

        $translation->setContent((string) $type->convertToDatabaseValue($value));

        return $this;
    }

    public function linkTranslationRecord(TranslationInterface $translation): WrapperInterface
    {
        $translation->setForeignKey($this->getIdentifier());

        return $this;
    }

    public function loadAllTranslations(
        AdapterInterface $adapter,
        string $locale,
        string $translationClass,
        string $objectClass,
        callable $callback
    ): WrapperInterface {
        $adapter->loadAllTranslations(
            $locale,
            $this->getIdentifier(),
            $translationClass,
            $objectClass,
            $callback
        );

        return $this;
    }

    public function findTranslation(
        AdapterInterface $adapter,
        string $locale,
        string $field,
        string $translationClass,
        string $objectClass,
        callable $callback
    ): WrapperInterface {
        $adapter->findTranslation(
            $locale,
            $field,
            $this->getIdentifier(),
            $translationClass,
            $objectClass,
            $callback
        );

        return $this;
    }

    public function removeAssociatedTranslations(
        AdapterInterface $adapter,
        string $translationClass,
        string $objectClass
    ): WrapperInterface {
        $adapter->removeAssociatedTranslations(
            $this->getIdentifier(),
            $translationClass,
            $objectClass
        );

        return $this;
    }
}
