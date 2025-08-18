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

namespace Teknoo\East\Translation\Doctrine\Translatable\Mapping;

use DateInterval;
use DateTime;
use DateTimeInterface;
use Psr\Cache\CacheItemInterface;

use function microtime;

/**
 * To wrap translation configuration about an object class.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Configuration implements CacheItemInterface
{
    private ?float $expiry = null;

    /**
     * @param array{
     *          useObjectClass?: string,
     *          translationClass?: string,
     *          fields?: list<string>|null,
     *          fallback?: array<string, bool>
     *      } $configurations
     */
    public function __construct(
        private readonly string $cacheId,
        private array $configurations,
        private bool $isHit = true,
    ) {
    }

    public function getKey(): string
    {
        return $this->cacheId;
    }

    /**
     * @return array{
     *          useObjectClass?: string,
     *          translationClass?: string,
     *          fields?: list<string>|null,
     *          fallback?: array<string, bool>
     *      }
     */
    public function get(): mixed
    {
        return $this->configurations;
    }

    public function isHit(): bool
    {
        return $this->isHit;
    }

    /**
     * @param array{
     *          useObjectClass?: string,
     *          translationClass?: string,
     *          fields?: list<string>|null,
     *          fallback?: array<string, bool>
     *      } $value
     */
    public function set(mixed $value): static
    {
        $this->configurations = $value;
        $this->isHit = false;

        return $this;
    }

    public function expiresAt(?DateTimeInterface $expiration): static
    {
        if (null === $expiration) {
            $this->expiry = null;
        } else {
            $this->expiry = (float) $expiration->format('U.u');
        }

        return $this;
    }

    public function expiresAfter(DateInterval|int|null $time): static
    {
        if ($time === null) {
            $this->expiry = null;
        } elseif ($time instanceof DateInterval) {
            $this->expiry = microtime(true)
                + (float) new DateTime()->add($time)->format('U.u')
            ;
        } else {
            $this->expiry = $time + microtime(true);
        }

        return $this;
    }

    /**
     * @internal
     */
    public function getExpiry(): ?float
    {
        return $this->expiry;
    }
}
