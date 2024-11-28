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

namespace Teknoo\East\Translation\Doctrine\Translatable\Wrapper;

use Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface as ManagerAdapterInterface;
use Teknoo\East\Translation\Doctrine\Translatable\Persistence\AdapterInterface;
use Teknoo\East\Translation\Doctrine\Translatable\TranslationInterface;

/**
 * Interface to help this extension to work evenly with Doctrine Document and Doctrine Entity.
 * Implementations of this interface must redirect calls to they wrapped object or class metadata.
 *
 * This interface defines method to update value in wrapped object, manipulate data in the object's manager (according
 * to its implementations/technology) or manage `TranslationInterface` instances linked to the wrapped object.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
interface WrapperInterface
{
    public function setPropertyValue(string $name, mixed $value): WrapperInterface;

    public function setObjectPropertyInManager(ManagerAdapterInterface $manager, string $name): WrapperInterface;

    public function updateTranslationRecord(
        TranslationInterface $translation,
        string $name,
        mixed $type
    ): WrapperInterface;

    public function linkTranslationRecord(TranslationInterface $translation): WrapperInterface;

    public function loadAllTranslations(
        AdapterInterface $adapter,
        string $locale,
        string $translationClass,
        string $objectClass,
        callable $callback
    ): WrapperInterface;

    public function findTranslation(
        AdapterInterface $adapter,
        string $locale,
        string $field,
        string $translationClass,
        string $objectClass,
        callable $callback
    ): WrapperInterface;

    public function removeAssociatedTranslations(
        AdapterInterface $adapter,
        string $translationClass,
        string $objectClass
    ): WrapperInterface;
}
