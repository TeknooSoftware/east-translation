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

namespace Teknoo\Tests\East\Translation\Doctrine\Translatable\ObjectManager\Adapter;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\Persistence\Mapping\ClassMetadata as BaseClassMetadata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 */
#[CoversClass(ODM::class)]
class ODMTest extends TestCase
{
    private (ManagerInterface&Stub)|(ManagerInterface&MockObject)|null $eastManager = null;

    private (DocumentManager&Stub)|(DocumentManager&MockObject)|null $doctrineManager = null;

    public function getEastManager(bool $stub = false): (ManagerInterface&Stub)|(ManagerInterface&MockObject)
    {
        if (!$this->eastManager instanceof ManagerInterface) {
            if ($stub) {
                $this->eastManager = $this->createStub(ManagerInterface::class);
            } else {
                $this->eastManager = $this->createMock(ManagerInterface::class);
            }
        }

        return $this->eastManager;
    }

    public function getDoctrineManager(bool $stub = false): (DocumentManager&Stub)|(DocumentManager&MockObject)
    {
        if (!$this->doctrineManager instanceof DocumentManager) {
            if ($stub) {
                $this->doctrineManager = $this->createStub(DocumentManager::class);
            } else {
                $this->doctrineManager = $this->createMock(DocumentManager::class);
            }
        }

        return $this->doctrineManager;
    }

    public function build(): ODM
    {
        return new ODM($this->getEastManager(true), $this->getDoctrineManager(true));
    }

    public function testOpenBatch(): void
    {
        $this->getEastManager()->expects($this->once())->method('openBatch');

        $this->assertInstanceOf(ODM::class, $this->build()->openBatch());
    }

    public function testCloseBatch(): void
    {
        $this->getEastManager()->expects($this->once())->method('closeBatch');

        $this->assertInstanceOf(ODM::class, $this->build()->closeBatch());
    }

    public function testPersist(): void
    {
        $object = $this->createMock(ObjectInterface::class);
        $this->getEastManager()->expects($this->once())->method('persist')->with($object);

        $this->assertInstanceOf(ODM::class, $this->build()->persist($object));
    }

    public function testRemove(): void
    {
        $object = $this->createMock(ObjectInterface::class);
        $this->getEastManager()->expects($this->once())->method('remove')->with($object);

        $this->assertInstanceOf(ODM::class, $this->build()->remove($object));
    }

    public function testFlush(): void
    {
        $this->getEastManager()->expects($this->once())->method('flush')->with();

        $this->assertInstanceOf(ODM::class, $this->build()->flush());
    }

    public function testRegisterFilter(): void
    {
        $this->getEastManager()->expects($this->once())->method('registerFilter')->with(\stdClass::class, ['foo']);

        $this->assertInstanceOf(ODM::class, $this->build()->registerFilter(\stdClass::class, ['foo']));
    }

    public function testEnableFilter(): void
    {
        $this->getEastManager()->expects($this->once())->method('enableFilter')->with(\stdClass::class);

        $this->assertInstanceOf(ODM::class, $this->build()->enableFilter(\stdClass::class));
    }

    public function testDisableFilter(): void
    {
        $this->getEastManager()->expects($this->once())->method('disableFilter')->with(\stdClass::class);

        $this->assertInstanceOf(ODM::class, $this->build()->disableFilter(\stdClass::class));
    }

    public function testFindClassMetadata(): void
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

        $this->assertInstanceOf(ODM::class, $this->build()->findClassMetadata($class, $listener));
    }

    public function testIfObjectHasChangeSetEmpty(): void
    {
        $object = $this->createMock(TranslatableInterface::class);

        $uow = $this->createMock(UnitOfWork::class);
        $this->getDoctrineManager()
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $neverCallback = function (): void {
            self::fail('must not be called');
        };

        $uow->method('getDocumentChangeSet')->willReturn([]);

        $this->assertInstanceOf(ODM::class, $this->build()->ifObjectHasChangeSet($object, $neverCallback));
    }

    public function testIfObjectHasChangeSet(): void
    {
        $object = $this->createMock(TranslatableInterface::class);

        $changset = ['foo1' => ['bar', 'baba'], 'foo2' => ['bar', 'baba']];

        $uow = $this->createMock(UnitOfWork::class);
        $this->getDoctrineManager()
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $uow->method('getDocumentChangeSet')->willReturn($changset);

        $called = false;

        $this->assertInstanceOf(ODM::class, $this->build()->ifObjectHasChangeSet($object, function () use (&$called): void {
            $called = true;
        }));

        $this->assertTrue($called);
    }

    public function testRecomputeSingleObjectChangeSetWithGenericClassMetaData(): void
    {
        $this->expectException(\RuntimeException::class);

        $meta = $this->createMock(BaseClassMetadata::class);
        $object = $this->createMock(TranslatableInterface::class);

        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects($this->never())->method('clearDocumentChangeSet');
        $uow->expects($this->never())->method('recomputeSingleDocumentChangeSet');

        $this->getDoctrineManager()
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $this->build()->recomputeSingleObjectChangeSet($meta, $object);
    }

    public function testRecomputeSingleObjectChangeSet(): void
    {
        $meta = $this->createMock(ClassMetadata::class);
        $object = $this->createMock(TranslatableInterface::class);

        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects($this->once())->method('clearDocumentChangeSet');
        $uow->expects($this->once())->method('recomputeSingleDocumentChangeSet');

        $this->getDoctrineManager()
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $this->assertInstanceOf(ODM::class, $this->build()->recomputeSingleObjectChangeSet($meta, $object));
    }

    public function testForeachScheduledObjectInsertions(): void
    {
        $list = [new \stdClass(), new \stdClass()];

        $uow = $this->createMock(UnitOfWork::class);
        $this->getDoctrineManager()
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $uow->method('getScheduledDocumentInsertions')->willReturn($list);

        $counter = 0;

        $this->assertInstanceOf(ODM::class, $this->build()->foreachScheduledObjectInsertions(function () use (&$counter): void {
            ++$counter;
        }));

        $this->assertEquals(2, $counter);
    }

    public function testForeachScheduledObjectUpdates(): void
    {
        $list = [new \stdClass(), new \stdClass()];

        $uow = $this->createMock(UnitOfWork::class);
        $this->getDoctrineManager()
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $uow->method('getScheduledDocumentUpdates')->willReturn($list);

        $counter = 0;

        $this->assertInstanceOf(ODM::class, $this->build()->foreachScheduledObjectUpdates(function () use (&$counter): void {
            ++$counter;
        }));

        $this->assertEquals(2, $counter);
    }

    public function testForeachScheduledObjectDeletions(): void
    {
        $list = [new \stdClass(), new \stdClass()];

        $uow = $this->createMock(UnitOfWork::class);
        $this->getDoctrineManager()
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $uow->method('getScheduledDocumentDeletions')->willReturn($list);

        $counter = 0;

        $this->assertInstanceOf(ODM::class, $this->build()->foreachScheduledObjectDeletions(function () use (&$counter): void {
            ++$counter;
        }));

        $this->assertEquals(2, $counter);
    }

    public function testSetObjectPropertyInManager(): void
    {
        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects($this->once())->method('setOriginalDocumentProperty');

        $this->getDoctrineManager()
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $this->assertInstanceOf(ODM::class, $this->build()->setObjectPropertyInManager(123, 'bar', 'hello'));
    }
}
