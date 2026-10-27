---
title: The back office
order: 38
---

# The back office

Loaded when omnibase/admin is installed. The screens open creating, editing and deleting to
`ROLE_ADMIN` (`OpenToAdminsTrait`: a teacher writes, not only the super-administrator).

| CRUD | Slug | Fields |
|---|---|---|
| `SequenceCrudController` | `sequences` | title, state, slug (following the title), publication date, featured, levels, subjects, purposes (one picker per kind), period, duration, headline, excerpt, objectives and competences (one per line), material, the presentation (EditorJS, autosaved and live), embeds, resources, cover; filters state, period, featured |
| `SessionCrudController` | `sessions` | sequence, position, duration, state, title, slug, aim, material, course; filter sequence |
| `CardCrudController` | `cards` | title, state, featured, position, slug, headline, levels, subjects, icon, colour, text, links, embeds, picture; filters state, featured |
| `ResourceCrudController` | `resources` | title, visibility, published, slug, file, saved name, description, levels, subjects, preview, downloads (list); filters visibility, published; **Mettre en vente** with omnibase/marketplace |
| `ResourceOfferCrudController` | `resource-offers` | the marketplace's product screen and the resource (with omnibase/marketplace) |
| `LevelCrudController` | `levels` | omnibase/admin's taxon screen, plus position, short name, colour |
| `SubjectCrudController` | `subjects` | the taxon screen, plus position, colour |
| `PurposeCrudController` | `purposes` | the taxon screen, plus position |

A sequence, a card or a resource created here has the signed-in user as owner. Saving a resource
keeps its file's size, type and - when empty - its name. Only what a record cannot do without is
required (a title, a state, a slug; a resource's file, a session's sequence).

The tile `classroom_overview` (`ClassroomOverviewWidgetType`): sequences online and in draft,
cards online, resources published, downloads, entitlements bought since the first of the month,
each linking to its screen.
