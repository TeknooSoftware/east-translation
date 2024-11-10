Teknoo Software - Translation library
=================================

[![Latest Stable Version](https://poser.pugx.org/teknoo/east-translation/v/stable)](https://packagist.org/packages/teknoo/east-translation)
[![Latest Unstable Version](https://poser.pugx.org/teknoo/east-translation/v/unstable)](https://packagist.org/packages/teknoo/east-translation)
[![Total Downloads](https://poser.pugx.org/teknoo/east-translation/downloads)](https://packagist.org/packages/teknoo/east-translation)
[![License](https://poser.pugx.org/teknoo/east-translation/license)](https://packagist.org/packages/teknoo/east-translation)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)

Package originally a part from `Teknoo East Website`, part forked then totaly reworked from the Gedmo Extension 
Translation, to allow developers to create translatable persisted object, in multiples languages, without manage them
directly into each object.

Translation are stored into a dedicated objects. Translation are loaded in e minimum of database requests into objects.
The translation manager is able to persist and update translations.

Example with Symfony 
--------------------

    //These operations are not reauired with teknoo/east-translation-symfony
    //config/packages/east_translation_di.yaml:
    di_bridge:
        definitions:
            - '%kernel.project_dir%/vendor/teknoo/east-translation/infrastructures/doctrine/di.php'

    //In doctrine config (east_translation_doctrine_mongodb.yaml)
    doctrine_mongodb:
        document_managers:
            default:
                auto_mapping: true
                mappings:
                    TeknooEastTranslationDoctrine:
                        type: 'xml'
                        dir: '%kernel.project_dir%/vendor/teknoo/east-translation/infrastructures/doctrine/config/doctrine'
                        is_bundle: false
                        prefix: 'Teknoo\East\Translation\Doctrine\Object'

Support this project
---------------------
This project is free and will remain free. It is fully supported by the activities of the EIRL.
If you like it and help me maintain it and evolve it, don't hesitate to support me on
[Patreon](https://patreon.com/teknoo_software) or [Github](https://github.com/sponsors/TeknooSoftware).

Thanks :) Richard.

Credits
-------
EIRL Richard Déloge - <https://deloge.io> - Lead developer.
SASU Teknoo Software - <https://teknoo.software>

About Teknoo Software
---------------------
**Teknoo Software** is a PHP software editor, founded by Richard Déloge, as part of EIRL Richard Déloge.
Teknoo Software's goals : Provide to our partners and to the community a set of high quality services or software,
sharing knowledge and skills.

License
-------
East Translation is licensed under the MIT License - see the licenses folder for details.

Installation & Requirements
---------------------------
To install this library with composer, run this command :

    composer require teknoo/east-translation
    
To start a project with Symfony :

    symfony new your_project_name new
    composer require teknoo/east-translation-symfony    

This library requires :

    * PHP 8.2+
    * A PHP autoloader (Composer is recommended)
    * Teknoo/Recipe.
    * Teknoo/East-Foundation.
    * Teknoo/East-Common.

Contribute :)
-------------
You are welcome to contribute to this project. [Fork it on Github](CONTRIBUTING.md)
