# Changelog

## [Unreleased]

### Fixed
- `EmbedsConsentTest` takes omnibase/consent's own features from `ConsentPanel::features()`: it
  built a path three directories above `Base\Consent\Twig\ConsentExtension`'s file, which
  omnibase/consent now declares under `src/Bridge/Symfony/Twig/`, and errored with consent at HEAD.

### Deprecated
- `classroom.storage_dir`: declared and read by nothing (the resources go to the flysystem
  storage `local.classroom`, whose directory the site sets). Deprecated rather than removed: the
  configuration tree is public API and sites set it; it goes at the next major.
