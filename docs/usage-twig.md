---
title: In a host page
order: 35
---

# In a host page

## Twig functions and filters

`Base\Classroom\Twig\ClassroomExtension`:

| | Returns |
|---|---|
| `classroom_cycles()` | the cycles, each holding its levels |
| `classroom_levels()` | the levels alone, in order |
| `classroom_subjects()`, `classroom_purposes()` | the top ones, in order |
| `classroom_cards(limit = 9)` | the fan |
| `classroom_sequences(limit = 3)` | the latest sequences, featured first |
| `classroom_resources(limit = 6)` | the latest resources |
| `classroom_consent()` | `EMBEDS` when the site has omnibase/consent, else `null` (the frames load at once) |
| `classroom_may_download(resource)` | the voter's answer |
| `x\|classroom_size` | bytes in French units: `2,4 Mo` |
| `x\|classroom_minutes` | `45 min`, `1 h 30` |

## Templates

`@Classroom/client/`: `index`, `listing`, `sequence`, `card`, `resource`, `search`, `shop`,
`thanks`, and the partials `_card`, `_sequence_card`, `_resource_card`, `_pills`, `_embeds`;
`@Classroom/email/files`; `@Classroom/admin/widget/overview`. An application overrides one in
`templates/bundles/ClassroomBundle/...`.

## Translations

The domains `classroom` (the pages, the back office's words), `entities` (the screens' names) and
`consent` (the `EMBEDS` feature's words for omnibase/consent's panel), ICU, in French, English and
German.
