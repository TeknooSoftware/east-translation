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

namespace Teknoo\Tests\East\Translation\Doctrine\Translatable\Wrapper;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Types\Type;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use ProxyManager\Proxy\GhostObjectInterface;
use Teknoo\East\Translation\Contracts\Object\TranslatableInterface;
use Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\AdapterInterface as ManagerAdapterInterface;
use Teknoo\East\Translation\Doctrine\Translatable\Persistence\AdapterInterface;
use Teknoo\East\Translation\Doctrine\Translatable\TranslationInterface;
use Teknoo\East\Translation\Doctrine\Translatable\Wrapper\DocumentWrapper;
use Teknoo\East\Translation\Doctrine\Translatable\Wrapper\WrapperInterface;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east/translation project website
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 */
#[CoversClass(DocumentWrapper::class)]
class DocumentWrapperTest extends TestCase
{
    private ?TranslatableInterface $object = null;

    private (ClassMetadata&Stub)|(ClassMetadata&MockObject)|null $meta = null;

    public function getObject(bool $stub = false): TranslatableInterface
    {
        if (!$this->object instanceof TranslatableInterface) {
            if ($stub) {
                $this->object = $this->createStub(TranslatableInterface::class);
            } else {
                $this->object = $this->createMock(TranslatableInterface::class);
            }
        }

        return $this->object;
    }

    public function getMeta(bool $stub = false): (ClassMetadata&Stub)|(ClassMetadata&MockObject)
    {
        if (!$this->meta instanceof ClassMetadata) {
            if ($stub) {
                $this->meta = $this->createStub(ClassMetadata::class);
            } else {
                $this->meta = $this->createMock(ClassMetadata::class);
            }
        }

        return $this->meta;
    }

    public function build(): DocumentWrapper
    {
        return new DocumentWrapper($this->getObject(true), $this->getMeta(true));
    }

    public function testSetPropertyValue(): void
    {
        $this->assertInstanceOf(WrapperInterface::class, $this->build()->setPropertyValue('foo', 'bar'));
    }

    public function testSetPropertyValueWithProxy(): void
    {
        $this->object = new class () implements TranslatableInterface, GhostObjectInterface {
            public function setProxyInitializer(?\Closure $initializer = null): void
            {
            }

            public function getProxyInitializer(): ?\Closure
            {
            }

            public function initializeProxy(): bool
            {
                return true;
            }

            public function isProxyInitialized(): bool
            {
                return false;
            }

            public function getId(): string
            {
            }

            public function getLocaleField(): ?string
            {
            }

            public function setLocaleField(?string $localeField): TranslatableInterface
            {
            }
        };

        $this->assertInstanceOf(WrapperInterface::class, $this->build()->setPropertyValue('foo', 'bar'));
    }

    public function testSetObjectPropertyInManagerWithProxy(): void
    {
        $manager = $this->createMock(ManagerAdapterInterface::class);
        $manager->expects($this->once())->method('setObjectPropertyInManager');

        $this->object = new class () implements TranslatableInterface, GhostObjectInterface {
            public function setProxyInitializer(?\Closure $initializer = null): void
            {
            }

            public function getProxyInitializer(): ?\Closure
            {
            }

            public function initializeProxy(): bool
            {
                return true;
            }

            public function isProxyInitialized(): bool
            {
                return false;
            }

            public function getId(): string
            {
            }

            public function getLocaleField(): ?string
            {
            }

            public function setLocaleField(?string $localeField): TranslatableInterface
            {
            }
        };

        $this->assertInstanceOf(WrapperInterface::class, $this->build()->setObjectPropertyInManager($manager, 'bar'));
    }

    public function testSetObjectPropertyInManager(): void
    {
        $manager = $this->createMock(ManagerAdapterInterface::class);
        $manager->expects($this->once())->method('setObjectPropertyInManager');

        $this->assertInstanceOf(WrapperInterface::class, $this->build()->setObjectPropertyInManager($manager, 'bar'));
    }

    public function testUpdateTranslationRecord(): void
    {
        $translation = $this->createMock(TranslationInterface::class);
        $type = $this->createMock(Type::class);

        $translation->expects($this->once())->method('setContent');

        $this->assertInstanceOf(WrapperInterface::class, $this->build()->updateTranslationRecord($translation, 'foo', $type));
    }

    public function testLinkTranslationRecord(): void
    {
        $translation = $this->createMock(TranslationInterface::class);

        $translation->expects($this->once())->method('setForeignKey');

        $this->assertInstanceOf(WrapperInterface::class, $this->build()->linkTranslationRecord($translation));
    }

    public function testloadAllTranslations(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->once())->method('loadAllTranslations');

        $this->assertInstanceOf(WrapperInterface::class, $this->build()->loadAllTranslations(
            $adapter,
            'fr',
            'fooClass',
            'barClass',
            function (): void {}
        ));
    }

    public function testFindTranslation(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->once())->method('findTranslation');

        $this->assertInstanceOf(WrapperInterface::class, $this->build()->findTranslation(
            $adapter,
            'fr',
            'field',
            'fooClass',
            'barClass',
            function (): void {}
        ));
    }

    public function testRemoveAssociatedTranslations(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->once())->method('removeAssociatedTranslations');

        $this->assertInstanceOf(WrapperInterface::class, $this->build()->removeAssociatedTranslations(
            $adapter,
            'fooClass',
            'barClass'
        ));
    }
}
