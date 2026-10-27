---
title: Testing
order: 60
---

# Testing

```bash
vendor/bin/phpunit
```

The tests are units. In omnisense/omnibase's harness, with this checkout and omnibase/consent's:

```bash
docker run --rm --network none \
    -v "$PWD":/srv/demo/vendor/omnibase/classroom:ro -v "$PWD/../consent":/srv/demo/vendor/omnibase/consent:ro \
    omnisense/omnibase-harness test classroom
```

Run on 2026-10-27 with both at HEAD: 27 tests, 1 error.

> [!WARNING]
> Known defect: `EmbedsConsentTest::testThePanelListsEmbedsWithTheBundlesWords` reads
> omnibase/consent's `config/features.php` three directories above
> `Base\Consent\Twig\ConsentExtension`'s file, which omnibase/consent now declares under
> `src/Bridge/Symfony/Twig/`: the path it builds does not exist.

| File | What |
|---|---|
| `DependencyInjection/EmbedsConsentTest` | without omnibase/consent the frames load at once; with it `EMBEDS` is one of the features every site starts from; the panel lists `EMBEDS` with the bundle's words |
| `Templates/EmbedsTemplateTest` | with consent no frame has an address before the yes; without, the frames load with the page; the placeholder speaks every language; every language has every key |
| `Entity/TaxaOfKindTest` | a card's levels replaced, its subjects kept; a sequence's three kinds written apart; what is not of the kind ignored; a resource the same way |
| `Controller/CrudFieldsTest`, `Controller/CrudRequiredFieldsTest` | one field per property, written through the entity; the slug follows the title; each kind of taxa has its picker; only what the record needs is required |
| `Controller/PeriodFieldTest` | a new sequence shows no period; sent empty it stays empty; a period chosen is recorded |
| `Controller/AdminWordsTest` | every screen named in every language; every entity of a screen listed; every key the screens give translated |

Saving through the back office is a host's functional test (a site's own).
