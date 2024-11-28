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

date_default_timezone_set('UTC');

error_reporting(E_ALL | E_STRICT);

ini_set('memory_limit', '128M');

include __DIR__ . '/fakeQuery.php';
include __DIR__ . '/fakeUOW.php';
include __DIR__ . '/fakeRuntimeException.php';
include __DIR__ . '/fakeObjectId.php';
include __DIR__.'/../vendor/autoload.php';

