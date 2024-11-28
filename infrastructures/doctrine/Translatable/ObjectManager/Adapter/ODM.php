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

namespace Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\Adapter;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\Persistence\Mapping\ClassMetadata as BaseClassMetadata;
use Teknoo\East\Common\Contracts\Object\ObjectInterface;
use Teknoo\East\Translation\Contracts\Object\TranslatableInterface;
use Teknoo\East\Common\Contracts\DBSource\ManagerInterface;
use Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface;
use Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\Exception\WrongClassMetadata;
use Teknoo\East\Translation\Doctrine\Translatable\TranslatableListener;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;

use function spl_object_hash;

/**
 * Implementation of adapter dedicated to Doctrine ODM Manager to use it into this library as Object Manager to update
 * objects's states.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ODM implements AdapterInterface
{
    private ?UnitOfWork $unitOfWork = null;

    public function __construct(
        private readonly ManagerInterface $eastManager,
        private readonly DocumentManager $doctrineManager,
    ) {
    }

    public function openBatch(): ManagerInterface
    {
        $this->eastManager->openBatch();

        return $this;
    }

    public function closeBatch(): ManagerInterface
    {
        $this->eastManager->closeBatch();

        return $this;
    }

    public function persist(ObjectInterface $object): ManagerInterface
    {
        $this->eastManager->persist($object);

        return $this;
    }

    public function remove(ObjectInterface $object): ManagerInterface
    {
        $this->eastManager->remove($object);

        return $this;
    }

    public function flush(): ManagerInterface
    {
        $this->eastManager->flush();

        return $this;
    }

    /**
     * @param class-string<object> $class
     */
    public function findClassMetadata(string $class, TranslatableListener $listener): AdapterInterface
    {
        $listener->registerClassMetadata(
            $class,
            $this->doctrineManager->getClassMetadata($class)
        );

        return $this;
    }

    private function getUnitOfWork(): UnitOfWork
    {
        return $this->unitOfWork ??= $this->doctrineManager->getUnitOfWork();
    }

    public function ifObjectHasChangeSet(TranslatableInterface $object, callable $callback): AdapterInterface
    {
        $changeSet = $this->getUnitOfWork()->getDocumentChangeSet($object);

        if (!empty($changeSet)) {
            $callback($changeSet);
        }

        return $this;
    }

    /**
     * @param BaseClassMetadata<IdentifiedObjectInterface> $metadata
     */
    public function recomputeSingleObjectChangeSet(
        BaseClassMetadata $metadata,
        TranslatableInterface $object
    ): AdapterInterface {
        if (!$metadata instanceof ClassMetadata) {
            throw new WrongClassMetadata('Error this ClassMetadata is not compatible with the document manager');
        }

        $uow = $this->getUnitOfWork();
        $uow->clearDocumentChangeSet(spl_object_hash($object));
        $uow->recomputeSingleDocumentChangeSet($metadata, $object);

        return $this;
    }

    public function foreachScheduledObjectInsertions(callable $callback): AdapterInterface
    {
        foreach ($this->getUnitOfWork()->getScheduledDocumentInsertions() as $document) {
            $callback($document);
        }

        return $this;
    }

    public function foreachScheduledObjectUpdates(callable $callback): AdapterInterface
    {
        foreach ($this->getUnitOfWork()->getScheduledDocumentUpdates() as $document) {
            $callback($document);
        }

        return $this;
    }

    public function foreachScheduledObjectDeletions(callable $callback): AdapterInterface
    {
        foreach ($this->getUnitOfWork()->getScheduledDocumentDeletions() as $document) {
            $callback($document);
        }

        return $this;
    }

    public function setObjectPropertyInManager(string $oid, string $property, mixed $value): AdapterInterface
    {
        $this->getUnitOfWork()->setOriginalDocumentProperty($oid, $property, $value);

        return $this;
    }
}
