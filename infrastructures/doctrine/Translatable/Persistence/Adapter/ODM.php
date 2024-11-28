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

namespace Teknoo\East\Translation\Doctrine\Translatable\Persistence\Adapter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as OdmClassMetadata;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\Persistence\Mapping\ClassMetadata;
use MongoDB\BSON\ObjectId;
use Teknoo\East\Translation\Doctrine\Translatable\Persistence\AdapterInterface;
use Teknoo\East\Translation\Doctrine\Translatable\Persistence\Exception\MissingIdGeneratorException;
use Teknoo\East\Translation\Doctrine\Translatable\Persistence\Exception\WrongClassMetadata;
use Teknoo\East\Translation\Doctrine\Translatable\TranslationInterface;
use Teknoo\East\Translation\Doctrine\Translatable\Wrapper\WrapperInterface;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;

use function array_keys;
use function strlen;

/**
 * Doctrine ODM adapter able to load and write translated value into a `TranslationInterface` document implementation
 * for each field of a translatable object, and load, in an optimized query, all translations for an object
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ODM implements AdapterInterface
{
    /**
     * @var array<string, array<string, array<string, array<string, callable>>>>
     */
    private array $translationsToLoad = [];

    public function __construct(
        private readonly DocumentManager $manager,
        private bool $deferred = false,
    ) {
    }

    public function setDeferred(bool $deferred): ODM
    {
        $this->deferred = $deferred;

        return $this;
    }

    private function fetchAllTranslations(
        string $locale,
        string $identifier,
        string $translationClass,
        string $objectClass,
        callable $callback,
    ): void {
        // load translated content for all translatable fields construct query
        $queryBuilder = $this->manager->createQueryBuilder($translationClass);
        $queryBuilder->field('foreignKey')->equals($identifier);
        $queryBuilder->field('locale')->equals($locale);
        $queryBuilder->field('objectClass')->equals($objectClass);

        $query = $queryBuilder->getQuery();
        $query->setHydrate(false);

        $result = $query->execute();

        $callback($result);
    }

    public function executeAllDeferredLoadings(): AdapterInterface
    {
        foreach ($this->translationsToLoad as $translationClass => &$locales) {
            foreach ($locales as $locale => &$classes) {
                foreach ($classes as $objectClass => &$ids) {
                    $queryBuilder = $this->manager->createQueryBuilder($translationClass);
                    $queryBuilder->field('foreignKey')->in(array_keys($ids));
                    $queryBuilder->field('locale')->equals($locale);
                    $queryBuilder->field('objectClass')->equals($objectClass);
                    $queryBuilder->sort('foreignKey');

                    $query = $queryBuilder->getQuery();
                    $query->setHydrate(false);

                    $currentForeignKey = null;
                    $subSets = [];
                    foreach ($query->execute() as $translation) {
                        /** @var array{foreign_key: string} $translation */
                        if (null !== $currentForeignKey && $currentForeignKey !== $translation['foreign_key']) {
                            ($ids[$currentForeignKey])($subSets);
                            $subSets = [];
                        }

                        $currentForeignKey = $translation['foreign_key'];
                        $subSets[] = $translation;
                    }

                    if (null !== $currentForeignKey) {
                        ($ids[$currentForeignKey])($subSets);
                    }
                }
            }
        }

        $this->translationsToLoad = [];

        return $this;
    }

    public function loadAllTranslations(
        string $locale,
        string $identifier,
        string $translationClass,
        string $objectClass,
        callable $callback,
    ): AdapterInterface {
        if (true === $this->deferred) {
            $this->translationsToLoad[$translationClass][$locale][$objectClass][$identifier] = $callback;

            return $this;
        }

        $this->fetchAllTranslations(
            locale: $locale,
            identifier: $identifier,
            translationClass: $translationClass,
            objectClass: $objectClass,
            callback: $callback,
        );

        return $this;
    }

    public function findTranslation(
        string $locale,
        string $field,
        string $identifier,
        string $translationClass,
        string $objectClass,
        callable $callback
    ): AdapterInterface {
        $queryBuilder = $this->manager->createQueryBuilder($translationClass);
        $queryBuilder->field('locale')->equals($locale);
        $queryBuilder->field('field')->equals($field);
        $queryBuilder->field('foreignKey')->equals($identifier);
        $queryBuilder->field('objectClass')->equals($objectClass);

        $queryBuilder->limit(1);

        $query = $queryBuilder->getQuery();
        $result = $query->getSingleResult();

        if ($result instanceof TranslationInterface) {
            $callback($result);
        }

        return $this;
    }

    public function removeAssociatedTranslations(
        string $identifier,
        string $translationClass,
        string $objectClass
    ): AdapterInterface {
        $queryBuilder = $this->manager->createQueryBuilder($translationClass);
        $queryBuilder->remove();
        $queryBuilder->field('foreignKey')->equals($identifier);
        $queryBuilder->field('objectClass')->equals($objectClass);

        $query = $queryBuilder->getQuery();
        $query->execute();

        return $this;
    }

    /**
     * @param string[] $updatedTranslations
     */
    public function removeOrphansTranslations(
        string $identifier,
        array $updatedTranslations,
        string $translationClass,
        string $objectClass
    ): AdapterInterface {
        $queryBuilder = $this->manager->createQueryBuilder($translationClass);
        $queryBuilder->remove();
        $queryBuilder->field('foreignKey')->equals($identifier);
        $queryBuilder->field('objectClass')->equals($objectClass);

        $finalUpdatedTranslation = [];
        foreach ($updatedTranslations as $id) {
            if (24 === strlen($id)) {
                $finalUpdatedTranslation[] = new ObjectId($id);
            } else {
                $finalUpdatedTranslation[] = $id;
            }
        }

        if (!empty($finalUpdatedTranslation)) {
            $queryBuilder->field('id')->notIn($finalUpdatedTranslation);
        }

        $queryBuilder->getQuery()->execute();

        return $this;
    }

    /**
     * @param OdmClassMetadata<TranslationInterface> $metadata
     */
    private function prepareId(OdmClassMetadata $metadata, TranslationInterface $translation): void
    {
        if (
            OdmClassMetadata::GENERATOR_TYPE_NONE === $metadata->generatorType
            || !empty($translation->getIdentifier())
        ) {
            return;
        }

        if (null === $metadata->idGenerator) {
            throw new MissingIdGeneratorException('Missing Id Generator');
        }

        $idValue = $metadata->idGenerator->generate($this->manager, $translation);
        $idValue = $metadata->getPHPIdentifierValue($metadata->getDatabaseIdentifierValue($idValue));

        $metadata->setIdentifierValue($translation, $idValue);
    }

    /**
     * @param OdmClassMetadata<TranslationInterface> $metadata
     * @return array<int|string, mixed>
     */
    private function generateInsertionArray(
        OdmClassMetadata $metadata,
        TranslationInterface $translation,
        mixed $id
    ): array {
        $final = [];
        foreach ($metadata->getFieldNames() as $fieldName) {
            $fm = $metadata->getFieldMapping($fieldName);

            if (null !== $id && !empty($fm['id'])) {
                $final[$fm['name'] ?? $fm['fieldName']] = $id;

                continue;
            }

            $final[$fm['name'] ?? $fm['fieldName']] = $metadata->getFieldValue($translation, $fieldName);
        }

        return $final;
    }

    public function persistTranslationRecord(TranslationInterface $translation): AdapterInterface
    {
        $meta = $this->manager->getClassMetadata($translation::class);

        $className = $meta->getName();
        $collection = $this->manager->getDocumentCollection($className);
        if (empty($translation->getIdentifier())) {
            $this->prepareId($meta, $translation);
            $collection->insertOne($this->generateInsertionArray($meta, $translation, null));
        } else {
            $id = $translation->getIdentifier();

            if (24 === strlen($id)) {
                $id = new ObjectId($id);
            }

            $set = $this->generateInsertionArray($meta, $translation, $id);

            $collection->updateOne(
                ['_id' => $id],
                ['$set' => $set]
            );
        }

        return $this;
    }

    private function getType(string $type): Type
    {
        return Type::getType($type);
    }

    /**
     * @param ClassMetadata<IdentifiedObjectInterface> $metadata
     */
    public function updateTranslationRecord(
        WrapperInterface $wrapped,
        ClassMetadata $metadata,
        string $field,
        TranslationInterface $translation
    ): AdapterInterface {
        if (!$metadata instanceof OdmClassMetadata) {
            throw new WrongClassMetadata('Error this classMetadata is not compatible with this adapter');
        }

        $mapping = $metadata->getFieldMapping($field);

        $type = $this->getType($mapping['type']);

        $wrapped->updateTranslationRecord($translation, $field, $type);

        return $this;
    }

    /**
     * @param ClassMetadata<IdentifiedObjectInterface> $metadata
     */
    public function setTranslatedValue(
        WrapperInterface $wrapped,
        ClassMetadata $metadata,
        string $field,
        mixed $value
    ): AdapterInterface {
        if (!$metadata instanceof OdmClassMetadata) {
            throw new WrongClassMetadata('Error this classMetadata is not compatible with this adapter');
        }

        $mapping = $metadata->getFieldMapping($field);
        $type = $this->getType($mapping['type']);

        $value = $type->convertToPHPValue($value);
        $wrapped->setPropertyValue($field, $value);

        return $this;
    }
}
