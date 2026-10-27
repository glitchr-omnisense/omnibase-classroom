---
title: Levels, sequences, cards and resources
order: 20
---

# Levels, sequences, cards and resources

## The taxa

`Level`, `Subject` and `Purpose` extend the core's `Base\Entity\Thread\Taxon` (a translated
label and description, an icon, a slug, a parent and children):

| Taxon | Discriminator | Adds |
|---|---|---|
| `Level` | `classroom_level` | `position` (youngest first), `short` ("CP"; the label when empty), `color`; `isCycle()` - no parent: a cycle holds levels |
| `Subject` | `classroom_subject` | `position`, `color` (the parent's when empty) |
| `Purpose` | `classroom_purpose` | `position` |

The threads (sequences, cards) and the resources keep their levels, subjects and purposes in one
association, `taxa`, read and written by kind (`ClassifiedTrait`, `TaxaOfKindTrait`):
`getLevels()` (by position), `getSubjects()`, `getPurposes()`, and `setLevels()`,
`setSubjects()`, `setPurposes()`, each replacing the taxa of its kind and leaving the others; a
value of another kind is ignored. `isMultiLevel()`, `getLevelsLabel()` ("CP · CE1").

## `Sequence`

Extends `Thread` (`classroom_sequence`); title, headline, excerpt, the presentation (`content`),
slug, state and `publishedAt` are the thread's.

| Property | Column | Meaning |
|---|---|---|
| `periodValue` | `period`, `string(2)`, nullable | `Period`: `p1` to `p5`, the five periods of the French school year; `getPeriod()` |
| `durationMinutes` | `integer`, nullable | the whole sequence; `null`: the sum of its sessions |
| `objectives`, `competences` | `json` | lists; `…Text` accessors take one per line |
| `materials` | `text` | |
| `cover` | upload, image, 8 MB | |
| `featured` | `boolean` | first among the latest |
| `resources` | many-to-many `Resource` (`classroom_sequence_resource`) | the files to download |
| `embeds` | `json` | what it frames: `{label, url}`; `embedsText` takes "Label \| https://..." per line (only `http(s)` addresses kept) |

`getSessions()` - its children that are sessions, by position then id.

## `Session`

Extends `Thread` (`classroom_session`), a child of its sequence (`setSequence()` sets the
parent): `position` (1 at least), `durationMinutes`, `materials` (a list, one per line). Its
headline is its aim, its text the course. Its link is the sequence's page at `#seance-<position>`.
A session created in the back office is published.

## `Card`

Extends `Thread` (`classroom_card`): `image` (8 MB), `links` (up to the author: `{label, url}`,
"label \| url" per line in `linksText`; a line without label is its own label), `featured` (on
the fan), `position`, `icon` (a Font Awesome class, big on a card without picture, a badge on one
with), `color`, `embeds`, and its levels and subjects.

## `Resource`

`classroom_resource`:

| Property | Meaning |
|---|---|
| `title` (not blank), `slug` (unique, from the title), `description` | |
| `file` | the upload on the private storage `local.classroom`, 64 MB: PDF, zip, Office and OpenDocument files, CSV and the like |
| `filename` | its name when saved ("kangourou-ce1.xlsx"); else the slug and the extension |
| `mimeType`, `size` | kept when the file is saved from the back office |
| `preview` | a picture of it, on the public storage, 8 MB |
| `visibilityValue` | `visibility` column: `free`, `members`, `paid` (`Visibility`); `getVisibility()`, `isFree()`, `isPaid()` |
| `published` | `true` |
| `taxa` | its levels and subjects (`classroom_resource_taxon`) |
| `sequences` | the sequences it belongs to |
| `downloads` | counted at each full download |
| `owner`, `createdAt`, `updatedAt` | |

`getExtension()` (from the name, else from the type), `isSpreadsheet()` (xlsx, xls, xlsm, csv,
ods), `hasFile()`.

## `Entitlement` and `ResourceOffer`

`Entitlement` (`classroom_entitlement`, unique on user and resource): the buyer, the resource,
the order's reference, `downloads`, `createdAt`. It never expires.

`Product\ResourceOffer` extends omnibase/marketplace's `Product` (discriminator
`classroom_resource_offer`): its `resource`; not shippable; one at most per order.

## Repositories

| Repository | Methods |
|---|---|
| `SequenceRepository` | `findPublishedPage(int $page, int $perPage, array $taxa = [], ?string $period = null)` (newest first), `countPublished()`, `findLatest(int $limit = 3)` (featured first), `findOnePublished()`, `search()` (title, excerpt, text), `countDrafts()` |
| `CardRepository` | `findFeatured(int $limit = 9)` (the fan: featured first, by position, newest), `findPublished(array $taxa = [], int $limit = 200)`, `findOnePublished()`, `search()` (title, headline, text) |
| `ResourceRepository` | `findPublished(array $taxa = [], int $limit = 200)` (newest first), `findOnePublished()`, `search()` (title, description), `sumDownloads()` |
| `EntitlementRepository` | `findForUser()`, `findOne(?object $user, Resource)`, `countSince()` |

`$taxa` are slugs, all required. A published thread is `PUBLISH` with a `publishedAt` past or
none. `Base\Classroom\Service\Taxa`: `cycles()`, `levels()` (the levels alone), `subjects()`,
`purposes()` (the top ones, in order), and one by slug: `level()`, `subject()`, `purpose()`.
