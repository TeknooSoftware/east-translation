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

namespace Teknoo\East\Translation\Doctrine\Translatable;

use Teknoo\East\Translation\Contracts\DBSource\TranslationManagerInterface;
use Teknoo\East\Translation\Doctrine\Translatable\Persistence\Adapter\ODM as ODMPersistence;

/**
 * Translation manager to enable or disable deferred translations loading
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class TranslationManager implements TranslationManagerInterface
{
    public function __construct(
        private readonly ODMPersistence $persistence,
    ) {
    }

    public function deferringTranslationsLoading(): TranslationManagerInterface
    {
        $this->persistence->setDeferred(true);

        return $this;
    }

    public function stopDeferringTranslationsLoading(): TranslationManagerInterface
    {
        $this->persistence->executeAllDeferredLoadings();
        $this->persistence->setDeferred(false);

        return $this;
    }
}
