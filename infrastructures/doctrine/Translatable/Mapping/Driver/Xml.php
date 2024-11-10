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
 * @author      Richard Déloge <richard@teknoo.software
 * @author      Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author      Miha Vrhovnik <miha.vrhovnik@gmail.com>
 */

declare(strict_types=1);

namespace Teknoo\East\Translation\Doctrine\Translatable\Mapping\Driver;

use Doctrine\Persistence\Mapping\Driver\FileLocator;
use Doctrine\Persistence\Mapping\ClassMetadata;
use SimpleXMLElement;
use Teknoo\East\Translation\Doctrine\Object\Translation;
use Teknoo\East\Translation\Doctrine\Translatable\Mapping\Driver\Exception\MalformedXmlException;
use Teknoo\East\Translation\Doctrine\Translatable\Mapping\DriverInterface;
use Teknoo\East\Translation\Doctrine\Exception\InvalidMappingException;

use function class_exists;
use function file_exists;
use function str_replace;

/**
 * Driver implementation to read Doctrine Translation configuration/metadata in XML files
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author      Miha Vrhovnik <miha.vrhovnik@gmail.com>
 */
class Xml implements DriverInterface
{
    private const DOCTRINE_NAMESPACE_URI = 'http://xml.teknoo.it/schemas/doctrine/east-translation';

    public function __construct(
        private readonly FileLocator $locator,
        private readonly SimpleXmlFactoryInterface $simpleXmlFactory,
    ) {
    }

    private function getMapping(string $className): ?SimpleXMLElement
    {
        $file = (string) $this->locator->findMappingFile($className);
        $file = str_replace('.xml', '.translate.xml', $file);

        if (!file_exists($file)) {
            return null;
        }

        return $this->loadMappingFile($file);
    }

    private function loadMappingFile(string $file): SimpleXMLElement
    {
        $xmlElement = ($this->simpleXmlFactory)($file);
        $xmlElement = $xmlElement->children(self::DOCTRINE_NAMESPACE_URI);

        if (!isset($xmlElement->object)) {
            throw new MalformedXmlException('Malformed XML');
        }

        return $xmlElement->object;
    }

    /**
     * @param array<string, array<int|string, mixed>> $config
     */
    private function inspectElementsForTranslatableFields(
        SimpleXMLElement $xml,
        array &$config
    ): void {
        $config['fields'] = [];
        $config['fallback'] = [];

        if (!isset($xml->field)) {
            return;
        }

        foreach ($xml->field as $mapping) {
            $attributes = $mapping->attributes();
            $fieldName = (string) $attributes['field-name'];

            $config['fields'][] = $fieldName;
            $config['fallback'][$fieldName] = (bool) ($attributes['fallback'] ?? true);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    public function readExtendedMetadata(ClassMetadata $meta, array &$config): self
    {
        $xml = $this->getMapping($meta->getName());

        if (null === $xml) {
            return $this;
        }

        $config['translationClass'] = (string) ($xml->attributes()['translation-class'] ?? Translation::class);
        $config['useObjectClass'] = (string) ($xml->attributes()['object-class'] ?? $meta->getName());

        if (!class_exists($config['translationClass'])) {
            throw new InvalidMappingException(
                "Translation entity class: {$config['translationClass']} does not exist."
            );
        }

        $this->inspectElementsForTranslatableFields($xml, $config);

        return $this;
    }
}
