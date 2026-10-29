---
title: Configuration reference
order: 40
---

# Configuration reference

## `classroom:`

`Base\Classroom\DependencyInjection\ClassroomConfiguration`, dumped with Symfony's
`YamlReferenceDumper` in omnisense/omnibase's harness image:

```yaml
classroom:

    # Where the resources are (the local.classroom flysystem storage points there).
    storage_dir:          '%kernel.project_dir%/var/storage/classroom'

    # nginx internal location streaming the files (X-Accel-Redirect); empty: PHP streams them.
    accel_prefix:         ''

    # Seconds a signed download link stays valid.
    download_ttl:         600

    # Rows of a spreadsheet shown in a resource's preview.
    preview_rows:         30

    # Who the MEMBERS resources are for.
    members_role:         ROLE_USER
    per_page:             24

    # Sender of the mail a paid order gets (the link to its files); null: the mailer's default sender.
    from_email:           null
```

| Option | Type | Default | Read by |
|---|---|---|---|
| `storage_dir` | path | `%kernel.project_dir%/var/storage/classroom` | nothing (see below) |
| `accel_prefix` | string | `''` | `ResourceController` |
| `download_ttl` | int, 30 or more | `600` | `ResourceController` |
| `preview_rows` | int, 1 or more | `30` | `SpreadsheetPreview` |
| `members_role` | role | `ROLE_USER` | `ResourceVoter` |
| `per_page` | int, 1 or more | `24` | `ClassroomController` |
| `from_email` | string or null | `null` | `OrderPaidSubscriber` |

`storage_dir` is deprecated: it was read by nothing. The files go to the flysystem storage
`local.classroom`, whose directory the site sets in its `flysystem.yaml`; a site that still sets
`storage_dir` gets Symfony's deprecation notice.

The extension also sets `classroom.consent`: whether the container has omnibase/consent's
extension.

## Services

`config/services.php` autowires and autoconfigures `src/` but `DependencyInjection/`, `Entity/`,
`Enum/`, `Controller/Admin/`, `Admin/`, `EventSubscriber/` and the bundle class;
`Controller/Client/` is tagged `controller.service_arguments` (`ShopController` only with
omnibase/marketplace's `QuickOrder`); with omnibase/admin, `Controller/Admin/`
(`ResourceOfferCrudController` only with omnibase/marketplace's `Product`) and `Admin/`; with
omnibase/marketplace's `OrderPaidEvent`, `EventSubscriber/` (`QuickOrderFilesListener` only with
its `QuickOrderDoneEvent`).

`Compiler\EmbedsConsentPass` adds `EMBEDS` to omnibase/consent's `consent.defaults`: on demand,
with the storage these providers keep - Canva's `CDI` (a year) and `CL` (60 days), Vimeo's
`vuid` (two years), the YouTube player's `yt-remote-*` (local storage). Another bundle's
`EMBEDS` keeps its fields; the entries it does not name are added.
