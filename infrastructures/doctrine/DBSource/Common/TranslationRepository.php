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

namespace Teknoo\East\Translation\Doctrine\DBSource\Common;

use Teknoo\East\Common\Doctrine\DBSource\Common\RepositoryTrait;
use Teknoo\East\Translation\Doctrine\Object\Translation;
use Teknoo\East\Common\Contracts\DBSource\RepositoryInterface;

/**
 * Default implementation of repository to manage translation in this library for Doctrine's repositories,
 * following generic Doctrine interfaces.
 * Usable with ORM or ODM, but a optimized version dedicated to ODM is available into `ODM`
 * namespace.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 * @implements RepositoryInterface<Translation>
 */
class TranslationRepository implements RepositoryInterface
{
    /**
     * @use RepositoryTrait<Translation>
     */
    use RepositoryTrait;
}