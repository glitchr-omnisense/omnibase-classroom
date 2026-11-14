# Changelog

## [Unreleased]

## [1.0.2] - 2026-11-14

### Changed

- Accepts omnisense/omnibase 6.0: `omnisense/omnibase` ^5.1 || ^6.0, `omnibridge/symfony` ^1.1 || ^2.0. The
  same code runs on both lines: it names no former name and no entry point 6.0 removed.

## [1.0.1] - 2026-11-14

### Changed

- The classes omnisense/omnibase 5.0 moved to omnibridge/symfony named by their new names:
  `Omnibridge\Symfony\Omnibase\Bundle\{AbstractBaseBundle, AbstractBaseExtension, AbstractBaseConfiguration}`
  in place of `Base\Bundle\*`, former names the core keeps only as aliases until 6.0. No behaviour change.
- docs/installation.md registers `Omnibridge\Symfony\Omnibase\BaseBundle`, `Base\BaseBundle`'s new name.

## [1.0.0] - 2026-11-13

### Changed

- Installs with the default minimum-stability: stable requirements (`omnisense/omnibase` ^5.1, `omnibridge/symfony` ^1.1, `omnibase/admin` ^1.0), a branch alias,
  no `minimum-stability: dev` left to the sites.
- CI: glitchr-studio/ci's package workflow (the bundle installed alone, composer validate, PHPUnit,
  PHPStan against its baseline, a consumer install without require-dev) and its release workflow.
- `doctrine/doctrine-bundle` required: the repositories extend its `ServiceEntityRepository`.
- require-dev: PHPUnit, and omnibase/marketplace for the resources sold (its tests and PHPStan).

### Changed
- `ResourceCrudController` describes the file in `persistRecord()` / `updateRecord()`, omnibase/admin's write hooks on the
  persistence the site chose, in place of the former `persistEntity()` / `updateEntity(EntityManagerInterface)`
  (deprecated since the admin's step B1a). Needs omnibase/admin from its step B1a.

### Changed
- Accepts omnisense/omnibase 5.0 (`3.0.*-dev || 4.0.*-dev || 5.0.*-dev`; plan "omnibase agnostic", step S): the bundle runs
  unchanged on it, `Base\Bundle\AbstractBaseBundle` and the other former names it uses being aliases of
  omnibridge/symfony's classes there. The sites on 4.x resolve as before.
- `Card`, `Sequence` and `Session::__toLink()` link through `Base\Contract\Link::to()` (the Urls contract; the same address under
  Symfony, in the core 3.x and 4.x) instead of BaseTrait's `getRouter()->generate()`, which the
  core deprecates since BaseTrait names contracts only (omnisense/omnibase 4.x, step F2f).

### Fixed
- `EmbedsConsentTest` takes omnibase/consent's own features from `ConsentPanel::features()`: it
  built a path three directories above `Base\Consent\Twig\ConsentExtension`'s file, which
  omnibase/consent now declares under `src/Bridge/Symfony/Twig/`, and errored with consent at HEAD.

### Deprecated
- `classroom.storage_dir`: declared and read by nothing (the resources go to the flysystem
  storage `local.classroom`, whose directory the site sets). Deprecated rather than removed: the
  configuration tree is public API and sites set it; it goes at the next major.
