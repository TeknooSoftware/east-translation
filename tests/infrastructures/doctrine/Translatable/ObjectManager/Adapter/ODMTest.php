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

namespace Teknoo\Tests\East\Translation\Doctrine\Translatable\ObjectManager\Adapter;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\Persistence\Mapping\ClassMetadata as BaseClassMetadata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\Contracts\Object\ObjectInterface;
use Teknoo\East\Translation\Contracts\Object\TranslatableInterface;
use Teknoo\East\Common\Contracts\DBSource\ManagerInterface;
use Teknoo\East\Translation\Doctrine\Translatable\ObjectManager\Adapter\ODM;
use Teknoo\East\Translation\Doctrine\Translatable\TranslatableListener;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east/translation project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 */
#[CoversClass(ODM::class)]
class ODMTest extends TestCase
{
    private ?ManagerInterface $eastManager = null;

    private ?DocumentManager $doctrineManager = null;

    /**
     * @return ManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getEastManager(): ManagerInterface
    {
        if (!$this->eastManager instanceof ManagerInterface) {
            $this->eastManager = $this->createMock(ManagerInterface::class);
        }

        return $this->eastManager;
    }

    /**
     * @return DocumentManager|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getDoctrineManager(): DocumentManager
    {
        if (!$this->doctrineManager instanceof DocumentManager) {
            $this->doctrineManager = $this->createMock(DocumentManager::class);
        }

        return $this->doctrineManager;
    }

    public function build(): ODM
    {
        return new ODM($this->getEastManager(), $this->getDoctrineManager());
    }

    public function testOpenBatch()
    {
        $this->getEastManager()->expects($this->once())->method('openBatch');

        self::assertInstanceOf(
            ODM::class,
            $this->build()->openBatch()
        );
    }

    public function testCloseBatch()
    {
        $this->getEastManager()->expects($this->once())->method('closeBatch');

        self::assertInstanceOf(
            ODM::class,
            $this->build()->closeBatch()
        );
    }

    public function testPersist()
    {
        $object = $this->createMock(ObjectInterface::class);
        $this->getEastManager()->expects($this->once())->method('persist')->with($object);

        self::assertInstanceOf(
            ODM::class,
            $this->build()->persist($object)
        );
    }

    public function testRemove()
    {
        $object = $this->createMock(ObjectInterface::class);
        $this->getEastManager()->expects($this->once())->method('remove')->with($object);

        self::assertInstanceOf(
            ODM::class,
            $this->build()->remove($object)
        );
    }

    public function testFlush()
    {
        $this->getEastManager()->expects($this->once())->method('flush')->with();

        self::assertInstanceOf(
            ODM::class,
            $this->build()->flush()
        );
    }

    public function testRegisterFilter()
    {
        $this->getEastManager()->expects($this->once())->method('registerFilter')->with(\stdClass::class, ['foo']);

        self::assertInstanceOf(
            ODM::class,
            $this->build()->registerFilter(\stdClass::class, ['foo'])
        );
    }

    public function testEnableFilter()
    {
        $this->getEastManager()->expects($this->once())->method('enableFilter')->with(\stdClass::class);

        self::assertInstanceOf(
            ODM::class,
            $this->build()->enableFilter(\stdClass::class)
        );
    }

    public function testDisableFilter()
    {
        $this->getEastManager()->expects($this->once())->method('disableFilter')->with(\stdClass::class);

        self::assertInstanceOf(
            ODM::class,
            $this->build()->disableFilter(\stdClass::class)
        );
    }

    public function testFindClassMetadata()
    {
        $class = 'Foo\Bar';
        $meta = $this->createMock(ClassMetadata::class);

        $this->getDoctrineManager()
            ->expects($this->once())
            ->method('getClassMetadata')
            ->with($class)
            ->willReturn($meta);

        $listener = $this->createMock(TranslatableListener::class);
        $listener->expects($this->once())->method('registerClassMetadata')->with($class, $meta);

        self::assertInstanceOf(
            ODM::class,
            $this->build()->findClassMetadata($class, $listener)
        );
    }

    public function testIfObjectHasChangeSetEmpty()
    {
        $object = $this->createMock(TranslatableInterface::class);

        $uow = $this->createMock(UnitOfWork::class);
        $this->getDoctrineManager()
            ->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $neverCallback = function () {
            self::fail('must not be called');
        };

        $uow->expects($this->any())->method('getDocumentChangeSet')->willReturn([]);

        self::assertInstanceOf(
            ODM::class,
            $this->build()->ifObjectHasChangeSet($object, $neverCallback)
        );
    }

    public function testIfObjectHasChangeSet()
    {
        $object = $this->createMock(TranslatableInterface::class);

        $changset = ['foo1' => ['bar', 'baba'], 'foo2' => ['bar', 'baba']];

        $uow = $this->createMock(UnitOfWork::class);
        $this->getDoctrineManager()
            ->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $uow->expects($this->any())->method('getDocumentChangeSet')->willReturn($changset);

        $called = false;

        self::assertInstanceOf(
            ODM::class,
            $this->build()->ifObjectHasChangeSet($object, function () use (&$called) {
                $called = true;
            })
        );

        self::assertTrue($called);
    }

    public function testRecomputeSingleObjectChangeSetWithGenericClassMetaData()
    {
        $this->expectException(\RuntimeException::class);

        $meta = $this->createMock(BaseClassMetadata::class);
        $object = $this->createMock(TranslatableInterface::class);

        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects($this->never())->method('clearDocumentChangeSet');
        $uow->expects($this->never())->method('recomputeSingleDocumentChangeSet');

        $this->getDoctrineManager()
            ->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $this->build()->recomputeSingleObjectChangeSet($meta, $object);
    }

    public function testRecomputeSingleObjectChangeSet()
    {
        $meta = $this->createMock(ClassMetadata::class);
        $object = $this->createMock(TranslatableInterface::class);

        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects($this->once())->method('clearDocumentChangeSet');
        $uow->expects($this->once())->method('recomputeSingleDocumentChangeSet');

        $this->getDoctrineManager()
            ->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        self::assertInstanceOf(
            ODM::class,
            $this->build()->recomputeSingleObjectChangeSet($meta, $object)
        );
    }

    public function testForeachScheduledObjectInsertions()
    {
        $list = [new \stdClass(), new \stdClass()];

        $uow = $this->createMock(UnitOfWork::class);
        $this->getDoctrineManager()
            ->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $uow->expects($this->any())->method('getScheduledDocumentInsertions')->willReturn($list);

        $counter = 0;

        self::assertInstanceOf(
            ODM::class,
            $this->build()->foreachScheduledObjectInsertions(function () use (&$counter) {
                $counter++;
            })
        );

        self::assertEquals(2, $counter);
    }

    public function testForeachScheduledObjectUpdates()
    {
        $list = [new \stdClass(), new \stdClass()];

        $uow = $this->createMock(UnitOfWork::class);
        $this->getDoctrineManager()
            ->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $uow->expects($this->any())->method('getScheduledDocumentUpdates')->willReturn($list);

        $counter = 0;

        self::assertInstanceOf(
            ODM::class,
            $this->build()->foreachScheduledObjectUpdates(function () use (&$counter) {
                $counter++;
            })
        );

        self::assertEquals(2, $counter);
    }

    public function testForeachScheduledObjectDeletions()
    {
        $list = [new \stdClass(), new \stdClass()];

        $uow = $this->createMock(UnitOfWork::class);
        $this->getDoctrineManager()
            ->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $uow->expects($this->any())->method('getScheduledDocumentDeletions')->willReturn($list);

        $counter = 0;

        self::assertInstanceOf(
            ODM::class,
            $this->build()->foreachScheduledObjectDeletions(function () use (&$counter) {
                $counter++;
            })
        );

        self::assertEquals(2, $counter);
    }

    public function testSetObjectPropertyInManager()
    {
        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects($this->once())->method('setOriginalDocumentProperty');

        $this->getDoctrineManager()
            ->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        self::assertInstanceOf(
            ODM::class,
            $this->build()->setObjectPropertyInManager(123, 'bar', 'hello')
        );
    }
}
