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

namespace Teknoo\Tests\East\Translation\Doctrine\Translatable\Wrapper;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Types\Type;
use PHPUnit\Framework\Attributes\CoversClass;
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
 * @link        http://teknoo.software/east/translation project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 */
#[CoversClass(DocumentWrapper::class)]
class DocumentWrapperTest extends TestCase
{
    private ?TranslatableInterface $object = null;

    private ?ClassMetadata $meta = null;

    /**
     * @return \Teknoo\East\Translation\Contracts\Object\TranslatableInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getObject(): TranslatableInterface
    {
        if (!$this->object instanceof TranslatableInterface) {
            $this->object = $this->createMock(TranslatableInterface::class);
        }

        return $this->object;
    }

    /**
     * @return ClassMetadata|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getMeta(): ClassMetadata
    {
        if (!$this->meta instanceof ClassMetadata) {
            $this->meta = $this->createMock(ClassMetadata::class);
        }

        return $this->meta;
    }

    public function build(): DocumentWrapper
    {
        return new DocumentWrapper($this->getObject(), $this->getMeta());
    }

    public function testSetPropertyValue()
    {
        self::assertInstanceOf(
            WrapperInterface::class,
            $this->build()->setPropertyValue('foo', 'bar')
        );
    }

    public function testSetPropertyValueWithProxy()
    {
        $this->object = new class implements TranslatableInterface, GhostObjectInterface {
            public function setProxyInitializer(?\Closure $initializer = null)
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

        self::assertInstanceOf(
            WrapperInterface::class,
            $this->build()->setPropertyValue('foo', 'bar')
        );
    }

    public function testSetObjectPropertyInManagerWithProxy()
    {
        $manager = $this->createMock(ManagerAdapterInterface::class);
        $manager->expects($this->once())->method('setObjectPropertyInManager');

        $this->object = new class implements TranslatableInterface, GhostObjectInterface {
            public function setProxyInitializer(?\Closure $initializer = null)
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

        self::assertInstanceOf(
            WrapperInterface::class,
            $this->build()->setObjectPropertyInManager($manager, 'bar')
        );
    }

    public function testSetObjectPropertyInManager()
    {
        $manager = $this->createMock(ManagerAdapterInterface::class);
        $manager->expects($this->once())->method('setObjectPropertyInManager');

        self::assertInstanceOf(
            WrapperInterface::class,
            $this->build()->setObjectPropertyInManager($manager, 'bar')
        );
    }

    public function testUpdateTranslationRecord()
    {
        $translation = $this->createMock(TranslationInterface::class);
        $type = $this->createMock(Type::class);

        $translation->expects($this->once())->method('setContent');

        self::assertInstanceOf(
            WrapperInterface::class,
            $this->build()->updateTranslationRecord($translation, 'foo', $type)
        );
    }

    public function testLinkTranslationRecord()
    {
        $translation = $this->createMock(TranslationInterface::class);

        $translation->expects($this->once())->method('setForeignKey');

        self::assertInstanceOf(
            WrapperInterface::class,
            $this->build()->linkTranslationRecord($translation)
        );
    }

    public function testloadAllTranslations()
    {
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->once())->method('loadAllTranslations');

        self::assertInstanceOf(
            WrapperInterface::class,
            $this->build()->loadAllTranslations(
                $adapter,
                'fr',
                'fooClass',
                'barClass',
                function() {}
            )
        );
    }

    public function testFindTranslation()
    {
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->once())->method('findTranslation');

        self::assertInstanceOf(
            WrapperInterface::class,
            $this->build()->findTranslation(
                $adapter,
                'fr',
                'field',
                'fooClass',
                'barClass',
                function() {}
            )
        );
    }

    public function testRemoveAssociatedTranslations()
    {
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->once())->method('removeAssociatedTranslations');

        self::assertInstanceOf(
            WrapperInterface::class,
            $this->build()->removeAssociatedTranslations(
                $adapter,
                'fooClass',
                'barClass'
            )
        );
    }
}
