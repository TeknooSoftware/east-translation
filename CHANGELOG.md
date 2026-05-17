 # Teknoo Software - Translation - Change Log

## [2.1.0] - 2026-05-17
### Stable Release
- Use new `Teknoo\East\Common\Doctrine\IdGenerator\UuidV7Generator` (`infrastructures/doctrine/IdGenerator/`),
  a Doctrine ODM `IdGenerator` returning RFC 4122 UUID v7 strings (time-ordered) via `Symfony\Component\Uid\Uuid::v7()`.
- Doctrine ODM mappings switched from built-in `strategy="UUID"` (v4) to `strategy="CUSTOM"` referencing `UuidV7Generator`.
- Backward compatible with documents already persisted with previous UUID versions: existing ids
  keep loading unchanged (the generator only runs when a new document has no id).
- New optional dependency: `symfony/uid` (declared in `require-dev` and listed under `suggest`;
  applications using the shipped Doctrine ODM mapping must add it to their own `require`).

## [2.0.1] - 2025-12-14
### Stable Release
- Fix bc break introduced into patch of phpstan and phpunit
- Support PHPStan 2.1.33+
- Support PHPUnit 12.5.1+

## [2.0.0] - 2025-08-18
### Stable Release
- Drop support of PHP 8.3
- Requires PHP 8.4
- Support Symfony 6.4.24+ or 7.3+
- Update to Teknoo States 7
- Update to Teknoo Recipe 7
- Update to Teknoo East Foundation 7
- Update to Teknoo East Common 4
- Update to PHPStan 2
- Remove deprecated feature `Cookbook`, use `Plan` instead
- Remove deprecated `DatesService`, use `Foundation\DatesService` instead
- Fix some bugs and QA issues
- Switch license from MIT to 3-Clause BSD

## [1.1.1] - 2025-04-11
### Stable Release
- Support of Teknoo East Common 3.5

## [1.1.0] - 2025-04-09
### Stable Release
- Drop Support of PHP 8.2
- Drop Support of Doctrine Persistence 3
- Fix bug in compliance with Doctrine Persistence 4
- 
## [1.0.3] - 2025-02-07
### Stable Release
- Update dev lib requirements
    - Require Symfony libraries 6.4 or 7.2
    - Update to PHPUnit 12
- Drop support of PHP 8.2
    - The library stay usable with PHP 8.2, without any waranties and tests
    - In the next major release, Support of PHP 8.2 will be dropped

## [1.0.2] - 2025-01-30
### Stable Release
- Remove `ProxyDetector`
- If Doctrine ODM, requires `Doctrine ODM Bundle 5.2`

## [1.0.1] - 2025-01-25
### Stable Release
- Update to support Doctrine ODM 2.10 and ODM Bundle 5.1

## [1.0.0] - 2024-11-09
### Stable Release
- Import translation from East Translation
