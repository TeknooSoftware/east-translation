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

namespace Teknoo\Tests\East\Translation\Support\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Teknoo\East\Translation\Doctrine\Form\Type\TranslatableTrait;
use Teknoo\Tests\East\Translation\Support\Object\ObjectOfTest;

class ObjectOfTestType extends AbstractType
{
    use TranslatableTrait;

    /**
     * @param FormBuilderInterface<ObjectOfTest> $builder
     * @param array<string, string> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): self
    {
        $builder->add('text', TextType::class, ['required' => false]);

        $this->addTranslatableLocaleFieldHidden($builder);

        return $this;
    }
}
