# omnibase/classroom

A teacher's open classroom for the sites built on [omnisense/omnibase](https://github.com/glitchr-omnisense/omnibase):
what a colleague passing by may pick up, organised the way the French teachers' sites are - by
level, by subject, by purpose - and built on omnibase's own `Thread` and `Taxon` instead of a
blog engine. It is a Symfony bundle (`Base\Classroom\ClassroomBundle`) on the core 3 or 4, with
its back office on omnibase/admin; with omnibase/marketplace it sells resources, with
omnibase/consent its frames wait for the visitor's yes.

| Entity | Is a | What |
|---|---|---|
| `Level` | `Taxon` | a class level, in a tree of cycles: Cycle 2 › CP, CE1, CE2 |
| `Subject` | `Taxon` | a discipline and its domains: Français › Lecture, Grammaire |
| `Purpose` | `Taxon` | "pour la classe": rituals, displays, autonomy, games |
| `Sequence` | `Thread` | a learning sequence: objectives, competences, period (P1-P5), material, cover, resources, and its sessions |
| `Session` | `Thread`, child of a sequence | one session: its place, duration, aim, material and course |
| `Card` | `Thread` | an idea card: a picture, a few lines, one to three links - the home page's fan |
| `Resource` | - | a file to download (PDF, document, spreadsheet, archive): free, for members, or paid |
| `Product\ResourceOffer` | omnibase/marketplace `Product` | a paid resource for sale |
| `Entitlement` | - | the right to download a paid resource, written when its order is paid |

Levels, subjects and purposes are the threads' taxa, so a sequence is "CP and CE1 · Allemand"
and every list filters by them. The text of a sequence, a session or a card is EditorJS,
autosaved, co-edited live when the core's relay runs. Resources live outside `public/` and are
served through signed, short-lived links.

## How this manual is organised

- [Installation](installation.md): requirements, the bundle, the routes, the storage, the
  layout.
- [Levels, sequences, cards and resources](concepts.md): every entity and its fields, the
  queries.
- [The pages](usage-pages.md): every route and what it lists, the search.
- [Downloads and embeds](downloads-and-embeds.md): who may download, the signed links, nginx,
  the spreadsheet preview, the frames and the consent.
- [The shop](usage-shop.md): selling a resource, the quick order, entitlements, the mail.
- [In a host page](usage-twig.md): the Twig functions and filters, the templates, the
  translations.
- [The back office](back-office.md): the screens, who writes, the tile.
- [Configuration reference](configuration.md): `classroom:`, the services.
- [Testing](testing.md).
