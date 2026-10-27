---
title: Installation
order: 10
---

# Installation

## Requirements

What `composer.json` requires: PHP 8.2 or later, `doctrine/orm` 2.15 or 3,
`omnisense/omnibase` 3.0 or 4.0, `omnibridge/symfony` 1.x, `omnibase/admin`,
`phpoffice/phpspreadsheet` 1.23 to 5, and `symfony/config`, `dependency-injection`, `form`,
`http-kernel` and `security-bundle` 6, 7 or 8. It suggests omnibase/marketplace (selling
resources) and omnibase/blog (comments under a sequence); it works with omnibase/consent when
the site has it.

## The bundle

```php
// config/bundles.php
return [
    // ...
    Base\BaseBundle::class => ['all' => true],
    Base\Admin\AdminBundle::class => ['all' => true],
    Base\Classroom\ClassroomBundle::class => ['all' => true],
];
```

`ClassroomBundle` aliases `Base\Classroom\Entity\*` to `App\Entity\Classroom\*` and the
repositories to `App\Repository\Classroom\*` (unless the application declares its own), and
registers `Compiler\EmbedsConsentPass`. Its `templates/` is `@Classroom`, its `public/`
`bundles/classroom/`.

## The routes

```yaml
# config/routes.yaml
classroom_controller:
    resource: "@ClassroomBundle/src/Controller/Client"
    type: attribute
    prefix: /
```

The paths are French ([The pages](usage-pages.md)); `/boutique` exists only with
omnibase/marketplace's quick order.

## The storage

The files go to the flysystem storage `local.classroom` (the `Resource::$file` uploader), which
the host defines, outside `public/` (`classroom.storage_dir` is not read: the storage's own
directory is what counts):

```yaml
# config/packages/flysystem.yaml
flysystem:
    storages:
        local.classroom:
            local:
                directory: '%kernel.project_dir%/var/storage/classroom'
```

## Configuration

```yaml
# config/packages/classroom.yaml (every key optional)
classroom:
    accel_prefix: ''
    download_ttl: 600
    preview_rows: 30
    members_role: ROLE_USER
    per_page: 24
    from_email: null
```

## The schema and the public files

```bash
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
bin/console assets:install
```

Tables `classroom_sequence`, `classroom_session`, `classroom_card` (joined to the core's thread
table), `classroom_resource`, `classroom_resource_taxon`, `classroom_sequence_resource`,
`classroom_entitlement`, and the taxa's discriminator entries; the stylesheet
`bundles/classroom/css/classroom.css`.

## What the layout provides

The pages extend `layout1.html.twig` and fill its `title`, `description`, `content` and
`stylesheets` blocks.
