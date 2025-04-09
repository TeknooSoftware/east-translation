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

namespace Teknoo\East\Translation\Doctrine\Translatable\ObjectManager;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Teknoo\East\Translation\Contracts\Object\TranslatableInterface;
use Teknoo\East\Common\Contracts\DBSource\ManagerInterface;
use Teknoo\East\Translation\Doctrine\Translatable\TranslatableListener;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;

/**
 * Interface to help this extension to work evenly with Doctrine Document Manager or Doctrine Entity Manager.
 * This manager extends `Teknoo\East\Common\Contracts\DBSource\ManagerInterface` because this parent interface define
 * wrapper for Persistence Manager to use in East Translation.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
interface AdapterInterface extends ManagerInterface
{
    public function findClassMetadata(string $class, TranslatableListener $listener): AdapterInterface;

    public function ifObjectHasChangeSet(TranslatableInterface $object, callable $callback): AdapterInterface;

    public function foreachScheduledObjectInsertions(callable $callback): AdapterInterface;

    public function foreachScheduledObjectUpdates(callable $callback): AdapterInterface;

    public function foreachScheduledObjectDeletions(callable $callback): AdapterInterface;

    /**
     * @param ClassMetadata<IdentifiedObjectInterface> $meta
     */
    public function recomputeSingleObjectChangeSet(
        ClassMetadata $meta,
        TranslatableInterface $object
    ): AdapterInterface;

    public function setObjectPropertyInManager(int $oid, string $property, mixed $value): AdapterInterface;
}
