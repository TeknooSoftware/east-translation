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

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as ClassMetadataODM;

/**
 * Interface to define driver, able to read a configuration / metadata about class/object's translations and return it
 * to the ExtensionMetadataFactory to configure this Doctrine extension.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
interface DriverInterface
{
    /*
     * Read extended metadata configuration for a single mapped class
     */
    /**
     * @param ClassMetadata<ClassMetadataODM|object> $meta
     * @param array<string, mixed> $config
     */
    public function readExtendedMetadata(ClassMetadata $meta, array &$config): DriverInterface;
}
