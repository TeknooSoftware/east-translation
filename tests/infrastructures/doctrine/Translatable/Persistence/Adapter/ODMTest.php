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

namespace Teknoo\Tests\East\Translation\Doctrine\Translatable\Persistence\Adapter;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Id\IdGenerator;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\Persistence\Mapping\ClassMetadata as BaseClassMetadata;
use MongoDB\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Translation\Doctrine\Translatable\Persistence\Adapter\ODM;
use Teknoo\East\Translation\Doctrine\Translatable\Persistence\AdapterInterface;
use Teknoo\East\Translation\Doctrine\Translatable\TranslationInterface;
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
#[CoversClass(ODM::class)]
class ODMTest extends TestCase
{
    private (DocumentManager&Stub)|(DocumentManager&MockObject)|null $manager = null;

    public function getManager(bool $stub = false): (DocumentManager&Stub)|(DocumentManager&MockObject)
    {
        if (!$this->manager instanceof DocumentManager) {
            if ($stub) {
                $this->manager = $this->createStub(DocumentManager::class);
            } else {
                $this->manager = $this->createMock(DocumentManager::class);
            }
        }

        return $this->manager;
    }

    public function build(): ODM
    {
        return new ODM($this->getManager(true));
    }

    public function testLoadAllTranslations(): void
    {
        $qBuilder = $this->createStub(Builder::class);
        $qBuilder
            ->method('field')
            ->willReturnSelf();

        $qBuilder
            ->method('equals')
            ->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('execute')->willReturn(
            $this->createStub(TranslationInterface::class)
        );

        $qBuilder
            ->method('getQuery')
            ->willReturn($query);

        $this->getManager()
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qBuilder);

        $called = false;

        $this->assertInstanceOf(AdapterInterface::class, $this->build()->loadAllTranslations(
            'fr',
            'fooId',
            'fooClass',
            'barClass',
            function () use (&$called): void {
                $called = true;
            }
        ));

        $this->assertTrue($called);
    }

    public function testLoadAllTranslationsOnDeferred(): void
    {
        $this->getManager()
            ->expects($this->never())
            ->method('createQueryBuilder');

        $this->assertInstanceOf(AdapterInterface::class, $this->build()->setDeferred(true)->loadAllTranslations(
            'fr',
            'fooId',
            'fooClass',
            'barClass',
            function () use (&$called): void {
                $called = true;
            }
        ));
    }

    public function testSetDeferred(): void
    {
        $this->assertInstanceOf(AdapterInterface::class, $this->build()->setDeferred(true));
    }

    public function testExecuteAllDeferredLoadingsOnNonDeferred(): void
    {
        $this->assertInstanceOf(AdapterInterface::class, $this->build()->setDeferred(true)->executeAllDeferredLoadings());
    }

    public function testExecuteAllDeferredLoadingsOnDeferred(): void
    {
        $qBuilder = $this->createStub(Builder::class);
        $qBuilder
            ->method('field')
            ->willReturnSelf();

        $qBuilder
            ->method('equals')
            ->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('execute')->willReturn(
            [
                ['foreign_key' => 'fooId'],
                ['foreign_key' => 'fooId'],
                ['foreign_key' => 'fooId'],
                ['foreign_key' => 'barId'],
                ['foreign_key' => 'barId'],
                ['foreign_key' => 'barId'],
            ]
        );

        $qBuilder
            ->method('getQuery')
            ->willReturn($query);

        $this->getManager()
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qBuilder);

        $called = 0;

        $odm = $this->build();

        $odm->setDeferred(true);

        $this->assertInstanceOf(AdapterInterface::class, $odm->loadAllTranslations(
            locale: 'fr',
            identifier: 'fooId',
            translationClass: 'fooClass',
            objectClass: 'barClass',
            callback: function () use (&$called): void {
                ++$called;
            }
        ));


        $this->assertInstanceOf(AdapterInterface::class, $odm->loadAllTranslations(
            locale: 'fr',
            identifier: 'barId',
            translationClass: 'fooClass',
            objectClass: 'barClass',
            callback: function () use (&$called): void {
                ++$called;
            }
        ));

        $this->assertEquals(0, $called);

        $this->assertInstanceOf(AdapterInterface::class, $odm->executeAllDeferredLoadings());

        $this->assertEquals(2, $called);
    }

    public function testFindTranslationNotFound(): void
    {
        $qBuilder = $this->createStub(Builder::class);
        $qBuilder
            ->method('field')
            ->willReturnSelf();

        $qBuilder
            ->method('equals')
            ->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('getSingleResult')->willReturn(
            null
        );

        $qBuilder
            ->method('getQuery')
            ->willReturn($query);

        $this->getManager()
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qBuilder);

        $this->assertInstanceOf(AdapterInterface::class, $this->build()->findTranslation(
            'fr',
            'fooField',
            'fooId',
            'fooClass',
            'barClass',
            function () use (&$called): void {
                self::fail();
            }
        ));
    }

    public function testFindTranslationFound(): void
    {
        $qBuilder = $this->createStub(Builder::class);
        $qBuilder
            ->method('field')
            ->willReturnSelf();

        $qBuilder
            ->method('equals')
            ->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('getSingleResult')->willReturn(
            $this->createStub(TranslationInterface::class)
        );

        $qBuilder
            ->method('getQuery')
            ->willReturn($query);

        $this->getManager()
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qBuilder);

        $called = false;

        $this->assertInstanceOf(AdapterInterface::class, $this->build()->findTranslation(
            'fr',
            'foo',
            'fooId',
            'fooClass',
            'barClass',
            function () use (&$called): void {
                $called = true;
            }
        ));

        $this->assertTrue($called);
    }

    public function testRemoveAssociatedTranslations(): void
    {
        $qBuilder = $this->createStub(Builder::class);
        $qBuilder
            ->method('field')
            ->willReturnSelf();

        $qBuilder
            ->method('equals')
            ->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('execute')->willReturn(true);

        $qBuilder
            ->method('getQuery')
            ->willReturn($query);

        $this->getManager()
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qBuilder);

        $this->assertInstanceOf(AdapterInterface::class, $this->build()->removeAssociatedTranslations('fooId', 'fooClass', 'barClass'));
    }

    public function testRemoveOrphansTranslations(): void
    {
        $qBuilder = $this->createStub(Builder::class);
        $qBuilder
            ->method('field')
            ->willReturnSelf();

        $qBuilder
            ->method('equals')
            ->willReturnSelf();

        $qBuilder
            ->method('notIn')
            ->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('execute')->willReturn(true);

        $qBuilder
            ->method('getQuery')
            ->willReturn($query);

        $this->getManager()
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qBuilder);

        $this->assertInstanceOf(AdapterInterface::class, $this->build()->removeOrphansTranslations(
            'fooId',
            [
                'barId',
                '5a4c12e03b8a7e000b55c7a2',
            ],
            'fooClass',
            'barClass',
        ));
    }

    public function testRemoveOrphansTranslationsWithoutId(): void
    {
        $qBuilder = $this->createMock(Builder::class);
        $qBuilder
            ->method('field')
            ->willReturnSelf();

        $qBuilder
            ->method('equals')
            ->willReturnSelf();

        $qBuilder->expects($this->never())
            ->method('notIn')
            ->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('execute')->willReturn(true);

        $qBuilder
            ->method('getQuery')
            ->willReturn($query);

        $this->getManager()
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qBuilder);

        $this->assertInstanceOf(AdapterInterface::class, $this->build()->removeOrphansTranslations(
            'fooId',
            [],
            'fooClass',
            'barClass',
        ));
    }

    public function testPersistTranslationRecordOnInsertNoneIdGeneration(): void
    {
        $translation = $this->createStub(TranslationInterface::class);
        $translation->method('getIdentifier')->willReturn('');

        $meta = $this->createStub(ClassMetadata::class);
        $meta->method('getFieldNames')->willReturn(['foo']);
        $meta->method('getFieldMapping')->willReturn(['fieldName' => 'foo']);
        $meta->method('getFieldValue')->willReturn('bar');
        $meta->generatorType = ClassMetadata::GENERATOR_TYPE_NONE;

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())->method('insertOne');
        $collection->expects($this->never())->method('updateOne');

        $this->getManager(true)->method('getClassMetadata')->willReturn($meta);
        $this->getManager(true)->method('getDocumentCollection')->willReturn($collection);

        $this->assertInstanceOf(AdapterInterface::class, $this->build()->persistTranslationRecord($translation));
    }

    public function testPersistTranslationRecordOnInsertWithoutIdGenerator(): void
    {
        $this->expectException(\RuntimeException::class);

        $translation = $this->createStub(TranslationInterface::class);
        $translation->method('getIdentifier')->willReturn('');

        $meta = $this->createStub(ClassMetadata::class);
        $meta->method('getFieldNames')->willReturn(['foo']);
        $meta->method('getFieldMapping')->willReturn(['fieldName' => 'foo']);
        $meta->method('getFieldValue')->willReturn('bar');
        $meta->generatorType = ClassMetadata::GENERATOR_TYPE_UUID;

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->never())->method('insertOne');
        $collection->expects($this->never())->method('updateOne');

        $this->getManager(true)->method('getClassMetadata')->willReturn($meta);
        $this->getManager(true)->method('getDocumentCollection')->willReturn($collection);

        $this->assertInstanceOf(AdapterInterface::class, $this->build()->persistTranslationRecord($translation));
    }

    public function testPersistTranslationRecordOnInsertWithIdGenerator(): void
    {
        $translation = $this->createStub(TranslationInterface::class);
        $translation->method('getIdentifier')->willReturn('');

        $meta = $this->createStub(ClassMetadata::class);
        $meta->method('getFieldNames')->willReturn(['foo']);
        $meta->method('getFieldMapping')->willReturn(['fieldName' => 'foo']);
        $meta->method('getFieldValue')->willReturn('bar');
        $meta->generatorType = ClassMetadata::GENERATOR_TYPE_UUID;
        $meta->idGenerator = $this->createStub(IdGenerator::class);

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())->method('insertOne');
        $collection->expects($this->never())->method('updateOne');

        $this->getManager(true)->method('getClassMetadata')->willReturn($meta);
        $this->getManager(true)->method('getDocumentCollection')->willReturn($collection);

        $this->assertInstanceOf(AdapterInterface::class, $this->build()->persistTranslationRecord($translation));
    }

    public function testPersistTranslationRecordOnUpdateWithUUID(): void
    {
        $translation = $this->createStub(TranslationInterface::class);
        $translation->method('getIdentifier')->willReturn('foo');

        $meta = $this->createStub(ClassMetadata::class);
        $meta->method('getFieldNames')->willReturn(['id', 'foo']);
        $meta->method('getFieldMapping')->willReturnOnConsecutiveCalls(
            ['id' => true, 'fieldName' => '_id'],
            ['fieldName' => 'foo']
        );
        $meta->method('getFieldValue')->willReturn('bar');

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->never())->method('insertOne');
        $collection->expects($this->once())->method('updateOne');

        $this->getManager(true)->method('getClassMetadata')->willReturn($meta);
        $this->getManager(true)->method('getDocumentCollection')->willReturn($collection);

        $this->assertInstanceOf(AdapterInterface::class, $this->build()->persistTranslationRecord($translation));
    }

    public function testPersistTranslationRecordOnUpdateWithObjectId(): void
    {
        $translation = $this->createStub(TranslationInterface::class);
        $translation->method('getIdentifier')->willReturn('5a3d3e2ef7f98a00110ab582');

        $meta = $this->createStub(ClassMetadata::class);
        $meta->method('getFieldNames')->willReturn(['id', 'foo']);
        $meta->method('getFieldMapping')->willReturnOnConsecutiveCalls(
            ['id' => true, 'fieldName' => '_id'],
            ['fieldName' => 'foo']
        );
        $meta->method('getFieldValue')->willReturn('bar');

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->never())->method('insertOne');
        $collection->expects($this->once())->method('updateOne');

        $this->getManager(true)->method('getClassMetadata')->willReturn($meta);
        $this->getManager(true)->method('getDocumentCollection')->willReturn($collection);

        $this->assertInstanceOf(AdapterInterface::class, $this->build()->persistTranslationRecord($translation));
    }

    public function testUpdateTranslationRecordWithGenericClassMetaData(): void
    {
        $this->expectException(\RuntimeException::class);

        $wrapper = $this->createMock(WrapperInterface::class);
        $wrapper->expects($this->never())->method('setPropertyValue');

        $meta = $this->createStub(BaseClassMetadata::class);

        $translation = $this->createStub(TranslationInterface::class);

        $this->build()->updateTranslationRecord($wrapper, $meta, 'foo', $translation);
    }

    public function testUpdateTranslationRecord(): void
    {
        $wrapper = $this->createMock(WrapperInterface::class);
        $wrapper->expects($this->once())->method('updateTranslationRecord');

        $meta = $this->createStub(ClassMetadata::class);
        $meta->method('getFieldMapping')->willReturn([
            'type' => Type::STRING
        ]);

        $translation = $this->createStub(TranslationInterface::class);

        $this->assertInstanceOf(AdapterInterface::class, $this->build()->updateTranslationRecord($wrapper, $meta, 'foo', $translation));
    }

    public function testSetTranslatedValueWithGenericClassMetaData(): void
    {
        $this->expectException(\RuntimeException::class);

        $wrapper = $this->createMock(WrapperInterface::class);
        $wrapper->expects($this->never())->method('setPropertyValue');

        $meta = $this->createStub(BaseClassMetadata::class);

        $this->build()->setTranslatedValue($wrapper, $meta, 'foo', 'bar');
    }

    public function testSetTranslatedValue(): void
    {
        $wrapper = $this->createMock(WrapperInterface::class);
        $wrapper->expects($this->once())->method('setPropertyValue');

        $meta = $this->createStub(ClassMetadata::class);
        $meta->method('getFieldMapping')->willReturn([
            'type' => Type::STRING
        ]);

        $this->assertInstanceOf(AdapterInterface::class, $this->build()->setTranslatedValue($wrapper, $meta, 'foo', 'bar'));
    }
}
