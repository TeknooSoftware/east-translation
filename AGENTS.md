# Project: teknoo/east-translation

## Overview
An extension designed to provide translation capabilities for East applications or websites, facilitating the management of translatable objects.

## Tech Stack
- **Language**: PHP (^8.4)
- **Dependency Management**: Composer
- **Database/ORM**: Doctrine (including MongoDB ODM support)
- **Key Libraries**:
  - `php-di/php-di` (Dependency Injection)
  - `teknoo/recipe` (Recipe management)
  - `teknoo/east-common` (Core components)
  - `symfony/property-access` (Object property access)
  - `symfony/form` (Form handling)

## Architecture & Project Structure
The project follows PSR-4 autoloading standards.

### Directories
- `src/`: Core business logic and domain interfaces.
- `infrastructures/`: Concrete implementations of infrastructure concerns (e.g., Doctrine/ODM, DI configurations).
- `tests/`: Test suites (Support and Infrastructures).

### Namespaces
- `Teknoo\East\Translation\`: Main library logic.
- `Teknoo\East\Translation\Doctrine\`: Doctrine-specific implementations.
- `Teknoo\Tests\East\Translation\Support\`: Testing support utilities.
- `Teknoo\Tests\East\East\Translation\Doctrine\`: Doctrine-specific testing utilities.

### Key Interfaces & Contracts
- `TranslatableInterface`: Represents an object that can be translated.
- `TranslationManagerInterface`: Manages the translation process.
- `LoadTranslationsInterface`: Interface for loading translations within a recipe step.

## Development & Validation
All validation and testing should be performed via the provided `Makefile`.

### Commands
- `make test`: Runs the PHPUnit test suite with Xdebug coverage.
- `make qa`: Performs full Quality Assurance:
  - `lint`: Validates PHP syntax in `src/` and `infrastructures/`.
  - `phpstan`: Performs static analysis.
  - `phpcs`: Ensures code adheres to PSR-12 standards.
  - `audit`: Runs `composer audit` to check for known security vulnerabilities in dependencies.
- `make qa-offline`: Performs static analysis and linting only (`lint`, `phpstan`, `phpcs`).

## Requirements
- **PHP**: ^8.4
- **Extensions**: `json`, `simplexml` (dev), `mongodb` (dev)
